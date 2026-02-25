<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,
            'dangers_identifies' => $this->dangers_identifies,
            'mesures_compensatoires' => $this->mesures_compensatoires,
            'ipl_affectees' => $this->ipl_affectees,
            'validee_par' => new UserResource($this->whenLoaded('validateurPar')),
            'date_validation' => $this->date_validation?->toISOString(),
            'statut_validation' => $this->statut_validation,
            'motif_rejet' => $this->motif_rejet,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
