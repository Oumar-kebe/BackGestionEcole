<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Niveau;
use Illuminate\Http\Request;

class NiveauController extends Controller
{
    public function index()
    {
        $niveaux = Niveau::orderBy('ordre')->get();

        return response()->json([
            'success' => true,
            'data' => $niveaux
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|unique:niveaux',
            'ordre' => 'required|integer|min:1'
        ]);

        $niveau = Niveau::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Niveau créé avec succès',
            'data' => $niveau
        ], 201);
    }

    public function show($id)
    {
        $niveau = Niveau::with(['classes', 'matieres'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $niveau
        ]);
    }

    public function update(Request $request, $id)
    {
        $niveau = Niveau::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:niveaux,code,' . $id,
            'ordre' => 'sometimes|integer|min:1'
        ]);

        $niveau->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Niveau mis à jour avec succès',
            'data' => $niveau
        ]);
    }

    public function destroy($id)
    {
        $niveau = Niveau::findOrFail($id);

        if ($niveau->classes()->exists() || $niveau->matieres()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce niveau car il est utilisé'
            ], 422);
        }

        $niveau->delete();

        return response()->json([
            'success' => true,
            'message' => 'Niveau supprimé avec succès'
        ]);
    }

    public function matieres($id)
    {
        $niveau = Niveau::findOrFail($id);
        $matieres = $niveau->matieres()->where('actif', true)->get();

        return response()->json([
            'success' => true,
            'data' => $matieres
        ]);
    }

    public function classes($id)
    {
        $niveau = Niveau::findOrFail($id);
        $classes = $niveau->classes()
            ->where('actif', true)
            ->with('anneeScolaire')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }
}
