<?php

namespace App\Http\Resources;

use App\Enums\SilLevel;
use App\Enums\SystemType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'type_systeme' => $this->type_systeme,
            'type_systeme_label' => SystemType::tryFrom($this->type_systeme)?->label() ?? $this->type_systeme,
            'niveau_sil' => $this->niveau_sil,
            'niveau_sil_label' => SilLevel::tryFrom($this->niveau_sil)?->label() ?? $this->niveau_sil,
            'fonction_securite' => $this->fonction_securite,
            'is_security_equipment' => $this->isSecurityEquipment(),
            'criticite' => $this->criticite,
            'fabricant' => $this->fabricant,
            'description' => $this->description,
            'status' => $this->status,
            'zone_id' => $this->zone_id,
            'zone' => new ZoneResource($this->whenLoaded('zone')),
            'sensors' => SensorResource::collection($this->whenLoaded('sensors')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
