<?php

use App\Models\Report;
use App\Models\CellMember;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    #[Validate('required|date|before_or_equal:today')]
    public $meeting_date = '';

    #[Validate('nullable|string|max:255')]
    public $host_name = '';

    #[Validate('required|numeric|min:0')]
    public $tithes = 0;

    #[Validate('required|numeric|min:0')]
    public $offerings = 0;

    // guests_count se eliminó porque los invitados ahora se registran exclusivamente desde el módulo de Miembros.

    #[Validate('nullable|string|max:1000')]
    public $notes = '';

    public $members = [];
    public $selected_members = [];

    public function mount()
    {
        $this->meeting_date = now()->format('Y-m-d');
        // Pre-fill the host name with the leader's name or cell info as a default, or leave blank
        $this->members = auth()->user()->cell->members()->orderBy('name')->get();
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();
        
        // Evitar reportes duplicados para la misma fecha y usuario
        if (Report::where('user_id', $user->id)->whereDate('meeting_date', $this->meeting_date)->exists()) {
            $this->addError('meeting_date', 'Ya has enviado un reporte para esta fecha.');
            return;
        }
        
        $attendance_count = count($this->selected_members);

        if ($attendance_count === 0) {
            $this->addError('selected_members', 'Debes marcar al menos un asistente en la lista.');
            return;
        }

        // Scoring algorithm (El plazo es hasta el final del día siguiente a la reunión)
        $deadlineDay = \Carbon\Carbon::parse($this->meeting_date)->addDay()->endOfDay();
        $now = now();

        if ($now->lessThanOrEqualTo($deadlineDay)) {
            $daysLate = 0;
            $score = 10;
        } else {
            $daysLate = $deadlineDay->startOfDay()->diffInDays($now->startOfDay());
            if ($daysLate == 1) $score = 8;
            elseif ($daysLate == 2) $score = 6;
            elseif ($daysLate == 3) $score = 4;
            elseif ($daysLate == 4) $score = 2;
            else $score = 0;
        }

        // Create the report
        $report = new Report([
            'meeting_date' => $this->meeting_date,
            'attendance_count' => $attendance_count,
            'guests_count' => 0, // Obsoleto, fijado a 0
            'notes' => $this->notes,
            'host_name' => $this->host_name,
            'tithes' => $this->tithes,
            'offerings' => $this->offerings,
            'score' => $score,
            'days_late' => $daysLate,
        ]);

        $user->reports()->save($report);
        
        // Sync the attended members
        $report->attendees()->sync($this->selected_members);

        // Recalculate metrics
        $user->recalculateMetrics();

        // Send email notification (queued)
        $user->notify(new \App\Notifications\ReportSubmittedNotification($report));

        session()->flash('status', 'Reporte enviado con éxito.');

        $this->redirectRoute('dashboard', navigate: true);
    }
}; ?>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Nuevo Reporte de Célula (Celugrama)</h2>
                    <p class="text-gray-600 text-sm mt-1">Completa los datos de tu reunión. Marca a los miembros que asistieron.</p>
                </div>

                <form wire:submit="save" class="space-y-8">
                    
                    <!-- Sección 1: Datos de la Reunión -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">1. Datos Generales y Finanzas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="meeting_date" value="Fecha de la reunión *" />
                                <x-text-input wire:model="meeting_date" id="meeting_date" type="date" class="mt-1 block w-full" required max="{{ now()->format('Y-m-d') }}" />
                                <x-input-error :messages="$errors->get('meeting_date')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="host_name" value="Nombre del Anfitrión (Opcional)" />
                                <x-text-input wire:model="host_name" id="host_name" type="text" class="mt-1 block w-full" placeholder="Casa donde se reunieron" />
                                <x-input-error :messages="$errors->get('host_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tithes" value="Diezmos Recogidos ($) *" />
                                <x-text-input wire:model="tithes" id="tithes" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('tithes')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="offerings" value="Ofrendas Recogidas ($) *" />
                                <x-text-input wire:model="offerings" id="offerings" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('offerings')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Asistencia -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">2. Asistencia (Celugrama Digital)</h3>
                        
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Selecciona los miembros de tu célula que asistieron a esta reunión:</p>
                            @if(count($members) > 0)
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-md border border-gray-200">
                                    @foreach($members as $member)
                                        <label class="flex items-start space-x-3 p-2 hover:bg-gray-100 rounded cursor-pointer transition-colors">
                                            <input type="checkbox" wire:model="selected_members" value="{{ $member->id }}" class="mt-1 rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-medium text-gray-900">{{ $member->name }}</span>
                                                <span class="text-xs text-gray-500">{{ $member->phone ?? 'Sin teléfono' }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-4 bg-yellow-50 text-yellow-800 rounded-md text-sm border border-yellow-200">
                                    No tienes miembros registrados en tu célula. 
                                    <a href="{{ route('members.index') }}" class="font-bold underline" wire:navigate>Ve a "Mis Miembros"</a> para añadirlos antes de poder enviar un reporte.
                                </div>
                            @endif
                        </div>

                        @error('selected_members')
                            <p class="text-sm text-red-600 font-bold mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Sección 3: Observaciones -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">3. Observaciones y Peticiones</h3>
                        <div>
                            <x-input-label for="notes" value="Peticiones de oración, testimonios o comentarios (Opcional)" />
                            <textarea wire:model="notes" id="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm"></textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 pt-4 border-t border-gray-200 gap-4">
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500" wire:navigate>
                            Cancelar
                        </a>
                        <x-primary-button wire:loading.attr="disabled">
                            <span wire:loading.remove>Enviar Reporte</span>
                            <span wire:loading>Enviando...</span>
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
