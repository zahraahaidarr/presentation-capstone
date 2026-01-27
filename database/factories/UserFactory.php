<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
        'first_name' => fake()->firstName(),
        'last_name'  => fake()->lastName(),
        'email'      => fake()->unique()->safeEmail(),
        'phone'      => fake()->numerify('70######'),
        'role'       => 'WORKER',
        'status'     => 'PENDING',
        'email_verified_at' => now(),
        'password'   => static::$password ??= Hash::make('password'),
        'remember_token' => Str::random(10),
    ];
}
public function worker(): static { return $this->state(fn() => ['role' => 'WORKER']); }
public function employee(): static { return $this->state(fn() => ['role' => 'EMPLOYEE']); }
public function admin(): static { return $this->state(fn() => ['role' => 'ADMIN']); }

public function active(): static { return $this->state(fn() => ['status' => 'ACTIVE']); }
public function suspended(): static { return $this->state(fn() => ['status' => 'SUSPENDED']); }


    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
