<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParentEleve;
use App\Services\EmailService;

class EnvoyerRecapitulatifHebdomadaire extends Command
{
    protected $signature = 'email:recapitulatif-hebdomadaire';
    protected $description = 'Envoyer le récapitulatif hebdomadaire aux parents';

    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    public function handle()
    {
        $this->info('Début de l\'envoi des récapitulatifs hebdomadaires...');

        $parents = ParentEleve::whereHas('user', function($q) {
            $q->where('actif', true);
        })->get();

        $compteur = 0;

        foreach ($parents as $parent) {
            try {
                $this->emailService->envoyerRecapitulatifHebdomadaire($parent);
                $compteur++;
                $this->info("Email envoyé à : {$parent->user->email}");
            } catch (\Exception $e) {
                $this->error("Erreur pour {$parent->user->email} : {$e->getMessage()}");
            }
        }

        $this->info("Terminé ! {$compteur} emails envoyés.");
    }
}
