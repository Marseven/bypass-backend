<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ──────────────────────────────────────────
        $permissions = [
            // Requests
            'requests.create',
            'requests.view.own',
            'requests.view.all',
            'requests.update.own',
            'requests.delete.own',
            'requests.validate.level1',
            'requests.validate.level2',

            // Bypass lifecycle (CDC)
            'bypass.create.process',
            'bypass.create.securite',
            'bypass.activate',
            'bypass.close',
            'bypass.approve.short_term',
            'bypass.approve.long_term',
            'bypass.approve.security',

            // ORA (CDC)
            'ora.validate',

            // MOC (CDC)
            'moc.trigger',

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Equipment
            'equipment.view',
            'equipment.create',
            'equipment.update',
            'equipment.delete',

            // Zones
            'zones.view',
            'zones.create',
            'zones.update',
            'zones.delete',

            // Sensors
            'sensors.view',
            'sensors.create',
            'sensors.update',
            'sensors.delete',

            // System
            'system.settings.manage',
            'history.view',
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── CDC Roles (8) ────────────────────────────────────────

        // Opérateur : consultation
        $operateur = Role::firstOrCreate(['name' => 'operateur']);
        $operateur->syncPermissions([
            'dashboard.view',
            'requests.view.own',
        ]);

        // Technicien : création bypass process
        $technicien = Role::firstOrCreate(['name' => 'technicien']);
        $technicien->syncPermissions([
            'dashboard.view',
            'requests.view.own',
            'requests.create',
            'requests.update.own',
            'requests.delete.own',
            'bypass.create.process',
        ]);

        // Instrumentiste : création bypass sécurité, activation/fermeture
        $instrumentiste = Role::firstOrCreate(['name' => 'instrumentiste']);
        $instrumentiste->syncPermissions([
            'dashboard.view',
            'requests.view.own',
            'requests.create',
            'requests.update.own',
            'requests.delete.own',
            'bypass.create.process',
            'bypass.create.securite',
            'bypass.activate',
            'bypass.close',
        ]);

        // Chef de quart : approbation court terme
        $chefDeQuart = Role::firstOrCreate(['name' => 'chef_de_quart']);
        $chefDeQuart->syncPermissions([
            'dashboard.view',
            'requests.view.own',
            'requests.view.all',
            'requests.create',
            'requests.update.own',
            'requests.delete.own',
            'requests.validate.level1',
            'bypass.approve.short_term',
            'equipment.view',
            'zones.view',
            'sensors.view',
        ]);

        // Responsable HSE : validation ORA, approbation sécurité
        $responsableHse = Role::firstOrCreate(['name' => 'responsable_hse']);
        $responsableHse->syncPermissions([
            'dashboard.view',
            'requests.view.own',
            'requests.view.all',
            'requests.validate.level1',
            'ora.validate',
            'bypass.approve.security',
            'equipment.view',
            'zones.view',
            'sensors.view',
        ]);

        // Resp exploitation : approbation long terme
        $respExploitation = Role::firstOrCreate(['name' => 'resp_exploitation']);
        $respExploitation->syncPermissions([
            'dashboard.view',
            'requests.view.own',
            'requests.view.all',
            'requests.create',
            'requests.update.own',
            'requests.validate.level1',
            'requests.validate.level2',
            'bypass.approve.long_term',
            'equipment.view',
            'equipment.create',
            'equipment.update',
            'equipment.delete',
            'zones.view',
            'zones.create',
            'zones.update',
            'zones.delete',
            'sensors.view',
        ]);

        // Directeur : approbation sécurité, MOC
        $directeur = Role::firstOrCreate(['name' => 'directeur']);
        $directeur->syncPermissions([
            'dashboard.view',
            'requests.view.own',
            'requests.view.all',
            'requests.create',
            'requests.update.own',
            'requests.delete.own',
            'requests.validate.level1',
            'requests.validate.level2',
            'bypass.approve.security',
            'moc.trigger',
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
        ]);

        // Administrateur : all permissions
        $administrateur = Role::firstOrCreate(['name' => 'administrateur']);
        $administrateur->syncPermissions(Permission::all());

        // ── Legacy roles (keep for backward compatibility) ──────
        $legacyUser = Role::firstOrCreate(['name' => 'user']);
        $legacyUser->syncPermissions([
            'requests.create',
            'requests.view.own',
            'requests.update.own',
            'requests.delete.own',
            'dashboard.view',
        ]);

        $legacySupervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $legacySupervisor->syncPermissions([
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

        $legacyDirector = Role::firstOrCreate(['name' => 'director']);
        $legacyDirector->syncPermissions([
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

        $legacyAdmin = Role::firstOrCreate(['name' => 'administrator']);
        $legacyAdmin->syncPermissions(Permission::all());
    }
}
