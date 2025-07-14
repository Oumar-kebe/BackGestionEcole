<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\User;
use App\Models\Inscription;
use App\Models\ParentEleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EleveController extends Controller
{
    public function index(Request $request)
    {
        $query = Eleve::with(['user', 'inscriptions.classe.niveau']);

        if ($request->has('classe_id')) {
            $query->whereHas('inscriptions', function($q) use ($request) {
                $q->where('classe_id', $request->classe_id)
                    ->where('statut', 'en_cours');
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $eleves = $query->get();

        return response()->json([
            'success' => true,
            'data' => $eleves
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string',
            'sexe' => 'required|in:M,F',
            'telephone' => 'nullable|string',
            'adresse' => 'required|string',
            'nationalite' => 'nullable|string',
            'groupe_sanguin' => 'nullable|string',
            'allergies' => 'nullable|string',
            'maladies' => 'nullable|string',
            'personne_urgence_nom' => 'nullable|string',
            'personne_urgence_telephone' => 'nullable|string',
            'classe_id' => 'required|exists:classes,id',
            // Informations du parent
            'parent_nom' => 'required|string',
            'parent_prenom' => 'required|string',
            'parent_email' => 'required|email',
            'parent_telephone' => 'required|string',
            'lien_parente' => 'required|in:pere,mere,tuteur,autre',
        ]);

        DB::beginTransaction();

        try {
            // Créer l'utilisateur élève
            $userEleve = User::create([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
                'password' => Hash::make('password123'),
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'role' => 'eleve',
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'matricule' => $this->generateMatricule('eleve'),
                'actif' => true
            ]);

            // Créer le profil élève
            $eleve = Eleve::create([
                'user_id' => $userEleve->id,
                'nationalite' => $request->nationalite,
                'groupe_sanguin' => $request->groupe_sanguin,
                'allergies' => $request->allergies,
                'maladies' => $request->maladies,
                'personne_urgence_nom' => $request->personne_urgence_nom,
                'personne_urgence_telephone' => $request->personne_urgence_telephone,
            ]);

            // Créer ou récupérer le parent
            $userParent = User::where('email', $request->parent_email)->first();

            if (!$userParent) {
                $userParent = User::create([
                    'name' => $request->parent_prenom . ' ' . $request->parent_nom,
                    'email' => $request->parent_email,
                    'password' => Hash::make('password123'),
                    'nom' => $request->parent_nom,
                    'prenom' => $request->parent_prenom,
                    'role' => 'parent',
                    'telephone' => $request->parent_telephone,
                    'matricule' => $this->generateMatricule('parent'),
                    'actif' => true
                ]);

                ParentEleve::create([
                    'user_id' => $userParent->id,
                    'profession' => $request->parent_profession ?? null,
                    'lieu_travail' => $request->parent_lieu_travail ?? null,
                ]);
            }

            $parent = ParentEleve::where('user_id', $userParent->id)->first();

            // Lier le parent à l'élève
            $eleve->parents()->attach($parent->id, [
                'lien_parente' => $request->lien_parente
            ]);

            // Inscrire l'élève dans la classe
            $classe = \App\Models\Classe::find($request->classe_id);
            Inscription::create([
                'eleve_id' => $eleve->id,
                'classe_id' => $request->classe_id,
                'annee_scolaire_id' => $classe->annee_scolaire_id,
                'date_inscription' => now(),
                'statut' => 'en_cours'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Élève inscrit avec succès',
                'data' => $eleve->load(['user', 'inscriptions.classe', 'parents.user'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription de l\'élève',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $eleve = Eleve::with([
            'user',
            'inscriptions.classe.niveau',
            'parents.user',
            'notes.matiere',
            'bulletins',
            'documents'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $eleve
        ]);
    }

    public function update(Request $request, $id)
    {
        $eleve = Eleve::findOrFail($id);

        $request->validate([
            'nationalite' => 'nullable|string',
            'groupe_sanguin' => 'nullable|string',
            'allergies' => 'nullable|string',
            'maladies' => 'nullable|string',
            'personne_urgence_nom' => 'nullable|string',
            'personne_urgence_telephone' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour les informations de l'utilisateur
            if ($request->has(['nom', 'prenom', 'telephone', 'adresse'])) {
                $eleve->user->update($request->only(['nom', 'prenom', 'telephone', 'adresse']));
                if ($request->has('nom') || $request->has('prenom')) {
                    $eleve->user->name = $eleve->user->prenom . ' ' . $eleve->user->nom;
                    $eleve->user->save();
                }
            }

            // Mettre à jour les informations de l'élève
            $eleve->update($request->only([
                'nationalite',
                'groupe_sanguin',
                'allergies',
                'maladies',
                'personne_urgence_nom',
                'personne_urgence_telephone'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Informations de l\'élève mises à jour avec succès',
                'data' => $eleve->load('user')
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

    public function destroy($id)
    {
        $eleve = Eleve::findOrFail($id);

        // Soft delete de l'utilisateur (qui cascade sur l'élève)
        $eleve->user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Élève supprimé avec succès'
        ]);
    }

    public function notes($id)
    {
        $eleve = Eleve::findOrFail($id);

        $notes = $eleve->notes()
            ->with(['matiere', 'periode', 'enseignant.user'])
            ->orderBy('periode_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notes
        ]);
    }

    public function bulletins($id)
    {
        $eleve = Eleve::findOrFail($id);

        $bulletins = $eleve->bulletins()
            ->with(['periode', 'classe.niveau'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bulletins
        ]);
    }

    public function changerClasse(Request $request, $id)
    {
        $eleve = Eleve::findOrFail($id);

        $request->validate([
            'nouvelle_classe_id' => 'required|exists:classes,id',
            'motif' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            // Terminer l'inscription actuelle
            $inscriptionActuelle = $eleve->inscriptions()
                ->where('statut', 'en_cours')
                ->first();

            if ($inscriptionActuelle) {
                $inscriptionActuelle->update([
                    'statut' => 'termine',
                    'observations' => $request->motif ?? 'Changement de classe'
                ]);
            }

            // Créer la nouvelle inscription
            $nouvelleClasse = \App\Models\Classe::find($request->nouvelle_classe_id);
            Inscription::create([
                'eleve_id' => $eleve->id,
                'classe_id' => $request->nouvelle_classe_id,
                'annee_scolaire_id' => $nouvelleClasse->annee_scolaire_id,
                'date_inscription' => now(),
                'statut' => 'en_cours',
                'observations' => 'Changement depuis ' . ($inscriptionActuelle?->classe?->nom ?? 'classe précédente')
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Élève changé de classe avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de classe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateMatricule($role)
    {
        $prefix = match($role) {
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
