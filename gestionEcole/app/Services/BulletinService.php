<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\Note;
use App\Models\Eleve;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\BulletinDisponible;
use ZipArchive;

class BulletinService
{
    public function genererPDF(Bulletin $bulletin)
    {
        // Charger les relations nécessaires
        $bulletin->load([
            'eleve.user',
            'eleve.inscriptions.classe.niveau',
            'classe.niveau',
            'periode.anneeScolaire'
        ]);

        // Récupérer les notes de l'élève pour cette période
        $notes = Note::where('eleve_id', $bulletin->eleve_id)
            ->where('periode_id', $bulletin->periode_id)
            ->with(['matiere', 'enseignant.user'])
            ->orderBy('id')
            ->get();

        // Calculer les statistiques
        $totalCoefficients = $notes->sum(function($note) {
            return $note->matiere->coefficient;
        });

        // Données pour la vue
        $data = [
            'bulletin' => $bulletin,
            'eleve' => $bulletin->eleve,
            'classe' => $bulletin->classe,
            'periode' => $bulletin->periode,
            'notes' => $notes,
            'totalCoefficients' => $totalCoefficients,
            'anneeScolaire' => $bulletin->periode->anneeScolaire,
            'dateGeneration' => now()->format('d/m/Y')
        ];

        // Générer le PDF
        $pdf = PDF::loadView('bulletins.template', $data);
        $pdf->setPaper('A4', 'portrait');

        // Sauvegarder le PDF
        $filename = 'bulletins/' . $bulletin->periode->anneeScolaire->libelle . '/' .
            $bulletin->periode->nom . '/' .
            $bulletin->eleve->user->matricule . '_' .
            str_replace(' ', '_', $bulletin->eleve->user->nom) . '.pdf';

        Storage::put($filename, $pdf->output());

        return $filename;
    }

    public function envoyerNotification(Bulletin $bulletin)
    {
        $bulletin->load(['eleve.user', 'eleve.parents.user', 'periode', 'classe']);

        // Envoyer à l'élève
        if ($bulletin->eleve->user->email) {
            Mail::to($bulletin->eleve->user->email)
                ->send(new BulletinDisponible($bulletin));
        }

        // Envoyer aux parents
        foreach ($bulletin->eleve->parents as $parent) {
            if ($parent->user->email) {
                Mail::to($parent->user->email)
                    ->send(new BulletinDisponible($bulletin));
            }
        }
    }

    public function creerZipBulletins($bulletins)
    {
        $zip = new ZipArchive();
        $zipFileName = 'temp/bulletins_' . time() . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        // Créer le dossier temp s'il n'existe pas
        if (!Storage::exists('temp')) {
            Storage::makeDirectory('temp');
        }

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($bulletins as $bulletin) {
                if ($bulletin->fichier_pdf && Storage::exists($bulletin->fichier_pdf)) {
                    $pdfPath = storage_path('app/' . $bulletin->fichier_pdf);
                    $pdfName = $bulletin->eleve->user->matricule . '_' .
                        $bulletin->eleve->user->nom . '_' .
                        $bulletin->eleve->user->prenom . '.pdf';
                    $zip->addFile($pdfPath, $pdfName);
                }
            }
            $zip->close();
        }

        return $zipPath;
    }

    public function calculerMoyennesPonderees($notes)
    {
        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($notes as $note) {
            if ($note->moyenne !== null) {
                $totalPoints += $note->moyenne * $note->matiere->coefficient;
                $totalCoefficients += $note->matiere->coefficient;
            }
        }

        return $totalCoefficients > 0 ? round($totalPoints / $totalCoefficients, 2) : 0;
    }

    public function determinerMention($moyenne)
    {
        return match(true) {
            $moyenne >= 18 => 'excellent',
            $moyenne >= 16 => 'tres_bien',
            $moyenne >= 14 => 'bien',
            $moyenne >= 12 => 'assez_bien',
            $moyenne >= 10 => 'passable',
            default => 'insuffisant'
        };
    }

    public function genererAppreciation($moyenne, $rang, $effectif)
    {
        $pourcentageRang = ($rang / $effectif) * 100;

        if ($moyenne >= 16) {
            return "Excellent travail ! Continuez ainsi.";
        } elseif ($moyenne >= 14) {
            return "Bon travail. Gardez ce rythme.";
        } elseif ($moyenne >= 12) {
            return "Travail satisfaisant. Des efforts supplémentaires permettraient d'améliorer vos résultats.";
        } elseif ($moyenne >= 10) {
            if ($pourcentageRang <= 50) {
                return "Résultats passables mais encourageants. Persévérez dans vos efforts.";
            } else {
                return "Résultats passables. Un travail plus régulier est nécessaire.";
            }
        } else {
            return "Résultats insuffisants. Un travail plus soutenu et régulier est indispensable.";
        }
    }
}
