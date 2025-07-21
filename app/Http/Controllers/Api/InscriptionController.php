<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Classe;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Inscription::with(['eleve.user', 'classe.niveau', 'anneeScolaire']);

        if ($request->has('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        if ($request->has('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $inscriptions = $query->orderBy('date_inscription', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $inscriptions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'classe_id' => 'required|exists:classes,id',
            'date_inscription' => 'nullable|date',
            'observations' => 'nullable|string'
        ]);

        // Vérifier la capacité de la classe
        $classe = Classe::find($request->classe_id);
        if ($classe->estPleine()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette classe est déjà pleine'
            ], 422);
        }

        // Vérifier si l'élève n'est pas déjà inscrit pour cette année
        $inscription = Inscription::where('eleve_id', $request->eleve_id)
            ->where('annee_scolaire_id', $classe->annee_scolaire_id)
            ->where('statut', 'en_cours')
            ->first();

        if ($inscription) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève est déjà inscrit pour cette année scolaire'
            ], 422);
        }

        $inscription = Inscription::create([
            'eleve_id' => $request->eleve_id,
            'classe_id' => $request->classe_id,
            'annee_scolaire_id' => $classe->annee_scolaire_id,
            'date_inscription' => $request->date_inscription ?? now(),
            'statut' => 'en_cours',
            'observations' => $request->observations
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inscription effectuée avec succès',
            'data' => $inscription->load(['eleve.user', 'classe.niveau'])
        ], 201);
    }

    public function show($id)
    {
        $inscription = Inscription::with([
            'eleve.user',
            'eleve.parents.user',
            'classe.niveau',
            'anneeScolaire'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $inscription
        ]);
    }

    public function update(Request $request, $id)
    {
        $inscription = Inscription::findOrFail($id);

        $request->validate([
            'statut' => 'sometimes|in:en_cours,termine,abandonne',
            'observations' => 'nullable|string'
        ]);

        $inscription->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Inscription mise à jour avec succès',
            'data' => $inscription
        ]);
    }

    public function destroy($id)
    {
        $inscription = Inscription::findOrFail($id);

        // Vérifier s'il y a des notes pour cet élève
        $hasNotes = \App\Models\Note::where('eleve_id', $inscription->eleve_id)
            ->whereHas('periode', function($q) use ($inscription) {
                $q->where('annee_scolaire_id', $inscription->annee_scolaire_id);
            })
            ->exists();

        if ($hasNotes) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette inscription car l\'élève a déjà des notes'
            ], 422);
        }

        $inscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inscription supprimée avec succès'
        ]);
    }

    public function terminer(Request $request, $id)
    {
        $inscription = Inscription::findOrFail($id);

        $request->validate([
            'motif' => 'required|string'
        ]);

        $inscription->update([
            'statut' => 'termine',
            'observations' => $inscription->observations . ' | Terminé: ' . $request->motif
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inscription terminée avec succès'
        ]);
    }

    public function statistiquesClasse($classeId)
    {
        $classe = Classe::findOrFail($classeId);

        $stats = [
            'total_inscrits' => $classe->inscriptions()->where('statut', 'en_cours')->count(),
            'capacite' => $classe->capacite,
            'places_disponibles' => $classe->places_disponibles,
            'taux_remplissage' => ($classe->effectif / $classe->capacite) * 100,
            'par_sexe' => [
                'garcons' => $classe->inscriptions()
                    ->where('statut', 'en_cours')
                    ->whereHas('eleve.user', function($q) {
                        $q->where('sexe', 'M');
                    })->count(),
                'filles' => $classe->inscriptions()
                    ->where('statut', 'en_cours')
                    ->whereHas('eleve.user', function($q) {
                        $q->where('sexe', 'F');
                    })->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
