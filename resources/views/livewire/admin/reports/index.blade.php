<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Report;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public $searchLeader = '';

    #[Url]
    public $filterSector = '';

    #[Url]
    public $filterDateFrom = '';

    #[Url]
    public $filterDateTo = '';

    #[Url]
    public $filterCompliance = '';

    public function with(): array
    {
        // Se removió 'user.cell' ya que la tabla no muestra información de la célula,
        // evitando cientos de queries N+1 innecesarios o joins inútiles.
        $query = Report::query()->with(['user']);

        // Filtro por Líder
        if ($this->searchLeader) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->searchLeader . '%');
            });
        }

        // Filtro por Sector
        if ($this->filterSector) {
            $query->whereHas('user', function ($q) {
                $q->where('sector', $this->filterSector);
            });
        }

        // Filtro por Fechas
        if ($this->filterDateFrom) {
            $query->where('meeting_date', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $query->where('meeting_date', '<=', $this->filterDateTo);
        }

        // Filtro por Cumplimiento (Score)
        if ($this->filterCompliance !== '') {
            $query->where('score', $this->filterCompliance);
        }

        // Listado de sectores únicos para el select - CACHEADO
        // Esta es una tabla estática que rara vez cambia, la cacheamos por 24 horas.
        $sectors = \Illuminate\Support\Facades\Cache::remember('admin.sectors.list', now()->addHours(24), function () {
            return User::whereNotNull('sector')->distinct()->pluck('sector');
        });

        return [
            'reports' => $query->latest('meeting_date')->paginate(15),
            'sectors' => $sectors,
        ];
    }
    
    public function clearFilters()
    {
        $this->reset(['searchLeader', 'filterSector', 'filterDateFrom', 'filterDateTo', 'filterCompliance']);
        $this->resetPage();
    }

    public function updatedSearchLeader() { $this->resetPage(); }
    public function updatedFilterSector() { $this->resetPage(); }
    public function updatedFilterDateFrom() { $this->resetPage(); }
    public function updatedFilterDateTo() { $this->resetPage(); }
    public function updatedFilterCompliance() { $this->resetPage(); }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión Global de Reportes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filters Section -->
            <div class="bg-white p-6 shadow-sm sm:rounded-lg border-t-4 border-indigo-500">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Filtros de Búsqueda</h3>
                    <button wire:click="clearFilters" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Limpiar Filtros
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search Leader -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Líder</label>
                        <input type="text" wire:model.live.debounce.300ms="searchLeader" placeholder="Buscar líder..." class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>

                    <!-- Sector -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                        <select wire:model.live="filterSector" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Todos los sectores</option>
                            @foreach($sectors as $sector)
                                <option value="{{ $sector }}">{{ $sector }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Desde (Fecha)</label>
                        <input type="date" wire:model.live="filterDateFrom" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>

                    <!-- Date To -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hasta (Fecha)</label>
                        <input type="date" wire:model.live="filterDateTo" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>

                    <!-- Compliance -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cumplimiento</label>
                        <select wire:model.live="filterCompliance" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Cualquier estado</option>
                            <option value="10">Excelente (+10 pts)</option>
                            <option value="8">Retraso 1 día (+8 pts)</option>
                            <option value="6">Retraso 2 días (+6 pts)</option>
                            <option value="4">Retraso 3 días (+4 pts)</option>
                            <option value="2">Retraso 4 días (+2 pts)</option>
                            <option value="0">Muy atrasado (0 pts)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Célula</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Líder y Sector</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Asistencia</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-right">Finanzas</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Puntaje</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($reports as $report)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">{{ \Carbon\Carbon::parse($report->meeting_date)->format('d M, Y') }}</div>
                                        <div class="text-xs text-gray-500" title="Fecha de Envío">Reportado: {{ $report->created_at->format('d M') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                                                    {{ substr($report->user->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $report->user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $report->user->sector ?? 'Sin sector' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center gap-2">
                                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-bold rounded-full bg-blue-100 text-blue-800" title="Regulares">
                                                {{ $report->attendance_count }}
                                            </span>
                                            @if($report->guests_count > 0)
                                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-bold rounded-full bg-green-100 text-green-800" title="Visitantes">
                                                    +{{ $report->guests_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="text-gray-900"><span class="text-gray-500 text-xs mr-1">D</span>${{ number_format($report->tithes, 0) }}</div>
                                        <div class="text-gray-900 mt-1"><span class="text-gray-500 text-xs mr-1">O</span>${{ number_format($report->offerings, 0) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($report->score == 10)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-green-100 text-green-800">
                                                +10 <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </span>
                                        @elseif($report->score >= 6)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-yellow-100 text-yellow-800">
                                                +{{ $report->score }} <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-red-100 text-red-800">
                                                +{{ $report->score }} <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.reports.show', $report->id) }}" wire:navigate class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-md transition-colors inline-flex items-center gap-1">
                                            Ver Detalles <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <p class="text-lg font-medium text-gray-900">No se encontraron reportes</p>
                                        <p class="text-sm">Ajusta los filtros de búsqueda para ver más resultados.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($reports->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        {{ $reports->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
