<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_api(): void
    {
        $response = $this->getJson('/api/v1/leaders');
        $response->assertStatus(401);
    }

    public function test_authenticated_system_can_get_leaders(): void
    {
        $systemUser = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($systemUser, ['*']);

        User::factory()->count(3)->create([
            'role' => 'leader',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/leaders');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_search_leaders(): void
    {
        $systemUser = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($systemUser, ['*']);

        User::factory()->create(['name' => 'Juan Pérez', 'role' => 'leader', 'status' => 'active']);
        User::factory()->create(['name' => 'Carlos López', 'role' => 'leader', 'status' => 'active']);

        $response = $this->getJson('/api/v1/leaders/search?q=Juan');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Juan Pérez');
    }
}
