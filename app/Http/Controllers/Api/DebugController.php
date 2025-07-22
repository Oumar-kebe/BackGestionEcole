<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function userInfo()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non connecté'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'matricule' => $user->matricule,
                'is_parent' => $user->isParent(),
                'parent_relation_exists' => $user->parent ? true : false,
                'parent_data' => $user->parent ? [
                    'id' => $user->parent->id,
                    'profession' => $user->parent->profession,
                    'enfants_count' => $user->parent->enfants()->count(),
                ] : null
            ]
        ]);
    }

    public function parentEnfants()
    {
        $user = auth()->user();
        
        if (!$user || !$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non parent'
            ], 403);
        }

        $parent = $user->parent;
        
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun profil parent trouvé'
            ], 404);
        }

        $enfants = $parent->enfants()->with(['user', 'inscriptions.classe.niveau'])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'parent_id' => $parent->id,
                'enfants_count' => $enfants->count(),
                'enfants' => $enfants->map(function($enfant) {
                    return [
                        'id' => $enfant->id,
                        'nom' => $enfant->user->nom ?? 'Non défini',
                        'prenom' => $enfant->user->prenom ?? 'Non défini',
                        'email' => $enfant->user->email ?? 'Non défini',
                        'classe' => $enfant->inscriptions->first()->classe->nom ?? 'Aucune classe',
                    ];
                })
            ]
        ]);
    }
}
