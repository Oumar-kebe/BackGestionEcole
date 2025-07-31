<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enseignant;
use App\Models\User;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EnseignantController extends Controller
{
    public function index(Request $request)
    {
        $query = Enseignant::with(['user', 'matieres', 'classes']);

        if ($request->has('matiere_id')) {
            $query->whereHas('matieres', function($q) use ($request) {
                $q->where('matieres.id', $request->matiere_id);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $enseignants = $query->get();

        return response()->json([
            'success' => true,
            'data' => $enseignants
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'telephone' => 'required|string',
            'adresse' => 'required|string',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string',
            'sexe' => 'required|in:M,F',
            'specialite' => 'nullable|string',
            'diplome' => 'nullable|string',
            'annees_experience' => 'nullable|integer|min:0',
            'matieres' => 'required|array',
            'matieres.*' => 'exists:matieres,id'
        ]);

        DB::beginTransaction();

        try {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
                'password' => Hash::make('password123'),
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'role' => 'enseignant',
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'matricule' => $this->generateMatricule(),
                'actif' => true
            ]);

            // Créer le profil enseignant
            $enseignant = Enseignant::create([
                'user_id' => $user->id,
                'specialite' => $request->specialite,
                'diplome' => $request->diplome,
                'annees_experience' => $request->annees_experience ?? 0,
            ]);

            // Assigner les matières pour l'année en cours
            $anneeCourante = AnneeScolaire::where('actuelle', true)->first();
            if ($anneeCourante && $request->has('matieres')) {
                foreach ($request->matieres as $matiereId) {
                    $enseignant->matieres()->attach($matiereId, [
                        'annee_scolaire_id' => $anneeCourante->id
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Enseignant créé avec succès',
                'data' => $enseignant->load(['user', 'matieres'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'enseignant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $enseignant = Enseignant::with([
            'user',
            'matieres.niveau',
            'classes.niveau',
            'notes'
        ])->findOrFail($id);

        // Ajouter les statistiques
        $enseignant->nombre_classes = $enseignant->classes()->distinct()->count();
        $enseignant->nombre_eleves = $enseignant->classes()
            ->join('inscriptions', 'classes.id', '=', 'inscriptions.classe_id')
            ->where('inscriptions.statut', 'en_cours')
            ->distinct('inscriptions.eleve_id')
            ->count();

        return response()->json([
            'success' => true,
            'data' => $enseignant
        ]);
    }

    public function update(Request $request, $id)
    {
        $enseignant = Enseignant::findOrFail($id);

        $request->validate([
            'specialite' => 'nullable|string',
            'diplome' => 'nullable|string',
            'annees_experience' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour les informations de l'utilisateur
            if ($request->has(['nom', 'prenom', 'telephone', 'adresse'])) {
                $enseignant->user->update($request->only(['nom', 'prenom', 'telephone', 'adresse']));
                if ($request->has('nom') || $request->has('prenom')) {
                    $enseignant->user->name = $enseignant->user->prenom . ' ' . $enseignant->user->nom;
                    $enseignant->user->save();
                }
            }

            // Mettre à jour les informations de l'enseignant
            $enseignant->update($request->only([
                'specialite',
                'diplome',
                'annees_experience'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Informations de l\'enseignant mises à jour avec succès',
                'data' => $enseignant->load('user')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $enseignant = Enseignant::findOrFail($id);

        // Vérifier s'il a des notes saisies
        if ($enseignant->notes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cet enseignant car il a déjà saisi des notes'
            ], 422);
        }

        // Soft delete de l'utilisateur
        $enseignant->user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enseignant supprimé avec succès'
        ]);
    }

    public function assignerMatiere(Request $request, $id)
    {
        $enseignant = Enseignant::findOrFail($id);

        $request->validate([
            'matiere_id' => 'required|exists:matieres,id',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id'
        ]);

        // Vérifier si pas déjà assigné
        $exists = $enseignant->matieres()
            ->wherePivot('annee_scolaire_id', $request->annee_scolaire_id)
            ->where('matieres.id', $request->matiere_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Cette matière est déjà assignée à cet enseignant pour cette année'
            ], 422);
        }

        $enseignant->matieres()->attach($request->matiere_id, [
            'annee_scolaire_id' => $request->annee_scolaire_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Matière assignée avec succès'
        ]);
    }

    public function retirerMatiere(Request $request, $id)
    {
        $enseignant = Enseignant::findOrFail($id);

        $request->validate([
            'matiere_id' => 'required|exists:matieres,id',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id'
        ]);

        $enseignant->matieres()
            ->wherePivot('annee_scolaire_id', $request->annee_scolaire_id)
            ->detach($request->matiere_id);

        return response()->json([
            'success' => true,
            'message' => 'Matière retirée avec succès'
        ]);
    }

    public function assignerClasse(Request $request, $id)
    {
        $enseignant = Enseignant::findOrFail($id);

        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id'
        ]);

        // Vérifier que l'enseignant enseigne cette matière
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();
        if (!$enseignant->enseigneMatiere($request->matiere_id, $anneeCourante?->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Cet enseignant n\'est pas assigné à cette matière'
            ], 422);
        }

        // Vérifier si pas déjà assigné
        $exists = $enseignant->classes()
            ->wherePivot('matiere_id', $request->matiere_id)
            ->where('classes.id', $request->classe_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Cet enseignant est déjà assigné à cette classe pour cette matière'
            ], 422);
        }

        $enseignant->classes()->attach($request->classe_id, [
            'matiere_id' => $request->matiere_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Classe assignée avec succès'
        ]);
    }

    public function mesClasses()
    {
        $user = auth()->user();
        $enseignant = Enseignant::where('user_id', $user->id)->firstOrFail();

        $classes = $enseignant->classes()
            ->with(['niveau', 'anneeScolaire'])
            ->withPivot('matiere_id')
            ->get();

        // Grouper par matière
        $classesParMatiere = [];
        foreach ($classes as $classe) {
            $matiereId = $classe->pivot->matiere_id;
            $matiere = \App\Models\Matiere::find($matiereId);

            if (!isset($classesParMatiere[$matiereId])) {
                $classesParMatiere[$matiereId] = [
                    'matiere' => $matiere,
                    'classes' => []
                ];
            }

            $classesParMatiere[$matiereId]['classes'][] = $classe;
        }

        return response()->json([
            'success' => true,
            'data' => array_values($classesParMatiere)
        ]);
    }

    private function generateMatricule()
    {
        $prefix = 'ENS';
        $year = date('Y');
        $lastUser = User::where('matricule', 'like', $prefix . $year . '%')
            ->where('role', 'enseignant')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastUser) {
            $lastNumber = intval(substr($lastUser->matricule, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
