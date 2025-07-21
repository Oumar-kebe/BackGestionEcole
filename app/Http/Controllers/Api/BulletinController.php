<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bulletin;
use App\Models\Note;
use App\Models\Eleve;
use App\Models\Periode;
use App\Models\Classe;
use App\Services\BulletinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BulletinController extends Controller
{
    protected $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }

    public function index(Request $request)
    {
        $query = Bulletin::with(['eleve.user', 'classe.niveau', 'periode']);

        if ($request->has('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        if ($request->has('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }

        if ($request->has('eleve_id')) {
            $query->where('eleve_id', $request->eleve_id);
        }

        $bulletins = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $bulletins
        ]);
    }

    public function generer(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periodes,id',
            'classe_id' => 'nullable|exists:classes,id',
            'eleve_id' => 'nullable|exists:eleves,id',
        ]);

        if (!$request->classe_id && !$request->eleve_id) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez spécifier une classe ou un élève'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $bulletinsGeneres = [];

            if ($request->eleve_id) {
                // Générer pour un seul élève
                $bulletin = $this->genererBulletinEleve($request->eleve_id, $request->periode_id);
                $bulletinsGeneres[] = $bulletin;
            } else {
                // Générer pour toute la classe
                $classe = Classe::find($request->classe_id);
                $eleves = $classe->inscriptions()
                    ->where('statut', 'en_cours')
                    ->with('eleve')
                    ->get()
                    ->pluck('eleve');

                foreach ($eleves as $eleve) {
                    $bulletin = $this->genererBulletinEleve($eleve->id, $request->periode_id);
                    $bulletinsGeneres[] = $bulletin;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($bulletinsGeneres) . ' bulletin(s) généré(s) avec succès',
                'data' => $bulletinsGeneres
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des bulletins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function genererBulletinEleve($eleveId, $periodeId)
    {
        $eleve = Eleve::find($eleveId);
        $periode = Periode::find($periodeId);

        // Récupérer l'inscription actuelle
        $inscription = $eleve->inscriptions()
            ->where('annee_scolaire_id', $periode->annee_scolaire_id)
            ->where('statut', 'en_cours')
            ->first();

        if (!$inscription) {
            throw new \Exception("L'élève n'est pas inscrit pour cette période");
        }

        // Récupérer toutes les notes de l'élève pour cette période
        $notes = Note::where('eleve_id', $eleveId)
            ->where('periode_id', $periodeId)
            ->with('matiere')
            ->get();

        // Calculer la moyenne générale
        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($notes as $note) {
            if ($note->moyenne !== null) {
                $totalPoints += $note->moyenne * $note->matiere->coefficient;
                $totalCoefficients += $note->matiere->coefficient;
            }
        }

        $moyenneGenerale = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;

        // Calculer le rang de l'élève dans la classe
        $rang = $this->calculerRang($inscription->classe_id, $periodeId, $moyenneGenerale);
        $effectifClasse = $inscription->classe->inscriptions()
            ->where('statut', 'en_cours')
            ->count();

        // Créer ou mettre à jour le bulletin
        $bulletin = Bulletin::updateOrCreate(
            [
                'eleve_id' => $eleveId,
                'periode_id' => $periodeId,
            ],
            [
                'classe_id' => $inscription->classe_id,
                'moyenne_generale' => round($moyenneGenerale, 2),
                'rang' => $rang,
                'effectif_classe' => $effectifClasse,
                'genere_le' => now(),
            ]
        );

        // Générer la mention
        $bulletin->genererMention();
        $bulletin->save();

        // Générer le PDF
        $pdfPath = $this->bulletinService->genererPDF($bulletin);
        $bulletin->fichier_pdf = $pdfPath;
        $bulletin->save();

        // Envoyer une notification email
        $this->bulletinService->envoyerNotification($bulletin);

        return $bulletin->load(['eleve.user', 'classe.niveau', 'periode']);
    }

    protected function calculerRang($classeId, $periodeId, $moyenneEleve)
    {
        // Récupérer toutes les moyennes de la classe
        $moyennes = DB::table('bulletins')
            ->where('classe_id', $classeId)
            ->where('periode_id', $periodeId)
            ->orderBy('moyenne_generale', 'desc')
            ->pluck('moyenne_generale')
            ->toArray();

        // Ajouter la moyenne de l'élève si elle n'existe pas encore
        if (!in_array($moyenneEleve, $moyennes)) {
            $moyennes[] = $moyenneEleve;
            sort($moyennes);
            $moyennes = array_reverse($moyennes);
        }

        // Trouver le rang
        $rang = array_search($moyenneEleve, $moyennes) + 1;

        return $rang;
    }

    public function show($id)
    {
        $bulletin = Bulletin::with([
            'eleve.user',
            'eleve.parents.user',
            'classe.niveau',
            'periode'
        ])->findOrFail($id);

        // Vérifier l'autorisation
        $user = auth()->user();
        if ($user->role === 'eleve' && $bulletin->eleve->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à consulter ce bulletin'
            ], 403);
        }

        if ($user->role === 'parent') {
            $parent = \App\Models\ParentEleve::where('user_id', $user->id)->first();
            if (!$parent || !$parent->peutVoirEleve($bulletin->eleve_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à consulter ce bulletin'
                ], 403);
            }
        }

        // Ajouter les notes détaillées
        $notes = Note::where('eleve_id', $bulletin->eleve_id)
            ->where('periode_id', $bulletin->periode_id)
            ->with(['matiere', 'enseignant.user'])
            ->get();

        $bulletin->notes = $notes;

        return response()->json([
            'success' => true,
            'data' => $bulletin
        ]);
    }

    public function telecharger($id)
    {
        $bulletin = Bulletin::findOrFail($id);

        // Vérifier l'autorisation
        $user = auth()->user();
        if ($user->role === 'eleve' && $bulletin->eleve->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à télécharger ce bulletin'
            ], 403);
        }

        if ($user->role === 'parent') {
            $parent = \App\Models\ParentEleve::where('user_id', $user->id)->first();
            if (!$parent || !$parent->peutVoirEleve($bulletin->eleve_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à télécharger ce bulletin'
                ], 403);
            }
        }

        // Vérifier si le fichier PDF existe
        if (!$bulletin->fichier_pdf || !Storage::exists($bulletin->fichier_pdf)) {
            // Régénérer le PDF si nécessaire
            $pdfPath = $this->bulletinService->genererPDF($bulletin);
            $bulletin->fichier_pdf = $pdfPath;
            $bulletin->save();
        }

        return Storage::download($bulletin->fichier_pdf, 'bulletin_' . $bulletin->id . '.pdf');
    }

    public function observationConseil(Request $request, $id)
    {
        $bulletin = Bulletin::findOrFail($id);

        $request->validate([
            'observation_conseil' => 'required|string'
        ]);

        $bulletin->observation_conseil = $request->observation_conseil;
        $bulletin->save();

        // Régénérer le PDF avec l'observation
        $pdfPath = $this->bulletinService->genererPDF($bulletin);
        $bulletin->fichier_pdf = $pdfPath;
        $bulletin->save();

        return response()->json([
            'success' => true,
            'message' => 'Observation du conseil ajoutée avec succès',
            'data' => $bulletin
        ]);
    }

    public function telechargerGroupe(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'periode_id' => 'required|exists:periodes,id',
        ]);

        $bulletins = Bulletin::where('classe_id', $request->classe_id)
            ->where('periode_id', $request->periode_id)
            ->with('eleve.user')
            ->get();

        if ($bulletins->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun bulletin trouvé pour cette classe et cette période'
            ], 404);
        }

        // Créer un ZIP contenant tous les bulletins
        $zipPath = $this->bulletinService->creerZipBulletins($bulletins);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function mesBulletins()
    {
        $user = auth()->user();
        $eleve = Eleve::where('user_id', $user->id)->firstOrFail();

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
