<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\ParentEleve;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
        $this->emailService = $emailService;
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = auth('api')->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            $user = auth('api')->user();

            if (!$user->actif) {
                auth('api')->logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a été désactivé'
                ], 403);
            }

            // Mettre à jour la dernière connexion
            $user->update(['last_login_at' => now()]);

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
            // Générer un mot de passe si non fourni
            $password = $request->password ?? $this->generatePassword($request->role ?? 'eleve');

            $user = User::create([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
                'password' => Hash::make($password),
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'role' => $request->role ?? 'eleve',
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'matricule' => $this->generateMatricule($request->role ?? 'eleve'),
                'actif' => true,
                'email_verified_at' => now()
            ]);

            // Créer le profil selon le rôle
            switch ($user->role) {
                case 'eleve':
                    Eleve::create([
                        'user_id' => $user->id,
                        'nationalite' => $request->nationalite ?? 'Sénégalaise',
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

            // Envoyer l'email de bienvenue avec les identifiants
            try {
                $this->emailService->envoyerEmailBienvenue($user, $password);
            } catch (\Exception $e) {
                Log::error('Erreur envoi email bienvenue: ' . $e->getMessage());
            }

            $token = auth('api')->login($user);
            return $this->respondWithToken($token);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    public function me()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        // Charger les relations selon le rôle
        switch ($user->role) {
            case 'eleve':
                $user->load([
                    'eleve.inscriptions' => function($q) {
                        $q->where('statut', 'en_cours');
                    },
                    'eleve.inscriptions.classe.niveau',
                    'eleve.parents.user'
                ]);
                break;

            case 'enseignant':
                $user->load([
                    'enseignant.matieres',
                    'enseignant.classes.niveau'
                ]);
                break;

            case 'parent':
                $user->load([
                    'parent.enfants.user',
                    'parent.enfants.inscriptions' => function($q) {
                        $q->where('statut', 'en_cours');
                    },
                    'parent.enfants.inscriptions.classe.niveau'
                ]);
                break;
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function logout()
    {
        try {
            auth('api')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion'
            ], 500);
        }
    }

    public function refresh()
    {
        try {
            $token = auth('api')->refresh();
            return $this->respondWithToken($token);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expiré, veuillez vous reconnecter'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de rafraîchir le token'
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'ancien_password' => 'required',
            'nouveau_password' => 'required|min:6|confirmed',
        ]);

        $user = auth('api')->user();

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
        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 60) * 60,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'role' => $user->role,
                'matricule' => $user->matricule,
                'nom_complet' => $user->nom_complet
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

    private function generatePassword($role)
    {
        $prefix = match($role) {
            'administrateur' => 'admin',
            'enseignant' => 'prof',
            'eleve' => 'eleve',
            'parent' => 'parent',
            default => 'user'
        };

        return $prefix . rand(1000, 9999);
    }
}
