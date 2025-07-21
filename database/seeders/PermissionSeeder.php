<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions pour la gestion des utilisateurs
        $userPermissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.restore',
            'users.force-delete',
            'users.toggle-status',
            'users.reset-password',
        ];

        // Permissions pour la gestion des années scolaires
        $yearPermissions = [
            'annees-scolaires.view',
            'annees-scolaires.create',
            'annees-scolaires.edit',
            'annees-scolaires.delete',
            'annees-scolaires.set-current',
            'annees-scolaires.statistics',
        ];

        // Permissions pour la gestion des niveaux
        $levelPermissions = [
            'niveaux.view',
            'niveaux.create',
            'niveaux.edit',
            'niveaux.delete',
            'niveaux.view-subjects',
            'niveaux.view-classes',
        ];

        // Permissions pour la gestion des classes
        $classPermissions = [
            'classes.view',
            'classes.create',
            'classes.edit',
            'classes.delete',
            'classes.view-students',
            'classes.view-teachers',
            'classes.assign-teacher',
            'classes.remove-teacher',
        ];

        // Permissions pour la gestion des matières
        $subjectPermissions = [
            'matieres.view',
            'matieres.create',
            'matieres.edit',
            'matieres.delete',
            'matieres.toggle-status',
            'matieres.view-teachers',
            'matieres.assign-teacher',
        ];

        // Permissions pour la gestion des élèves
        $studentPermissions = [
            'eleves.view',
            'eleves.create',
            'eleves.edit',
            'eleves.delete',
            'eleves.change-class',
            'eleves.view-documents',
            'eleves.upload-documents',
            'eleves.view-grades',
            'eleves.view-bulletins',
        ];

        // Permissions pour la gestion des parents
        $parentPermissions = [
            'parents.view',
            'parents.edit',
            'parents.view-children',
            'parents.add-child',
            'parents.remove-child',
        ];

        // Permissions pour la gestion des enseignants
        $teacherPermissions = [
            'enseignants.view',
            'enseignants.create',
            'enseignants.edit',
            'enseignants.delete',
            'enseignants.assign-subject',
            'enseignants.remove-subject',
            'enseignants.assign-class',
        ];

        // Permissions pour la gestion des inscriptions
        $enrollmentPermissions = [
            'inscriptions.view',
            'inscriptions.create',
            'inscriptions.edit',
            'inscriptions.delete',
            'inscriptions.complete',
            'inscriptions.statistics',
        ];

        // Permissions pour la gestion des notes
        $gradePermissions = [
            'notes.view',
            'notes.create',
            'notes.edit',
            'notes.delete',
            'notes.view-by-class',
            'notes.bulk-entry',
            'notes.statistics',
            'notes.view-own', // Pour les élèves voir leurs propres notes
        ];

        // Permissions pour la gestion des bulletins
        $bulletinPermissions = [
            'bulletins.view',
            'bulletins.generate',
            'bulletins.edit',
            'bulletins.delete',
            'bulletins.download',
            'bulletins.bulk-download',
            'bulletins.add-observation',
            'bulletins.view-own', // Pour les élèves voir leurs propres bulletins
            'bulletins.view-children', // Pour les parents voir les bulletins de leurs enfants
        ];

        // Permissions pour la gestion des périodes
        $periodPermissions = [
            'periodes.view',
            'periodes.create',
            'periodes.edit',
            'periodes.delete',
            'periodes.set-current',
            'periodes.statistics',
        ];

        // Permissions pour la gestion des documents
        $documentPermissions = [
            'documents.view',
            'documents.create',
            'documents.edit',
            'documents.delete',
            'documents.download',
            'documents.upload',
        ];

        // Permissions pour le tableau de bord
        $dashboardPermissions = [
            'dashboard.view-admin',
            'dashboard.view-teacher',
            'dashboard.view-student',
            'dashboard.view-parent',
            'dashboard.statistics-general',
            'dashboard.statistics-teacher',
            'dashboard.statistics-student',
            'dashboard.statistics-parent',
        ];

        // Permissions système
        $systemPermissions = [
            'system.backup',
            'system.restore',
            'system.maintenance',
            'system.logs',
            'system.settings',
        ];

        // Combiner toutes les permissions
        $allPermissions = array_merge(
            $userPermissions,
            $yearPermissions,
            $levelPermissions,
            $classPermissions,
            $subjectPermissions,
            $studentPermissions,
            $parentPermissions,
            $teacherPermissions,
            $enrollmentPermissions,
            $gradePermissions,
            $bulletinPermissions,
            $periodPermissions,
            $documentPermissions,
            $dashboardPermissions,
            $systemPermissions
        );

        // Créer toutes les permissions
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }

        $this->command->info('Permissions créées avec succès!');
        $this->command->info('Total: ' . count($allPermissions) . ' permissions');
    }
}
