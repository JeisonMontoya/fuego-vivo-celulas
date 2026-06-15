<?php

use Livewire\Volt\Component;
use App\Models\Report;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public Report $report;

    public function mount(Report $report)
    {
        $this->report = $report->load(['user.cell', 'attendees']);
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <a href="{{ route('admin.reports.index') }}" wire:navigate class="text-indigo-600 hover:text-indigo-800 transition-colors">
                    <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                {{ __('Detalle de Reporte') }}
            </h2>
            <div class="text-sm text-gray-500 bg-white px-3 py-1 rounded-full shadow-sm">
                Enviado el {{ $report->created_at->format('d/m/Y h:i A') }}
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Info del Líder -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-blue-500">
                    <div class="p-6">
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Información del Líder</div>
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center text-blue-700 font-black text-2xl">
                                {{ substr($report->user->name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ $report->user->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $report->user->email }}</p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mt-1">
                                    Sector: {{ $report->user->sector ?? 'No asignado' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles de la Célula -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-emerald-500">
                    <div class="p-6">
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Métricas Financieras</div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-emerald-50 p-4 rounded-xl text-center">
                                <div class="text-emerald-500 mb-1"><svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                                <div class="text-2xl font-black text-emerald-700">${{ number_format($report->tithes, 0) }}</div>
                                <div class="text-xs text-emerald-600 font-bold uppercase mt-1">Diezmos</div>
                            </div>
                            <div class="bg-emerald-50 p-4 rounded-xl text-center">
                                <div class="text-emerald-500 mb-1"><svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></div>
                                <div class="text-2xl font-black text-emerald-700">${{ number_format($report->offerings, 0) }}</div>
                                <div class="text-xs text-emerald-600 font-bold uppercase mt-1">Ofrendas</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cumplimiento -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-amber-500">
                    <div class="p-6 h-full flex flex-col justify-center">
                        <div class="text-center">
                            <div class="text-sm font-bold text-gray-500 uppercase mb-2">Puntaje Obtenido</div>
                            
                            @if($report->score == 10)
                                <div class="text-5xl font-black text-green-500 mb-2">+10</div>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    Reporte a tiempo
                                </div>
                            @elseif($report->score >= 6)
                                <div class="text-5xl font-black text-yellow-500 mb-2">+{{ $report->score }}</div>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    Reporte atrasado
                                </div>
                            @else
                                <div class="text-5xl font-black text-red-500 mb-2">+{{ $report->score }}</div>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    Muy atrasado
                                </div>
                            @endif
                            <div class="mt-4 text-sm text-gray-600 font-medium">
                                Fecha de la célula: {{ \Carbon\Carbon::parse($report->meeting_date)->format('d M, Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asistencia Listado -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Listado de Asistencia
                    </h3>
                    <span class="bg-indigo-100 text-indigo-800 text-sm font-bold px-3 py-1 rounded-full">
                        Total: {{ $report->attendance_count + $report->guests_count }}
                    </span>
                </div>
                
                @if($report->attendees->count() > 0 || $report->guests_count > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-0 border-t border-gray-200">
                        @foreach($report->attendees as $attendee)
                            <div class="p-4 border-b border-r border-gray-100 flex items-center gap-3">
                                <div class="h-10 w-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 font-bold shrink-0">
                                    {{ substr($attendee->name ?? 'I', 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900">{{ $attendee->name ?? 'Miembro Eliminado' }}</div>
                                    @if($attendee->is_new)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1">
                                            Invitado / Nuevo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 mt-1">
                                            Miembro Fijo
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        
                        @if($report->guests_count > 0)
                            <div class="p-4 border-b border-r border-gray-100 flex items-center gap-3">
                                <div class="h-10 w-10 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-500 font-bold shrink-0">
                                    +{{ $report->guests_count }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900">Visitantes Extras</div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 mt-1">
                                        No Registrados
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-12 text-center text-gray-500">
                        No se registraron asistentes o no hay datos almacenados.
                    </div>
                @endif
                
                @if($report->notes)
                    <div class="p-6 bg-yellow-50 border-t border-yellow-100">
                        <h4 class="text-sm font-bold text-yellow-800 mb-2">Observaciones Adicionales</h4>
                        <p class="text-sm text-yellow-900">{{ $report->notes }}</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
