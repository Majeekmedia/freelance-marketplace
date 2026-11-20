<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Portfolio>
 */
class PortfolioFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->approvedFreelancer(),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(12),
            'external_url' => 'https://example.test/work/'.fake()->slug(2),
            'sort_order' => 0,
        ];
    }
}
