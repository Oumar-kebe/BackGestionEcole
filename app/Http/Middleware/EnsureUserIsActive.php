<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && !auth()->user()->actif) {
            auth()->logout();

            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administration.'
            ], 403);
        }

        return $next($request);
    }
}
