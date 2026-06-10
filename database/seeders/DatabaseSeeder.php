<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Crear Administrador
        User::factory()->admin()->create([
            'name' => 'Administrador Fuego Vivo',
            'email' => 'admin@fuegovivo.com.co',
            'password' => Hash::make('admin123'),
            'document' => '1000000001',
            'cell_id' => null,
        ]);

        // Crear Supervisor
        User::factory()->supervisor()->create([
            'name' => 'Supervisor Zona Norte',
            'email' => 'supervisor@fuegovivo.com.co',
            'password' => Hash::make('super123'),
            'document' => '1000000002',
        ]);

        // Crear Líder Activo
        User::factory()->create([
            'name' => 'Líder Activo Prueba',
            'email' => 'lider@fuegovivo.com.co',
            'password' => Hash::make('lider123'),
            'document' => '1000000003',
            'status' => 'active',
        ]);

        // Crear Líder Pendiente
        User::factory()->pending()->create([
            'name' => 'Líder Pendiente Prueba',
            'email' => 'pendiente@fuegovivo.com.co',
            'password' => Hash::make('lider123'),
            'document' => '1000000004',
        ]);
    }
}
