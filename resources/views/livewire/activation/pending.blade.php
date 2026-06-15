<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.guest')] class extends Component {
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    public function checkStatus()
    {
        if (auth()->user()->fresh()->status === 'active') {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }
}
?>

<div wire:poll.5s="checkStatus">
    <div class="mb-4 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 mb-4">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900">
            Cuenta en Espera de Activación
        </h2>
    </div>

    <div class="mb-4 text-sm text-gray-600 text-center">
        ¡Hola, <span class="font-semibold text-gray-900">{{ auth()->user()->name }}</span>! Tu solicitud de registro para la célula <span class="font-semibold text-gray-900">{{ auth()->user()->cell?->name ?? 'registrada' }}</span> ha sido recibida con éxito.
    </div>

    <div class="mb-6 text-sm text-gray-600 text-center">
        Actualmente tu cuenta se encuentra en estado <span class="px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-800 rounded-full border border-amber-200">Pendiente de Aprobación</span>. Un administrador o supervisor debe validar tu información antes de que puedas acceder al panel completo.
    </div>

    <div class="mt-6 flex flex-col space-y-3">
        <a href="https://wa.me/573124878228?text=Hola,%20me%20acabo%20de%20registrar%20como%20líder%20en%20lideres.fuegovivo.com.co%20y%20requiero%20activación." 
           target="_blank" 
           class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full">
            Contactar a Soporte / Supervisor
        </a>

        <button wire:click="logout" 
                type="button" 
                class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 w-full">
            Cerrar Sesión
        </button>
    </div>
</div>
