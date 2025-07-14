<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;

class AnneeScolaireController extends Controller
{
    public function index()
    {
        $annees = AnneeScolaire::orderBy('date_debut', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $annees
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'libelle' => 'required|string|unique:annees_scolaires',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'actuelle' => 'boolean'
        ]);

        $annee = AnneeScolaire::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Année scolaire créée avec succès',
            'data' => $annee
        ], 201);
    }

    public function show($id)
    {
        $annee = AnneeScolaire::with(['classes', 'periodes'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $annee
        ]);
    }

    public function update(Request $request, $id)
    {
        $annee = AnneeScolaire::findOrFail($id);

        $request->validate([
            'libelle' => 'sometimes|string|unique:annees_scolaires,libelle,' . $id,
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after:date_debut',
            'actuelle' => 'boolean'
        ]);

        $annee->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Année scolaire mise à jour avec succès',
            'data' => $annee
        ]);
    }

    public function destroy($id)
    {
        $annee = AnneeScolaire::findOrFail($id);

        if ($annee->classes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette année scolaire car elle contient des classes'
            ], 422);
        }

        $annee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Année scolaire supprimée avec succès'
        ]);
    }

    public function setActuelle($id)
    {
        $annee = AnneeScolaire::findOrFail($id);

        // Désactiver toutes les autres années
        AnneeScolaire::where('id', '!=', $id)->update(['actuelle' => false]);

        // Activer l'année sélectionnée
        $annee->actuelle = true;
        $annee->save();

        return response()->json([
            'success' => true,
            'message' => 'Année scolaire définie comme actuelle',
            'data' => $annee
        ]);
    }

    public function statistiques($id)
    {
        $annee = AnneeScolaire::findOrFail($id);

        $stats = [
            'nombre_classes' => $annee->classes()->count(),
            'nombre_eleves' => $annee->inscriptions()->where('statut', 'en_cours')->count(),
            'nombre_enseignants' => $annee->enseignantsMatieres()->distinct('enseignant_id')->count(),
            'periodes' => $annee->periodes()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
