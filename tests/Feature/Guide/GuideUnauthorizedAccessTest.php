<?php

namespace Tests\Feature\Guide;

use App\Models\Equipment;
use App\Models\Request;
use App\Models\Sensor;
use App\Models\User;
use App\Models\Zone;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guide de Test — Scénario 6 : Tentatives d'accès non autorisés.
 *
 * Vérifie que le système retourne 403 pour les actions non autorisées.
 */
class GuideUnauthorizedAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $operateur;
    private User $technicien;
    private User $chefDeQuart;
    private Equipment $equipment;
    private Sensor $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->operateur = User::factory()->create([
            'username' => 'j.moussavou',
            'role' => 'operateur',
        ]);
        $this->operateur->assignRole('operateur');

        $this->technicien = User::factory()->create([
            'username' => 'p.ndong',
            'role' => 'technicien',
        ]);
        $this->technicien->assignRole('technicien');

        $this->chefDeQuart = User::factory()->create([
            'username' => 'm.mbadinga',
            'role' => 'chef_de_quart',
        ]);
        $this->chefDeQuart->assignRole('chef_de_quart');

        $zone = Zone::factory()->create();
        $this->equipment = Equipment::factory()->create(['zone_id' => $zone->id]);
        $this->sensor = Sensor::factory()->create(['equipment_id' => $this->equipment->id]);
    }

    // ── Opérateur : ne peut PAS créer de demandes ───────────────────

    public function test_operateur_cannot_create_request(): void
    {
        $response = $this->actingAs($this->operateur)->postJson('/api/v1/requests', [
            'reason' => 'preventive_maintenance',
            'detailedJustification' => 'Test',
            'urgencyLevel' => 'normal',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addDay()->toISOString(),
            'estimatedDuration' => 2,
            'safetyImpact' => 'low',
            'operationalImpact' => 'low',
            'environmentalImpact' => 'very_low',
            'mitigationMeasures' => ['Standard'],
        ]);

        $response->assertStatus(403);
    }

    // ── Technicien : ne peut PAS valider ────────────────────────────

    public function test_technicien_cannot_validate_request(): void
    {
        // Admin creates a request for testing
        $admin = User::factory()->create(['role' => 'administrateur']);
        $admin->assignRole('administrateur');

        $request = Request::factory()->create([
            'requester_id' => $admin->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->technicien)->putJson("/api/v1/requests/{$request->id}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertStatus(403);
    }

    // ── Technicien : ne peut PAS accéder à la gestion des utilisateurs

    public function test_technicien_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->technicien)->getJson('/api/v1/users');
        $response->assertStatus(403);
    }

    // ── Opérateur : ne peut PAS modifier un équipement ──────────────

    public function test_operateur_cannot_modify_equipment(): void
    {
        $response = $this->actingAs($this->operateur)->putJson("/api/v1/equipment/{$this->equipment->id}", [
            'name' => 'Modified Name',
        ]);

        $response->assertStatus(403);
    }

    // ── Opérateur : ne peut PAS supprimer un équipement ─────────────

    public function test_operateur_cannot_delete_equipment(): void
    {
        $response = $this->actingAs($this->operateur)->deleteJson("/api/v1/equipment/{$this->equipment->id}");

        $response->assertStatus(403);
    }

    // ── Chef de quart : ne peut PAS accéder aux paramètres système ──

    public function test_chef_de_quart_cannot_access_system_settings(): void
    {
        $response = $this->actingAs($this->chefDeQuart)->getJson('/api/v1/admin/settings');
        $response->assertStatus(403);
    }

    // ── Opérateur : ne peut PAS accéder aux demandes en attente ─────

    public function test_operateur_cannot_access_pending_requests(): void
    {
        $response = $this->actingAs($this->operateur)->getJson('/api/v1/requests/pending');
        $response->assertStatus(403);
    }

    // ── Technicien : ne peut PAS supprimer la demande d'un autre ────

    public function test_technicien_cannot_delete_other_user_request(): void
    {
        $otherUser = User::factory()->create(['role' => 'technicien']);
        $otherUser->assignRole('technicien');

        $request = Request::factory()->create([
            'requester_id' => $otherUser->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->technicien)->deleteJson("/api/v1/requests/{$request->id}");
        $response->assertStatus(403);
    }
}
