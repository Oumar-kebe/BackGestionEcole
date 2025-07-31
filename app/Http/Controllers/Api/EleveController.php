<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\User;
use App\Models\Inscription;
use App\Models\ParentEleve;
use App\Models\Classe;
use App\Models\AnneeScolaire;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\EleveRequest;

class EleveController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function index(Request $request)
    {
        $query = Eleve::with(['user', 'inscriptions.classe.niveau','parents.user']);

        // if ($request->has('classe_id')) {
        //     $query->whereHas('inscriptions', function($q) use ($request) {
        //         $q->where('classe_id', $request->classe_id)
        //             ->where('statut', 'en_cours');
        //     });
        // }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('annee_scolaire_id')) {
            $query->whereHas('inscriptions', function($q) use ($request) {
                $q->where('annee_scolaire_id', $request->annee_scolaire_id);
            });
        }

        $eleves = $request->has('paginate') && $request->paginate == false
            ? $query->get()
            : $query->paginate($request->per_page ?? 100);

        return response()->json([
            'success' => true,
            'data' => $eleves
        ]);
    }

    public function store(EleveRequest $request)
    {
        DB::beginTransaction();

        try {
            // Générer les mots de passe
            $passwordEleve = $this->generatePassword('eleve');

            // Créer l'utilisateur élève
            $userEleve = User::create([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
                'password' => Hash::make($passwordEleve),
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'role' => 'eleve',
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'matricule' => $this->generateMatricule('eleve'),
                'actif' => true,
                'email_verified_at' => now()
            ]);

            // Créer le profil élève
            $eleve = Eleve::create([
                'user_id' => $userEleve->id,
                'nationalite' => $request->nationalite ?? 'Sénégalaise',
                'groupe_sanguin' => $request->groupe_sanguin,
                'allergies' => $request->allergies,
                'maladies' => $request->maladies,
                'personne_urgence_nom' => $request->personne_urgence_nom,
                'personne_urgence_telephone' => $request->personne_urgence_telephone,
            ]);

            // Gérer le parent
            $passwordParent = null;
            $parentCree = false;

            // Vérifier si le parent existe déjà
            $userParent = User::where('email', $request->parent_email)->first();

            if (!$userParent) {
                // Créer le nouveau parent
                $passwordParent = $this->generatePassword('parent');
                $parentCree = true;

                $userParent = User::create([
                    'name' => $request->parent_prenom . ' ' . $request->parent_nom,
                    'email' => $request->parent_email,
                    'password' => Hash::make($passwordParent),
                    'nom' => $request->parent_nom,
                    'prenom' => $request->parent_prenom,
                    'role' => 'parent',
                    'telephone' => $request->parent_telephone,
                    'adresse' => $request->parent_adresse ?? $request->adresse,
                    'sexe' => $request->parent_sexe ?? ($request->lien_parente === 'pere' ? 'M' : 'F'),
                    'matricule' => $this->generateMatricule('parent'),
                    'actif' => true,
                    'email_verified_at' => now()
                ]);

                ParentEleve::create([
                    'user_id' => $userParent->id,
                    'profession' => $request->parent_profession,
                    'lieu_travail' => $request->parent_lieu_travail,
                    'telephone_bureau' => $request->parent_telephone_bureau,
                ]);
            }

            $parent = ParentEleve::where('user_id', $userParent->id)->first();

            // Lier le parent à l'élève
            $eleve->parents()->attach($parent->id, [
                'lien_parente' => $request->lien_parente
            ]);

            // Inscrire l'élève dans la classe
            $classe = Classe::findOrFail($request->classe_id);

            // Vérifier la capacité de la classe
            if ($classe->estPleine()) {
                throw new \Exception('La classe sélectionnée est pleine');
            }

            Inscription::create([
                'eleve_id' => $eleve->id,
                'classe_id' => $classe->id,
                'annee_scolaire_id' => $classe->annee_scolaire_id,
                'date_inscription' => $request->date_inscription ?? now(),
                'statut' => 'en_cours',
                'observations' => $request->observations
            ]);

            DB::commit();

            // Envoyer les emails
            $this->envoyerEmailsInscription($userEleve, $passwordEleve, $userParent, $passwordParent, $parentCree);

            $message = $parentCree
                ? 'Élève inscrit avec succès. Les identifiants ont été envoyés par email à l\'élève et au parent.'
                : 'Élève inscrit avec succès. Les identifiants de l\'élève ont été envoyés par email.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $eleve->load(['user', 'inscriptions.classe.niveau', 'parents.user'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription de l\'élève',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    public function show($id)
    {
        $eleve = Eleve::with([
            'user',
            'inscriptions.classe.niveau',
            'inscriptions.anneeScolaire',
            'parents.user',
            'notes.matiere',
            'notes.periode',
            'bulletins.periode',
            'documents'
        ])->findOrFail($id);

        // Ajouter des statistiques
        $eleve->statistiques = [
            'nombre_inscriptions' => $eleve->inscriptions->count(),
            'inscription_actuelle' => $eleve->inscription_actuelle,
            'classe_actuelle' => $eleve->classe_actuelle,
            'nombre_notes' => $eleve->notes->count(),
            'nombre_bulletins' => $eleve->bulletins->count(),
            'nombre_documents' => $eleve->documents->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $eleve
        ]);
    }

    public function update(Request $request, $id)
    {
        $eleve = Eleve::findOrFail($id);

        $request->validate([
            'nationalite' => 'nullable|string|max:100',
            'groupe_sanguin' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'allergies' => 'nullable|string',
            'maladies' => 'nullable|string',
            'personne_urgence_nom' => 'nullable|string|max:255',
            'personne_urgence_telephone' => 'nullable|string|max:20',
            // Informations utilisateur
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour les informations de l'utilisateur
            $userData = $request->only(['nom', 'prenom', 'telephone', 'adresse']);
            if (!empty($userData)) {
                if (isset($userData['nom']) || isset($userData['prenom'])) {
                    $nom = $userData['nom'] ?? $eleve->user->nom;
                    $prenom = $userData['prenom'] ?? $eleve->user->prenom;
                    $userData['name'] = $prenom . ' ' . $nom;
                }
                $eleve->user->update($userData);
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
                'data' => $eleve->fresh()->load('user')
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
        $eleve = Eleve::findOrFail($id);

        // Vérifier si l'élève a des notes ou des bulletins
        if ($eleve->notes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cet élève car il a des notes enregistrées'
            ], 422);
        }

        if ($eleve->bulletins()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cet élève car il a des bulletins générés'
            ], 422);
        }

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
            ->orderBy('matiere_id')
            ->get();

        // Grouper par période
        $notesParPeriode = $notes->groupBy('periode_id')->map(function($notesPeriode) {
            $periode = $notesPeriode->first()->periode;
            $totalPoints = 0;
            $totalCoefficients = 0;

            foreach ($notesPeriode as $note) {
                if ($note->moyenne !== null) {
                    $totalPoints += $note->moyenne * $note->matiere->coefficient;
                    $totalCoefficients += $note->matiere->coefficient;
                }
            }

            $moyenneGenerale = $totalCoefficients > 0
                ? round($totalPoints / $totalCoefficients, 2)
                : null;

            return [
                'periode' => $periode,
                'notes' => $notesPeriode,
                'moyenne_generale' => $moyenneGenerale,
                'total_matieres' => $notesPeriode->count(),
                'matieres_notees' => $notesPeriode->filter(function($n) {
                    return $n->moyenne !== null;
                })->count()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $notesParPeriode->values()
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
            'motif' => 'required|string|max:500',
            'date_changement' => 'nullable|date'
        ]);

        DB::beginTransaction();

        try {
            // Récupérer l'inscription actuelle
            $inscriptionActuelle = $eleve->inscriptions()
                ->where('statut', 'en_cours')
                ->first();

            if (!$inscriptionActuelle) {
                throw new \Exception('Aucune inscription en cours trouvée pour cet élève');
            }

            // Vérifier que la nouvelle classe est de la même année scolaire
            $nouvelleClasse = Classe::findOrFail($request->nouvelle_classe_id);
            if ($nouvelleClasse->annee_scolaire_id !== $inscriptionActuelle->annee_scolaire_id) {
                throw new \Exception('La nouvelle classe doit être de la même année scolaire');
            }

            // Vérifier la capacité de la nouvelle classe
            if ($nouvelleClasse->estPleine()) {
                throw new \Exception('La classe sélectionnée est pleine');
            }

            // Terminer l'inscription actuelle
            $inscriptionActuelle->update([
                'statut' => 'termine',
                'observations' => $inscriptionActuelle->observations .
                    ' | Changement de classe le ' . now()->format('d/m/Y') .
                    ' - Motif: ' . $request->motif
            ]);

            // Créer la nouvelle inscription
            $nouvelleInscription = Inscription::create([
                'eleve_id' => $eleve->id,
                'classe_id' => $request->nouvelle_classe_id,
                'annee_scolaire_id' => $nouvelleClasse->annee_scolaire_id,
                'date_inscription' => $request->date_changement ?? now(),
                'statut' => 'en_cours',
                'observations' => 'Changement depuis ' . $inscriptionActuelle->classe->nom .
                    ' - Motif: ' . $request->motif
            ]);

            DB::commit();

            // Envoyer une notification aux parents
            try {
                $this->notifierChangementClasse($eleve, $inscriptionActuelle->classe, $nouvelleClasse, $request->motif);
            } catch (\Exception $e) {
                Log::error('Erreur notification changement classe: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Élève changé de classe avec succès',
                'data' => $nouvelleInscription->load('classe.niveau')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de classe',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
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
            ->where('role', $role)
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
            'eleve' => 'eleve',
            'parent' => 'parent',
            default => 'user'
        };

        return $prefix . rand(1000, 9999);
    }

    private function envoyerEmailsInscription($userEleve, $passwordEleve, $userParent, $passwordParent, $parentCree)
    {
        try {
            // Email à l'élève
            $this->emailService->envoyerEmailBienvenue($userEleve, $passwordEleve);

            // Email au parent si nouveau
            if ($parentCree && $passwordParent) {
                $this->emailService->envoyerEmailBienvenue($userParent, $passwordParent);
            }
        } catch (\Exception $e) {
            Log::error('Erreur envoi emails inscription: ' . $e->getMessage());
            // Ne pas bloquer l'inscription si l'email échoue
        }
    }

    private function notifierChangementClasse($eleve, $ancienneClasse, $nouvelleClasse, $motif)
    {
        // Implémenter la notification par email aux parents
        // Vous pouvez créer un nouveau template d'email pour cela
        foreach ($eleve->parents as $parent) {
            if ($parent->user->email) {
                // Envoyer email de notification
                Log::info("Notification changement classe pour {$parent->user->email}");
            }
        }
    }
}
