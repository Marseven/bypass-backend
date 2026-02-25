<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'email' => 'admin@bypassguard.com',
            'password' => Hash::make('password123'),
            'full_name' => 'Administrateur Système',
            'role' => 'administrator',
        ]);

        User::create([
            'username' => 'jdupont',
            'email' => 'jean.dupont@bypassguard.com',
            'password' => Hash::make('password123'),
            'full_name' => 'Jean Dupont',
            'role' => 'supervisor',
        ]);

        User::create([
            'username' => 'mmartin',
            'email' => 'marie.martin@bypassguard.com',
            'password' => Hash::make('password123'),
            'full_name' => 'Marie Martin',
            'role' => 'user',
        ]);
    }
}
