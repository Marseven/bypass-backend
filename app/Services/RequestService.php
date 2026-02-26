<?php

namespace App\Services;

use App\Enums\BypassCriticality;
use App\Enums\DureeType;
use App\Enums\EquipmentStatus;
use App\Enums\Priority;
use App\Enums\RequestStatus;
use App\Enums\SensorStatus;
use App\Enums\ValidationStatus;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\Request;
use App\Models\RequestApproval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RequestService implements \App\Contracts\RequestServiceInterface
{
    public function __construct(
        private CodeGenerationService $codeService,
        private NotificationService $notificationService,
        private ApprovalWorkflowService $approvalWorkflowService,
    ) {}

    public function create(array $validatedData, User $requester): Request
    {
        $priority = Priority::from(strtolower($validatedData['urgencyLevel']));
        $duration = (int) $validatedData['estimatedDuration'];
        $isDraft = $validatedData['isDraft'] ?? false;

        // Auto-calculate criticite from equipment SIL
        $equipment = Equipment::find($validatedData['equipmentId']);
        $criticite = $equipment?->getBypassCriticality() ?? 'process';

        // Auto-calculate duree_type
        $dureeType = DureeType::fromDurationHours($duration)->value;

        $requestData = [
            'request_code' => $this->codeService->generateBypassCode(),
            'requester_id' => $requester->id,
            'title' => $validatedData['reason'],
            'description' => $validatedData['detailedJustification'],
            'priority' => $priority->value,
            'equipment_id' => $validatedData['equipmentId'],
            'sensor_id' => $validatedData['sensorId'],
            'status' => $isDraft ? RequestStatus::Draft->value : RequestStatus::Pending->value,
            'bypass_type' => $validatedData['bypassType'] ?? null,
            'criticite' => $criticite,
            'duree_type' => $dureeType,
            'submitted_at' => $isDraft ? null : now(),
            'validation_required_by_role' => $priority->validationRole(),
            'start_time' => $validatedData['plannedStartDate'],
            'end_time' => Carbon::parse($validatedData['plannedStartDate'])->addHours($duration),
            'impact_securite' => $validatedData['safetyImpact'],
            'impact_operationnel' => $validatedData['operationalImpact'],
            'impact_environnemental' => $validatedData['environmentalImpact'],
            'mesure_attenuation' => is_array($validatedData['mitigationMeasures'])
                ? implode(', ', $validatedData['mitigationMeasures'])
                : $validatedData['mitigationMeasures'],
            'plan_contingence' => $validatedData['contingencyPlan'] ?? null,
        ];

        // Legacy dual validation
        if ($priority->requiresDualValidation()) {
            $requestData['validation_status_level1'] = ValidationStatus::Pending->value;
            $requestData['validation_status_level2'] = ValidationStatus::Pending->value;
        }

        $bypassRequest = Request::create($requestData);

        // CDC: create approval workflow steps (only for non-draft)
        if (!$isDraft) {
            $this->approvalWorkflowService->createApprovalSteps($bypassRequest);
        }

        AuditLog::log(
            $isDraft ? 'Request Draft Created' : 'Request Created',
            $requester,
            'Request',
            $bypassRequest->id,
            ['title' => $bypassRequest->title, 'priority' => $bypassRequest->priority]
        );

        if (!$isDraft) {
            $this->notificationService->notifyRequestCreated($bypassRequest);
        }

        self::clearDashboardCache();

        return $bypassRequest;
    }

    /**
     * CDC: submit a draft → pending
     */
    public function submitDraft(Request $request, User $user): Request
    {
        if ($request->status !== RequestStatus::Draft->value) {
            throw new HttpResponseException(response()->json(['message' => 'Seuls les brouillons peuvent être soumis'], 422));
        }

        $request->update([
            'status' => RequestStatus::Pending->value,
            'submitted_at' => now(),
        ]);

        $this->approvalWorkflowService->createApprovalSteps($request);

        AuditLog::log('Request Submitted', $user, 'Request', $request->id, ['title' => $request->title]);

        $request->refresh();
        $this->notificationService->notifyRequestCreated($request);

        self::clearDashboardCache();

        return $request;
    }

    /**
     * CDC: activate an approved bypass → active
     */
    public function activateApprovedBypass(Request $request, User $user): Request
    {
        if ($request->status !== RequestStatus::Approved->value) {
            throw new HttpResponseException(response()->json(['message' => 'Seules les demandes approuvées peuvent être activées'], 422));
        }

        $request->update(['status' => RequestStatus::Active->value]);

        $this->activateBypass($request, $user);

        AuditLog::log('Bypass Activated', $user, 'Request', $request->id, ['title' => $request->title]);

        self::clearDashboardCache();

        return $request->refresh();
    }

    /**
     * CDC: close an active bypass → closed
     */
    public function closeBypass(Request $request, User $user): Request
    {
        if ($request->status !== RequestStatus::Active->value) {
            throw new HttpResponseException(response()->json(['message' => 'Seuls les bypass actifs peuvent être clôturés'], 422));
        }

        $request->update(['status' => RequestStatus::Closed->value]);

        $this->restoreEquipmentAndSensor($request, $user);

        AuditLog::log('Bypass Closed', $user, 'Request', $request->id, ['title' => $request->title]);

        self::clearDashboardCache();

        return $request->refresh();
    }

    public function validateRequest(Request $request, array $data, User $validator): Request
    {
        // CDC: try new approval workflow first
        if ($request->approvals->isNotEmpty()) {
            return $this->handleCdcValidation($request, $data, $validator);
        }

        // Legacy fallback
        $requiresDualValidation = $request->requiresDualValidation();

        $result = $requiresDualValidation
            ? $this->handleDualValidation($request, $data, $validator)
            : $this->handleSimpleValidation($request, $data, $validator);

        self::clearDashboardCache();

        return $result;
    }

    // ── CDC Approval Workflow ────────────────────────────────────

    private function handleCdcValidation(Request $request, array $data, User $validator): Request
    {
        $nextApproval = $request->nextPendingApproval();

        if (!$nextApproval) {
            throw new HttpResponseException(response()->json(['message' => 'Aucune approbation en attente'], 422));
        }

        if (!$this->approvalWorkflowService->canUserApprove($validator, $nextApproval)) {
            throw new HttpResponseException(response()->json(['message' => "Non autorisé : rôle '{$nextApproval->required_role}' requis"], 403));
        }

        $nextApproval->update([
            'approved_by_id' => $validator->id,
            'approved_at' => now(),
            'status' => $data['validation_status'],
            'rejection_reason' => $data['validation_status'] === 'rejected'
                ? ($data['rejection_reason'] ?? null)
                : null,
        ]);

        AuditLog::log(
            'Request Approval Level ' . $nextApproval->level . ' ' . ucfirst($data['validation_status']),
            $validator,
            'Request',
            $request->id,
            [
                'title' => $request->title,
                'level' => $nextApproval->level,
                'role' => $nextApproval->required_role,
                'rejection_reason' => $data['rejection_reason'] ?? null,
            ]
        );

        $request->refresh();
        $request->load('approvals');

        if ($data['validation_status'] === 'rejected') {
            $request->update(['status' => RequestStatus::Rejected->value]);
            $request->refresh();
            $this->notificationService->notifyValidationResult($request, 'rejected', $data['rejection_reason'] ?? null, $nextApproval->level);
        } elseif ($request->allApprovalsComplete()) {
            $request->update([
                'status' => RequestStatus::Approved->value,
                'validated_by_id' => $validator->id,
                'validated_at' => now(),
            ]);
            $request->refresh();
            $this->notificationService->notifyValidationResult($request, 'approved', null, $nextApproval->level);
        } else {
            $this->notificationService->notifyValidationResult($request, 'approved', null, $nextApproval->level);
        }

        self::clearDashboardCache();

        return $request;
    }

    // ── Legacy Validation ────────────────────────────────────────

    private function handleSimpleValidation(Request $request, array $data, User $validator): Request
    {
        if (!$validator->canValidateRequests()) {
            throw new HttpResponseException(response()->json(['message' => 'Non autorisé à valider'], 403));
        }

        $request->update([
            'status' => $data['validation_status'],
            'validated_by_id' => $validator->id,
            'validated_at' => now(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        AuditLog::log(
            'Request ' . ucfirst($data['validation_status']),
            $validator,
            'Request',
            $request->id,
            ['title' => $request->title, 'rejection_reason' => $data['rejection_reason'] ?? null]
        );

        if ($data['validation_status'] === ValidationStatus::Approved->value) {
            $this->activateBypass($request, $validator);
        }

        $request->refresh();
        $this->notificationService->notifyValidationResult($request, $data['validation_status'], $data['rejection_reason'] ?? null);

        return $request;
    }

    private function handleDualValidation(Request $request, array $data, User $validator): Request
    {
        if ($validator->canValidateLevel1() && !$validator->canValidateLevel2()) {
            return $this->handleLevel1Validation($request, $data, $validator);
        }

        if ($validator->canValidateLevel2()) {
            return $this->handleLevel2Validation($request, $data, $validator);
        }

        throw new HttpResponseException(response()->json(['message' => 'Non autorisé à valider cette demande'], 403));
    }

    private function handleLevel1Validation(Request $request, array $data, User $validator): Request
    {
        $request->update([
            'validated_by_level1_id' => $validator->id,
            'validated_at_level1' => now(),
            'validation_status_level1' => $data['validation_status'],
            'rejection_reason_level1' => $data['validation_status'] === ValidationStatus::Rejected->value
                ? ($data['rejection_reason'] ?? null)
                : null,
        ]);

        AuditLog::log(
            'Request Validation Level 1 ' . ucfirst($data['validation_status']),
            $validator,
            'Request',
            $request->id,
            ['title' => $request->title, 'level' => 1, 'rejection_reason' => $data['rejection_reason'] ?? null]
        );

        if ($data['validation_status'] === ValidationStatus::Rejected->value) {
            $request->update(['status' => RequestStatus::Rejected->value]);
            $request->refresh();
            $this->notificationService->notifyValidationResult($request, $data['validation_status'], $data['rejection_reason'] ?? null, 1);
            return $request;
        }

        if ($data['validation_status'] === ValidationStatus::Approved->value) {
            $request->refresh();
            $this->notificationService->notifyLevel1Approved($request);
        }

        return $request;
    }

    private function handleLevel2Validation(Request $request, array $data, User $validator): Request
    {
        if ($request->validation_status_level1 !== ValidationStatus::Approved->value) {
            throw new HttpResponseException(response()->json(['message' => 'La validation niveau 1 (supervisor) doit être approuvée avant la validation niveau 2'], 422));
        }

        $request->update([
            'validated_by_level2_id' => $validator->id,
            'validated_at_level2' => now(),
            'validation_status_level2' => $data['validation_status'],
            'rejection_reason_level2' => $data['validation_status'] === ValidationStatus::Rejected->value
                ? ($data['rejection_reason'] ?? null)
                : null,
        ]);

        AuditLog::log(
            'Request Validation Level 2 ' . ucfirst($data['validation_status']),
            $validator,
            'Request',
            $request->id,
            ['title' => $request->title, 'level' => 2, 'rejection_reason' => $data['rejection_reason'] ?? null]
        );

        if ($data['validation_status'] === ValidationStatus::Rejected->value) {
            $request->update(['status' => RequestStatus::Rejected->value]);
            $request->refresh();
            $this->notificationService->notifyValidationResult($request, $data['validation_status'], $data['rejection_reason'] ?? null, 2);
            return $request;
        }

        if ($data['validation_status'] === ValidationStatus::Approved->value) {
            $request->update([
                'status' => RequestStatus::Approved->value,
                'validated_by_id' => $validator->id,
                'validated_at' => now(),
            ]);

            $this->activateBypass($request, $validator);
            $request->refresh();
            $this->notificationService->notifyValidationResult($request, $data['validation_status'], null, 2);
        }

        return $request;
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function notifyUpdate(Request $request): void
    {
        $this->notificationService->notifyRequestUpdated($request);
    }

    public static function clearDashboardCache(): void
    {
        Cache::forget('dashboard:summary');
        Cache::forget('dashboard:recent_requests');
        Cache::forget('dashboard:system_status');
        Cache::forget('dashboard:top_sensors');
    }

    private function activateBypass(Request $request, User $validator): void
    {
        $sensor = $request->sensor;
        $equipment = $request->equipment;

        if (!$sensor) {
            return;
        }

        $sensor->update(['status' => SensorStatus::Bypassed->value]);
        $equipment?->update(['status' => EquipmentStatus::Maintenance->value]);

        AuditLog::log(
            'Sensor Deactivated and Equipment deactivated',
            $validator,
            'Sensor/Equipment',
            $sensor->id,
            ['reason' => 'Bypass request approved', 'request_id' => $request->id]
        );
    }

    private function restoreEquipmentAndSensor(Request $request, User $user): void
    {
        $sensor = $request->sensor;
        $equipment = $request->equipment;

        if ($sensor) {
            $sensor->update(['status' => SensorStatus::Active->value]);
        }
        if ($equipment) {
            $equipment->update(['status' => EquipmentStatus::Operational->value]);
        }

        AuditLog::log(
            'Sensor Reactivated and Equipment restored',
            $user,
            'Sensor/Equipment',
            $sensor?->id,
            ['reason' => 'Bypass closed', 'request_id' => $request->id]
        );
    }
}
