<?php

namespace Database\Factories;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'EQ-' . fake()->unique()->numerify('###'),
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(['Pump', 'Valve', 'Compressor', 'Motor']),
            'criticite' => fake()->randomElement(['Haute', 'Moyenne', 'Basse']),
            'fabricant' => fake()->company(),
            'description' => fake()->sentence(),
            'zone_id' => Zone::factory(),
            'status' => 'operational',
        ];
    }
}
