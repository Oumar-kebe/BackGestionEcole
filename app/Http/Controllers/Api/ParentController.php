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

    public function voirBulletin($eleveId, $trimestreId)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur est bien un parent
        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être un parent.'
            ], 403);
        }

        // Récupérer l'enregistrement parent
        $parent = $user->parent; // Utiliser la bonne relation
        
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun profil parent trouvé pour cet utilisateur.'
            ], 404);
        }
    
        $estSonEnfant = $parent->enfants()->where('eleves.id', $eleveId)->exists();
    
        if (!$estSonEnfant) {
            return response()->json([
                'success' => false,
                'message' => 'Cet enfant ne vous appartient pas.'
            ], 403);
        }
    
        $bulletin = \App\Models\Bulletin::where('eleve_id', $eleveId)
            ->where('periode_id', $trimestreId)
            ->with(['classe.niveau', 'periode', 'eleve.user'])
            ->first();
    
        if (!$bulletin) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun bulletin trouvé pour cet élève et ce trimestre.'
            ], 404);
        }

        // Récupérer les notes détaillées de l'élève pour cette période ET cette classe seulement
        $notes = \App\Models\Note::where('eleve_id', $eleveId)
            ->where('periode_id', $trimestreId)
            ->whereHas('matiere', function($query) use ($bulletin) {
                $query->where('niveau_id', $bulletin->classe->niveau_id);
            })
            ->with([
                'matiere:id,nom,code,coefficient,niveau_id',
                'enseignant.user:id,nom,prenom'
            ])
            ->get()
            ->map(function ($note) use ($trimestreId, $bulletin) {
                return [
                    'matiere_nom' => $note->matiere->nom,
                    'matiere_code' => $note->matiere->code,
                    'matiere_couleur' => '#3B82F6', // Couleur par défaut
                    'coefficient' => $note->matiere->coefficient,
                    'enseignant_nom' => $note->enseignant ? 
                        trim($note->enseignant->user->prenom . ' ' . $note->enseignant->user->nom) : 
                        'Non assigné',
                    'note_devoir1' => $note->note_devoir1,
                    'note_devoir2' => $note->note_devoir2,
                    'note_composition' => $note->note_composition,
                    'moyenne' => $note->moyenne,
                    'appreciation' => $note->appreciation,
                    'moyenne_classe' => $this->getMoyenneClasse($note->matiere_id, $trimestreId, $bulletin->classe_id)
                ];
            });

        // Enrichir les données du bulletin
        $bulletinData = $bulletin->toArray();
        $bulletinData['notes'] = $notes;
        $bulletinData['trimestre'] = $bulletin->periode->nom ?? 'Trimestre ' . $trimestreId;
        $bulletinData['appreciation_generale'] = $bulletin->observation_conseil;
        $bulletinData['professeur_principal'] = $this->getProfesseurPrincipal($bulletin->classe_id);
        $bulletinData['conseils'] = $this->getConseils($bulletin->moyenne_generale);
        $bulletinData['evolution'] = $this->getEvolution($eleveId, $trimestreId);
    
        return response()->json([
            'success' => true,
            'data' => $bulletinData
        ]);
    }

    private function getMoyenneClasse($matiereId, $periodeId, $classeId)
    {
        // Calculer la moyenne de la classe pour une matière donnée
        $moyenneClasse = \App\Models\Note::whereHas('eleve.inscriptions', function($q) use ($classeId) {
                $q->where('classe_id', $classeId)
                  ->where('statut', 'confirmee');
            })
            ->where('matiere_id', $matiereId)
            ->where('periode_id', $periodeId)
            ->whereNotNull('moyenne')
            ->avg('moyenne');

        return $moyenneClasse ? round($moyenneClasse, 2) : null;
    }

    private function getProfesseurPrincipal($classeId)
    {
        // Récupérer le professeur principal de la classe
        $classe = \App\Models\Classe::with(['enseignants.user'])
            ->find($classeId);
        
        if ($classe && $classe->enseignants->isNotEmpty()) {
            $principal = $classe->enseignants->first();
            return trim($principal->user->prenom . ' ' . $principal->user->nom);
        }

        return 'Non assigné';
    }

    private function getConseils($moyenne)
    {
        // Générer des conseils basés sur la moyenne
        if ($moyenne >= 15) {
            return "Excellent travail ! Continuez sur cette lancée.";
        } elseif ($moyenne >= 12) {
            return "Bon travail, quelques efforts supplémentaires vous permettront d'atteindre l'excellence.";
        } elseif ($moyenne >= 10) {
            return "Travail satisfaisant, mais des améliorations sont possibles dans certaines matières.";
        } else {
            return "Des efforts considérables sont nécessaires pour améliorer les résultats.";
        }
    }

    private function getEvolution($eleveId, $periodeId)
    {
        // Calculer l'évolution par rapport à la période précédente
        $periodeActuelle = \App\Models\Periode::find($periodeId);
        if (!$periodeActuelle) return 0;

        // Trouver la période précédente
        $periodePrecedente = \App\Models\Periode::where('annee_scolaire_id', $periodeActuelle->annee_scolaire_id)
            ->where('ordre', $periodeActuelle->ordre - 1)
            ->first();

        if (!$periodePrecedente) return 0;

        $bulletinActuel = \App\Models\Bulletin::where('eleve_id', $eleveId)
            ->where('periode_id', $periodeId)
            ->first();

        $bulletinPrecedent = \App\Models\Bulletin::where('eleve_id', $eleveId)
            ->where('periode_id', $periodePrecedente->id)
            ->first();

        if ($bulletinActuel && $bulletinPrecedent) {
            return round($bulletinActuel->moyenne_generale - $bulletinPrecedent->moyenne_generale, 2);
        }

        return 0;
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
        
        // Vérifier que l'utilisateur est bien un parent
        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être un parent.'
            ], 403);
        }

        $parent = $user->parent; // Utiliser la bonne relation
        
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun profil parent trouvé pour cet utilisateur.'
            ], 404);
        }

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
        
        // Vérifier que l'utilisateur est bien un parent
        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Vous devez être un parent.'
            ], 403);
        }

        $parent = $user->parent; // Utiliser la bonne relation
        
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun profil parent trouvé pour cet utilisateur.'
            ], 404);
        }

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
