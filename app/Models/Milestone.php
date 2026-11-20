<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Currency;
use App\Domain\MilestoneStatus;
use App\Domain\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    use HasFactory;

    /**
     * `status` is not fillable: it is advanced through transitionTo() so an
     * illegal jump (pending → released) cannot be written from request input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sequence',
        'title',
        'amount_cents',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => MilestoneStatus::class,
            'sequence' => 'integer',
            'amount_cents' => 'integer',
            'delivered_at' => 'datetime',
            'accepted_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function amount(): Money
    {
        return Money::fromMinorUnits(
            (int) $this->amount_cents,
            Currency::fromCode((string) ($this->job?->currency ?? 'USD')),
        );
    }

    public function transitionTo(MilestoneStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new \DomainException(sprintf(
                'Milestone cannot move from %s to %s.',
                $this->status->value,
                $target->value,
            ));
        }

        $this->status = $target;

        match ($target) {
            MilestoneStatus::DELIVERED => $this->delivered_at ??= now(),
            MilestoneStatus::ACCEPTED => $this->accepted_at ??= now(),
            MilestoneStatus::RELEASED => $this->released_at ??= now(),
            default => null,
        };

        $this->save();
    }
}
