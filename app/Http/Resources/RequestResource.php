<?php

namespace App\Http\Resources;

use App\Enums\BypassCriticality;
use App\Enums\BypassType;
use App\Enums\DureeType;
use App\Enums\Priority;
use App\Enums\RequestReason;
use App\Enums\RequestStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_code' => $this->request_code,
            'title' => $this->title,
            'title_label' => RequestReason::tryFrom($this->title)?->label() ?? $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'priority_label' => Priority::tryFrom($this->priority)?->label() ?? $this->priority,
            'status' => $this->status,
            'status_label' => RequestStatus::tryFrom($this->status)?->label() ?? $this->status,

            // CDC fields
            'bypass_type' => $this->bypass_type,
            'bypass_type_label' => BypassType::tryFrom($this->bypass_type ?? '')?->label(),
            'criticite' => $this->criticite,
            'criticite_label' => BypassCriticality::tryFrom($this->criticite ?? '')?->label(),
            'duree_type' => $this->duree_type,
            'duree_type_label' => DureeType::tryFrom($this->duree_type ?? '')?->label(),
            'requires_ora' => $this->requiresOra(),
            'requires_moc' => $this->requires_moc,

            // Impacts
            'impact_securite' => $this->impact_securite,
            'impact_operationnel' => $this->impact_operationnel,
            'impact_environnemental' => $this->impact_environnemental,
            'mesure_attenuation' => $this->mesure_attenuation,
            'plan_contingence' => $this->plan_contingence,
            'commentaires' => $this->commentaires,
            'validation_required_by_role' => $this->validation_required_by_role,
            'rejection_reason' => $this->rejection_reason,

            // Legacy dual validation
            'validation_status_level1' => $this->validation_status_level1,
            'rejection_reason_level1' => $this->rejection_reason_level1,
            'validation_status_level2' => $this->validation_status_level2,
            'rejection_reason_level2' => $this->rejection_reason_level2,

            // Timestamps
            'submitted_at' => $this->submitted_at?->toISOString(),
            'validated_at' => $this->validated_at?->toISOString(),
            'validated_at_level1' => $this->validated_at_level1?->toISOString(),
            'validated_at_level2' => $this->validated_at_level2?->toISOString(),
            'start_time' => $this->start_time?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'requester' => new UserResource($this->whenLoaded('requester')),
            'validator' => new UserResource($this->whenLoaded('validator')),
            'validator_level1' => new UserResource($this->whenLoaded('validatorLevel1')),
            'validator_level2' => new UserResource($this->whenLoaded('validatorLevel2')),
            'equipment' => new EquipmentResource($this->whenLoaded('equipment')),
            'sensor' => new SensorResource($this->whenLoaded('sensor')),
            'ora' => new OraResource($this->whenLoaded('ora')),
            'approvals' => RequestApprovalResource::collection($this->whenLoaded('approvals')),
        ];
    }
}
