<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate-token {name=SistemaConsolidacion : El nombre para identificar el token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un Bearer Token de Sanctum para que sistemas externos consuman la API REST';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tokenName = $this->argument('name');

        // Asociamos el token al primer administrador del sistema
        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->error('No se encontró ningún administrador en el sistema para asociar el token.');

            return;
        }

        $token = $admin->createToken($tokenName);

        $this->info('¡Token generado exitosamente!');
        $this->newLine();
        $this->warn('Copia este token de acceso, solo se mostrará una vez:');
        $this->line($token->plainTextToken);
        $this->newLine();
    }
}
