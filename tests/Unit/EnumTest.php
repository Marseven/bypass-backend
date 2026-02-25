<?php

namespace Tests\Unit;

use App\Enums\EquipmentStatus;
use App\Enums\ImpactLevel;
use App\Enums\Priority;
use App\Enums\RequestReason;
use App\Enums\RequestStatus;
use App\Enums\SensorStatus;
use App\Enums\ValidationStatus;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function test_request_status_has_all_values(): void
    {
        $values = array_column(RequestStatus::cases(), 'value');

        $this->assertContains('draft', $values);
        $this->assertContains('pending', $values);
        $this->assertContains('approved', $values);
        $this->assertContains('active', $values);
        $this->assertContains('closed', $values);
        $this->assertContains('expired', $values);
        $this->assertContains('rejected', $values);
    }

    public function test_request_status_labels_are_french(): void
    {
        $this->assertEquals('En attente', RequestStatus::Pending->label());
        $this->assertEquals('Approuvé', RequestStatus::Approved->label());
        $this->assertEquals('Rejeté', RequestStatus::Rejected->label());
        $this->assertEquals('Brouillon', RequestStatus::Draft->label());
        $this->assertEquals('Actif', RequestStatus::Active->label());
        $this->assertEquals('Clôturé', RequestStatus::Closed->label());
        $this->assertEquals('Expiré', RequestStatus::Expired->label());
    }

    public function test_priority_labels(): void
    {
        $this->assertEquals('Faible', Priority::Low->label());
        $this->assertEquals('Normale', Priority::Normal->label());
        $this->assertEquals('Critique', Priority::Critical->label());
        $this->assertEquals('Urgence', Priority::Emergency->label());
    }

    public function test_priority_requires_dual_validation(): void
    {
        $this->assertFalse(Priority::Low->requiresDualValidation());
        $this->assertFalse(Priority::Normal->requiresDualValidation());
        $this->assertFalse(Priority::High->requiresDualValidation());
        $this->assertTrue(Priority::Critical->requiresDualValidation());
        $this->assertTrue(Priority::Emergency->requiresDualValidation());
    }

    public function test_priority_validation_role(): void
    {
        $this->assertEquals('supervisor', Priority::Low->validationRole());
        $this->assertEquals('supervisor', Priority::Normal->validationRole());
        $this->assertEquals('supervisor', Priority::High->validationRole());
        $this->assertEquals('administrator', Priority::Critical->validationRole());
        $this->assertEquals('administrator', Priority::Emergency->validationRole());
    }

    public function test_impact_level_has_all_values(): void
    {
        $values = array_column(ImpactLevel::cases(), 'value');

        $this->assertContains('very_low', $values);
        $this->assertContains('low', $values);
        $this->assertContains('medium', $values);
        $this->assertContains('high', $values);
        $this->assertContains('very_high', $values);
    }

    public function test_equipment_status_values(): void
    {
        $this->assertEquals('operational', EquipmentStatus::Operational->value);
        $this->assertEquals('maintenance', EquipmentStatus::Maintenance->value);
        $this->assertEquals('down', EquipmentStatus::Down->value);
        $this->assertEquals('standby', EquipmentStatus::Standby->value);
    }

    public function test_sensor_status_values(): void
    {
        $this->assertEquals('active', SensorStatus::Active->value);
        $this->assertEquals('bypassed', SensorStatus::Bypassed->value);
        $this->assertEquals('maintenance', SensorStatus::Maintenance->value);
    }

    public function test_validation_status_values(): void
    {
        $this->assertEquals('pending', ValidationStatus::Pending->value);
        $this->assertEquals('approved', ValidationStatus::Approved->value);
        $this->assertEquals('rejected', ValidationStatus::Rejected->value);
    }

    public function test_request_reason_labels_are_french(): void
    {
        $this->assertEquals('Maintenance préventive', RequestReason::PreventiveMaintenance->label());
        $this->assertEquals('Étalonnage', RequestReason::Calibration->label());
        $this->assertEquals('Réparation d\'urgence', RequestReason::EmergencyRepair->label());
    }

    public function test_request_reason_try_from_valid(): void
    {
        $reason = RequestReason::tryFrom('preventive_maintenance');
        $this->assertNotNull($reason);
        $this->assertEquals(RequestReason::PreventiveMaintenance, $reason);
    }

    public function test_request_reason_try_from_invalid(): void
    {
        $reason = RequestReason::tryFrom('nonexistent');
        $this->assertNull($reason);
    }
}
