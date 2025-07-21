<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentEleve;
use App\Models\User;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $query = ParentEleve::with(['user', 'enfants.user']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $parents = $query->get();

        return response()->json([
            'success' => true,
            'data' => $parents
        ]);
    }

    public function show($id)
    {
        $parent = ParentEleve::with([
            'user',
            'enfants.user',
            'enfants.inscriptions.classe.niveau'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $parent
        ]);
    }

    public function enfants($id)
    {
        $parent = ParentEleve::findOrFail($id);

        $enfants = $parent->enfants()
            ->with(['user', 'inscriptions.classe.niveau'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $enfants
        ]);
    }

    public function ajouterEnfant(Request $request, $id)
    {
        $parent = ParentEleve::findOrFail($id);

        $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'lien_parente' => 'required|in:pere,mere,tuteur,autre'
        ]);

        // Vérifier si la relation existe déjà
        if ($parent->enfants()->where('eleve_id', $request->eleve_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève est déjà lié à ce parent'
            ], 422);
        }

        $parent->enfants()->attach($request->eleve_id, [
            'lien_parente' => $request->lien_parente
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enfant ajouté avec succès'
        ]);
    }

    public function retirerEnfant($parentId, $eleveId)
    {
        $parent = ParentEleve::findOrFail($parentId);

        $parent->enfants()->detach($eleveId);

        return response()->json([
            'success' => true,
            'message' => 'Enfant retiré avec succès'
        ]);
    }

    public function bulletinsEnfants($id)
    {
        $parent = ParentEleve::findOrFail($id);

        $bulletins = [];

        foreach ($parent->enfants as $enfant) {
            $bulletinsEnfant = $enfant->bulletins()
                ->with(['periode', 'classe.niveau'])
                ->orderBy('created_at', 'desc')
                ->get();

            $bulletins[] = [
                'eleve' => $enfant->user,
                'bulletins' => $bulletinsEnfant
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $bulletins
        ]);
    }

    public function update(Request $request, $id)
    {
        $parent = ParentEleve::findOrFail($id);

        $request->validate([
            'profession' => 'nullable|string',
            'lieu_travail' => 'nullable|string',
            'telephone_bureau' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour les informations de l'utilisateur
            if ($request->has(['nom', 'prenom', 'telephone', 'adresse'])) {
                $parent->user->update($request->only(['nom', 'prenom', 'telephone', 'adresse']));
                if ($request->has('nom') || $request->has('prenom')) {
                    $parent->user->name = $parent->user->prenom . ' ' . $parent->user->nom;
                    $parent->user->save();
                }
            }

            // Mettre à jour les informations du parent
            $parent->update($request->only([
                'profession',
                'lieu_travail',
                'telephone_bureau'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Informations du parent mises à jour avec succès',
                'data' => $parent->load('user')
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

    // Méthodes pour le portail parent
    public function mesEnfants()
    {
        $user = auth()->user();
        $parent = ParentEleve::where('user_id', $user->id)->firstOrFail();

        $enfants = $parent->enfants()
            ->with(['user', 'inscriptions.classe.niveau'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $enfants
        ]);
    }

    public function bulletinEnfant($eleveId)
    {
        $user = auth()->user();
        $parent = ParentEleve::where('user_id', $user->id)->firstOrFail();

        // Vérifier que l'élève est bien un enfant du parent
        if (!$parent->peutVoirEleve($eleveId)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à consulter les informations de cet élève'
            ], 403);
        }

        $eleve = Eleve::find($eleveId);
        $bulletins = $eleve->bulletins()
            ->with(['periode', 'classe.niveau'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bulletins
        ]);
    }
}
