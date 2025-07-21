<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TestPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('=== TEST DES PERMISSIONS ===');
        
        // Tester le Super Admin
        $superAdmin = User::where('email', 'superadmin@gestionecole.com')->first();
        if ($superAdmin) {
            $this->command->info("Super Admin ({$superAdmin->email}):");
            $this->command->info("- Rôles: " . $superAdmin->roles->pluck('name')->implode(', '));
            $this->command->info("- Permissions: " . $superAdmin->getAllPermissions()->count() . " permissions");
            $this->command->info("- Peut créer utilisateurs: " . ($superAdmin->can('users.create') ? 'OUI' : 'NON'));
            $this->command->info("- Peut voir dashboard admin: " . ($superAdmin->can('dashboard.view-admin') ? 'OUI' : 'NON'));
        }
        
        // Tester l'Administrateur
        $admin = User::where('email', 'admin@ecole.com')->first();
        if ($admin) {
            $this->command->info("\nAdministrateur ({$admin->email}):");
            $this->command->info("- Rôles: " . $admin->roles->pluck('name')->implode(', '));
            $this->command->info("- Permissions: " . $admin->getAllPermissions()->count() . " permissions");
            $this->command->info("- Peut créer utilisateurs: " . ($admin->can('users.create') ? 'OUI' : 'NON'));
            $this->command->info("- Peut voir dashboard admin: " . ($admin->can('dashboard.view-admin') ? 'OUI' : 'NON'));
        }
        
        // Tester l'Enseignant
        $teacher = User::where('email', 'prof@ecole.com')->first();
        if ($teacher) {
            $this->command->info("\nEnseignant ({$teacher->email}):");
            $this->command->info("- Rôles: " . $teacher->roles->pluck('name')->implode(', '));
            $this->command->info("- Permissions: " . $teacher->getAllPermissions()->count() . " permissions");
            $this->command->info("- Peut créer notes: " . ($teacher->can('notes.create') ? 'OUI' : 'NON'));
            $this->command->info("- Peut voir dashboard enseignant: " . ($teacher->can('dashboard.view-teacher') ? 'OUI' : 'NON'));
            $this->command->info("- Peut créer utilisateurs: " . ($teacher->can('users.create') ? 'OUI' : 'NON'));
        }
        
        $this->command->info("\n=== STATISTIQUES GLOBALES ===");
        $this->command->info("Total permissions: " . Permission::count());
        $this->command->info("Total rôles: " . Role::count());
        $this->command->info("Total utilisateurs: " . User::count());
        
        $this->command->info("\n=== RÔLES ET LEURS PERMISSIONS ===");
        $roles = Role::with('permissions')->get();
        foreach ($roles as $role) {
            $this->command->info("{$role->name}: {$role->permissions->count()} permissions");
        }
    }
}
