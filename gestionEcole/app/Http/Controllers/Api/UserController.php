<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\ParentEleve;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function index(Request $request)
    {
        $query = User::with(['eleve', 'enseignant', 'parent']);

        // Filtres
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('actif')) {
            $query->where('actif', filter_var($request->actif, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('nom')->orderBy('prenom')->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'role' => 'required|in:administrateur,enseignant,eleve,parent',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:M,F',
            // Champs spécifiques selon le rôle
            'specialite' => 'required_if:role,enseignant|string|max:255',
            'diplome' => 'required_if:role,enseignant|string|max:255',
            'annees_experience' => 'nullable|integer|min:0',
            'nationalite' => 'required_if:role,eleve|string|max:100',
            'groupe_sanguin' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'profession' => 'nullable|string|max:255',
            'lieu_travail' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $password = $this->generatePassword($request->role);

            $user = User::create([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
                'password' => Hash::make($password),
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'role' => $request->role,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'matricule' => $this->generateMatricule($request->role),
                'actif' => true,
                'email_verified_at' => now()
            ]);

            // Créer le profil spécifique selon le rôle
            $this->createRoleSpecificProfile($user, $request);

            DB::commit();

            // Envoyer l'email de bienvenue
            try {
                $this->emailService->envoyerEmailBienvenue($user, $password);
            } catch (\Exception $e) {
                Log::error('Erreur envoi email bienvenue: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès. Un email a été envoyé avec les identifiants.',
                'data' => $user->load(['eleve', 'enseignant', 'parent'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    public function show($id)
    {
        $user = User::with([
            'eleve.inscriptions.classe.niveau',
            'eleve.parents.user',
            'eleve.documents',
            'enseignant.matieres',
            'enseignant.classes.niveau',
            'parent.enfants.user',
            'parent.enfants.inscriptions.classe'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($id)
            ],
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'actif' => 'sometimes|boolean',
        ]);

        DB::beginTransaction();

        try {
            $updateData = $request->only([
                'nom', 'prenom', 'email', 'telephone',
                'adresse', 'date_naissance', 'lieu_naissance',
                'sexe', 'actif'
            ]);

            if (isset($updateData['nom']) || isset($updateData['prenom'])) {
                $nom = $updateData['nom'] ?? $user->nom;
                $prenom = $updateData['prenom'] ?? $user->prenom;
                $updateData['name'] = $prenom . ' ' . $nom;
            }

            $user->update($updateData);

            // Mettre à jour le profil spécifique si nécessaire
            $this->updateRoleSpecificProfile($user, $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => $user->fresh()->load(['eleve', 'enseignant', 'parent'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Vérifier si l'utilisateur peut être supprimé
        if ($user->role === 'enseignant' && $user->enseignant && $user->enseignant->notes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cet enseignant car il a déjà saisi des notes'
            ], 422);
        }

        if ($user->role === 'eleve' && $user->eleve && $user->eleve->notes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cet élève car il a des notes enregistrées'
            ], 422);
        }

        // Soft delete pour garder l'historique
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->actif = !$user->actif;
        $user->save();

        $status = $user->actif ? 'activé' : 'désactivé';

        return response()->json([
            'success' => true,
            'message' => "Utilisateur {$status} avec succès",
            'data' => $user
        ]);
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $newPassword = $this->generatePassword($user->role);

        $user->password = Hash::make($newPassword);
        $user->save();

        // Envoyer l'email avec le nouveau mot de passe
        try {
            $this->emailService->envoyerEmailResetPassword($user, $newPassword);
        } catch (\Exception $e) {
            Log::error('Erreur envoi email reset password: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès. Un email a été envoyé à l\'utilisateur.'
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

    private function createRoleSpecificProfile($user, $request)
    {
        switch ($user->role) {
            case 'eleve':
                $user->eleve()->create([
                    'nationalite' => $request->nationalite ?? 'Sénégalaise',
                    'groupe_sanguin' => $request->groupe_sanguin,
                    'allergies' => $request->allergies,
                    'maladies' => $request->maladies,
                    'personne_urgence_nom' => $request->personne_urgence_nom,
                    'personne_urgence_telephone' => $request->personne_urgence_telephone,
                ]);
                break;

            case 'enseignant':
                $user->enseignant()->create([
                    'specialite' => $request->specialite,
                    'diplome' => $request->diplome,
                    'annees_experience' => $request->annees_experience ?? 0,
                ]);
                break;

            case 'parent':
                $user->parent()->create([
                    'profession' => $request->profession,
                    'lieu_travail' => $request->lieu_travail,
                    'telephone_bureau' => $request->telephone_bureau,
                ]);
                break;
        }
    }

    private function updateRoleSpecificProfile($user, $request)
    {
        switch ($user->role) {
            case 'eleve':
                if ($user->eleve) {
                    $user->eleve->update($request->only([
                        'nationalite', 'groupe_sanguin', 'allergies',
                        'maladies', 'personne_urgence_nom', 'personne_urgence_telephone'
                    ]));
                }
                break;

            case 'enseignant':
                if ($user->enseignant) {
                    $user->enseignant->update($request->only([
                        'specialite', 'diplome', 'annees_experience'
                    ]));
                }
                break;

            case 'parent':
                if ($user->parent) {
                    $user->parent->update($request->only([
                        'profession', 'lieu_travail', 'telephone_bureau'
                    ]));
                }
                break;
        }
    }
}
