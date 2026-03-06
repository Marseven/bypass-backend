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
 * Guide de Test — Scénario 4 : Cycle complet.
 *
 * Création → Validation N1 → Validation N2 → Activation → Fermeture.
 *
 * CDC approval matrix (court terme + sécurité) :
 * - Chef de quart valide au niveau 1
 * - Responsable HSE valide au niveau 2
 */
class GuideFullCycleTest extends TestCase
{
    use RefreshDatabase;

    private User $technicien;
    private User $chefDeQuart;
    private User $responsableHse;
    private Equipment $equipment;
    private Equipment $securityEquipment;
    private Sensor $sensor;
    private Sensor $securitySensor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

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

        $this->responsableHse = User::factory()->create([
            'username' => 's.nzoghe',
            'role' => 'responsable_hse',
        ]);
        $this->responsableHse->assignRole('responsable_hse');

        $zone = Zone::factory()->create(['name' => 'Station de Pompage']);

        // Process equipment (SIL NA → criticite = process → single validation)
        $this->equipment = Equipment::factory()->create([
            'zone_id' => $zone->id,
            'code' => 'PMP-001',
            'name' => 'Pompe Centrifuge DN300',
            'niveau_sil' => 'na',
        ]);
        $this->sensor = Sensor::factory()->create([
            'equipment_id' => $this->equipment->id,
            'code' => 'CAP-PMP-001-01',
            'name' => 'Débit sortie',
        ]);

        // Security equipment (SIL1 → criticite = securite → dual validation)
        $this->securityEquipment = Equipment::factory()->create([
            'zone_id' => $zone->id,
            'code' => 'PMP-002',
            'name' => 'Pompe Sécurité SIL1',
            'niveau_sil' => 'sil1',
        ]);
        $this->securitySensor = Sensor::factory()->create([
            'equipment_id' => $this->securityEquipment->id,
            'code' => 'CAP-PMP-002-01',
            'name' => 'Pression sécurité',
        ]);
    }

    public function test_full_lifecycle_create_to_close(): void
    {
        // ── 1. Technicien crée une demande sur équipement sécurité (double validation CDC)
        $createResponse = $this->actingAs($this->technicien)->postJson('/api/v1/requests', [
            'reason' => 'preventive_maintenance',
            'detailedJustification' => 'Remplacement joint mécanique pompe sécurité',
            'urgencyLevel' => 'critical',
            'equipmentId' => $this->securityEquipment->id,
            'sensorId' => $this->securitySensor->id,
            'plannedStartDate' => now()->addDay()->toISOString(),
            'estimatedDuration' => 8,
            'safetyImpact' => 'medium',
            'operationalImpact' => 'high',
            'environmentalImpact' => 'low',
            'mitigationMeasures' => ['Vanne bypass ouverte', 'Pompe secours en standby'],
            'contingencyPlan' => 'Basculement sur pompe secours PMP-003',
        ]);

        $createResponse->assertStatus(201);
        $requestId = $createResponse->json('data.id');
        $this->assertEquals('pending', $createResponse->json('data.status'));

        // ── 2. Chef de quart valide au niveau 1 (CDC: court_terme + securite → chef_de_quart L1)
        $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'approved',
        ])->assertOk();

        $request = Request::find($requestId);
        // CDC path: status still pending (needs level 2)
        $this->assertEquals('pending', $request->status);

        // ── 3. Responsable HSE valide au niveau 2 (CDC: court_terme + securite → responsable_hse L2)
        $this->actingAs($this->responsableHse)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'approved',
        ])->assertOk();

        $request->refresh();
        $this->assertEquals('approved', $request->status);

        // ── 4. Activation du bypass
        $activateResponse = $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/activate");
        $activateResponse->assertOk();

        $request->refresh();
        $this->assertEquals('active', $request->status);

        // ── 5. Fermeture du bypass
        $closeResponse = $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/close");
        $closeResponse->assertOk();

        $request->refresh();
        $this->assertEquals('closed', $request->status);
    }

    public function test_status_transitions_are_correct_at_each_step(): void
    {
        // Create with process equipment (single validation: chef_de_quart only)
        $createResponse = $this->actingAs($this->technicien)->postJson('/api/v1/requests', [
            'reason' => 'testing',
            'detailedJustification' => 'Test cycle normal priority',
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
        $requestId = $createResponse->json('data.id');

        // Step 1: Status = pending
        $this->assertEquals('pending', Request::find($requestId)->status);

        // Step 2: Validate → approved (single validation for process equipment)
        $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'approved',
        ])->assertOk();
        $this->assertEquals('approved', Request::find($requestId)->status);

        // Step 3: Activate → active
        $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/activate")
            ->assertOk();
        $this->assertEquals('active', Request::find($requestId)->status);

        // Step 4: Close → closed
        $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/close")
            ->assertOk();
        $this->assertEquals('closed', Request::find($requestId)->status);
    }
}
