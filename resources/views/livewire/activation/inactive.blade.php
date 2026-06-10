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
}
?>

<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-950 text-white">
    <div class="w-full sm:max-w-md mt-6 px-8 py-10 bg-slate-900 border border-slate-800 shadow-2xl rounded-2xl text-center relative overflow-hidden">
        
        <!-- Ambient Background Glow -->
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-red-600 rounded-full blur-3xl opacity-15 pointer-events-none"></div>
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-rose-600 rounded-full blur-3xl opacity-15 pointer-events-none"></div>

        <!-- Animated Warning Icon -->
        <div class="relative flex justify-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-tr from-red-500 to-rose-500 rounded-full flex items-center justify-center shadow-lg shadow-red-500/30">
                <!-- Warning icon -->
                <svg class="w-10 h-10 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <!-- Pulse rings -->
            <span class="absolute inline-flex h-20 w-20 rounded-full bg-red-400 opacity-20 animate-ping"></span>
        </div>

        <h2 class="text-2xl font-extrabold tracking-tight bg-gradient-to-r from-red-400 via-rose-400 to-orange-400 bg-clip-text text-transparent">
            Cuenta Desactivada
        </h2>

        <p class="mt-4 text-slate-350 text-sm leading-relaxed text-slate-400">
            ¡Hola, <span class="font-semibold text-slate-200">{{ auth()->user()->name }}</span>! Tu cuenta de líder ha sido <span class="px-2 py-0.5 text-xs font-semibold bg-red-500/10 text-red-400 rounded-full border border-red-500/20">Desactivada</span> por el equipo administrativo de Fuego Vivo.
        </p>

        <p class="mt-3 text-slate-400 text-sm leading-relaxed">
            Si crees que esto se debe a un error o necesitas reactivar tu cuenta para reanudar el reporte de tus células, por favor ponte en contacto de inmediato con tu supervisor directo o con el administrador de la plataforma.
        </p>

        <div class="mt-8 space-y-3">
            <a href="https://wa.me/573000000000?text=Hola,%20mi%20cuenta%20de%20líder%20en%20lideres.fuegovivo.com.co%20aparece%20como%20desactivada%20y%20necesito%20revisar%20el%20caso." 
               target="_blank" 
               class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-red-650 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition duration-150 ease-in-out cursor-pointer">
                <span>Contactar a Soporte</span>
            </a>

            <button wire:click="logout" 
                    type="button" 
                    class="w-full px-4 py-3 bg-slate-800 hover:bg-slate-700 border border-slate-700 hover:border-slate-650 text-slate-300 hover:text-white font-semibold rounded-lg transition duration-150 ease-in-out cursor-pointer">
                Cerrar Sesión
            </button>
        </div>

        <div class="mt-8 text-xs text-slate-500">
            Plataforma Administrativa Fuego Vivo © 2026
        </div>
    </div>
</div>
