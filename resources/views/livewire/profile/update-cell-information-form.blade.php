<?php

use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    public $cell;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $address = '';

    #[Validate('required|string|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo')]
    public string $meeting_day = 'Viernes';

    #[Validate('required|string|date_format:H:i')]
    public string $meeting_time = '19:00';

    public function mount()
    {
        $this->cell = auth()->user()->cell;

        if ($this->cell) {
            $this->name = $this->cell->name;
            $this->address = $this->cell->address;
            $this->meeting_day = $this->cell->meeting_day;
            $this->meeting_time = date('H:i', strtotime($this->cell->meeting_time));
        }
    }

    public function updateCellInformation()
    {
        if (! $this->cell) {
            return;
        }

        $this->validate();

        $this->cell->update([
            'name' => $this->name,
            'address' => $this->address,
            'meeting_day' => $this->meeting_day,
            'meeting_time' => $this->meeting_time,
        ]);

        $this->dispatch('cell-updated', name: $this->cell->name);
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-bold text-gray-900">
            Modificar Datos de la Célula
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Actualiza la dirección, el día o la hora en la que se reúnen.
        </p>
    </header>

    <form wire:submit="updateCellInformation" class="mt-6 space-y-6">
        <!-- Nombre de la Célula -->
        <div>
            <x-input-label for="cell_name" value="Nombre de la Célula *" />
            <x-text-input wire:model="name" id="cell_name" type="text" class="mt-1 block w-full" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Dirección -->
        <div>
            <x-input-label for="cell_address" value="Dirección o Ubicación *" />
            <x-text-input wire:model="address" id="cell_address" type="text" class="mt-1 block w-full" required />
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Día de reunión -->
            <div>
                <x-input-label for="cell_meeting_day" value="Día de Reunión *" />
                <select wire:model="meeting_day" id="cell_meeting_day" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
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

            <!-- Hora de reunión -->
            <div>
                <x-input-label for="cell_meeting_time" value="Hora de Reunión *" />
                <x-text-input wire:model="meeting_time" id="cell_meeting_time" type="time" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('meeting_time')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar Cambios</x-primary-button>

            <x-action-message class="me-3" on="cell-updated">
                Guardado.
            </x-action-message>
        </div>
    </form>
</section>
