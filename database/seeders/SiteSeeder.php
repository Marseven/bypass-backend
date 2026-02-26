<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'code' => 'CML-MOANDA',
                'name' => 'Complexe Minier de Moanda',
                'location' => 'Moanda, Haut-Ogooue, Gabon',
            ],
            [
                'code' => 'CML-OWENDO',
                'name' => 'Terminal Mineralier d\'Owendo',
                'location' => 'Owendo, Estuaire, Gabon',
            ],
        ];

        foreach ($sites as $site) {
            Site::firstOrCreate(['code' => $site['code']], $site);
        }
    }
}
