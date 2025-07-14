<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ParentEleve;

class CheckParentAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'parent') {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux parents'
            ], 403);
        }

        // Vérifier si c'est bien un parent et qu'il accède aux données de ses enfants
        $eleveId = $request->route('eleve') ?? $request->route('id');

        if ($eleveId) {
            $parent = ParentEleve::where('user_id', $user->id)->first();

            if (!$parent || !$parent->peutVoirEleve($eleveId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à accéder aux informations de cet élève'
                ], 403);
            }
        }

        return $next($request);
    }
}
