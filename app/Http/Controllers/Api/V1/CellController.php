<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CellResource;
use App\Models\Cell;

class CellController extends Controller
{
    /**
     * Display the specified cell.
     */
    public function show(string $id)
    {
        $cell = Cell::with(['leaders' => function ($query) {
            $query->where('role', 'leader')->where('status', 'active');
        }])->findOrFail($id);

        return (new CellResource($cell))->additional(['success' => true]);
    }
}
