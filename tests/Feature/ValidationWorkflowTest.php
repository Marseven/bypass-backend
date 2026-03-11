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

class ValidationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $supervisor;
    private User $director;
    private User $user;
    private Equipment $equipment;
    private Sensor $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->administrator()->create();
        $this->admin->assignRole('administrator');

        $this->supervisor = User::factory()->supervisor()->create();
        $this->supervisor->assignRole('supervisor');

        $this->director = User::factory()->director()->create();
        $this->director->assignRole('director');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $zone = Zone::factory()->create();
        $this->equipment = Equipment::factory()->create(['zone_id' => $zone->id]);
        $this->sensor = Sensor::factory()->create(['equipment_id' => $this->equipment->id]);
    }

    public function test_supervisor_can_approve_normal_request(): void
    {
        $request = Request::factory()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($this->supervisor)->putJson("/api/v1/requests/{$request->id}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertOk();

        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertEquals($this->supervisor->id, $request->validated_by_id);
    }

    public function test_supervisor_can_reject_request(): void
    {
        $request = Request::factory()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($this->supervisor)->putJson("/api/v1/requests/{$request->id}/validate", [
            'validation_status' => 'rejected',
            'rejection_reason' => 'Not justified',
        ]);

        $response->assertOk();

        $request->refresh();
        $this->assertEquals('rejected', $request->status);
        $this->assertEquals('Not justified', $request->rejection_reason);
    }

    public function test_regular_user_cannot_validate(): void
    {
        $request = Request::factory()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/requests/{$request->id}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertStatus(403);
    }

    public function test_critical_request_requires_dual_validation(): void
    {
        $request = Request::factory()->critical()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'validation_status_level1' => 'pending',
            'validation_status_level2' => 'pending',
        ]);

        // Level 1: Supervisor approves
        $response = $this->actingAs($this->supervisor)->putJson("/api/v1/requests/{$request->id}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertOk();
        $request->refresh();
        $this->assertEquals('approved', $request->validation_status_level1);
        $this->assertEquals('pending', $request->status); // Still pending, needs level 2

        // Level 2: Admin approves
        $response = $this->actingAs($this->admin)->putJson("/api/v1/requests/{$request->id}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertOk();
        $request->refresh();
        $this->assertEquals('approved', $request->validation_status_level2);
        $this->assertEquals('approved', $request->status);
    }

    public function test_critical_request_rejected_at_level1(): void
    {
        $request = Request::factory()->critical()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'validation_status_level1' => 'pending',
            'validation_status_level2' => 'pending',
        ]);

        $response = $this->actingAs($this->supervisor)->putJson("/api/v1/requests/{$request->id}/validate", [
            'validation_status' => 'rejected',
            'rejection_reason' => 'Risk too high',
        ]);

        $response->assertOk();
        $request->refresh();
        $this->assertEquals('rejected', $request->status);
        $this->assertEquals('rejected', $request->validation_status_level1);
    }

    public function test_level2_user_validates_level1_when_level1_pending(): void
    {
        $request = Request::factory()->critical()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'validation_status_level1' => 'pending',
            'validation_status_level2' => 'pending',
        ]);

        // Admin (level2 user) validates level1 when it's pending
        $response = $this->actingAs($this->admin)->putJson("/api/v1/requests/{$request->id}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertStatus(200);
        $request->refresh();
        $this->assertEquals('approved', $request->validation_status_level1);
        $this->assertEquals('pending', $request->validation_status_level2);
        // Request still pending (needs level2)
        $this->assertEquals('pending', $request->status);
    }

    public function test_bypass_activation_on_approval(): void
    {
        $request = Request::factory()->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'priority' => 'normal',
        ]);

        $this->actingAs($this->supervisor)->putJson("/api/v1/requests/{$request->id}/validate", [
            'validation_status' => 'approved',
        ]);

        $this->sensor->refresh();
        $this->equipment->refresh();

        $this->assertEquals('bypassed', $this->sensor->status);
        $this->assertEquals('maintenance', $this->equipment->status);
    }

    public function test_pending_requests_endpoint_for_supervisor(): void
    {
        Request::factory()->count(2)->create([
            'requester_id' => $this->user->id,
            'equipment_id' => $this->equipment->id,
            'sensor_id' => $this->sensor->id,
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($this->supervisor)->getJson('/api/v1/requests/pending');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }
}
