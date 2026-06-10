<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                {{ __('Mi Célula') }}
            </h2>
            <div class="flex items-center gap-3">
                @if(!auth()->user()->isAdmin())
                    <a href="{{ route('members.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-50 border border-indigo-200 rounded-md font-semibold text-xs text-indigo-700 uppercase tracking-widest shadow-sm hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" wire:navigate>
                        Mis Miembros
                    </a>
                @endif
                <a href="{{ route('profile') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150" wire:navigate>
                    Ajustes
                </a>
                <a href="{{ route('reports.create') }}" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-md hover:bg-orange-500 focus:bg-orange-500 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150" wire:navigate>
                    + Nuevo Reporte
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $cell = $user->cell;
        $reports = $user->reports()->latest('meeting_date')->get();
        
        // Cálculos de Cumplimiento
        $missingReports = $user->getMissingReportsCount();
        $puntosAcumulados = $reports->sum('score') - ($missingReports * 10);
        
        // Notificaciones
        $adminAlerts = collect();
        if (method_exists($user, 'unreadNotifications')) {
            $adminAlerts = $user->unreadNotifications;
        }
        
        // Ranking
        $allLeaders = \App\Models\User::where('role', 'leader')->where('status', 'active')->get()
            ->sortByDesc(function ($l) {
                return $l->rating * 1000 + $l->compliance_percentage; 
            })->values();
        $ranking = $allLeaders->search(fn($l) => $l->id === $user->id) + 1;
        $totalLeaders = $allLeaders->count();

        // Estadísticas
        $totalAsistentes = $reports->sum('attendance_count');
        $promedioAsistencia = $reports->count() > 0 ? round($reports->avg('attendance_count')) : 0;
        $totalInvitados = $reports->sum('guests_count');
        
        // Próxima Fecha
        $daysMap = [
            'Lunes' => \Carbon\Carbon::MONDAY, 'Martes' => \Carbon\Carbon::TUESDAY, 'Miércoles' => \Carbon\Carbon::WEDNESDAY,
            'Jueves' => \Carbon\Carbon::THURSDAY, 'Viernes' => \Carbon\Carbon::FRIDAY, 'Sábado' => \Carbon\Carbon::SATURDAY, 'Domingo' => \Carbon\Carbon::SUNDAY,
        ];
        $meetingDayName = $cell->meeting_day ?? 'Viernes';
        $dayOfWeek = $daysMap[$meetingDayName] ?? \Carbon\Carbon::FRIDAY;
        
        $nextMeeting = now()->next($dayOfWeek);
        if (now()->isDayOfWeek($dayOfWeek)) {
            $nextMeeting = now();
        }
        $nextDeadline = $nextMeeting->clone()->addDay()->endOfDay();
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Mensaje de éxito flash -->
            @if(session('status'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm mb-6 flex items-center">
                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <p class="font-medium">{{ session('status') }}</p>
                </div>
            @endif

            <!-- 1. Perfil y Resumen de Célula -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 sm:p-8 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        @if(auth()->user()->photo_path)
                            <img class="h-20 w-20 object-cover rounded-full border-4 border-white shadow-md" src="{{ asset('storage/' . auth()->user()->photo_path) }}" alt="{{ auth()->user()->name }}">
                        @else
                            <div class="h-20 w-20 rounded-full bg-gradient-to-r from-orange-400 to-orange-600 flex items-center justify-center text-white text-3xl font-bold shadow-inner">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h3>
                            <p class="text-gray-500 font-medium">{{ $cell->name ?? 'Mi Célula' }}</p>
                            <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ $meetingDayName }}s, {{ $cell->meeting_time ? \Carbon\Carbon::parse($cell->meeting_time)->format('g:i A') : 'Sin horario' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.242-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    {{ $cell->address ?? 'Sin dirección' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center min-w-[200px]">
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Tu Posición General</p>
                        <div class="text-3xl font-extrabold text-indigo-600">
                            #{{ $ranking }} <span class="text-lg text-gray-400 font-medium">/ {{ $totalLeaders }}</span>
                        </div>
                        <p class="text-xs text-indigo-800 mt-1 font-medium bg-indigo-100 rounded-full px-2 py-1 inline-block">Ranking de Líderes</p>
                    </div>
                </div>
            </div>

            <!-- 2. Alertas (Full Width Banner) -->
            @if($adminAlerts->count() > 0)
                <div class="bg-gradient-to-r from-red-800 to-red-900 rounded-xl shadow-lg p-6 mb-6 flex items-start gap-4 text-white relative overflow-hidden border-2 border-red-500 animate-pulse" style="animation-iteration-count: 3;">
                    <div class="absolute right-0 top-0 w-64 h-full bg-white opacity-10 transform skew-x-12 translate-x-16"></div>
                    <div class="p-3 bg-red-500/30 rounded-full shrink-0">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div class="flex-1 relative z-10">
                        <div class="flex justify-between items-start">
                            <h4 class="text-lg font-bold text-white mb-2">Mensaje Importante del Administrador</h4>
                            <form action="{{ route('notifications.read') }}" method="POST">
                                @csrf
                                <button type="submit" class="text-red-300 hover:text-white transition-colors" title="Marcar como leído">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </form>
                        </div>
                        <div class="space-y-3">
                            @foreach($adminAlerts as $alert)
                                <div class="bg-red-900/50 p-4 rounded border border-red-500/30">
                                    <p class="text-red-100 font-medium">{{ $alert->data['message'] ?? 'Tienes una alerta de administración.' }}</p>
                                    <span class="text-xs text-red-300 mt-2 block">{{ $alert->created_at->diffForHumans() }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-xl shadow-lg p-6 flex flex-col md:flex-row items-center justify-between gap-4 text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 w-64 h-full bg-white opacity-5 transform skew-x-12 translate-x-16"></div>
                <div class="flex items-center gap-4 relative z-10">
                    <div class="p-3 bg-white/10 rounded-full">
                        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-300 font-medium">Próxima Fecha Límite de Reporte</p>
                        <p class="text-2xl font-bold">{{ $nextDeadline->translatedFormat('l d \d\e F') }}</p>
                    </div>
                </div>
                
                @if($missingReports > 0)
                    <div class="bg-red-500/20 border border-red-500/50 rounded-lg px-4 py-3 flex items-center gap-3 relative z-10">
                        <svg class="w-6 h-6 text-red-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div>
                            <p class="text-red-200 font-bold text-sm">¡Atención! Tienes {{ $missingReports }} {{ $missingReports == 1 ? 'reporte pendiente' : 'reportes pendientes' }}.</p>
                            <p class="text-red-300/80 text-xs">Esto está afectando tu calificación de estrellas.</p>
                        </div>
                    </div>
                @else
                    <div class="bg-green-500/20 border border-green-500/30 rounded-lg px-4 py-3 relative z-10 hidden md:block">
                        <p class="text-green-300 font-medium text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Estás al día con tus reportes
                        </p>
                    </div>
                @endif
            </div>

            <!-- 3. Métricas Principales (Grid de 4) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Calificación -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4 border-yellow-400 flex flex-col justify-between">
                    <div class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Calificación Actual</div>
                    <div class="flex items-end justify-between">
                        <span class="text-5xl font-black text-gray-900 leading-none">{{ number_format($user->rating, 0) }}</span>
                        <div class="flex text-yellow-400 mb-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="h-5 w-5 {{ $i <= round($user->rating) ? 'text-yellow-400 drop-shadow-sm' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                    </div>
                </div>

                <!-- Cumplimiento -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4 border-green-500 flex flex-col justify-between">
                    <div class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Cumplimiento</div>
                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-4xl font-black text-gray-900 leading-none">{{ $user->compliance_percentage }}%</span>
                            <span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-1 rounded-md">{{ $puntosAcumulados }} pts</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ $user->compliance_percentage }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Asistentes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4 border-blue-500 flex flex-col justify-between">
                    <div class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Total Asistentes</div>
                    <div class="flex justify-between items-end">
                        <span class="text-4xl font-black text-gray-900 leading-none">{{ $totalAsistentes }}</span>
                        <span class="text-xs text-gray-400 font-medium mb-1">Histórico</span>
                    </div>
                </div>

                <!-- Promedio / Nuevos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b-4 border-orange-500 flex flex-col justify-between">
                    <div class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Promedio Semanal</div>
                    <div class="flex justify-between items-end">
                        <span class="text-4xl font-black text-gray-900 leading-none">{{ $promedioAsistencia }}</span>
                        <span class="text-xs font-bold text-orange-600 bg-orange-50 px-2 py-1 rounded-md border border-orange-100">
                            +{{ $totalInvitados }} nuevos
                        </span>
                    </div>
                </div>
            </div>

            <!-- 4. Tabla de Reportes (Fila completa) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Historial de Reportes</h3>
                    <span class="text-xs font-bold bg-gray-100 text-gray-600 px-3 py-1 rounded-full">{{ $user->reports_count }} enviados</span>
                </div>
                
                @if($reports->isEmpty())
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="mt-4 text-sm text-gray-500 font-medium">Aún no has enviado ningún reporte.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha Reunión</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Asistencia</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Puntaje</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Enviado el</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($reports as $report)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                            {{ $report->meeting_date->translatedFormat('d M, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex justify-center gap-2">
                                                <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded bg-blue-50 text-blue-700 border border-blue-100" title="Regulares">
                                                    {{ $report->attendance_count }}
                                                </span>
                                                @if($report->guests_count > 0)
                                                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded bg-green-50 text-green-700 border border-green-100" title="Nuevos">
                                                        +{{ $report->guests_count }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex flex-col items-center">
                                                <span class="font-black text-gray-900 text-base">+{{ $report->score }}</span>
                                                @if($report->days_late == 0)
                                                    <span class="text-[10px] font-bold text-green-600 uppercase tracking-wider">A tiempo</span>
                                                @else
                                                    <span class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ $report->days_late }} {{ $report->days_late == 1 ? 'día' : 'días' }} tarde</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">
                                            {{ $report->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
