<?php

namespace Tests\Feature;

use App\Models\Cell;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ReportDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_submit_duplicate_report_for_same_date(): void
    {
        // Crear un usuario con una célula
        $cell = Cell::factory()->create();
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'leader',
            'cell_id' => $cell->id,
        ]);

        // Autenticar
        $this->actingAs($user);

        // Crear un reporte existente
        Report::create([
            'user_id' => $user->id,
            'meeting_date' => now()->format('Y-m-d'),
            'attendance_count' => 5,
            'guests_count' => 0,
            'tithes' => 10,
            'offerings' => 20,
            'score' => 10,
            'days_late' => 0,
        ]);

        // Intentar crear otro reporte para la misma fecha a través de Volt
        $component = Volt::test('reports.create')
            ->set('meeting_date', now()->format('Y-m-d'))
            ->set('tithes', 50)
            ->set('offerings', 50)
            ->set('selected_members', [1]) // Datos ficticios para pasar esa validación
            ->call('save');

        // Debería tener error de duplicado en meeting_date
        $component->assertHasErrors(['meeting_date']);

        // Asegurarse de que en la BD solo hay 1 reporte
        $this->assertEquals(1, Report::where('user_id', $user->id)->count());
    }
}
