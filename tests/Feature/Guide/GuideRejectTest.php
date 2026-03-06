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
 * Guide de Test — Scénario 5 : Rejet d'une demande.
 *
 * Le chef de quart rejette une demande avec un motif.
 * Le technicien peut voir le statut Rejeté et le motif.
 */
class GuideRejectTest extends TestCase
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

        $zone = Zone::factory()->create(['name' => 'Zone Test']);
        $this->equipment = Equipment::factory()->create(['zone_id' => $zone->id]);
        $this->sensor = Sensor::factory()->create(['equipment_id' => $this->equipment->id]);
    }

    public function test_chef_de_quart_can_reject_request_with_reason(): void
    {
        // Technicien creates a request
        $createResponse = $this->actingAs($this->technicien)->postJson('/api/v1/requests', [
            'reason' => 'preventive_maintenance',
            'detailedJustification' => 'Demande à rejeter pour test',
            'urgencyLevel' => 'normal',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addDay()->toISOString(),
            'estimatedDuration' => 2,
            'safetyImpact' => 'low',
            'operationalImpact' => 'low',
            'environmentalImpact' => 'very_low',
            'mitigationMeasures' => ['Mesure standard'],
        ]);
        $requestId = $createResponse->json('data.id');

        // Chef de quart rejects
        $rejectResponse = $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'rejected',
            'rejection_reason' => 'Intervention non justifiée, attendre autorisation responsable',
        ]);

        $rejectResponse->assertOk();

        // Verify status and rejection reason in DB
        $request = Request::find($requestId);
        $this->assertEquals('rejected', $request->status);
    }

    public function test_technicien_sees_rejected_request_in_list(): void
    {
        // Create and reject
        $createResponse = $this->actingAs($this->technicien)->postJson('/api/v1/requests', [
            'reason' => 'preventive_maintenance',
            'detailedJustification' => 'Test rejet',
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

        $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'rejected',
            'rejection_reason' => 'Intervention non justifiée',
        ])->assertOk();

        // Technicien sees rejected status
        $mineResponse = $this->actingAs($this->technicien)->getJson('/api/v1/requests/mine');
        $mineResponse->assertOk();

        $foundRejected = false;
        foreach ($mineResponse->json('data') as $req) {
            if ($req['id'] == $requestId) {
                $this->assertEquals('rejected', $req['status']);
                $foundRejected = true;
            }
        }
        $this->assertTrue($foundRejected, 'Rejected request should appear in technicien list');
    }

    public function test_reject_requires_rejection_reason(): void
    {
        $createResponse = $this->actingAs($this->technicien)->postJson('/api/v1/requests', [
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
        $requestId = $createResponse->json('data.id');

        // Reject without reason → should fail validation
        $response = $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'rejected',
        ]);

        $response->assertStatus(422);
    }
}
