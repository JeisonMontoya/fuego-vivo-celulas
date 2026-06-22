<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:clean-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia reportes duplicados (misma fecha y usuario) y recalcula métricas de líderes afectados.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando reportes duplicados...');

        $duplicates = DB::table('reports')
            ->select('user_id', 'meeting_date', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(id) as total_count'))
            ->groupBy('user_id', 'meeting_date')
            ->havingRaw('COUNT(id) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No se encontraron reportes duplicados. Todo está en orden.');
            return;
        }

        $this->warn('Se encontraron ' . $duplicates->count() . ' casos de reportes duplicados. Procediendo a limpiar...');

        $usersToRecalculate = [];
        $totalDeleted = 0;

        foreach ($duplicates as $duplicate) {
            $deleted = Report::where('user_id', $duplicate->user_id)
                ->whereDate('meeting_date', $duplicate->meeting_date)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();

            $totalDeleted += $deleted;
            $usersToRecalculate[] = $duplicate->user_id;
            
            $this->line("Usuario ID {$duplicate->user_id}: Se mantuvo el reporte ID {$duplicate->keep_id} y se eliminaron {$deleted} duplicados para la fecha {$duplicate->meeting_date}.");
        }

        $usersToRecalculate = array_unique($usersToRecalculate);
        $this->info("Recalculando métricas para " . count($usersToRecalculate) . " usuarios afectados...");

        foreach ($usersToRecalculate as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->recalculateMetrics();
            }
        }

        $this->info("¡Limpieza completada! Se eliminaron un total de {$totalDeleted} reportes duplicados.");
    }
}
