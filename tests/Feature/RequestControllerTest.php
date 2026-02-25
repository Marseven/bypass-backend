<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Request;
use App\Models\Sensor;
use App\Models\User;
use App\Models\Zone;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private User $supervisor;
    private Equipment $equipment;
    private Sensor $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->administrator()->create();
        $this->admin->assignRole('administrator');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->supervisor = User::factory()->supervisor()->create();
        $this->supervisor->assignRole('supervisor');

        $zone = Zone::factory()->create();
        $this->equipment = Equipment::factory()->create(['zone_id' => $zone->id]);
        $this->sensor = Sensor::factory()->create(['equipment_id' => $this->equipment->id]);
    }

    public function test_admin_can_list_all_requests(): void
    {
        Request::factory()->count(3)->create([
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/requests');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_user_can_only_see_own_requests(): void
    {
        Request::factory()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
        ]);
        Request::factory()->create([
            'requester_id' => $this->admin->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/requests');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_can_create_request(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/requests', [
            'reason' => 'preventive_maintenance',
            'detailedJustification' => 'Equipment needs scheduled maintenance',
            'urgencyLevel' => 'normal',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addDay()->toISOString(),
            'estimatedDuration' => 4,
            'safetyImpact' => 'low',
            'operationalImpact' => 'medium',
            'environmentalImpact' => 'very_low',
            'mitigationMeasures' => ['Wear PPE', 'Area cordoned off'],
            'contingencyPlan' => 'Revert to manual control',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'request_code', 'title', 'status']]);
    }

    public function test_create_request_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/requests', []);

        $response->assertStatus(422);
    }

    public function test_user_can_view_own_request(): void
    {
        $request = Request::factory()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/requests/{$request->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'request_code']]);
    }

    public function test_user_cannot_view_others_request(): void
    {
        $request = Request::factory()->create([
            'requester_id' => $this->admin->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/requests/{$request->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_pending_request(): void
    {
        $request = Request::factory()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/requests/{$request->id}");

        $response->assertOk();
        $this->assertSoftDeleted('requests', ['id' => $request->id]);
    }

    public function test_user_cannot_delete_approved_request(): void
    {
        $request = Request::factory()->approved()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/requests/{$request->id}");

        $response->assertStatus(422);
    }

    public function test_can_filter_requests_by_status(): void
    {
        Request::factory()->create([
            'requester_id' => $this->admin->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'status' => 'pending',
        ]);
        Request::factory()->create([
            'requester_id' => $this->admin->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/requests?status=pending');

        $response->assertOk();
        foreach ($response->json('data') as $item) {
            $this->assertEquals('pending', $item['status']);
        }
    }

    public function test_my_requests_returns_only_user_requests(): void
    {
        Request::factory()->count(2)->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
        ]);
        Request::factory()->create([
            'requester_id' => $this->admin->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/requests/mine');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_unauthenticated_cannot_access_requests(): void
    {
        $this->getJson('/api/v1/requests')->assertStatus(401);
        $this->postJson('/api/v1/requests', [])->assertStatus(401);
    }
}
