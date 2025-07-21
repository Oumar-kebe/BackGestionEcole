<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\BienvenueMail;
use App\Mail\ResetPasswordMail;
use App\Mail\BulletinDisponible;
use App\Mail\NouvelleNote;
use App\Models\User;
use App\Models\Bulletin;

class EmailService
{
    public function envoyerEmailBienvenue(User $user, $motDePasse = null)
    {
        $data = [
            'user' => $user,
            'motDePasse' => $motDePasse ?? 'password123',
            'loginUrl' => config('app.url') . '/login'
        ];

        Mail::to($user->email)->send(new BienvenueMail($data));
    }

    public function envoyerEmailResetPassword(User $user, $nouveauMotDePasse)
    {
        $data = [
            'user' => $user,
            'nouveauMotDePasse' => $nouveauMotDePasse,
            'loginUrl' => config('app.url') . '/login'
        ];

        Mail::to($user->email)->send(new ResetPasswordMail($data));
    }

    public function notifierBulletinDisponible(Bulletin $bulletin)
    {
        $bulletin->load(['eleve.user', 'eleve.parents.user', 'periode', 'classe']);

        // Notifier l'élève
        if ($bulletin->eleve->user->email) {
            Mail::to($bulletin->eleve->user->email)
                ->send(new BulletinDisponible($bulletin));
        }

        // Notifier les parents
        foreach ($bulletin->eleve->parents as $parent) {
            if ($parent->user->email) {
                Mail::to($parent->user->email)
                    ->send(new BulletinDisponible($bulletin));
            }
        }
    }

    public function notifierNouvelleNote($note)
    {
        $note->load(['eleve.user', 'eleve.parents.user', 'matiere', 'periode']);

        $data = [
            'eleve' => $note->eleve,
            'matiere' => $note->matiere,
            'periode' => $note->periode,
            'note' => $note
        ];

        // Notifier l'élève
        if ($note->eleve->user->email) {
            Mail::to($note->eleve->user->email)
                ->send(new NouvelleNote($data));
        }

        // Notifier les parents
        foreach ($note->eleve->parents as $parent) {
            if ($parent->user->email) {
                Mail::to($parent->user->email)
                    ->send(new NouvelleNote($data));
            }
        }
    }

    public function envoyerRappelConnexion($users)
    {
        foreach ($users as $user) {
            if ($user->email && $user->actif) {
                $data = [
                    'user' => $user,
                    'derniere_connexion' => $user->last_login_at,
                    'loginUrl' => config('app.url') . '/login'
                ];

                Mail::to($user->email)->send(new RappelConnexion($data));
            }
        }
    }

    public function envoyerRecapitulatifHebdomadaire($parent)
    {
        $parent->load(['enfants.notes' => function($q) {
            $q->where('updated_at', '>=', now()->subWeek());
        }, 'enfants.bulletins' => function($q) {
            $q->where('created_at', '>=', now()->subWeek());
        }]);

        $data = [
            'parent' => $parent,
            'semaine' => [
                'debut' => now()->subWeek()->format('d/m/Y'),
                'fin' => now()->format('d/m/Y')
            ],
            'activites' => $this->compilerActivitesSemaine($parent)
        ];

        if (!empty($data['activites'])) {
            Mail::to($parent->user->email)
                ->send(new RecapitulatifHebdomadaire($data));
        }
    }

    private function compilerActivitesSemaine($parent)
    {
        $activites = [];

        foreach ($parent->enfants as $enfant) {
            $activitesEnfant = [
                'eleve' => $enfant,
                'nouvelles_notes' => $enfant->notes,
                'nouveaux_bulletins' => $enfant->bulletins
            ];

            if ($enfant->notes->count() > 0 || $enfant->bulletins->count() > 0) {
                $activites[] = $activitesEnfant;
            }
        }

        return $activites;
    }
}
