<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Request>
 */
class RequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_code' => 'BR-' . date('Y') . '-' . date('m') . '-' . fake()->unique()->numerify('###'),
            'requester_id' => User::factory(),
            'title' => 'preventive_maintenance',
            'description' => fake()->paragraph(),
            'priority' => 'normal',
            'equipment_id' => Equipment::factory(),
            'sensor_id' => Sensor::factory(),
            'status' => 'pending',
            'submitted_at' => now(),
            'impact_securite' => 'low',
            'impact_operationnel' => 'low',
            'impact_environnemental' => 'low',
            'validation_required_by_role' => 'supervisor',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
            'mesure_attenuation' => 'Mesures de sécurité standard',
        ];
    }

    public function critical(): static
    {
        return $this->state(fn () => [
            'priority' => 'critical',
            'validation_required_by_role' => 'administrator',
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn () => [
            'priority' => 'emergency',
            'validation_required_by_role' => 'administrator',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }
}
