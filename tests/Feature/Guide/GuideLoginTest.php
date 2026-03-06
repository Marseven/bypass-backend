<?php

namespace Tests\Feature\Guide;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guide de Test — Scénario 3 : Connexion et accès par rôle.
 *
 * Vérifie que chaque rôle CDC peut se connecter et accède uniquement aux
 * menus/endpoints autorisés.
 */
class GuideLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ── 3.1 Procédure de connexion ──────────────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'p.ndong',
            'password' => 'Comilog@2026!',
        ]);
        $user->assignRole('technicien');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'p.ndong',
            'password' => 'Comilog@2026!',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 200)
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'username' => 'p.ndong',
            'password' => 'Comilog@2026!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'p.ndong',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    // ── 3.2 Vérifier les accès selon le rôle ────────────────────────

    public function test_operateur_cannot_access_user_management(): void
    {
        $operateur = User::factory()->create(['role' => 'operateur']);
        $operateur->assignRole('operateur');

        $this->actingAs($operateur)
            ->getJson('/api/v1/users')
            ->assertStatus(403);
    }

    public function test_operateur_cannot_access_zone_management(): void
    {
        $operateur = User::factory()->create(['role' => 'operateur']);
        $operateur->assignRole('operateur');

        $this->actingAs($operateur)
            ->postJson('/api/v1/zones', ['name' => 'Test'])
            ->assertStatus(403);
    }

    public function test_technicien_cannot_access_validation(): void
    {
        $technicien = User::factory()->create(['role' => 'technicien']);
        $technicien->assignRole('technicien');

        $this->actingAs($technicien)
            ->getJson('/api/v1/requests/pending')
            ->assertStatus(403);
    }

    public function test_chef_de_quart_cannot_access_user_management(): void
    {
        $cdq = User::factory()->create(['role' => 'chef_de_quart']);
        $cdq->assignRole('chef_de_quart');

        $this->actingAs($cdq)
            ->getJson('/api/v1/users')
            ->assertStatus(403);
    }

    public function test_admin_can_access_all_endpoints(): void
    {
        $admin = User::factory()->create(['role' => 'administrateur']);
        $admin->assignRole('administrateur');

        $this->actingAs($admin)
            ->getJson('/api/v1/users')
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/api/v1/requests')
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/api/v1/dashboard/summary')
            ->assertOk();
    }

    public function test_technicien_can_access_own_requests(): void
    {
        $technicien = User::factory()->create(['role' => 'technicien']);
        $technicien->assignRole('technicien');

        $this->actingAs($technicien)
            ->getJson('/api/v1/requests/mine')
            ->assertOk();
    }

    public function test_chef_de_quart_can_access_pending_requests(): void
    {
        $cdq = User::factory()->create(['role' => 'chef_de_quart']);
        $cdq->assignRole('chef_de_quart');

        $this->actingAs($cdq)
            ->getJson('/api/v1/requests/pending')
            ->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_api(): void
    {
        $this->getJson('/api/v1/requests')
            ->assertStatus(401);
    }
}
