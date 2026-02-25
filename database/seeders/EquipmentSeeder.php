<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Equipment;
use App\Models\Sensor;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipment1 = Equipment::create([
            'name' => 'Ligne de production A',
            'description' => 'Ligne principale de production automobile',
            'location' => 'Atelier 1 - Zone Nord',
            'status' => 'active',
        ]);

        $equipment2 = Equipment::create([
            'name' => 'Four industriel B',
            'description' => 'Four de traitement thermique',
            'location' => 'Atelier 2 - Zone Sud',
            'status' => 'active',
        ]);

        $equipment3 = Equipment::create([
            'name' => 'Compresseur C',
            'description' => 'Compresseur d\'air principal',
            'location' => 'Salle des machines',
            'status' => 'active',
        ]);

        // Ajout de capteurs
        Sensor::create([
            'equipment_id' => $equipment1->id,
            'name' => 'Capteur de pression #12',
            'type' => 'pressure',
            'status' => 'online',
            'last_reading' => 2.5,
            'last_reading_at' => now(),
        ]);

        Sensor::create([
            'equipment_id' => $equipment2->id,
            'name' => 'Capteur de température #8',
            'type' => 'temperature',
            'status' => 'online',
            'last_reading' => 850.0,
            'last_reading_at' => now(),
        ]);

        Sensor::create([
            'equipment_id' => $equipment3->id,
            'name' => 'Capteur de vibration #5',
            'type' => 'vibration',
            'status' => 'online',
            'last_reading' => 0.8,
            'last_reading_at' => now(),
        ]);
    }
}
