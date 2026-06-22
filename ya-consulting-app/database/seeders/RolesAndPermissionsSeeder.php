<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permissions ─────────────────────────────────────────
        $permissions = [
            // Projets
            'view projects',   'create projects', 'edit projects',   'delete projects',
            // Dépenses
            'view expenses',   'create expenses', 'edit expenses',   'delete expenses',
            // Clients
            'view clients',    'create clients',  'edit clients',    'delete clients',
            // Rapports
            'generate reports', 'view reports',
            // Utilisateurs
            'manage users',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ─── Rôles ───────────────────────────────────────────────

        // Administrateur — accès total
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // Chef de projet
        $chef = Role::firstOrCreate(['name' => 'chef_projet']);
        $chef->syncPermissions([
            'view projects', 'create projects', 'edit projects',
            'view expenses', 'create expenses', 'edit expenses', 'delete expenses',
            'view clients',
            'view reports', 'generate reports',
        ]);

        // Collaborateur — lecture seule
        $collab = Role::firstOrCreate(['name' => 'collaborateur']);
        $collab->syncPermissions([
            'view projects',
            'view expenses',
            'view clients',
        ]);

        $adminUser = User::updateOrCreate(
            ['email' => 'courriel@ya-consulting.com'],
            [
                'name'     => 'Administrateur YA',
                'password' => Hash::make('Admin@2024'),
            ]
        );
        $adminUser->assignRole('admin');

        $this->command->info('Compte Admin créé avec succès.');
        $this->command->info('   Email : courriel@ya-consulting.com');
        $this->command->info('   Mot de passe : Admin@2024');
    }
}
