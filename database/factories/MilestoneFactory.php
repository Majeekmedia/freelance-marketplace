<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\MilestoneStatus;
use App\Models\Job;
use App\Models\Milestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Milestone> */
class MilestoneFactory extends Factory
{
    protected $model = Milestone::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'sequence' => 1,
            'title' => $this->faker->sentence(3),
            'amount_cents' => $this->faker->numberBetween(10_000, 200_000),
            'status' => MilestoneStatus::PENDING,
        ];
    }
}
