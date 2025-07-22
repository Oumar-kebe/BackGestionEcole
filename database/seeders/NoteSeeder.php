<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Note;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\Periode;
use App\Models\Enseignant;
use App\Models\Inscription;
use App\Models\AnneeScolaire;
use App\Models\Classe;

class NoteSeeder extends Seeder
{
    public function run()
    {
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();
        
        if (!$anneeCourante) {
            $this->command->error('Aucune année scolaire active trouvée');
            return;
        }

        // Récupérer toutes les données nécessaires
        $periodes = Periode::where('annee_scolaire_id', $anneeCourante->id)->get();
        $matieres = Matiere::where('actif', true)->get();
        $enseignants = Enseignant::with('user')->get();
        $classes = Classe::where('annee_scolaire_id', $anneeCourante->id)->get();

        if ($periodes->isEmpty() || $matieres->isEmpty() || $enseignants->isEmpty()) {
            $this->command->error('Données manquantes : périodes, matières ou enseignants');
            return;
        }

        $this->command->info('Génération des notes pour ' . $classes->count() . ' classes...');

        foreach ($classes as $classe) {
            $this->command->info("Traitement de la classe : {$classe->nom}");
            
            // Récupérer les élèves de cette classe
            $inscriptions = Inscription::where('classe_id', $classe->id)
                ->where('annee_scolaire_id', $anneeCourante->id)
                ->where('statut', 'en_cours')
                ->with('eleve')
                ->get();

            if ($inscriptions->isEmpty()) {
                $this->command->warn("Aucun élève dans la classe {$classe->nom}");
                continue;
            }

            // Pour chaque matière
            foreach ($matieres as $matiere) {
                // Sélectionner un enseignant au hasard pour cette matière
                $enseignant = $enseignants->random();
                
                // Pour chaque période
                foreach ($periodes as $periode) {
                    // Skip les périodes futures
                    if ($periode->date_debut > now()) {
                        continue;
                    }

                    // Pour chaque élève
                    foreach ($inscriptions as $inscription) {
                        $eleve = $inscription->eleve;
                        
                        // Vérifier si la note existe déjà
                        $noteExistante = Note::where('eleve_id', $eleve->id)
                            ->where('matiere_id', $matiere->id)
                            ->where('periode_id', $periode->id)
                            ->first();

                        if ($noteExistante) {
                            continue; // Skip si la note existe déjà
                        }

                        // Générer les notes de façon réaliste
                        $noteData = $this->genererNotesRealistes();

                        Note::create([
                            'eleve_id' => $eleve->id,
                            'matiere_id' => $matiere->id,
                            'periode_id' => $periode->id,
                            'enseignant_id' => $enseignant->id,
                            'note_devoir1' => $noteData['devoir1'],
                            'note_devoir2' => $noteData['devoir2'],
                            'note_composition' => $noteData['composition'],
                            // La moyenne sera calculée automatiquement par le modèle
                        ]);
                    }
                }
            }
        }

        $totalNotes = Note::count();
        $this->command->info("✅ Seeder des notes terminé ! {$totalNotes} notes générées au total.");
        
        // Afficher quelques statistiques
        $this->afficherStatistiquesNotes();
    }

    /**
     * Générer des notes réalistes pour un élève
     */
    private function genererNotesRealistes(): array
    {
        // Définir le niveau de l'élève (influencera toutes ses notes)
        $niveauEleve = mt_rand(1, 100);
        
        if ($niveauEleve <= 5) {
            // Élève excellent (5%)
            $baseMin = 16;
            $baseMax = 20;
        } elseif ($niveauEleve <= 15) {
            // Très bon élève (10%)
            $baseMin = 14;
            $baseMax = 18;
        } elseif ($niveauEleve <= 35) {
            // Bon élève (20%)
            $baseMin = 12;
            $baseMax = 16;
        } elseif ($niveauEleve <= 60) {
            // Élève moyen (25%)
            $baseMin = 10;
            $baseMax = 14;
        } elseif ($niveauEleve <= 85) {
            // Élève faible (25%)
            $baseMin = 8;
            $baseMax = 12;
        } else {
            // Élève en grande difficulté (15%)
            $baseMin = 4;
            $baseMax = 10;
        }

        // Générer les notes avec une petite variation
        $devoir1 = $this->genererNote($baseMin, $baseMax, 1.5); // Plus de variation pour les devoirs
        $devoir2 = $this->genererNote($baseMin, $baseMax, 1.5);
        $composition = $this->genererNote($baseMin, $baseMax, 1.0); // Moins de variation pour la composition
        
        return [
            'devoir1' => $devoir1,
            'devoir2' => $devoir2,
            'composition' => $composition
        ];
    }

    /**
     * Générer une note dans une plage avec variation
     */
    private function genererNote(float $min, float $max, float $variation = 1.0): float
    {
        $base = mt_rand($min * 100, $max * 100) / 100;
        $variationValue = (mt_rand(-100, 100) / 100) * $variation;
        $note = $base + $variationValue;
        
        // S'assurer que la note reste dans la plage 0-20
        return max(0, min(20, round($note, 2)));
    }

    /**
     * Afficher des statistiques sur les notes générées
     */
    private function afficherStatistiquesNotes()
    {
        $this->command->info("\n📊 STATISTIQUES DES NOTES GÉNÉRÉES :");
        
        $moyenneGenerale = round(Note::avg('moyenne'), 2);
        $this->command->info("📈 Moyenne générale de toutes les notes: {$moyenneGenerale}/20");
        
        $meilleureNote = Note::max('moyenne');
        $moinsBonneNote = Note::min('moyenne');
        $this->command->info("📊 Meilleure moyenne: {$meilleureNote}/20");
        $this->command->info("📊 Moins bonne moyenne: {$moinsBonneNote}/20");
        
        // Répartition par appréciation
        $appreciations = [
            'Excellent' => Note::where('appreciation', 'Excellent')->count(),
            'Très bien' => Note::where('appreciation', 'Très bien')->count(),
            'Bien' => Note::where('appreciation', 'Bien')->count(),
            'Assez bien' => Note::where('appreciation', 'Assez bien')->count(),
            'Passable' => Note::where('appreciation', 'Passable')->count(),
            'Insuffisant' => Note::where('appreciation', 'Insuffisant')->count(),
        ];

        $totalNotes = Note::count();
        $this->command->info("\n📊 RÉPARTITION PAR APPRÉCIATION :");
        foreach ($appreciations as $appreciation => $count) {
            $pourcentage = $count > 0 ? round(($count / $totalNotes) * 100, 1) : 0;
            $this->command->info("  {$appreciation}: {$count} notes ({$pourcentage}%)");
        }
    }
}
