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
            'cells' => $this->whenLoaded('cells', function () {
                return $this->cells->map(function ($cell) {
                    return [
                        'id' => $cell->id,
                        'name' => $cell->name,
                        'meeting_day' => $cell->meeting_day,
                        'meeting_time' => $cell->meeting_time,
                    ];
                });
            }),
            'assigned_converts' => 0, // Hardcoded por ahora según plan
        ];
    }
}
