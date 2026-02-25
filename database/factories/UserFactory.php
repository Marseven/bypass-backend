<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'full_name' => fake()->name(),
            'role' => 'user',
            'phone' => fake()->e164PhoneNumber(),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function supervisor(): static
    {
        return $this->state(fn () => ['role' => 'supervisor']);
    }

    public function administrator(): static
    {
        return $this->state(fn () => ['role' => 'administrator']);
    }

    public function director(): static
    {
        return $this->state(fn () => ['role' => 'director']);
    }
}
