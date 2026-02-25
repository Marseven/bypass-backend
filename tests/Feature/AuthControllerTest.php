<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $user->assignRole('user');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => ['user', 'token'],
                'message',
            ])
            ->assertJsonPath('status', 200);
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $user->assignRole('user');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 200);
    }

    public function test_login_returns_401_with_wrong_password(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->username,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 401);
    }

    public function test_login_returns_401_for_inactive_user(): void
    {
        $user = User::factory()->inactive()->create();
        $user->assignRole('user');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->username,
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_requires_identifier(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'testuser',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Déconnexion réussie');
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('username', $user->username);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->username,
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
    }
}
