<?php

use App\Models\Cell;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.public')] class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $document = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $sector = '';
    public string $password = '';
    public string $password_confirmation = '';
    public $photo;

    // Datos de la célula
    public string $cell_name = '';
    public string $cell_address = '';
    public string $cell_meeting_day = '';
    public string $cell_meeting_time = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:50', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'sector' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'cell_name' => ['required', 'string', 'max:255'],
            'cell_address' => ['required', 'string', 'max:255'],
            'cell_meeting_day' => ['required', 'string', 'max:50'],
            'cell_meeting_time' => ['required', 'string', 'max:50'],
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        $photoPath = null;
        if ($this->photo) {
            $photoPath = $this->photo->store('photos', 'public');
        }

        // 1. Crear la Célula primero
        $cell = Cell::create([
            'name' => $validated['cell_name'],
            'address' => $validated['cell_address'],
            'meeting_day' => $validated['cell_meeting_day'],
            'meeting_time' => $validated['cell_meeting_time'],
            'status' => 'active',
        ]);

        // 2. Crear el Líder asociado a la Célula
        $user = User::create([
            'name' => $validated['name'],
            'document' => $validated['document'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'sector' => $validated['sector'],
            'cell_id' => $cell->id,
            'entry_date' => now(),
            'role' => 'leader',
            'status' => 'pending',
            'photo_path' => $photoPath,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="max-w-4xl mx-auto sm:px-6 lg:px-8 mt-10 mb-10">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 sm:p-8">
        <div class="mb-8 text-center">
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
                Registro de Líder
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Únete a la plataforma de gestión de líderes y células de Fuego Vivo.
            </p>
        </div>

        <form wire:submit="register">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Columna 1: Información Personal -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                        1. Información Personal
                    </h3>

                    <!-- Name -->
                    <div>
                        <x-input-label for="name" :value="__('Nombre Completo')" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <!-- Document -->
                    <div>
                        <x-input-label for="document" :value="__('Documento de Identidad')" />
                        <x-text-input wire:model="document" id="document" class="block mt-1 w-full" type="text" name="document" required />
                        <x-input-error :messages="$errors->get('document')" class="mt-1" />
                    </div>

                    <!-- Email Address -->
                    <div>
                        <x-input-label for="email" :value="__('Correo Electrónico')" />
                        <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <!-- Phone -->
                    <div>
                        <x-input-label for="phone" :value="__('Teléfono / WhatsApp')" />
                        <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" name="phone" required />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>

                    <!-- Address -->
                    <div>
                        <x-input-label for="address" :value="__('Dirección de Residencia')" />
                        <x-text-input wire:model="address" id="address" class="block mt-1 w-full" type="text" name="address" required />
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>

                    <!-- Sector -->
                    <div>
                        <x-input-label for="sector" :value="__('Sector o Zona')" />
                        <x-text-input wire:model="sector" id="sector" class="block mt-1 w-full" type="text" name="sector" required placeholder="Ej: Norte, Monserrate, Zona 3" />
                        <x-input-error :messages="$errors->get('sector')" class="mt-1" />
                    </div>

                    <!-- Photo -->
                    <div>
                        <x-input-label for="photo" :value="__('Foto de Perfil *')" />
                        @if($photo)
                            <div class="mt-2 mb-2 flex items-center gap-3 bg-green-50 p-2 rounded-md border border-green-200">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span class="text-sm font-medium text-green-700">Imagen cargada y guardada temporalmente</span>
                            </div>
                        @endif
                        <input wire:model="photo" id="photo" type="file" {{ $photo ? '' : 'required' }} accept="image/*" class="block mt-1 w-full text-sm text-gray-600 border border-gray-300 rounded-md shadow-sm bg-white focus:border-orange-500 focus:ring-orange-500 file:mr-4 file:py-2.5 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-orange-600 file:text-white hover:file:bg-orange-700 file:cursor-pointer file:transition-colors cursor-pointer" />
                        <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                    </div>
                </div>

                <!-- Columna 2: Datos de la Célula -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                        2. Datos de la Célula
                    </h3>

                    <!-- Cell Name -->
                    <div>
                        <x-input-label for="cell_name" :value="__('Nombre de la Célula')" />
                        <x-text-input wire:model="cell_name" id="cell_name" class="block mt-1 w-full" type="text" name="cell_name" required placeholder="Ej: Jóvenes Monserrate" />
                        <x-input-error :messages="$errors->get('cell_name')" class="mt-1" />
                    </div>

                    <!-- Cell Address -->
                    <div>
                        <x-input-label for="cell_address" :value="__('Dirección de la Célula')" />
                        <x-text-input wire:model="cell_address" id="cell_address" class="block mt-1 w-full" type="text" name="cell_address" required placeholder="Ej: Carrera XX # XX - XX" />
                        <x-input-error :messages="$errors->get('cell_address')" class="mt-1" />
                    </div>

                    <!-- Cell Meeting Day -->
                    <div>
                        <x-input-label for="cell_meeting_day" :value="__('Día Habitual de Reunión')" />
                        <select wire:model="cell_meeting_day" id="cell_meeting_day" name="cell_meeting_day" required class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">-- Selecciona un día --</option>
                            <option value="Lunes">Lunes</option>
                            <option value="Martes">Martes</option>
                            <option value="Miércoles">Miércoles</option>
                            <option value="Jueves">Jueves</option>
                            <option value="Viernes">Viernes</option>
                            <option value="Sábado">Sábado</option>
                            <option value="Domingo">Domingo</option>
                        </select>
                        <x-input-error :messages="$errors->get('cell_meeting_day')" class="mt-1" />
                    </div>

                    <!-- Cell Meeting Time -->
                    <div>
                        <x-input-label for="cell_meeting_time" :value="__('Hora Habitual de Reunión')" />
                        <x-text-input wire:model="cell_meeting_time" id="cell_meeting_time" class="block mt-1 w-full" type="time" name="cell_meeting_time" required />
                        <x-input-error :messages="$errors->get('cell_meeting_time')" class="mt-1" />
                    </div>

                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 pt-4">
                        3. Contraseña
                    </h3>

                    <!-- Password -->
                    <div>
                        <x-input-label for="password" :value="__('Contraseña')" />
                        <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" />
                        <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between mt-8 pt-6 border-t border-gray-200 gap-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                    ¿Ya estás registrado? Iniciar Sesión
                </a>

                <x-primary-button class="w-full sm:w-auto justify-center py-3">
                    Registrar e Iniciar
                </x-primary-button>
            </div>
        </form>
    </div>
</div>

