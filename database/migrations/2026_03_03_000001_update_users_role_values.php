<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate old role values to new CDC roles
        DB::table('users')->where('role', 'user')->update(['role' => 'operateur']);
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'chef_de_quart']);
        DB::table('users')->where('role', 'director')->update(['role' => 'directeur']);
        DB::table('users')->where('role', 'administrator')->update(['role' => 'administrateur']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'operateur')->update(['role' => 'user']);
        DB::table('users')->where('role', 'chef_de_quart')->update(['role' => 'supervisor']);
        DB::table('users')->where('role', 'directeur')->update(['role' => 'director']);
        DB::table('users')->where('role', 'administrateur')->update(['role' => 'administrator']);
    }
};
