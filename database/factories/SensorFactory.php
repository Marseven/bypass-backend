<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sensor>
 */
class SensorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'code' => 'SN-' . fake()->unique()->numerify('###'),
            'name' => 'Capteur ' . fake()->word(),
            'type' => fake()->randomElement(['temperature', 'pressure', 'vibration']),
            'unite' => fake()->randomElement(['°C', 'bar', 'mm/s']),
            'seuil_critique' => fake()->numberBetween(50, 200) . '',
            'Dernier_Etallonnage' => now(),
            'status' => 'active',
            'last_reading' => fake()->randomFloat(2, 0, 100),
            'last_reading_at' => now(),
        ];
    }
}
