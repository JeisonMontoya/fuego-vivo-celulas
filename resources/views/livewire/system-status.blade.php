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

<div class="relative flex items-center group cursor-pointer" x-data="{ showTooltip: false }" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
    <!-- Indicador Visual -->
    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-100 bg-white shadow-sm transition-all duration-200 hover:shadow-md">
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
    </div>

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
        <p class="text-gray-300">{{ $message }}</p>
        <div class="absolute -top-1 right-8 w-3 h-3 bg-gray-900 transform rotate-45"></div>
    </div>
</div>
