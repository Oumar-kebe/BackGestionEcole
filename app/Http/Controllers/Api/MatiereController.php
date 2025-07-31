<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Matiere;
use Illuminate\Http\Request;

class MatiereController extends Controller
{
    public function index(Request $request)
    {
        $query = Matiere::with('niveau','enseignants.user');

        if ($request->has('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        if ($request->has('actif')) {
            $query->where('actif', $request->actif);
        }

        $matieres = $query->orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'data' => $matieres
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|unique:matieres',
            'coefficient' => 'required|numeric|min:0.5|max:10',
            'niveau_id' => 'required|exists:niveaux,id',
            'actif' => 'boolean',
            'enseignants' => 'sometimes|array',
            'enseignants.*' => 'exists:enseignants,id',
            'annee_scolaire_id' => 'sometimes|exists:annees_scolaires,id'
        ]);

        $matiere = Matiere::create($request->only([
            'nom', 'code', 'coefficient', 'niveau_id', 'actif'
        ]));

        // Assigner les enseignants si fournis
        if ($request->has('enseignants') && !empty($request->enseignants)) {
            $anneeScolaireId = $request->annee_scolaire_id ?? 
                \App\Models\AnneeScolaire::where('actuelle', true)->first()?->id;
            
            if ($anneeScolaireId) {
                $enseignantsData = [];
                foreach ($request->enseignants as $enseignantId) {
                    $enseignantsData[$enseignantId] = ['annee_scolaire_id' => $anneeScolaireId];
                }
                $matiere->enseignants()->attach($enseignantsData);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Matière créée avec succès',
            'data' => $matiere->load(['niveau', 'enseignants.user'])
        ], 201);
    }

    public function show($id)
    {
        $matiere = Matiere::with(['niveau', 'enseignants.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $matiere
        ]);
    }

    public function update(Request $request, $id)
    {
        $matiere = Matiere::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:matieres,code,' . $id,
            'coefficient' => 'sometimes|numeric|min:0.5|max:10',
            'niveau_id' => 'sometimes|exists:niveaux,id',
            'actif' => 'sometimes|boolean',
            'enseignants' => 'sometimes|array',
            'enseignants.*' => 'exists:enseignants,id',
            'annee_scolaire_id' => 'sometimes|exists:annees_scolaires,id'
        ]);

        $matiere->update($request->only([
            'nom', 'code', 'coefficient', 'niveau_id', 'actif'
        ]));

        // Gérer les enseignants si fournis
        if ($request->has('enseignants')) {
            $anneeScolaireId = $request->annee_scolaire_id ?? 
                \App\Models\AnneeScolaire::where('actuelle', true)->first()?->id;
            
            if ($anneeScolaireId) {
                // Supprimer les anciens enseignants pour cette année scolaire
                $matiere->enseignants()
                    ->wherePivot('annee_scolaire_id', $anneeScolaireId)
                    ->detach();
                
                // Ajouter les nouveaux enseignants
                if (!empty($request->enseignants)) {
                    $enseignantsData = [];
                    foreach ($request->enseignants as $enseignantId) {
                        $enseignantsData[$enseignantId] = ['annee_scolaire_id' => $anneeScolaireId];
                    }
                    $matiere->enseignants()->attach($enseignantsData);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Matière mise à jour avec succès',
            'data' => $matiere->load(['niveau', 'enseignants.user'])
        ]);
    }

    public function destroy($id)
    {
        $matiere = Matiere::findOrFail($id);

        if ($matiere->notes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette matière car elle contient des notes'
            ], 422);
        }

        // Détacher tous les enseignants avant suppression
        $matiere->enseignants()->detach();
        
        $matiere->delete();

        return response()->json([
            'success' => true,
            'message' => 'Matière supprimée avec succès'
        ]);
    }

    public function toggleStatus($id)
    {
        $matiere = Matiere::findOrFail($id);
        $matiere->actif = !$matiere->actif;
        $matiere->save();

        return response()->json([
            'success' => true,
            'message' => $matiere->actif ? 'Matière activée' : 'Matière désactivée',
            'data' => $matiere
        ]);
    }

    public function enseignants($id)
    {
        $matiere = Matiere::findOrFail($id);
        $anneeCourante = \App\Models\AnneeScolaire::where('actuelle', true)->first();

        $enseignants = $matiere->enseignants()
            ->wherePivot('annee_scolaire_id', $anneeCourante?->id)
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $enseignants
        ]);
    }

    public function assignerEnseignant(Request $request, $id)
    {
        $matiere = Matiere::findOrFail($id);

        $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id'
        ]);

        // Vérifier si l'enseignant n'est pas déjà assigné pour cette année
        $existingAssignment = $matiere->enseignants()
            ->wherePivot('enseignant_id', $request->enseignant_id)
            ->wherePivot('annee_scolaire_id', $request->annee_scolaire_id)
            ->exists();

        if ($existingAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Cet enseignant est déjà assigné à cette matière pour cette année scolaire'
            ], 422);
        }

        $matiere->enseignants()->attach($request->enseignant_id, [
            'annee_scolaire_id' => $request->annee_scolaire_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enseignant assigné à la matière avec succès'
        ]);
    }

    public function detacherEnseignant(Request $request, $id)
    {
        $matiere = Matiere::findOrFail($id);

        $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id'
        ]);

        $matiere->enseignants()
            ->wherePivot('enseignant_id', $request->enseignant_id)
            ->wherePivot('annee_scolaire_id', $request->annee_scolaire_id)
            ->detach($request->enseignant_id);

        return response()->json([
            'success' => true,
            'message' => 'Enseignant détaché de la matière avec succès'
        ]);
    }
}
