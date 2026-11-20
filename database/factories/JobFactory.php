<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\JobStatus;
use App\Models\Category;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Job> */
class JobFactory extends Factory
{
    protected $model = Job::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'poster_id' => User::factory()->approvedPoster(),
            'category_id' => Category::factory(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(4),
            'budget_cents' => $this->faker->numberBetween(50_000, 5_000_000),
            'currency' => 'USD',
            'fx_rate_to_usd' => 1,
            'deadline' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'status' => JobStatus::DRAFT,
            'published_at' => null,
        ];
    }

    /** A draft: private, not fundable until published through escrow. */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => JobStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * A published job.
     *
     * The factory can create this state directly, but production code cannot:
     * in the application, `published` is only reachable after EscrowService has
     * moved money. Tests need the state without the money setup, so it is
     * granted here explicitly.
     *
     * NOTE: this state alone is NOT publicly visible any more — the public
     * scope also requires an escrow row. Use `funded()` for that.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => JobStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * A published job with its budget actually held in escrow.
     *
     * This is the only shape a visitor may see. Anything less is a job claiming
     * a funding guarantee it does not have.
     */
    public function funded(): static
    {
        return $this->published()->afterCreating(function (Job $job): void {
            $job->escrow()->create([
                'held_cents' => $job->budget_cents,
                'currency' => $job->currency,
                'funded_at' => now(),
            ]);
        });
    }

    public function forPoster(User $poster): static
    {
        return $this->state(fn (array $attributes): array => [
            'poster_id' => $poster->id,
        ]);
    }
}
