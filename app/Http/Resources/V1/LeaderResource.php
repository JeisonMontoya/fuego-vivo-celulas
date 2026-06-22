<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'cell' => $this->whenLoaded('cell', function () {
                return [
                    'id' => $this->cell->id,
                    'name' => $this->cell->name,
                    'meeting_day' => $this->cell->meeting_day,
                    'meeting_time' => $this->cell->meeting_time,
                ];
            }),
            'assigned_converts' => 0, // Hardcoded por ahora según plan
        ];
    }
}
