<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'default_priority',
                'value' => 'medium',
                'description' => 'Priorite par defaut pour les nouvelles demandes',
            ],
            [
                'key' => 'auto_escalation_hours',
                'value' => '24',
                'description' => 'Nombre d\'heures avant escalade automatique',
            ],
            [
                'key' => 'max_pending_requests_per_user',
                'value' => '5',
                'description' => 'Nombre maximum de demandes en attente par utilisateur',
            ],
            [
                'key' => 'notification_email',
                'value' => 'notifications@comilog.com',
                'description' => 'Email pour les notifications systeme',
            ],
            [
                'key' => 'app_name',
                'value' => 'ByPass',
                'description' => 'Nom de l\'application affiche dans l\'interface',
            ],
            [
                'key' => 'app_tagline',
                'value' => 'Systeme de gestion des bypass — COMILOG Moanda',
                'description' => 'Sous-titre de l\'application',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }
    }
}
