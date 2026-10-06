<?php

namespace Database\Factories;

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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Admin by default: most tests just need "a signed-in user" and don't
     * care about role boundaries. Use ->staff() to test those.
     *
     * Assumes the seeding migration has already created these roles
     * (2026_09_28_070000_seed_roles_and_permissions.php runs before any
     * test or seeder that uses this factory).
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Admin');
        });
    }

    /**
     * Overrides configure()'s default. This relies on afterCreating()
     * callbacks running in registration order — configure()'s 'Admin'
     * assignment always registers first, so this one, added by chaining
     * ->staff() afterward, always runs second and wins.
     */
    public function staff(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->syncRoles(['Staff']);
        });
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
}
