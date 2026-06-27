<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an active user can access the dashboard.
     */
    public function test_active_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    /**
     * Test that a pending user is redirected to the pending activation page.
     */
    public function test_pending_user_is_redirected_to_activation_pending(): void
    {
        $user = User::factory()->pending()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('activation.pending'));
    }

    /**
     * Test that an inactive user is redirected to the inactive page.
     */
    public function test_inactive_user_is_redirected_to_activation_inactive(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('activation.inactive'));
    }

    /**
     * Test that an admin user can access the dashboard.
     */
    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Test that users on the pending page can logout.
     */
    public function test_pending_user_can_logout(): void
    {
        $user = User::factory()->pending()->create();

        $this->actingAs($user);

        $component = Volt::test('activation.pending');

        $component->call('logout');

        $component->assertRedirect('/');
        $this->assertGuest();
    }
}
