<?php

use App\Models\User;
use App\Models\Report;
use App\Models\Cell;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public $pendingUsers = [];
    public $leaders = [];
    
    public $totalTithes = 0;
    public $totalOfferings = 0;
    public $totalAttendance = 0;
    public $newMembers = 0;
    public $recurrentMembers = 0;
    public $activeCellsCount = 0;

    // Estado del Sistema
    public $systemStatus = 'green';
    public $systemStatusMessage = '';

    // Notificaciones
    public $showNotificationModal = false;
    public $selectedLeaderId = null;
    public $notificationMessage = '';

    public function mount()
    {
        $this->systemStatus = \Illuminate\Support\Facades\Cache::get('system_status', 'green');
        $this->systemStatusMessage = \Illuminate\Support\Facades\Cache::get('system_status_message', 'Todos los sistemas operativos.');
        $this->loadData();
    }

    public function loadData()
    {
        $this->pendingUsers = User::where('status', 'pending')->with('cells')->get();
        
        $this->leaders = \Illuminate\Support\Facades\Cache::remember('admin.dashboard.leaders', now()->addMinutes(15), function() {
            return User::where('role', 'leader')
                ->where('status', 'active')
                ->with('cells')
                ->get()
                ->sortByDesc(function ($l) {
                    return $l->rating * 1000 + $l->compliance_percentage;
                })->values();
        });

        $this->activeCellsCount = \Illuminate\Support\Facades\Cache::remember('admin.dashboard.cells_count', now()->addMinutes(30), function() {
            return Cell::where('status', 'active')->count();
        });
        
        // Optimización: Unificamos 3 consultas separadas de SUM() en 1 sola consulta SQL.
        // Además, agregamos una capa de caché de 15 minutos para que el panel cargue instantáneo.
        $stats = \Illuminate\Support\Facades\Cache::remember('admin.dashboard.report_stats', now()->addMinutes(15), function() {
            return Report::selectRaw('
                SUM(tithes) as tithes, 
                SUM(offerings) as offerings
            ')->first();
        });

        $membersStats = \Illuminate\Support\Facades\Cache::remember('admin.dashboard.members_stats', now()->addMinutes(15), function() {
            return \App\Models\CellMember::selectRaw('
                COUNT(*) as total,
                SUM(is_new = 1) as new_members,
                SUM(is_new = 0) as recurrent_members
            ')->first();
        });

        $this->totalTithes = $stats->tithes ?? 0;
        $this->totalOfferings = $stats->offerings ?? 0;
        $this->totalAttendance = $membersStats->total ?? 0;
        $this->newMembers = $membersStats->new_members ?? 0;
        $this->recurrentMembers = $membersStats->recurrent_members ?? 0;
    }

    public function approve($userId)
    {
        $user = User::find($userId);
        if ($user) {
            $user->status = 'active';
            $user->entry_date = now();
            $user->save();
            $this->loadData();
            session()->flash('status', 'Líder ' . $user->name . ' aprobado con éxito.');
        }
    }

    public function reject($userId)
    {
        $user = User::find($userId);
        if ($user) {
            foreach ($user->cells as $cell) {
                $cell->members()->delete();
                $cell->delete();
            }
            $user->delete();
            $this->loadData();
            session()->flash('error', 'Solicitud rechazada y eliminada.');
        }
    }

    // Modal de Notificaciones
    public function openNotificationModal($userId)
    {
        $this->selectedLeaderId = $userId;
        $this->notificationMessage = '';
        $this->showNotificationModal = true;
    }

    public function sendNotification()
    {
        $this->validate([
            'notificationMessage' => 'required|string|min:5|max:500'
        ]);

        // Guardar notificación en tabla (Se requiere la tabla notifications y el trait Notifiable)
        // Por ahora, crearemos una notificación simple
        // O si no usamos la db notif, podemos crear un modelo custom. 
        // ¡Esperar, usaré Laravel Notifications!
        
        $user = User::find($this->selectedLeaderId);
        if ($user) {
            $user->notify(new \App\Notifications\AdminAlertNotification($this->notificationMessage));
            session()->flash('status', 'Notificación enviada a ' . $user->name);
        }

        $this->showNotificationModal = false;
        $this->notificationMessage = '';
    }
    public function updateSystemStatus()
    {
        $this->validate([
            'systemStatus' => 'required|in:green,orange,red',
            'systemStatusMessage' => 'required|string|max:255'
        ]);

        \Illuminate\Support\Facades\Cache::forever('system_status', $this->systemStatus);
        \Illuminate\Support\Facades\Cache::forever('system_status_message', $this->systemStatusMessage);

        session()->flash('status', 'Estado global del sistema actualizado con éxito.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Centro de Mando (Admin)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Mensajes flash -->
            @if(session('status'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm mb-6 flex items-center">
                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <p class="font-medium">{{ session('status') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm mb-6 flex items-center">
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Dashboard Global Stats (AdminLTE Style) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Diezmos (Indigo) -->
                <div class="relative overflow-hidden rounded-lg bg-indigo-600 text-white shadow-md transition-transform hover:scale-[1.02] pb-2">
                    <div class="p-4 relative z-10">
                        <h3 class="text-3xl font-black">${{ number_format($totalTithes, 0) }}</h3>
                        <p class="text-indigo-100 font-medium text-sm mt-1">Total Diezmos</p>
                    </div>
                    <svg class="absolute -right-2 -bottom-4 w-24 h-24 text-black opacity-10 transform -rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>

                <!-- Ofrendas (Emerald) -->
                <div class="relative overflow-hidden rounded-lg bg-emerald-500 text-white shadow-md transition-transform hover:scale-[1.02] pb-2">
                    <div class="p-4 relative z-10">
                        <h3 class="text-3xl font-black">${{ number_format($totalOfferings, 0) }}</h3>
                        <p class="text-emerald-100 font-medium text-sm mt-1">Total Ofrendas</p>
                    </div>
                    <svg class="absolute -right-2 -bottom-4 w-24 h-24 text-black opacity-10 transform -rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>

                <!-- Asistencia (Blue) -->
                <div class="relative overflow-hidden rounded-lg bg-blue-500 text-white shadow-md transition-transform hover:scale-[1.02] pb-2">
                    <div class="p-4 relative z-10">
                        <h3 class="text-3xl font-black">{{ number_format($totalAttendance, 0) }}</h3>
                        <p class="text-blue-100 font-medium text-sm mt-1">Asistencia Global (Miembros)</p>
                        <div class="mt-2 text-xs text-blue-200 flex gap-2 font-bold">
                            <span class="bg-blue-600 px-2 py-0.5 rounded">{{ number_format($newMembers, 0) }} Nuevos</span>
                            <span class="bg-blue-700 px-2 py-0.5 rounded">{{ number_format($recurrentMembers, 0) }} Recurrentes</span>
                        </div>
                    </div>
                    <svg class="absolute -right-2 -bottom-4 w-24 h-24 text-black opacity-10 transform -rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>

                <!-- Células (Orange) -->
                <div class="relative overflow-hidden rounded-lg bg-orange-500 text-white shadow-md transition-transform hover:scale-[1.02] pb-2">
                    <div class="p-4 relative z-10">
                        <h3 class="text-3xl font-black">{{ $activeCellsCount }}</h3>
                        <p class="text-orange-100 font-medium text-sm mt-1">Células Activas</p>
                    </div>
                    <svg class="absolute -right-2 -bottom-4 w-24 h-24 text-black opacity-10 transform -rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
            </div>

            <!-- Control de Estado del Sistema -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border border-gray-100">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Estado Global del Sistema
                    </h3>
                </div>
                <div class="p-6">
                    <form wire:submit="updateSystemStatus" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                        <div class="col-span-1">
                            <label for="systemStatus" class="block text-sm font-bold text-gray-700 mb-1">Indicador Visual</label>
                            <select wire:model="systemStatus" id="systemStatus" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                                <option value="green">🟢 Operativo (Verde)</option>
                                <option value="orange">🟠 Problemas / Mantenimiento (Naranja)</option>
                                <option value="red">🔴 Caída Crítica (Rojo)</option>
                            </select>
                            @error('systemStatus') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-1 md:col-span-2 flex gap-4 items-end">
                            <div class="flex-1">
                                <label for="systemStatusMessage" class="block text-sm font-bold text-gray-700 mb-1">Mensaje para los usuarios</label>
                                <input type="text" wire:model="systemStatusMessage" id="systemStatusMessage" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Ej: Todos los sistemas operativos.">
                                @error('systemStatusMessage') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-bold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Centro de Aprobaciones -->
            @if($pendingUsers->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-red-100 relative">
                    <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Solicitudes Pendientes ({{ $pendingUsers->count() }})
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Líder</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Célula y Zona</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contacto</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($pendingUsers as $pUser)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    @if($pUser->photo_path)
                                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('storage/'.$pUser->photo_path) }}" alt="">
                                                    @else
                                                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold">
                                                            {{ substr($pUser->name, 0, 1) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-bold text-gray-900">{{ $pUser->name }}</div>
                                                    <div class="text-sm text-gray-500">Doc: {{ $pUser->document }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 font-medium">{{ optional($pUser->cells->first())->name ?? 'N/A' }}</div>
                                            <div class="text-sm text-gray-500">Sector: {{ $pUser->sector }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $pUser->phone }}</div>
                                            <div class="text-sm text-gray-500">{{ $pUser->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button wire:click="approve({{ $pUser->id }})" class="text-green-600 hover:text-green-900 font-bold bg-green-50 px-3 py-1 rounded-md border border-green-200 mr-2">Aprobar</button>
                                            <button wire:click="reject({{ $pUser->id }})" class="text-red-600 hover:text-red-900 font-bold bg-red-50 px-3 py-1 rounded-md border border-red-200" onclick="confirm('¿Estás seguro de rechazar y borrar esta solicitud?') || event.stopImmediatePropagation()">Rechazar</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Ranking General de Líderes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Ranking General de Líderes</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Líder</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Calificación</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Cumplimiento</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($leaders as $index => $leader)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-lg font-black {{ $index < 3 ? 'text-orange-500' : 'text-gray-400' }}">
                                            {{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                @if($leader->photo_path)
                                                    <img class="h-8 w-8 rounded-full object-cover" src="{{ asset('storage/' . $leader->photo_path) }}" alt="{{ $leader->name }}">
                                                @else
                                                    <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-xs">
                                                        {{ substr($leader->name, 0, 1) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-bold text-gray-900">{{ $leader->name }}</div>
                                                <div class="text-xs text-gray-500 truncate max-w-[150px]">{{ $leader->cells->pluck('name')->implode(', ') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center text-yellow-400">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="h-4 w-4 {{ $i <= round($leader->rating) ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @endfor
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $leader->compliance_percentage >= 80 ? 'bg-green-100 text-green-800' : ($leader->compliance_percentage >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $leader->compliance_percentage }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <button wire:click="openNotificationModal({{ $leader->id }})" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded border border-indigo-200 flex items-center gap-1 mx-auto">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                            Alertar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal de Notificación -->
    @if($showNotificationModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showNotificationModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Enviar Alerta al Líder
                                </h3>
                                <div class="mt-4">
                                    <textarea wire:model="notificationMessage" rows="4" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Escribe el mensaje de alerta aquí..."></textarea>
                                    @error('notificationMessage') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="sendNotification" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Enviar Alerta
                        </button>
                        <button wire:click="$set('showNotificationModal', false)" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
