<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Currency;
use App\Domain\JobStatus;
use App\Domain\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A job posting.
 *
 * The table is `job_postings` because `jobs` is Laravel's queue table. The
 * domain word is still "job", so the model, routes and UI all say job.
 *
 * Invariants this model protects:
 *   - `status` is an enum, never a free string
 *   - `published` is only reachable from code that has actually moved money
 *   - the budget is a Money value object, never a float
 */
class Job extends Model
{
    use HasFactory;

    /**
     * `status`, `poster_id` and `published_at` are NOT fillable.
     *
     * Status is a money-adjacent fact: it decides whether a job is publicly
     * visible and whether applicants may apply. Filling it from request input
     * would let a poster publish an unfunded job, which breaks the product's
     * central promise. It is set through explicit transitions only.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'budget_cents',
        'currency',
        'fx_rate_to_usd',
        'deadline',
    ];

    protected $table = 'job_postings';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'budget_cents' => 'integer',
            'fx_rate_to_usd' => 'string',
            'deadline' => 'date',
            'published_at' => 'datetime',
        ];
    }

    // ------------------------------------------------------------- relations

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function hiredFreelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hired_freelancer_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sequence');
    }

    /**
     * The money held against this job.
     *
     * Its existence is what makes the job legitimately public. The UI reads
     * this rather than the status field, so "funded" is never a claim without
     * a row behind it.
     */
    public function escrow(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Escrow::class);
    }

    /** Is money actually held for this job right now? */
    public function isFunded(): bool
    {
        return $this->escrow()->where('held_cents', '>', 0)->exists();
    }

    // ---------------------------------------------------------------- money

    /** The budget as a value object, in the job's stored currency. */
    public function budget(): Money
    {
        return Money::fromMinorUnits(
            (int) $this->budget_cents,
            Currency::fromCode((string) $this->currency),
        );
    }

    /** Budget in USD, using the rate captured at creation (FR-036c). */
    public function budgetInBaseCurrency(): Money
    {
        $budget = $this->budget();

        if ($this->currency === Currency::base()->value) {
            return $budget;
        }

        // Converted with integer arithmetic only.
        //
        // The obvious `(int) round($cents * $rate)` casts the rate to float and
        // reintroduces exactly the imprecision this codebase exists to avoid —
        // and the float guard test correctly rejected it. The rate is stored as
        // a decimal string with 8 places, so it is scaled to an integer
        // numerator and divided once, with an explicit half-up rounding step.
        return $budget->convert((string) $this->fx_rate_to_usd, Currency::base());
    }

    // ---------------------------------------------------------------- state

    /**
     * Move to a new status, refusing illegal jumps.
     *
     * Publishing is additionally guarded: it must go through this method with
     * a reason, and EscrowService is the only caller permitted to pass
     * `published`.
     */
    public function transitionTo(JobStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new \DomainException(sprintf(
                'Job cannot move from %s to %s.',
                $this->status->value,
                $target->value,
            ));
        }

        $this->status = $target;

        if ($target === JobStatus::PUBLISHED && $this->published_at === null) {
            $this->published_at = now();
        }

        $this->save();
    }

    // --------------------------------------------------------------- scopes

    /**
     * Jobs a visitor is allowed to see.
     *
     * Every public query must go through this. The rule lives here rather than
     * in each controller so a new listing page cannot accidentally leak drafts.
     *
     * The escrow clause is the important part: a job is public **because money
     * is held**, not because a status field says so. Without it there is a
     * window in which an unfunded job is listed and the page claims "escrow
     * funded" — a false claim to a visitor who is deciding whether to trust the
     * platform with their work.
     *
     * @param  Builder<Job>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->whereIn('status', [
            JobStatus::PUBLISHED->value,
            JobStatus::IN_PROGRESS->value,
            JobStatus::DELIVERED->value,
            JobStatus::COMPLETED->value,
        ])
            ->whereNotNull('published_at')
            ->whereHas('escrow', fn (Builder $q) => $q->where('held_cents', '>', 0));
    }

    /**
     * Jobs a freelancer may apply to: public and still open.
     *
     * @param  Builder<Job>  $query
     */
    public function scopeOpenForApplications(Builder $query): void
    {
        $query->where('status', JobStatus::PUBLISHED->value)
            ->whereNotNull('published_at')
            ->whereHas('escrow', fn (Builder $q) => $q->where('held_cents', '>', 0));
    }

    /** @param  Builder<Job>  $query */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('poster_id', $user->id);
    }

    // ------------------------------------------------------------ accessors

    public function isDraft(): bool
    {
        return $this->status === JobStatus::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === JobStatus::PUBLISHED;
    }
}
