<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser les rôles et permissions en cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            // Permissions pour les demandes
            'requests.create',
            'requests.view.own',
            'requests.view.all',
            'requests.update.own',
            'requests.delete.own',
            'requests.validate.level1',
            'requests.validate.level2',
            
            // Permissions pour les utilisateurs
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            
            // Permissions pour les équipements
            'equipment.view',
            'equipment.create',
            'equipment.update',
            'equipment.delete',
            
            // Permissions pour les zones
            'zones.view',
            'zones.create',
            'zones.update',
            'zones.delete',
            
            // Permissions pour les capteurs
            'sensors.view',
            'sensors.create',
            'sensors.update',
            'sensors.delete',
            
            // Permissions système
            'system.settings.manage',
            'history.view',
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions
        
        // Rôle: User (opérateur)
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo([
            'requests.create',
            'requests.view.own',
            'requests.update.own',
            'requests.delete.own',
            'dashboard.view',
        ]);

        // Rôle: Supervisor (superviseur - validation niveau 1)
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor']);
        $supervisorRole->givePermissionTo([
            'requests.create',
            'requests.view.own',
            'requests.view.all',
            'requests.update.own',
            'requests.delete.own',
            'requests.validate.level1',
            'equipment.view',
            'zones.view',
            'sensors.view',
            'dashboard.view',
        ]);

        // Rôle: Director (directeur - validation niveau 2)
        $directorRole = Role::firstOrCreate(['name' => 'director']);
        $directorRole->givePermissionTo([
            'requests.create',
            'requests.view.own',
            'requests.view.all',
            'requests.update.own',
            'requests.delete.own',
            'requests.validate.level1',
            'requests.validate.level2',
            'equipment.view',
            'equipment.create',
            'equipment.update',
            'equipment.delete',
            'zones.view',
            'zones.create',
            'zones.update',
            'zones.delete',
            'sensors.view',
            'sensors.create',
            'sensors.update',
            'sensors.delete',
            'dashboard.view',
        ]);

        // Rôle: Administrator (administrateur - tous les droits + validation niveau 2)
        $adminRole = Role::firstOrCreate(['name' => 'administrator']);
        $adminRole->givePermissionTo(Permission::all());
    }
}
