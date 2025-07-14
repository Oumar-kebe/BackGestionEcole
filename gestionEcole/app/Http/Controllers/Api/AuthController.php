<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\ParentEleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            $user = auth()->user();

            if (!$user->actif) {
                JWTAuth::invalidate($token);
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a été désactivé'
                ], 403);
            }

            return $this->respondWithToken($token);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de créer le token'
            ], 500);
        }
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'role' => $request->role ?? 'eleve',
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'matricule' => $this->generateMatricule($request->role ?? 'eleve'),
            ]);

            // Créer le profil selon le rôle
            switch ($user->role) {
                case 'eleve':
                    Eleve::create([
                        'user_id' => $user->id,
                        'nationalite' => $request->nationalite,
                        'groupe_sanguin' => $request->groupe_sanguin,
                        'allergies' => $request->allergies,
                        'maladies' => $request->maladies,
                        'personne_urgence_nom' => $request->personne_urgence_nom,
                        'personne_urgence_telephone' => $request->personne_urgence_telephone,
                    ]);
                    break;

                case 'enseignant':
                    Enseignant::create([
                        'user_id' => $user->id,
                        'specialite' => $request->specialite,
                        'diplome' => $request->diplome,
                        'annees_experience' => $request->annees_experience ?? 0,
                    ]);
                    break;

                case 'parent':
                    ParentEleve::create([
                        'user_id' => $user->id,
                        'profession' => $request->profession,
                        'lieu_travail' => $request->lieu_travail,
                        'telephone_bureau' => $request->telephone_bureau,
                    ]);
                    break;
            }

            DB::commit();

            $token = JWTAuth::fromUser($user);
            return $this->respondWithToken($token);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me()
    {
        $user = auth()->user();

        // Charger les relations selon le rôle
        switch ($user->role) {
            case 'eleve':
                $user->load(['eleve.inscriptions.classe.niveau', 'eleve.parents.user']);
                break;
            case 'enseignant':
                $user->load(['enseignant.matieres', 'enseignant.classes']);
                break;
            case 'parent':
                $user->load(['parent.enfants.user', 'parent.enfants.inscriptions.classe']);
                break;
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'ancien_password' => 'required',
            'nouveau_password' => 'required|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->ancien_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'L\'ancien mot de passe est incorrect'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->nouveau_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès'
        ]);
    }

    protected function respondWithToken($token)
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'role' => $user->role,
                'matricule' => $user->matricule,
            ]
        ]);
    }

    private function generateMatricule($role)
    {
        $prefix = match($role) {
            'administrateur' => 'ADM',
            'enseignant' => 'ENS',
            'eleve' => 'ELV',
            'parent' => 'PAR',
            default => 'USR'
        };

        $year = date('Y');
        $lastUser = User::where('matricule', 'like', $prefix . $year . '%')
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
