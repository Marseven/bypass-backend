<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Comilog@2026!');

        $users = [
            [
                'username' => 'admin.comilog',
                'email' => 'admin@comilog.com',
                'full_name' => 'Administrateur Systeme COMILOG',
                'role' => 'administrateur',
                'phone' => '+241 77 00 00 01',
                'spatie_role' => 'administrateur',
            ],
            [
                'username' => 'j.moussavou',
                'email' => 'jean.moussavou@comilog.com',
                'full_name' => 'Jean-Baptiste Moussavou',
                'role' => 'operateur',
                'phone' => '+241 77 10 10 01',
                'spatie_role' => 'operateur',
            ],
            [
                'username' => 'p.ndong',
                'email' => 'patrick.ndong@comilog.com',
                'full_name' => 'Patrick Ndong Essono',
                'role' => 'technicien',
                'phone' => '+241 77 20 20 02',
                'spatie_role' => 'technicien',
            ],
            [
                'username' => 'a.obame',
                'email' => 'alain.obame@comilog.com',
                'full_name' => 'Alain Obame Nguema',
                'role' => 'instrumentiste',
                'phone' => '+241 77 30 30 03',
                'spatie_role' => 'instrumentiste',
            ],
            [
                'username' => 'm.mbadinga',
                'email' => 'marcel.mbadinga@comilog.com',
                'full_name' => 'Marcel Mbadinga Ondo',
                'role' => 'chef_de_quart',
                'phone' => '+241 77 40 40 04',
                'spatie_role' => 'chef_de_quart',
            ],
            [
                'username' => 's.nzoghe',
                'email' => 'sylvie.nzoghe@comilog.com',
                'full_name' => 'Sylvie Nzoghe Mba',
                'role' => 'responsable_hse',
                'phone' => '+241 77 50 50 05',
                'spatie_role' => 'responsable_hse',
            ],
            [
                'username' => 'r.edzang',
                'email' => 'roger.edzang@comilog.com',
                'full_name' => 'Roger Edzang Essono',
                'role' => 'resp_exploitation',
                'phone' => '+241 77 60 60 06',
                'spatie_role' => 'resp_exploitation',
            ],
            [
                'username' => 'f.mba',
                'email' => 'francois.mba@comilog.com',
                'full_name' => 'Francois Mba Abessolo',
                'role' => 'directeur',
                'phone' => '+241 77 70 70 07',
                'spatie_role' => 'directeur',
            ],
            [
                'username' => 't.engonga',
                'email' => 'thierry.engonga@comilog.com',
                'full_name' => 'Thierry Engonga Ondo',
                'role' => 'administrateur',
                'phone' => '+241 77 80 80 08',
                'spatie_role' => 'administrateur',
            ],
        ];

        foreach ($users as $userData) {
            $spatieRole = $userData['spatie_role'];
            unset($userData['spatie_role']);

            $user = User::create(array_merge($userData, [
                'password' => $password,
                'is_active' => true,
            ]));

            $user->assignRole($spatieRole);
        }
    }
}
