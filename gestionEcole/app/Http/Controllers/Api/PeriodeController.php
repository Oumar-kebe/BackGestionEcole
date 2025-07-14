<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Periode;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;

class PeriodeController extends Controller
{
    public function index(Request $request)
    {
        $query = Periode::with('anneeScolaire');

        if ($request->has('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $periodes = $query->orderBy('ordre')->get();

        return response()->json([
            'success' => true,
            'data' => $periodes
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:trimestre,semestre',
            'ordre' => 'required|integer|min:1',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'actuelle' => 'boolean'
        ]);

        // Vérifier qu'il n'y a pas de chevauchement de dates
        $chevauchement = Periode::where('annee_scolaire_id', $request->annee_scolaire_id)
            ->where(function($q) use ($request) {
                $q->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                    ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin]);
            })
            ->exists();

        if ($chevauchement) {
            return response()->json([
                'success' => false,
                'message' => 'Les dates se chevauchent avec une période existante'
            ], 422);
        }

        $periode = Periode::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Période créée avec succès',
            'data' => $periode->load('anneeScolaire')
        ], 201);
    }

    public function show($id)
    {
        $periode = Periode::with(['anneeScolaire', 'notes', 'bulletins'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $periode
        ]);
    }

    public function update(Request $request, $id)
    {
        $periode = Periode::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'ordre' => 'sometimes|integer|min:1',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after:date_debut',
            'actuelle' => 'sometimes|boolean'
        ]);

        // Vérifier le chevauchement si les dates sont modifiées
        if ($request->has('date_debut') || $request->has('date_fin')) {
            $dateDebut = $request->date_debut ?? $periode->date_debut;
            $dateFin = $request->date_fin ?? $periode->date_fin;

            $chevauchement = Periode::where('annee_scolaire_id', $periode->annee_scolaire_id)
                ->where('id', '!=', $id)
                ->where(function($q) use ($dateDebut, $dateFin) {
                    $q->whereBetween('date_debut', [$dateDebut, $dateFin])
                        ->orWhereBetween('date_fin', [$dateDebut, $dateFin]);
                })
                ->exists();

            if ($chevauchement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les dates se chevauchent avec une période existante'
                ], 422);
            }
        }

        $periode->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Période mise à jour avec succès',
            'data' => $periode
        ]);
    }

    public function destroy($id)
    {
        $periode = Periode::findOrFail($id);

        if ($periode->notes()->exists() || $periode->bulletins()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette période car elle contient des données'
            ], 422);
        }

        $periode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Période supprimée avec succès'
        ]);
    }

    public function setActuelle($id)
    {
        $periode = Periode::findOrFail($id);

        // Désactiver toutes les autres périodes de l'année
        Periode::where('annee_scolaire_id', $periode->annee_scolaire_id)
            ->where('id', '!=', $id)
            ->update(['actuelle' => false]);

        // Activer la période sélectionnée
        $periode->actuelle = true;
        $periode->save();

        return response()->json([
            'success' => true,
            'message' => 'Période définie comme actuelle',
            'data' => $periode
        ]);
    }

    public function statistiques($id)
    {
        $periode = Periode::findOrFail($id);

        $stats = [
            'notes_saisies' => $periode->notes()->count(),
            'bulletins_generes' => $periode->bulletins()->count(),
            'classes_concernees' => $periode->notes()
                ->join('eleves', 'notes.eleve_id', '=', 'eleves.id')
                ->join('inscriptions', 'eleves.id', '=', 'inscriptions.eleve_id')
                ->distinct('inscriptions.classe_id')
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
