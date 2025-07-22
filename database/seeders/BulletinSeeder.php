<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bulletin;
use App\Models\Eleve;
use App\Models\Classe;
use App\Models\Periode;
use App\Models\Note;
use App\Models\AnneeScolaire;
use App\Models\Inscription;
use Illuminate\Support\Facades\DB;

class BulletinSeeder extends Seeder
{
    public function run()
    {
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();
        
        if (!$anneeCourante) {
            $this->command->error('Aucune année scolaire active trouvée');
            return;
        }

        // Récupérer toutes les périodes de l'année courante
        $periodes = Periode::where('annee_scolaire_id', $anneeCourante->id)
            ->orderBy('ordre')
            ->get();

        if ($periodes->isEmpty()) {
            $this->command->error('Aucune période trouvée pour l\'année scolaire courante');
            return;
        }

        // Récupérer toutes les classes de l'année courante
        $classes = Classe::where('annee_scolaire_id', $anneeCourante->id)->get();

        $this->command->info('Génération des bulletins pour ' . $classes->count() . ' classes et ' . $periodes->count() . ' périodes...');

        $observations = [
            'Élève sérieux et appliqué. Continue ainsi !',
            'Bon niveau général. Peut faire mieux en mathématiques.',
            'Excellente participation en classe. Très bon travail.',
            'Élève timide mais travailleur. Encouragez-le à participer davantage.',
            'Résultats satisfaisants. Doit maintenir ses efforts.',
            'Très bonne élève. Félicitations !',
            'Quelques difficultés en sciences. Un soutien est recommandé.',
            'Élève dynamique avec de bons résultats.',
            'Progression notable ce trimestre. Continuez !',
            'Excellent comportement et très bons résultats.',
            'Élève régulier dans son travail.',
            'Doit redoubler d\'efforts pour le prochain trimestre.',
            'Très à l\'écoute en classe. Bon travail !',
            'Résultats en baisse. Vigilance nécessaire.',
            'Élève méritant. Félicitations du conseil de classe.'
        ];

        foreach ($classes as $classe) {
            $this->command->info("Traitement de la classe : {$classe->nom}");
            
            // Récupérer les élèves inscrits dans cette classe
            $inscriptions = Inscription::where('classe_id', $classe->id)
                ->where('annee_scolaire_id', $anneeCourante->id)
                ->where('statut', 'en_cours')
                ->with('eleve')
                ->get();

            $effectifClasse = $inscriptions->count();

            if ($effectifClasse === 0) {
                $this->command->warn("Aucun élève inscrit dans la classe {$classe->nom}");
                continue;
            }

            foreach ($periodes as $periode) {
                $this->command->info("  - Période : {$periode->nom}");
                
                $bulletinsData = [];

                // Calculer les moyennes pour tous les élèves de la classe pour cette période
                foreach ($inscriptions as $inscription) {
                    $eleve = $inscription->eleve;
                    
                    // Récupérer toutes les notes de l'élève pour cette période
                    $notes = Note::where('eleve_id', $eleve->id)
                        ->where('periode_id', $periode->id)
                        ->whereNotNull('moyenne')
                        ->get();

                    if ($notes->isNotEmpty()) {
                        // Calculer la moyenne générale à partir des notes existantes
                        $moyenneGenerale = $notes->avg('moyenne');
                        $this->command->info("    → Élève {$eleve->user->prenom} {$eleve->user->nom}: moyenne calculée à partir de {$notes->count()} notes = {$moyenneGenerale}");
                    } else {
                        // Si pas de notes, créer une moyenne fictive pour le test
                        $moyenneGenerale = $this->genererMoyenneFictive();
                        $this->command->warn("    → Élève {$eleve->user->prenom} {$eleve->user->nom}: aucune note trouvée, moyenne fictive = {$moyenneGenerale}");
                    }

                    $bulletinsData[] = [
                        'eleve_id' => $eleve->id,
                        'eleve' => $eleve,
                        'moyenne_generale' => round($moyenneGenerale, 2)
                    ];
                }

                // Trier par moyenne décroissante pour calculer les rangs
                usort($bulletinsData, function($a, $b) {
                    return $b['moyenne_generale'] <=> $a['moyenne_generale'];
                });

                // Créer les bulletins avec les rangs
                foreach ($bulletinsData as $index => $bulletinData) {
                    $bulletin = new Bulletin();
                    $bulletin->eleve_id = $bulletinData['eleve_id'];
                    $bulletin->classe_id = $classe->id;
                    $bulletin->periode_id = $periode->id;
                    $bulletin->moyenne_generale = $bulletinData['moyenne_generale'];
                    $bulletin->rang = $index + 1;
                    $bulletin->effectif_classe = $effectifClasse;
                    $bulletin->observation_conseil = $observations[array_rand($observations)];
                    $bulletin->genere_le = now();
                    
                    // Générer la mention automatiquement
                    $bulletin->genererMention();
                    
                    $bulletin->save();
                }

                $this->command->info("    → {$effectifClasse} bulletins générés pour {$periode->nom}");
            }
        }

        $totalBulletins = Bulletin::count();
        $this->command->info("✅ Seeder terminé ! {$totalBulletins} bulletins générés au total.");

        // Afficher quelques statistiques
        $this->afficherStatistiques();
    }

    /**
     * Générer une moyenne fictive réaliste
     */
    private function genererMoyenneFictive(): float
    {
        // Générer des moyennes selon une distribution réaliste
        $rand = mt_rand(1, 100);
        
        if ($rand <= 5) {
            // 5% d'excellents élèves (17-20)
            return mt_rand(1700, 2000) / 100;
        } elseif ($rand <= 15) {
            // 10% de très bons élèves (15-16.99)
            return mt_rand(1500, 1699) / 100;
        } elseif ($rand <= 35) {
            // 20% de bons élèves (13-14.99)
            return mt_rand(1300, 1499) / 100;
        } elseif ($rand <= 60) {
            // 25% d'élèves moyens (11-12.99)
            return mt_rand(1100, 1299) / 100;
        } elseif ($rand <= 85) {
            // 25% d'élèves passables (10-10.99)
            return mt_rand(1000, 1099) / 100;
        } else {
            // 15% d'élèves en difficulté (5-9.99)
            return mt_rand(500, 999) / 100;
        }
    }

    /**
     * Afficher des statistiques sur les bulletins générés
     */
    private function afficherStatistiques()
    {
        $this->command->info("\n📊 STATISTIQUES DES BULLETINS GÉNÉRÉS :");
        
        $mentions = [
            'excellent' => 'Excellent',
            'tres_bien' => 'Très bien', 
            'bien' => 'Bien',
            'assez_bien' => 'Assez bien',
            'passable' => 'Passable',
            'insuffisant' => 'Insuffisant'
        ];

        foreach ($mentions as $mention => $label) {
            $count = Bulletin::where('mention', $mention)->count();
            $pourcentage = $count > 0 ? round(($count / Bulletin::count()) * 100, 1) : 0;
            $this->command->info("  {$label}: {$count} bulletins ({$pourcentage}%)");
        }

        $moyenneGenerale = round(Bulletin::avg('moyenne_generale'), 2);
        $this->command->info("\n📈 Moyenne générale de tous les bulletins: {$moyenneGenerale}/20");
        
        $meilleureNote = Bulletin::max('moyenne_generale');
        $moinsBonneNote = Bulletin::min('moyenne_generale');
        $this->command->info("📊 Note la plus haute: {$meilleureNote}/20");
        $this->command->info("📊 Note la plus basse: {$moinsBonneNote}/20");
    }
}
