<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Líder de Prueba')
            ->set('document', '1234567890')
            ->set('email', 'lider.prueba@example.com')
            ->set('phone', '555-1234')
            ->set('address', 'Calle Falsa 123')
            ->set('sector', 'Monserrate')
            ->set('cell_name', 'Célula Juvenil Monserrate')
            ->set('cell_address', 'Carrera 15 # 10-20')
            ->set('cell_meeting_day', 'Viernes')
            ->set('cell_meeting_time', '7:00 PM')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        // Verificar que la célula se haya creado en la base de datos
        $this->assertDatabaseHas('cells', [
            'name' => 'Célula Juvenil Monserrate',
            'address' => 'Carrera 15 # 10-20',
            'meeting_day' => 'Viernes',
            'meeting_time' => '7:00 PM',
            'status' => 'active',
        ]);

        // Verificar que el usuario se haya creado en la base de datos con los datos correctos
        $this->assertDatabaseHas('users', [
            'name' => 'Líder de Prueba',
            'document' => '1234567890',
            'email' => 'lider.prueba@example.com',
            'phone' => '555-1234',
            'address' => 'Calle Falsa 123',
            'sector' => 'Monserrate',
            'role' => 'leader',
            'status' => 'pending', // Registro pendiente
        ]);

        // Obtener el usuario recién registrado
        $user = User::where('email', 'lider.prueba@example.com')->first();
        $this->assertNotNull($user->cell_id);

        // Verificar que, al estar pendiente, al intentar acceder al dashboard es redirigido a activation/pending
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('activation.pending'));
    }
}
