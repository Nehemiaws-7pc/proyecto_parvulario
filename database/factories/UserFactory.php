<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
            'rol_id' => Role::factory(),
            'codigo_usuario' => fake()->unique()->bothify('USR-####'),
            'nombre' => fake()->name(),
            'password' => static::$password ??= Hash::make('password'),
            'telefono' => fake()->optional()->numerify('########'),
            'correo' => fake()->optional()->safeEmail(),
            'activo' => true,
            'cambiar_password' => false,
            'ultimo_acceso' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
