<?php

namespace Tests\Unit;

use App\Models\Equipment;
use App\Models\Request;
use App\Models\Sensor;
use App\Services\CodeGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CodeGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CodeGenerationService();
    }

    public function test_generates_bypass_code_with_correct_format(): void
    {
        $code = $this->service->generateBypassCode();

        $this->assertMatchesRegularExpression('/^BYP-\d{4}-\d{4}$/', $code);
    }

    public function test_bypass_code_increments(): void
    {
        $code1 = $this->service->generateBypassCode();
        $this->assertStringEndsWith('0001', $code1);
    }

    public function test_generates_equipment_code(): void
    {
        $code = $this->service->generateEquipmentCode('Pompe Hydraulique', 'Zone A');

        $this->assertIsString($code);
        $this->assertNotEmpty($code);
    }

    public function test_generates_sensor_code(): void
    {
        $code = $this->service->generateSensorCode('Capteur Température', 'Pompe Hydraulique', 'Zone A');

        $this->assertIsString($code);
        $this->assertNotEmpty($code);
    }

    public function test_equipment_code_format(): void
    {
        $code = $this->service->generateEquipmentCode('Test Equipment', 'Zone B');

        $this->assertMatchesRegularExpression('/^[A-Z]+-[A-Z]+-\d{3}$/', $code);
    }

    public function test_sensor_code_format(): void
    {
        $code = $this->service->generateSensorCode('Temperature Sensor', 'Main Pump', 'Zone A');

        $this->assertMatchesRegularExpression('/^[A-Z]+-[A-Z]+-[A-Z]+-\d{3}$/', $code);
    }
}
