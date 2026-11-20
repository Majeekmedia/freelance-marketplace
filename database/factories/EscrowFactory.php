<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Escrow;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Escrow> */
class EscrowFactory extends Factory
{
    protected $model = Escrow::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'held_cents' => 250_000,
            'currency' => 'USD',
            'funded_at' => now(),
        ];
    }

    /** Hold exactly what the job is worth. */
    public function forJob(Job $job): static
    {
        return $this->state(fn (array $attributes): array => [
            'job_id' => $job->id,
            'held_cents' => $job->budget_cents,
            'currency' => $job->currency,
        ]);
    }

    /** Nothing actually held — the job must not be publicly visible. */
    public function empty(): static
    {
        return $this->state(fn (array $attributes): array => [
            'held_cents' => 0,
        ]);
    }
}
