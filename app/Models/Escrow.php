<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Currency;
use App\Domain\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The money held against a job.
 *
 * A job is publicly listed **because** an escrow row exists. The badge in the UI
 * reads this relation rather than the job status, so the page cannot claim
 * "funded" while nothing is held.
 */
class Escrow extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'held_cents',
        'currency',
        'funded_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'held_cents' => 'integer',
            'funded_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function held(): Money
    {
        return Money::fromMinorUnits(
            (int) $this->held_cents,
            Currency::fromCode((string) $this->currency),
        );
    }

    public function isFullyHeld(): bool
    {
        return $this->job !== null
            && $this->held_cents >= (int) $this->job->budget_cents;
    }
}
