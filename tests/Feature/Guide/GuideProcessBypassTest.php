<?php

namespace Tests\Feature\Guide;

use App\Models\Equipment;
use App\Models\Sensor;
use App\Models\User;
use App\Models\Zone;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guide de Test — Scénarios 1 & 2 : Créer un bypass process + Validation.
 *
 * Scénario 1 : Le technicien p.ndong crée une demande de bypass process
 *              sur le capteur Vibration châssis du Concasseur Primaire.
 * Scénario 2 : Le chef de quart m.mbadinga valide la demande au niveau 1.
 */
class GuideProcessBypassTest extends TestCase
{
    use RefreshDatabase;

    private User $technicien;
    private User $chefDeQuart;
    private Equipment $equipment;
    private Sensor $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Technicien p.ndong
        $this->technicien = User::factory()->create([
            'username' => 'p.ndong',
            'full_name' => 'Patrick Ndong Essono',
            'role' => 'technicien',
            'password' => 'Comilog@2026!',
        ]);
        $this->technicien->assignRole('technicien');

        // Chef de quart m.mbadinga
        $this->chefDeQuart = User::factory()->create([
            'username' => 'm.mbadinga',
            'full_name' => 'Marcel Mbadinga Ondo',
            'role' => 'chef_de_quart',
            'password' => 'Comilog@2026!',
        ]);
        $this->chefDeQuart->assignRole('chef_de_quart');

        // Zone → Equipment → Sensor (Station de Concassage)
        $zone = Zone::factory()->create([
            'name' => 'Station de Concassage',
        ]);
        $this->equipment = Equipment::factory()->create([
            'zone_id' => $zone->id,
            'code' => 'CON-001',
            'name' => 'Concasseur Primaire à Mâchoires',
        ]);
        $this->sensor = Sensor::factory()->create([
            'equipment_id' => $this->equipment->id,
            'code' => 'CAP-CON-001-01',
            'name' => 'Vibration châssis',
        ]);
    }

    // ── Scénario 1 : Création de la demande ─────────────────────────

    public function test_technicien_can_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'p.ndong',
            'password' => 'Comilog@2026!',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.username', 'p.ndong');
    }

    public function test_technicien_can_create_process_bypass(): void
    {
        $response = $this->actingAs($this->technicien)->postJson('/api/v1/requests', [
            'reason' => 'preventive_maintenance',
            'detailedJustification' => 'Maintenance préventive palier excentrique',
            'urgencyLevel' => 'normal',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addDay()->toISOString(),
            'estimatedDuration' => 4,
            'safetyImpact' => 'low',
            'operationalImpact' => 'medium',
            'environmentalImpact' => 'very_low',
            'mitigationMeasures' => ['Port EPI obligatoire', 'Zone balisée'],
            'contingencyPlan' => 'Retour commande manuelle',
            'bypassType' => 'maintenance',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'request_code', 'status']])
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_created_request_appears_in_technicien_list(): void
    {
        // Create the request first
        $this->actingAs($this->technicien)->postJson('/api/v1/requests', [
            'reason' => 'preventive_maintenance',
            'detailedJustification' => 'Maintenance préventive palier excentrique',
            'urgencyLevel' => 'normal',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addDay()->toISOString(),
            'estimatedDuration' => 4,
            'safetyImpact' => 'low',
            'operationalImpact' => 'medium',
            'environmentalImpact' => 'very_low',
            'mitigationMeasures' => ['Port EPI'],
        ]);

        // Verify it appears in "mine"
        $response = $this->actingAs($this->technicien)->getJson('/api/v1/requests/mine');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
        $this->assertEquals('pending', $response->json('data.0.status'));
    }

    // ── Scénario 2 : Validation par le chef de quart ────────────────

    public function test_chef_de_quart_sees_pending_request(): void
    {
        // Technicien creates request
        $this->actingAs($this->technicien)->postJson('/api/v1/requests', [
            'reason' => 'preventive_maintenance',
            'detailedJustification' => 'Maintenance préventive palier excentrique',
            'urgencyLevel' => 'normal',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addDay()->toISOString(),
            'estimatedDuration' => 4,
            'safetyImpact' => 'low',
            'operationalImpact' => 'medium',
            'environmentalImpact' => 'very_low',
            'mitigationMeasures' => ['Port EPI'],
        ]);

        // Chef de quart sees it in pending
        $response = $this->actingAs($this->chefDeQuart)->getJson('/api/v1/requests/pending');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_chef_de_quart_validates_level1(): void
    {
        // Technicien creates request
        $createResponse = $this->actingAs($this->technicien)->postJson('/api/v1/requests', [
            'reason' => 'preventive_maintenance',
            'detailedJustification' => 'Maintenance préventive palier excentrique',
            'urgencyLevel' => 'normal',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addDay()->toISOString(),
            'estimatedDuration' => 4,
            'safetyImpact' => 'low',
            'operationalImpact' => 'medium',
            'environmentalImpact' => 'very_low',
            'mitigationMeasures' => ['Port EPI'],
        ]);

        $requestId = $createResponse->json('data.id');

        // Chef de quart validates
        $response = $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertOk();

        // Verify status changed
        $showResponse = $this->actingAs($this->chefDeQuart)->getJson("/api/v1/requests/{$requestId}");
        $this->assertContains($showResponse->json('data.status'), ['approved', 'validated_level1']);
    }
}
