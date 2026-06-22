<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CellResource extends JsonResource
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
            'meeting_day' => $this->meeting_day,
            'meeting_time' => $this->meeting_time,
            'leader' => $this->whenLoaded('leaders', function () {
                $mainLeader = $this->leaders->where('role', 'leader')->first();
                if ($mainLeader) {
                    return [
                        'id' => $mainLeader->id,
                        'name' => $mainLeader->name,
                    ];
                }

                return null;
            }),
        ];
    }
}
