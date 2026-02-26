<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SiteSeeder::class,
            EquipmentSeeder::class,
            UserSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
