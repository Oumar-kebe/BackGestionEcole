<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Récupérer tous les rôles
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'api')->first();
        $admin = Role::where('name', 'administrateur')->where('guard_name', 'api')->first();
        $director = Role::where('name', 'directeur')->where('guard_name', 'api')->first();
        $teacher = Role::where('name', 'enseignant')->where('guard_name', 'api')->first();
        $mainTeacher = Role::where('name', 'professeur-principal')->where('guard_name', 'api')->first();
        $supervisor = Role::where('name', 'surveillant')->where('guard_name', 'api')->first();
        $parent = Role::where('name', 'parent')->where('guard_name', 'api')->first();
        $student = Role::where('name', 'eleve')->where('guard_name', 'api')->first();
        $secretary = Role::where('name', 'secretaire')->where('guard_name', 'api')->first();
        $accountant = Role::where('name', 'comptable')->where('guard_name', 'api')->first();

        // SUPER ADMIN - Toutes les permissions
        if ($superAdmin) {
            $allPermissions = Permission::where('guard_name', 'api')->pluck('name')->toArray();
            $superAdmin->syncPermissions($allPermissions);
            $this->command->info('Super Admin: ' . count($allPermissions) . ' permissions assignées');
        }

        // ADMINISTRATEUR - Permissions administratives complètes
        if ($admin) {
            $adminPermissions = [
                // Gestion des utilisateurs
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.toggle-status', 'users.reset-password',
                
                // Gestion des années scolaires
                'annees-scolaires.view', 'annees-scolaires.create', 'annees-scolaires.edit', 'annees-scolaires.delete', 
                'annees-scolaires.set-current', 'annees-scolaires.statistics',
                
                // Gestion des niveaux
                'niveaux.view', 'niveaux.create', 'niveaux.edit', 'niveaux.delete', 'niveaux.view-subjects', 'niveaux.view-classes',
                
                // Gestion des classes
                'classes.view', 'classes.create', 'classes.edit', 'classes.delete', 'classes.view-students', 
                'classes.view-teachers', 'classes.assign-teacher', 'classes.remove-teacher',
                
                // Gestion des matières
                'matieres.view', 'matieres.create', 'matieres.edit', 'matieres.delete', 'matieres.toggle-status', 
                'matieres.view-teachers', 'matieres.assign-teacher',
                
                // Gestion des élèves
                'eleves.view', 'eleves.create', 'eleves.edit', 'eleves.delete', 'eleves.change-class', 
                'eleves.view-documents', 'eleves.upload-documents', 'eleves.view-grades', 'eleves.view-bulletins',
                
                // Gestion des parents
                'parents.view', 'parents.edit', 'parents.view-children', 'parents.add-child', 'parents.remove-child',
                
                // Gestion des enseignants
                'enseignants.view', 'enseignants.create', 'enseignants.edit', 'enseignants.delete', 
                'enseignants.assign-subject', 'enseignants.remove-subject', 'enseignants.assign-class',
                
                // Gestion des inscriptions
                'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.delete', 
                'inscriptions.complete', 'inscriptions.statistics',
                
                // Gestion des notes
                'notes.view', 'notes.create', 'notes.edit', 'notes.delete', 'notes.view-by-class', 
                'notes.bulk-entry', 'notes.statistics',
                
                // Gestion des bulletins
                'bulletins.view', 'bulletins.generate', 'bulletins.edit', 'bulletins.delete', 
                'bulletins.download', 'bulletins.bulk-download', 'bulletins.add-observation',
                
                // Gestion des périodes
                'periodes.view', 'periodes.create', 'periodes.edit', 'periodes.delete', 'periodes.set-current', 'periodes.statistics',
                
                // Gestion des documents
                'documents.view', 'documents.create', 'documents.edit', 'documents.delete', 'documents.download', 'documents.upload',
                
                // Dashboard
                'dashboard.view-admin', 'dashboard.statistics-general',
            ];
            
            $admin->syncPermissions($adminPermissions);
            $this->command->info('Administrateur: ' . count($adminPermissions) . ' permissions assignées');
        }

        // DIRECTEUR - Permissions de supervision et gestion
        if ($director) {
            $directorPermissions = [
                // Consultation générale
                'users.view', 'annees-scolaires.view', 'niveaux.view', 'classes.view', 'matieres.view',
                'eleves.view', 'parents.view', 'enseignants.view', 'inscriptions.view',
                
                // Gestion des notes et bulletins
                'notes.view', 'notes.statistics', 'bulletins.view', 'bulletins.generate', 'bulletins.add-observation',
                'bulletins.download', 'bulletins.bulk-download',
                
                // Gestion des périodes
                'periodes.view', 'periodes.edit', 'periodes.set-current', 'periodes.statistics',
                
                // Documents
                'documents.view', 'documents.download',
                
                // Dashboard
                'dashboard.view-admin', 'dashboard.statistics-general',
            ];
            
            $director->syncPermissions($directorPermissions);
            $this->command->info('Directeur: ' . count($directorPermissions) . ' permissions assignées');
        }

        // ENSEIGNANT - Permissions pour l'enseignement
        if ($teacher) {
            $teacherPermissions = [
                // Consultation des données de base
                'classes.view', 'classes.view-students', 'matieres.view', 'eleves.view', 'eleves.view-grades',
                
                // Gestion des notes dans ses classes
                'notes.view', 'notes.create', 'notes.edit', 'notes.view-by-class', 'notes.bulk-entry', 'notes.statistics',
                
                // Consultation des bulletins
                'bulletins.view',
                
                // Dashboard enseignant
                'dashboard.view-teacher', 'dashboard.statistics-teacher',
                
                // Documents
                'documents.view', 'documents.download',
            ];
            
            $teacher->syncPermissions($teacherPermissions);
            $this->command->info('Enseignant: ' . count($teacherPermissions) . ' permissions assignées');
        }

        // PROFESSEUR PRINCIPAL - Permissions étendues d'enseignant
        if ($mainTeacher) {
            $mainTeacherPermissions = [
                // Toutes les permissions d'enseignant
                'classes.view', 'classes.view-students', 'matieres.view', 'eleves.view', 'eleves.view-grades',
                'notes.view', 'notes.create', 'notes.edit', 'notes.view-by-class', 'notes.bulk-entry', 'notes.statistics',
                'bulletins.view', 'dashboard.view-teacher', 'dashboard.statistics-teacher', 'documents.view', 'documents.download',
                
                // Permissions supplémentaires pour professeur principal
                'eleves.edit', 'eleves.view-documents', 'eleves.upload-documents',
                'bulletins.generate', 'bulletins.add-observation', 'bulletins.download',
                'parents.view', 'parents.view-children',
            ];
            
            $mainTeacher->syncPermissions($mainTeacherPermissions);
            $this->command->info('Professeur Principal: ' . count($mainTeacherPermissions) . ' permissions assignées');
        }

        // SURVEILLANT - Permissions de surveillance
        if ($supervisor) {
            $supervisorPermissions = [
                'classes.view', 'classes.view-students', 'eleves.view',
                'documents.view', 'documents.download',
                'dashboard.view-teacher',
            ];
            
            $supervisor->syncPermissions($supervisorPermissions);
            $this->command->info('Surveillant: ' . count($supervisorPermissions) . ' permissions assignées');
        }

        // PARENT - Permissions pour voir les données de ses enfants
        if ($parent) {
            $parentPermissions = [
                // Consultation des données de ses enfants
                'notes.view-own', 'bulletins.view-own', 'bulletins.view-children', 'bulletins.download',
                'eleves.view-grades', 'eleves.view-bulletins',
                
                // Dashboard parent
                'dashboard.view-parent', 'dashboard.statistics-parent',
                
                // Gestion de son profil
                'parents.view-children',
                
                // Documents de ses enfants
                'documents.view', 'documents.download',
            ];
            
            $parent->syncPermissions($parentPermissions);
            $this->command->info('Parent: ' . count($parentPermissions) . ' permissions assignées');
        }

        // ÉLÈVE - Permissions pour voir ses propres données
        if ($student) {
            $studentPermissions = [
                // Consultation de ses propres données
                'notes.view-own', 'bulletins.view-own', 'bulletins.download',
                
                // Dashboard élève
                'dashboard.view-student', 'dashboard.statistics-student',
                
                // Ses propres documents
                'documents.view', 'documents.download',
            ];
            
            $student->syncPermissions($studentPermissions);
            $this->command->info('Élève: ' . count($studentPermissions) . ' permissions assignées');
        }

        // SECRÉTAIRE - Permissions administratives limitées
        if ($secretary) {
            $secretaryPermissions = [
                // Gestion des élèves et parents
                'eleves.view', 'eleves.create', 'eleves.edit', 'eleves.view-documents', 'eleves.upload-documents',
                'parents.view', 'parents.edit', 'parents.view-children', 'parents.add-child', 'parents.remove-child',
                
                // Gestion des inscriptions
                'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.complete',
                
                // Consultation des autres données
                'classes.view', 'classes.view-students', 'enseignants.view', 'matieres.view',
                
                // Documents
                'documents.view', 'documents.create', 'documents.edit', 'documents.upload', 'documents.download',
                
                // Dashboard
                'dashboard.view-admin',
            ];
            
            $secretary->syncPermissions($secretaryPermissions);
            $this->command->info('Secrétaire: ' . count($secretaryPermissions) . ' permissions assignées');
        }

        // COMPTABLE - Permissions financières (pour extension future)
        if ($accountant) {
            $accountantPermissions = [
                // Consultation des données
                'eleves.view', 'parents.view', 'classes.view', 'inscriptions.view', 'inscriptions.statistics',
                
                // Documents
                'documents.view', 'documents.download',
                
                // Dashboard
                'dashboard.view-admin',
            ];
            
            $accountant->syncPermissions($accountantPermissions);
            $this->command->info('Comptable: ' . count($accountantPermissions) . ' permissions assignées');
        }

        $this->command->info('Attribution des permissions aux rôles terminée avec succès!');
    }
}
