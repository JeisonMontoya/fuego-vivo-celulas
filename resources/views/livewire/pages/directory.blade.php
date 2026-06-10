<?php

use App\Models\User;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\with;

layout('layouts.public');

with(fn () => [
    'leaders' => User::where('role', 'leader')
        ->where('status', 'active')
        ->with('cell')
        ->orderByDesc('rating')
        ->get()
]);

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
        <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Directorio de Líderes</h1>
        <p class="mt-3 max-w-2xl mx-auto text-xl text-gray-500 sm:mt-4">
            Conoce a los líderes que guían nuestras células con excelencia y pasión.
        </p>
    </div>

    @if($leaders->isEmpty())
        <div class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay líderes activos</h3>
            <p class="mt-1 text-sm text-gray-500">Pronto tendremos líderes en nuestro directorio.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($leaders as $leader)
                <div class="bg-white overflow-hidden shadow-lg rounded-2xl border border-gray-100 flex flex-col transition-transform duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div class="px-6 py-8 flex-grow">
                        <div class="flex items-center gap-4 mb-6">
                            @if($leader->photo_path)
                                <img class="h-24 w-24 rounded-[10px] object-cover shadow-sm ring-2 ring-orange-100" src="{{ Storage::url($leader->photo_path) }}" alt="{{ $leader->name }}">
                            @else
                                <div class="h-24 w-24 rounded-[10px] bg-gray-100 flex items-center justify-center shadow-sm ring-2 ring-gray-200 overflow-hidden">
                                    <svg class="h-full w-full text-gray-400 mt-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                            @endif
                            
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $leader->name }}</h3>
                                <div class="flex items-center mt-1">
                                    <svg class="h-4 w-4 text-gray-400 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <p class="text-sm text-gray-500 font-medium">{{ $leader->sector }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Célula -->
                            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Célula</p>
                                <p class="text-sm font-medium text-gray-900">{{ $leader->cell ? $leader->cell->name : 'Sin asignar' }}</p>
                            </div>

                            <!-- Calificación y Cumplimiento -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Calificación</p>
                                    <div class="flex items-center gap-1">
                                        <span class="text-lg font-bold text-gray-900">{{ number_format($leader->rating, 1) }}</span>
                                        <div class="flex">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="h-4 w-4 {{ $i <= round($leader->rating) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Cumplimiento</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-gray-900">{{ $leader->compliance_percentage }}%</span>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $leader->compliance_percentage }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cumplidos -->
                            <!-- Indicador Visual Dinámico -->
                            @php
                                $roundedRating = round($leader->rating);
                                if ($roundedRating >= 5) {
                                    $badgeText = 'Excelente cumplimiento';
                                    $badgeColor = 'bg-green-100 text-green-800 border-green-200';
                                } elseif ($roundedRating == 4) {
                                    $badgeText = 'Muy buen cumplimiento';
                                    $badgeColor = 'bg-blue-100 text-blue-800 border-blue-200';
                                } elseif ($roundedRating == 3) {
                                    $badgeText = 'Cumplimiento aceptable';
                                    $badgeColor = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                } elseif ($roundedRating == 2) {
                                    $badgeText = 'Necesita mejorar';
                                    $badgeColor = 'bg-orange-100 text-orange-800 border-orange-200';
                                } else {
                                    $badgeText = 'Incumplimiento frecuente';
                                    $badgeColor = 'bg-red-100 text-red-800 border-red-200';
                                }
                            @endphp
                            
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-2">Indicador Visual</p>
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeColor }}">
                                        ✨ {{ $badgeText }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
