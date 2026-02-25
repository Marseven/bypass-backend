<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'name' => $this->name,
            'description' => $this->description,
            'location' => $this->location,
            'site' => new SiteResource($this->whenLoaded('site')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
