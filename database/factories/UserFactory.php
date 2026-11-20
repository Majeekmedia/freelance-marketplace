<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\UserStatus;
use App\Domain\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'type' => UserType::FREELANCER,
            'status' => UserStatus::PENDING,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    // ------------------------------------------------------------------ types

    public function freelancer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => UserType::FREELANCER,
        ]);
    }

    public function poster(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => UserType::POSTER,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => UserType::ADMIN,
            'status' => UserStatus::APPROVED,
        ]);
    }

    // ----------------------------------------------------------------- status

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::PENDING,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::APPROVED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::REJECTED,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    // -------------------------------------------------------------- compounds

    /** An approved freelancer, ready to apply for work. */
    public function approvedFreelancer(): static
    {
        return $this->freelancer()->approved();
    }

    /** An approved poster, ready to post and fund jobs. */
    public function approvedPoster(): static
    {
        return $this->poster()->approved();
    }
}
