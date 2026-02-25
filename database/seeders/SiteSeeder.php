<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            ['code' => 'PG-NORD', 'name' => 'Port-Gentil Nord', 'location' => 'Zone industrielle Nord, Port-Gentil'],
            ['code' => 'PG-SUD', 'name' => 'Port-Gentil Sud', 'location' => 'Zone industrielle Sud, Port-Gentil'],
            ['code' => 'PG-EST', 'name' => 'Port-Gentil Est', 'location' => 'Zone industrielle Est, Port-Gentil'],
            ['code' => 'PG-OUEST', 'name' => 'Port-Gentil Ouest', 'location' => 'Zone industrielle Ouest, Port-Gentil'],
            ['code' => 'PG-CENTRE', 'name' => 'Port-Gentil Centre', 'location' => 'Zone industrielle Centre, Port-Gentil'],
        ];

        foreach ($sites as $site) {
            Site::firstOrCreate(['code' => $site['code']], $site);
        }
    }
}
