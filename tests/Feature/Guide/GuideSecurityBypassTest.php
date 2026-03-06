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
 * Guide de Test — Scénario 3 : Bypass sécurité (double validation).
 *
 * Un instrumentiste crée un bypass sur un équipement SIL (criticité sécurité).
 * CDC approval matrix (court terme + sécurité) :
 * - Chef de quart valide au niveau 1
 * - Responsable HSE valide au niveau 2
 */
class GuideSecurityBypassTest extends TestCase
{
    use RefreshDatabase;

    private User $instrumentiste;
    private User $chefDeQuart;
    private User $responsableHse;
    private User $directeur;
    private Equipment $equipment;
    private Sensor $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // a.obame — Instrumentiste
        $this->instrumentiste = User::factory()->create([
            'username' => 'a.obame',
            'full_name' => 'Alain Obame Nguema',
            'role' => 'instrumentiste',
        ]);
        $this->instrumentiste->assignRole('instrumentiste');

        // m.mbadinga — Chef de quart (CDC L1 for court_terme + securite)
        $this->chefDeQuart = User::factory()->create([
            'username' => 'm.mbadinga',
            'role' => 'chef_de_quart',
        ]);
        $this->chefDeQuart->assignRole('chef_de_quart');

        // s.nzoghe — Responsable HSE (CDC L2 for court_terme + securite)
        $this->responsableHse = User::factory()->create([
            'username' => 's.nzoghe',
            'full_name' => 'Sylvie Nzoghe Mba',
            'role' => 'responsable_hse',
        ]);
        $this->responsableHse->assignRole('responsable_hse');

        // f.mba — Directeur (not in court_terme + securite approval chain)
        $this->directeur = User::factory()->create([
            'username' => 'f.mba',
            'full_name' => 'François Mba Abessolo',
            'role' => 'directeur',
        ]);
        $this->directeur->assignRole('directeur');

        // Security equipment: SIL1 → criticite = securite → dual validation
        $zone = Zone::factory()->create([
            'name' => 'Usine de Traitement du Minerai (UTM)',
        ]);
        $this->equipment = Equipment::factory()->create([
            'zone_id' => $zone->id,
            'code' => 'UTM-003',
            'name' => 'Four de Frittage Rotatif',
            'criticite' => 'Haute',
            'niveau_sil' => 'sil1',
        ]);
        $this->sensor = Sensor::factory()->create([
            'equipment_id' => $this->equipment->id,
            'code' => 'CAP-UTM-003-02',
            'name' => 'Pression gaz',
        ]);
    }

    // ── Étape A : Création par l'instrumentiste ─────────────────────

    public function test_instrumentiste_creates_critical_security_bypass(): void
    {
        $response = $this->actingAs($this->instrumentiste)->postJson('/api/v1/requests', [
            'reason' => 'corrective_maintenance',
            'detailedJustification' => 'Remplacement capteur pression gaz défaillant',
            'urgencyLevel' => 'critical',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addHours(2)->toISOString(),
            'estimatedDuration' => 6,
            'safetyImpact' => 'high',
            'operationalImpact' => 'high',
            'environmentalImpact' => 'medium',
            'mitigationMeasures' => ['Surveillance permanente', 'Extincteur à proximité', 'Ronde HSE renforcée'],
            'contingencyPlan' => 'Arrêt four immédiat si anomalie détectée',
            'bypassType' => 'maintenance',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');
    }

    // ── Étape B : Validation niveau 1 (Chef de quart — CDC) ──────────

    public function test_chef_de_quart_validates_level1(): void
    {
        // Instrumentiste creates security bypass
        $createResponse = $this->actingAs($this->instrumentiste)->postJson('/api/v1/requests', [
            'reason' => 'corrective_maintenance',
            'detailedJustification' => 'Remplacement capteur pression gaz défaillant',
            'urgencyLevel' => 'critical',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addHours(2)->toISOString(),
            'estimatedDuration' => 6,
            'safetyImpact' => 'high',
            'operationalImpact' => 'high',
            'environmentalImpact' => 'medium',
            'mitigationMeasures' => ['Surveillance permanente'],
            'contingencyPlan' => 'Arrêt four immédiat',
        ]);
        $requestId = $createResponse->json('data.id');

        // Chef de quart validates level 1 (CDC: court_terme + securite → chef_de_quart L1)
        $response = $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertOk();

        // Request should still be pending (needs level 2 for security equipment)
        $request = Request::find($requestId);
        $this->assertEquals('pending', $request->status);
    }

    // ── Étape C : Validation niveau 2 (Responsable HSE — CDC) ────────

    public function test_responsable_hse_validates_level2_after_level1(): void
    {
        // Create security bypass
        $createResponse = $this->actingAs($this->instrumentiste)->postJson('/api/v1/requests', [
            'reason' => 'corrective_maintenance',
            'detailedJustification' => 'Remplacement capteur pression gaz défaillant',
            'urgencyLevel' => 'critical',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addHours(2)->toISOString(),
            'estimatedDuration' => 6,
            'safetyImpact' => 'high',
            'operationalImpact' => 'high',
            'environmentalImpact' => 'medium',
            'mitigationMeasures' => ['Surveillance permanente'],
            'contingencyPlan' => 'Arrêt four immédiat',
        ]);
        $requestId = $createResponse->json('data.id');

        // Level 1: Chef de quart validates
        $this->actingAs($this->chefDeQuart)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'approved',
        ])->assertOk();

        // Level 2: Responsable HSE validates
        $response = $this->actingAs($this->responsableHse)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertOk();

        // Request should now be approved
        $request = Request::find($requestId);
        $this->assertEquals('approved', $request->status);
    }

    public function test_level2_cannot_validate_without_level1(): void
    {
        // Create security bypass
        $createResponse = $this->actingAs($this->instrumentiste)->postJson('/api/v1/requests', [
            'reason' => 'corrective_maintenance',
            'detailedJustification' => 'Remplacement capteur pression gaz défaillant',
            'urgencyLevel' => 'critical',
            'equipmentId' => $this->equipment->id,
            'sensorId' => $this->sensor->id,
            'plannedStartDate' => now()->addHours(2)->toISOString(),
            'estimatedDuration' => 6,
            'safetyImpact' => 'high',
            'operationalImpact' => 'high',
            'environmentalImpact' => 'medium',
            'mitigationMeasures' => ['Surveillance permanente'],
            'contingencyPlan' => 'Arrêt four immédiat',
        ]);
        $requestId = $createResponse->json('data.id');

        // Responsable HSE tries to validate without level 1 first
        // CDC: next pending approval requires chef_de_quart, not responsable_hse → 403
        $response = $this->actingAs($this->responsableHse)->putJson("/api/v1/requests/{$requestId}/validate", [
            'validation_status' => 'approved',
        ]);

        $response->assertStatus(403);
    }
}
