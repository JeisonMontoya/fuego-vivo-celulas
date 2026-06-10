<?php

namespace Database\Factories;

use App\Models\Cell;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'document' => $this->faker->unique()->numerify('##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'photo_path' => null,
            'cell_id' => Cell::factory(),
            'sector' => $this->faker->randomElement(['Norte', 'Sur', 'Este', 'Oeste', 'Centro']),
            'entry_date' => $this->faker->date(),
            'supervisor_id' => null,
            'role' => 'leader',
            'status' => 'active',
            'rating' => fake()->randomFloat(1, 1, 5),
            'reports_count' => fake()->numberBetween(0, 50),
            'compliance_percentage' => fake()->numberBetween(50, 100),
            'compliments' => [fake()->randomElement(['Gran líder', 'Muy dedicado', 'Excelente maestro', 'Siempre dispuesto', 'Muy puntual'])],
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the user is a supervisor.
     */
    public function supervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'supervisor',
            'cell_id' => null,
        ]);
    }

    /**
     * Indicate that the user is pending activation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
