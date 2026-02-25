<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // First expand the ENUM to include all new roles
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user', 'supervisor', 'administrator', 'director', 'operateur', 'chef_de_quart', 'directeur', 'administrateur', 'responsable_hse', 'resp_exploitation', 'technicien', 'instrumentiste') DEFAULT 'operateur'");
        }

        // Migrate old role values to new CDC roles
        DB::table('users')->where('role', 'user')->update(['role' => 'operateur']);
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'chef_de_quart']);
        DB::table('users')->where('role', 'director')->update(['role' => 'directeur']);
        DB::table('users')->where('role', 'administrator')->update(['role' => 'administrateur']);

        if (DB::getDriverName() !== 'sqlite') {
            // Now remove old values from ENUM
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('operateur', 'chef_de_quart', 'directeur', 'administrateur', 'responsable_hse', 'resp_exploitation', 'technicien', 'instrumentiste') DEFAULT 'operateur'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user', 'supervisor', 'administrator', 'director', 'operateur', 'chef_de_quart', 'directeur', 'administrateur', 'responsable_hse', 'resp_exploitation', 'technicien', 'instrumentiste') DEFAULT 'user'");
        }

        DB::table('users')->where('role', 'operateur')->update(['role' => 'user']);
        DB::table('users')->where('role', 'chef_de_quart')->update(['role' => 'supervisor']);
        DB::table('users')->where('role', 'directeur')->update(['role' => 'director']);
        DB::table('users')->where('role', 'administrateur')->update(['role' => 'administrator']);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user', 'supervisor', 'administrator', 'director') DEFAULT 'user'");
        }
    }
};
