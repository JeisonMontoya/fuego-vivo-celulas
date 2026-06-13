<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    public string $status = 'green';
    public string $message = 'Todos los sistemas operativos.';

    public function mount()
    {
        $this->status = Cache::get('system_status', 'green');
        $this->message = Cache::get('system_status_message', 'Todos los sistemas operativos.');
    }
}; ?>

<div class="relative flex items-center" x-data="{ showTooltip: false }" @mouseleave="showTooltip = false">
    <!-- Indicador Visual -->
    <button 
        @mouseenter="showTooltip = true"
        @click="showTooltip = !showTooltip"
        class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-100 bg-white shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none"
    >
        <span class="relative flex h-3 w-3">
            @if($status === 'red')
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            @elseif($status === 'orange')
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-orange-500"></span>
            @else
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            @endif
        </span>
        <span class="text-xs font-semibold text-gray-700 hidden sm:block">
            @if($status === 'red') Caída Crítica
            @elseif($status === 'orange') Problemas / Mantenimiento
            @else Operativo
            @endif
        </span>
    </button>

    <!-- Tooltip Flotante -->
    <div 
        x-show="showTooltip" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="absolute top-full right-0 mt-2 w-64 bg-gray-900 text-white text-sm rounded-lg p-3 shadow-xl z-50 text-center"
        style="display: none;"
    >
        <div class="font-bold mb-1">
            @if($status === 'red') ⚠️ Atención Requerida
            @elseif($status === 'orange') 🛠️ Aviso del Sistema
            @else ✅ Estado del Sistema
            @endif
        </div>
        <p class="text-gray-300 mb-2">{{ $message }}</p>
        
        @if(auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->role === 'supervisor'))
            <div class="border-t border-gray-700 pt-2 mt-2">
                <a href="{{ route('admin.dashboard') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold inline-flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Editar Estado
                </a>
            </div>
        @endif
        
        <div class="absolute -top-1 right-8 w-3 h-3 bg-gray-900 transform rotate-45"></div>
    </div>
</div>
