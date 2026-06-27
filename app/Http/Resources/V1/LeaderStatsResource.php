<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cell_names' => $this->whenLoaded('cells', fn () => $this->cells->pluck('name')->toArray()),
            'compliance' => $this->compliance_percentage,
            'stars' => $this->rating,
            'assigned_converts' => 0, // Hardcoded por ahora
        ];
    }
}
