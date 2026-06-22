<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RecalculateAllMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:recalculate-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula las métricas (reportes, puntajes) de todos los usuarios líderes del sistema.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando recalculo masivo de métricas...');

        $users = User::where('role', 'leader')->get();

        if ($users->isEmpty()) {
            $this->info('No hay líderes en el sistema para recalcular.');
            return;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $user->recalculateMetrics();
            $bar->advance();
        }

        $bar->finish();
        
        $this->newLine(2);
        $this->info('¡Métricas de ' . $users->count() . ' líderes recalculadas exitosamente!');
    }
}
