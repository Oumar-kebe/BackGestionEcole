<?php

namespace App\Services;

use App\Models\Note;
use App\Models\Classe;
use App\Models\Periode;

class NoteService
{
    public function calculerMoyenne($noteDevoir1, $noteDevoir2, $noteComposition)
    {
        $notes = array_filter([
            $noteDevoir1,
            $noteDevoir2,
            $noteComposition ? $noteComposition * 2 : null // La composition compte double
        ], function($n) { return $n !== null; });

        if (count($notes) === 0) {
            return null;
        }

        // Si on a les 3 notes, la somme est divisée par 4 (1+1+2)
        // Sinon on divise par le nombre de notes
        $diviseur = ($noteDevoir1 !== null && $noteDevoir2 !== null && $noteComposition !== null) ? 4 : count($notes);

        return round(array_sum($notes) / $diviseur, 2);
    }

    public function genererAppreciation($moyenne)
    {
        if ($moyenne === null) return null;

        return match(true) {
            $moyenne >= 18 => 'Excellent',
            $moyenne >= 16 => 'Très bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez bien',
            $moyenne >= 10 => 'Passable',
            default => 'Insuffisant'
        };
    }

    public function calculerStatistiquesClasse($classeId, $periodeId, $matiereId = null)
    {
        $query = Note::whereHas('eleve.inscriptions', function($q) use ($classeId) {
            $q->where('classe_id', $classeId)
                ->where('statut', 'en_cours');
        })
            ->where('periode_id', $periodeId);

        if ($matiereId) {
            $query->where('matiere_id', $matiereId);
        }

        $notes = $query->get();

        if ($notes->isEmpty()) {
            return [
                'moyenne' => 0,
                'min' => 0,
                'max' => 0,
                'nombre_notes' => 0,
                'taux_reussite' => 0
            ];
        }

        $moyennes = $notes->pluck('moyenne')->filter();

        return [
            'moyenne' => round($moyennes->avg(), 2),
            'min' => $moyennes->min(),
            'max' => $moyennes->max(),
            'nombre_notes' => $moyennes->count(),
            'taux_reussite' => round(($moyennes->filter(function($m) { return $m >= 10; })->count() / $moyennes->count()) * 100, 2),
            'repartition' => [
                'excellent' => $moyennes->filter(function($m) { return $m >= 18; })->count(),
                'tres_bien' => $moyennes->filter(function($m) { return $m >= 16 && $m < 18; })->count(),
                'bien' => $moyennes->filter(function($m) { return $m >= 14 && $m < 16; })->count(),
                'assez_bien' => $moyennes->filter(function($m) { return $m >= 12 && $m < 14; })->count(),
                'passable' => $moyennes->filter(function($m) { return $m >= 10 && $m < 12; })->count(),
                'insuffisant' => $moyennes->filter(function($m) { return $m < 10; })->count(),
            ]
        ];
    }

    public function verifierCompletude($classeId, $periodeId, $matiereId)
    {
        $classe = Classe::find($classeId);
        $effectifClasse = $classe->inscriptions()->where('statut', 'en_cours')->count();

        $notesCount = Note::whereHas('eleve.inscriptions', function($q) use ($classeId) {
            $q->where('classe_id', $classeId)
                ->where('statut', 'en_cours');
        })
            ->where('periode_id', $periodeId)
            ->where('matiere_id', $matiereId)
            ->count();

        return [
            'effectif_classe' => $effectifClasse,
            'notes_saisies' => $notesCount,
            'taux_completude' => $effectifClasse > 0 ? round(($notesCount / $effectifClasse) * 100, 2) : 0,
            'complet' => $notesCount === $effectifClasse
        ];
    }

    public function exporterNotes($classeId, $periodeId)
    {
        $classe = Classe::with('niveau')->find($classeId);
        $periode = Periode::find($periodeId);

        $notes = Note::with(['eleve.user', 'matiere'])
            ->whereHas('eleve.inscriptions', function($q) use ($classeId) {
                $q->where('classe_id', $classeId)
                    ->where('statut', 'en_cours');
            })
            ->where('periode_id', $periodeId)
            ->orderBy('eleve_id')
            ->orderBy('matiere_id')
            ->get();

        // Grouper par élève
        $notesParEleve = $notes->groupBy('eleve_id');

        $data = [];
        foreach ($notesParEleve as $eleveId => $notesEleve) {
            $eleve = $notesEleve->first()->eleve;
            $row = [
                'matricule' => $eleve->user->matricule,
                'nom' => $eleve->user->nom,
                'prenom' => $eleve->user->prenom,
            ];

            foreach ($notesEleve as $note) {
                $row[$note->matiere->code . '_devoir1'] = $note->note_devoir1;
                $row[$note->matiere->code . '_devoir2'] = $note->note_devoir2;
                $row[$note->matiere->code . '_composition'] = $note->note_composition;
                $row[$note->matiere->code . '_moyenne'] = $note->moyenne;
            }

            $data[] = $row;
        }

        return [
            'classe' => $classe,
            'periode' => $periode,
            'data' => $data
        ];
    }
}
