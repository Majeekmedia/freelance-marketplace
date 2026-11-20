<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreelancerProfile>
 */
class FreelancerProfileFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->approvedFreelancer(),
            'headline' => fake()->jobTitle(),
            'bio' => fake()->paragraph(),
            'skills' => ['Figma', 'Laravel'],
            'hourly_rate_cents' => 4500,
            'hourly_rate_currency' => 'USD',
            'location' => 'Remote',
            'is_public' => true,
        ];
    }

    public function private(): static
    {
        return $this->state(fn (): array => ['is_public' => false]);
    }
}
