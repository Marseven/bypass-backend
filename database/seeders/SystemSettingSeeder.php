<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::create([
            'key' => 'default_priority',
            'value' => 'medium',
            'description' => 'Priorité par défaut pour les nouvelles demandes',
        ]);

        SystemSetting::create([
            'key' => 'auto_escalation_hours',
            'value' => '24',
            'description' => 'Nombre d\'heures avant escalade automatique',
        ]);

        SystemSetting::create([
            'key' => 'max_pending_requests_per_user',
            'value' => '5',
            'description' => 'Nombre maximum de demandes en attente par utilisateur',
        ]);

        SystemSetting::create([
            'key' => 'notification_email',
            'value' => 'notifications@bypassguard.com',
            'description' => 'Email pour les notifications système',
        ]);
    }
}
