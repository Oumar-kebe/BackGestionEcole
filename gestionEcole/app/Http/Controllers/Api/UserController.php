<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['eleve', 'enseignant', 'parent']);

        // Filtres
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('actif')) {
            $query->where('actif', $request->actif);
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

        $users = $query->orderBy('nom')->orderBy('prenom')->paginate(20);

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
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string',
            'sexe' => 'nullable|in:M,F',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
                'password' => Hash::make('password123'), // Mot de passe par défaut
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'role' => $request->role,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'matricule' => $this->generateMatricule($request->role),
            ]);

            // Créer le profil spécifique selon le rôle
            $this->createRoleSpecificProfile($user, $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => $user->load(['eleve', 'enseignant', 'parent'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = User::with(['eleve.inscriptions.classe', 'enseignant.matieres', 'parent.enfants'])
            ->findOrFail($id);

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
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string',
            'sexe' => 'nullable|in:M,F',
            'actif' => 'sometimes|boolean',
        ]);

        $user->update($request->all());

        if ($request->has('name')) {
            $user->name = $user->prenom . ' ' . $user->nom;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => $user
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

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

        return response()->json([
            'success' => true,
            'message' => $user->actif ? 'Utilisateur activé' : 'Utilisateur désactivé',
            'data' => $user
        ]);
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $newPassword = 'password123'; // Ou générer un mot de passe aléatoire

        $user->password = Hash::make($newPassword);
        $user->save();

        // TODO: Envoyer un email avec le nouveau mot de passe

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès'
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

    private function createRoleSpecificProfile($user, $request)
    {
        switch ($user->role) {
            case 'eleve':
                $user->eleve()->create($request->only([
                    'nationalite',
                    'groupe_sanguin',
                    'allergies',
                    'maladies',
                    'personne_urgence_nom',
                    'personne_urgence_telephone'
                ]));
                break;

            case 'enseignant':
                $user->enseignant()->create($request->only([
                    'specialite',
                    'diplome',
                    'annees_experience'
                ]));
                break;

            case 'parent':
                $user->parent()->create($request->only([
                    'profession',
                    'lieu_travail',
                    'telephone_bureau'
                ]));
                break;
        }
    }
}
