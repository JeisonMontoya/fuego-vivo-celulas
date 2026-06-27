<?php

use App\Models\Cell;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $cells;
    public bool $isEditing = false;
    public bool $isCreating = false;
    public ?int $cell_id = null;
    
    #[Validate('required|string|max:255')]
    public string $name = '';
    
    #[Validate('required|string|max:255')]
    public string $address = '';
    
    #[Validate('required|string|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo')]
    public string $meeting_day = 'Viernes';
    
    #[Validate('required|string')]
    public string $meeting_time = '19:00';

    public function mount()
    {
        $this->loadCells();
    }

    public function loadCells()
    {
        $this->cells = auth()->user()->cells()->orderBy('name')->get();
    }

    public function resetForm()
    {
        $this->isEditing = false;
        $this->isCreating = false;
        $this->cell_id = null;
        $this->name = '';
        $this->address = '';
        $this->meeting_day = 'Viernes';
        $this->meeting_time = '19:00';
        $this->resetValidation();
    }

    public function createCell()
    {
        $this->resetForm();
        $this->isCreating = true;
    }

    public function editCell($id)
    {
        $this->resetForm();
        $cell = auth()->user()->cells()->find($id);
        if ($cell) {
            $this->isEditing = true;
            $this->cell_id = $cell->id;
            $this->name = $cell->name;
            $this->address = $cell->address;
            $this->meeting_day = $cell->meeting_day;
            $this->meeting_time = date('H:i', strtotime($cell->meeting_time));
        }
    }

    public function save()
    {
        $this->validate();

        if ($this->isEditing && $this->cell_id) {
            $cell = auth()->user()->cells()->find($this->cell_id);
            if ($cell) {
                $cell->update([
                    'name' => $this->name,
                    'address' => $this->address,
                    'meeting_day' => $this->meeting_day,
                    'meeting_time' => $this->meeting_time,
                ]);
                session()->flash('status', 'Célula actualizada con éxito.');
            }
        } else {
            auth()->user()->cells()->create([
                'name' => $this->name,
                'address' => $this->address,
                'meeting_day' => $this->meeting_day,
                'meeting_time' => $this->meeting_time,
            ]);
            session()->flash('status', 'Célula creada con éxito.');
        }

        $this->loadCells();
        auth()->user()->recalculateMetrics();
        $this->resetForm();
    }

    public function deleteCell($id)
    {
        $cell = auth()->user()->cells()->find($id);
        if ($cell) {
            $cell->delete();
            $this->loadCells();
            auth()->user()->recalculateMetrics();
            session()->flash('status', 'Célula eliminada.');
        }
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Header Section inside component -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                {{ __('Gestión de Células') }}
            </h2>
            <div class="flex items-center gap-3">
                <button type="button" wire:click="createCell" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    + Nueva Célula
                </button>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150" wire:navigate>
                    Volver
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm mb-6 flex items-center">
                <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <p class="font-medium">{{ session('status') }}</p>
            </div>
        @endif

        @if($isCreating || $isEditing)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">{{ $isEditing ? 'Editar Célula' : 'Crear Nueva Célula' }}</h3>
                    
                    <form wire:submit="save" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nombre -->
                            <div>
                                <x-input-label for="name" value="Nombre de la Célula *" />
                                <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <!-- Dirección -->
                            <div>
                                <x-input-label for="address" value="Dirección o Ubicación *" />
                                <x-text-input wire:model="address" id="address" type="text" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>

                            <!-- Día -->
                            <div>
                                <x-input-label for="meeting_day" value="Día de Reunión *" />
                                <select wire:model="meeting_day" id="meeting_day" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="Lunes">Lunes</option>
                                    <option value="Martes">Martes</option>
                                    <option value="Miércoles">Miércoles</option>
                                    <option value="Jueves">Jueves</option>
                                    <option value="Viernes">Viernes</option>
                                    <option value="Sábado">Sábado</option>
                                    <option value="Domingo">Domingo</option>
                                </select>
                                <x-input-error :messages="$errors->get('meeting_day')" class="mt-2" />
                            </div>

                            <!-- Hora -->
                            <div>
                                <x-input-label for="meeting_time" value="Hora de Reunión *" />
                                <x-text-input wire:model="meeting_time" id="meeting_time" type="time" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('meeting_time')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Guardar</x-primary-button>
                            <button type="button" wire:click="resetForm" class="text-sm text-gray-600 hover:text-gray-900 underline">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                @if($cells->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-gray-500">No tienes células creadas todavía.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($cells as $cell)
                            <div class="border border-gray-200 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow bg-gray-50">
                                <div class="flex justify-between items-start mb-4">
                                    <h4 class="font-bold text-lg text-indigo-700">{{ $cell->name }}</h4>
                                    <div class="flex gap-2">
                                        <button wire:click="editCell({{ $cell->id }})" class="text-gray-500 hover:text-indigo-600 transition-colors" title="Editar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button wire:click="deleteCell({{ $cell->id }})" wire:confirm="¿Estás seguro de eliminar esta célula?" class="text-gray-500 hover:text-red-600 transition-colors" title="Eliminar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-2 text-sm text-gray-600">
                                    <p class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.242-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        {{ $cell->address }}
                                    </p>
                                    <p class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        {{ $cell->meeting_day }}s, {{ \Carbon\Carbon::parse($cell->meeting_time)->format('g:i A') }}
                                    </p>
                                    <p class="flex items-center gap-2 mt-2">
                                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded">
                                            {{ $cell->members()->count() }} miembros
                                        </span>
                                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded">
                                            {{ $cell->reports()->count() }} reportes
                                        </span>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
