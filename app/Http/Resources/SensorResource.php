<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SensorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'unite' => $this->unite,
            'seuil_critique' => $this->seuil_critique,
            'status' => $this->status,
            'last_reading' => $this->last_reading,
            'last_reading_at' => $this->last_reading_at?->toISOString(),
            'Dernier_Etallonnage' => $this->Dernier_Etallonnage,
            'equipment_id' => $this->equipment_id,
            'equipment' => new EquipmentResource($this->whenLoaded('equipment')),
            'bypass_history' => $this->whenLoaded('requests', fn () =>
                $this->requests->map(fn ($r) => [
                    'id' => $r->id,
                    'bypass_code' => $r->bypass_code,
                    'status' => $r->status,
                    'start_time' => $r->start_time?->toISOString(),
                    'end_time' => $r->end_time?->toISOString(),
                    'reason' => $r->reason,
                    'requester_name' => $r->requester?->name,
                    'created_at' => $r->created_at->toISOString(),
                ])
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
