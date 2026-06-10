<?php

namespace Database\Factories;

use App\Models\Cell;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cell>
 */
class CellFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Célula '.$this->faker->words(2, true),
            'address' => $this->faker->address(),
            'meeting_day' => $this->faker->randomElement(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']),
            'meeting_time' => $this->faker->randomElement(['06:00 PM', '07:00 PM', '08:00 PM']),
            'status' => 'active',
        ];
    }
}
