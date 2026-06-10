<?php

use App\Models\CellMember;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $members;
    public bool $showForm = false;

    // Form Fields
    #[Validate('required|string|max:255')]
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public ?int $age = null;

    public bool $is_new = true;
    public bool $went_to_encounter = false;
    public bool $is_baptized = false;
    public bool $attends_church = false;
    public bool $attends_school = false;
    public string $ministry = '';

    public function mount()
    {
        $this->loadMembers();
    }

    public function loadMembers()
    {
        $this->members = auth()->user()->cell->members()->orderBy('name')->get();
    }

    public function saveMember()
    {
        $this->validate();

        auth()->user()->cell->members()->create([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'age' => $this->age,
            'is_new' => $this->is_new,
            'went_to_encounter' => $this->went_to_encounter,
            'is_baptized' => $this->is_baptized,
            'attends_church' => $this->attends_church,
            'attends_school' => $this->attends_school,
            'ministry' => $this->ministry,
        ]);

        $this->reset([
            'name', 'phone', 'email', 'address', 'age', 
            'is_new', 'went_to_encounter', 'is_baptized', 'attends_church', 'attends_school', 'ministry'
        ]);
        $this->is_new = true; // reset default
        
        $this->showForm = false;
        $this->loadMembers();
        
        session()->flash('status', 'Miembro guardado con éxito.');
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">Miembros de mi Célula</h2>
            <button wire:click="toggleForm" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-500 focus:bg-orange-500 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ $showForm ? 'Cancelar' : '+ Agregar Miembro' }}
            </button>
        </div>

        @if (session('status'))
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                {{ session('status') }}
            </div>
        @endif

        @if($showForm)
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h3 class="text-lg font-bold mb-4">Registrar Nuevo Miembro</h3>
                <form wire:submit="saveMember" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="name" value="Nombres y Apellidos *" />
                            <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="Celular" />
                            <x-text-input wire:model="phone" id="phone" type="text" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="email" value="E-Mail" />
                            <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="address" value="Dirección" />
                            <x-text-input wire:model="address" id="address" type="text" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="age" value="Edad" />
                            <x-text-input wire:model="age" id="age" type="number" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="ministry" value="Ministerio (Si sirve en alguno)" />
                            <x-text-input wire:model="ministry" id="ministry" type="text" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200 mt-4">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="is_new" class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-600">Nuevo en Célula (N.C.)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="went_to_encounter" class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-600">Fue a Encuentro (EN)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="is_baptized" class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-600">Bautizado (BA)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="attends_church" class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-600">Asiste a Iglesia (A.I.)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="attends_school" class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-600">Asiste a Escuela (A.E.)</span>
                        </label>
                    </div>

                    <div class="flex justify-end pt-4">
                        <x-primary-button>Guardar Miembro</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Lista de Miembros -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            @if(count($members) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progreso</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ministerio</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($members as $member)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $member->age ? $member->age . ' años' : 'Edad N/D' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $member->phone ?? 'Sin celular' }}</div>
                                        <div class="text-sm text-gray-500">{{ $member->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex gap-1">
                                            @if($member->is_new) <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800" title="Nuevo en Célula">NC</span> @endif
                                            @if($member->went_to_encounter) <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800" title="Encuentro">EN</span> @endif
                                            @if($member->is_baptized) <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800" title="Bautizado">BA</span> @endif
                                            @if($member->attends_church) <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800" title="Asiste a Iglesia">AI</span> @endif
                                            @if($member->attends_school) <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800" title="Asiste a Escuela">AE</span> @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $member->ministry ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center text-gray-500">
                    Aún no tienes miembros registrados en tu célula. ¡Agrega a tu primer discípulo!
                </div>
            @endif
        </div>
    </div>
</div>
