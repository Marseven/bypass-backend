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
 * Guide de Test — Scénario 9 : Consultation des zones et équipements.
 *
 * Vérifie que les utilisateurs autorisés peuvent consulter les zones,
 * équipements et capteurs, et que les rôles sans permission reçoivent 403.
 */
class GuideZonesEquipmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $chefDeQuart;
    private User $operateur;
    private Zone $zone;
    private Equipment $equipment;
    private Sensor $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['role' => 'administrateur']);
        $this->admin->assignRole('administrateur');

        $this->chefDeQuart = User::factory()->create([
            'username' => 'm.mbadinga',
            'role' => 'chef_de_quart',
        ]);
        $this->chefDeQuart->assignRole('chef_de_quart');

        $this->operateur = User::factory()->create([
            'username' => 'j.moussavou',
            'role' => 'operateur',
        ]);
        $this->operateur->assignRole('operateur');

        $this->zone = Zone::factory()->create(['name' => 'Station de Pompage']);
        $this->equipment = Equipment::factory()->create([
            'zone_id' => $this->zone->id,
            'name' => 'Pompe Centrifuge DN300',
        ]);
        $this->sensor = Sensor::factory()->create([
            'equipment_id' => $this->equipment->id,
            'name' => 'Débit sortie',
        ]);
    }

    // ── Admin peut lister les zones ────────────────────────────────

    public function test_admin_can_list_zones(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/zones');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
    }

    // ── Admin peut voir le détail d'une zone ──────────────────────

    public function test_admin_can_view_zone_detail(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/v1/zones/{$this->zone->id}");

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Station de Pompage']);
    }

    // ── Admin peut lister les équipements ─────────────────────────

    public function test_admin_can_list_equipment(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/equipment');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
    }

    // ── Admin peut voir le détail d'un équipement ─────────────────

    public function test_admin_can_view_equipment_detail(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/v1/equipment/{$this->equipment->id}");

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Pompe Centrifuge DN300']);
    }

    // ── Chef de quart peut consulter zones et équipements ─────────

    public function test_chef_de_quart_can_list_zones(): void
    {
        $response = $this->actingAs($this->chefDeQuart)->getJson('/api/v1/zones');
        $response->assertOk();
    }

    public function test_chef_de_quart_can_list_equipment(): void
    {
        $response = $this->actingAs($this->chefDeQuart)->getJson('/api/v1/equipment');
        $response->assertOk();
    }

    // ── Opérateur ne peut PAS consulter les zones ─────────────────

    public function test_operateur_cannot_list_zones(): void
    {
        $response = $this->actingAs($this->operateur)->getJson('/api/v1/zones');
        $response->assertStatus(403);
    }

    // ── Opérateur ne peut PAS consulter les équipements ───────────

    public function test_operateur_cannot_list_equipment(): void
    {
        $response = $this->actingAs($this->operateur)->getJson('/api/v1/equipment');
        $response->assertStatus(403);
    }

    // ── Les équipements d'une zone sont listés ────────────────────

    public function test_admin_can_list_equipment_by_zone(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/v1/zones/{$this->zone->id}/equipements");

        $response->assertOk();
        $this->assertNotEmpty($response->json());
    }

    // ── Les capteurs sont visibles via l'équipement ───────────────

    public function test_equipment_detail_includes_sensors(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/v1/equipment/{$this->equipment->id}");

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Débit sortie']);
    }
}
