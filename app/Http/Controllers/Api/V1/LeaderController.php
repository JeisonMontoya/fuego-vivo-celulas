<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchLeaderRequest;
use App\Http\Resources\V1\LeaderResource;
use App\Http\Resources\V1\LeaderStatsResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class LeaderController extends Controller
{
    /**
     * Display a listing of active leaders.
     */
    public function index()
    {
        $leaders = User::with('cell')->where('role', 'leader')->where('status', 'active')->paginate(15);

        return LeaderResource::collection($leaders)->additional(['success' => true]);
    }

    /**
     * Display the specified leader.
     */
    public function show(string $id)
    {
        $leader = User::with('cell')->where('role', 'leader')->findOrFail($id);

        return (new LeaderResource($leader))->additional(['success' => true]);
    }

    /**
     * Search for a leader by name.
     */
    public function search(SearchLeaderRequest $request): JsonResponse
    {
        $query = $request->validated('q');

        $leaders = User::where('role', 'leader')
            ->where('status', 'active')
            ->where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $leaders->map(fn ($leader) => [
                'id' => $leader->id,
                'name' => $leader->name,
            ]),
        ]);
    }

    /**
     * Get available leaders for consolidation.
     */
    public function available(): JsonResponse
    {
        $leaders = User::where('role', 'leader')
            ->where('status', 'active')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $leaders->map(fn ($leader) => [
                'id' => $leader->id,
                'name' => $leader->name,
                'assigned_converts' => 0, // Hardcoded for now
            ]),
        ]);
    }

    /**
     * Get leaders with their stats.
     */
    public function stats()
    {
        $leaders = User::with('cell')->where('role', 'leader')->where('status', 'active')->get();

        return LeaderStatsResource::collection($leaders)->additional(['success' => true]);
    }

    /**
     * Validate if a leader is active and can receive converts.
     */
    public function validateLeader(string $id): JsonResponse
    {
        $leader = User::where('role', 'leader')->find($id);

        if (! $leader) {
            return response()->json([
                'success' => false,
                'message' => 'Líder no encontrado',
            ], 404);
        }

        $isActive = $leader->isActive();

        return response()->json([
            'success' => true,
            'active' => $isActive,
            'can_receive_converts' => $isActive,
        ]);
    }
}
