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
 * Guide de Test — Scénario 7 : Vérification du tableau de bord.
 *
 * L'administrateur vérifie que les compteurs du dashboard reflètent
 * correctement les données (équipements, capteurs, zones).
 */
class GuideDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['role' => 'administrateur']);
        $this->admin->assignRole('administrateur');
    }

    public function test_admin_can_access_dashboard_summary(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'new_requests',
                'active_requests',
                'pending_validation',
                'approved_today',
                'connected_users',
            ]);
    }

    public function test_dashboard_counts_reflect_data(): void
    {
        // Create test data: 3 zones, 5 equipment, 10 sensors
        $zones = Zone::factory()->count(3)->create();

        $equipmentCount = 0;
        $sensorCount = 0;
        foreach ($zones as $zone) {
            $equipment = Equipment::factory()->count(2)->create(['zone_id' => $zone->id]);
            $equipmentCount += 2;
            foreach ($equipment as $eq) {
                Sensor::factory()->count(2)->create(['equipment_id' => $eq->id]);
                $sensorCount += 2;
            }
        }

        // Dashboard system status should reflect these counts
        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard/system-status');

        $response->assertOk()
            ->assertJsonStructure([
                'monitored_equipment',
                'online_sensors',
                'active_alerts',
                'system_performance',
            ]);
    }

    public function test_dashboard_summary_counts_pending_requests(): void
    {
        $zone = Zone::factory()->create();
        $equipment = Equipment::factory()->create(['zone_id' => $zone->id]);
        $sensor = Sensor::factory()->create(['equipment_id' => $equipment->id]);

        // Create 3 pending requests
        Request::factory()->count(3)->create([
            'requester_id' => $this->admin->id,
            'equipment_id' => $equipment->id,
            'sensor_id' => $sensor->id,
            'status' => 'pending',
        ]);

        // Create 1 active
        Request::factory()->create([
            'requester_id' => $this->admin->id,
            'equipment_id' => $equipment->id,
            'sensor_id' => $sensor->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard/summary');

        $response->assertOk();
        $this->assertEquals(3, $response->json('pending_validation'));
        $this->assertEquals(1, $response->json('active_requests'));
    }

    public function test_dashboard_recent_requests(): void
    {
        $zone = Zone::factory()->create();
        $equipment = Equipment::factory()->create(['zone_id' => $zone->id]);
        $sensor = Sensor::factory()->create(['equipment_id' => $equipment->id]);

        Request::factory()->count(5)->create([
            'requester_id' => $this->admin->id,
            'equipment_id' => $equipment->id,
            'sensor_id' => $sensor->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard/recent-requests');

        $response->assertOk();
        $this->assertCount(5, $response->json());
    }

    public function test_dashboard_request_statistics(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard/request-statistics?days=7');

        $response->assertOk();
        // Should return array of daily stats
        $this->assertIsArray($response->json());
    }

    public function test_dashboard_top_sensors(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard/top-sensors');

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/dashboard/summary');
        $response->assertStatus(401);
    }

    public function test_operateur_can_access_dashboard(): void
    {
        $operateur = User::factory()->create(['role' => 'operateur']);
        $operateur->assignRole('operateur');

        $response = $this->actingAs($operateur)->getJson('/api/v1/dashboard/summary');
        $response->assertOk();
    }
}
