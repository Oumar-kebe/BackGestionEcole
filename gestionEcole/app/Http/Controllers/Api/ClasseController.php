<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;

class ClasseController extends Controller
{
    public function index(Request $request)
    {
        $query = Classe::with(['niveau', 'anneeScolaire']);

        // Filtres
        if ($request->has('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        if ($request->has('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->has('actif')) {
            $query->where('actif', $request->actif);
        }

        // Par défaut, afficher les classes de l'année en cours
        if (!$request->has('annee_scolaire_id')) {
            $anneeCourante = AnneeScolaire::where('actuelle', true)->first();
            if ($anneeCourante) {
                $query->where('annee_scolaire_id', $anneeCourante->id);
            }
        }

        $classes = $query->orderBy('nom')->get();

        // Ajouter les statistiques
        $classes->each(function ($classe) {
            $classe->effectif = $classe->inscriptions()->where('statut', 'en_cours')->count();
            $classe->places_disponibles = $classe->capacite - $classe->effectif;
        });

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'niveau_id' => 'required|exists:niveaux,id',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
            'capacite' => 'required|integer|min:1',
            'actif' => 'boolean'
        ]);

        $classe = Classe::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Classe créée avec succès',
            'data' => $classe->load(['niveau', 'anneeScolaire'])
        ], 201);
    }

    public function show($id)
    {
        $classe = Classe::with([
            'niveau',
            'anneeScolaire',
            'inscriptions.eleve.user',
            'enseignants.user'
        ])->findOrFail($id);

        $classe->effectif = $classe->inscriptions()->where('statut', 'en_cours')->count();
        $classe->places_disponibles = $classe->capacite - $classe->effectif;

        return response()->json([
            'success' => true,
            'data' => $classe
        ]);
    }

    public function update(Request $request, $id)
    {
        $classe = Classe::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'niveau_id' => 'sometimes|exists:niveaux,id',
            'capacite' => 'sometimes|integer|min:1',
            'actif' => 'sometimes|boolean'
        ]);

        $classe->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Classe mise à jour avec succès',
            'data' => $classe->load(['niveau', 'anneeScolaire'])
        ]);
    }

    public function destroy($id)
    {
        $classe = Classe::findOrFail($id);

        if ($classe->inscriptions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette classe car elle contient des élèves'
            ], 422);
        }

        $classe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Classe supprimée avec succès'
        ]);
    }

    public function eleves($id)
    {
        $classe = Classe::findOrFail($id);

        $eleves = $classe->inscriptions()
            ->where('statut', 'en_cours')
            ->with('eleve.user')
            ->get()
            ->pluck('eleve');

        return response()->json([
            'success' => true,
            'data' => $eleves
        ]);
    }

    public function enseignants($id)
    {
        $classe = Classe::findOrFail($id);

        $enseignants = $classe->enseignants()
            ->with(['user', 'matieres'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $enseignants
        ]);
    }

    public function assignerEnseignant(Request $request, $id)
    {
        $classe = Classe::findOrFail($id);

        $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'matiere_id' => 'required|exists:matieres,id'
        ]);

        // Vérifier que la matière appartient au niveau de la classe
        $matiereNiveau = $classe->niveau->matieres()->where('id', $request->matiere_id)->exists();
        if (!$matiereNiveau) {
            return response()->json([
                'success' => false,
                'message' => 'Cette matière n\'appartient pas au niveau de la classe'
            ], 422);
        }

        $classe->enseignants()->attach($request->enseignant_id, [
            'matiere_id' => $request->matiere_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enseignant assigné à la classe avec succès'
        ]);
    }

    public function retirerEnseignant(Request $request, $id)
    {
        $classe = Classe::findOrFail($id);

        $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'matiere_id' => 'required|exists:matieres,id'
        ]);

        $classe->enseignants()->wherePivot('matiere_id', $request->matiere_id)
            ->detach($request->enseignant_id);

        return response()->json([
            'success' => true,
            'message' => 'Enseignant retiré de la classe avec succès'
        ]);
    }
}
