<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Currency;
use App\Domain\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FreelancerProfile extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'headline',
        'bio',
        'skills',
        'hourly_rate_cents',
        'hourly_rate_currency',
        'location',
        'is_public',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'is_public' => 'boolean',
            'hourly_rate_cents' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'user_id', 'user_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    // --------------------------------------------------------------- display

    /** Only the fields a visitor is allowed to see. */
    public function publicAttributes(): array
    {
        return [
            'headline' => $this->headline,
            'bio' => $this->bio,
            'skills' => $this->skills ?? [],
            'location' => $this->location,
            'avatar_url' => $this->avatarUrl(),
            'hourly_rate' => $this->hourlyRate()?->format(),
        ];
    }

    public function hourlyRate(): ?Money
    {
        if ($this->hourly_rate_cents === null) {
            return null;
        }

        $currency = Currency::tryFrom((string) $this->hourly_rate_currency) ?? Currency::base();

        return Money::fromMinorUnits($this->hourly_rate_cents, $currency);
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_path === null) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path);
    }

    // ---------------------------------------------------------------- scopes

    /**
     * Profiles shown in the public directory.
     *
     * Requires visibility AND an approved account. An unapproved freelancer is
     * never discoverable even if they ticked "public", because approval is the
     * platform's own gate (FR-004).
     *
     * @param  Builder<FreelancerProfile>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('is_public', true)
            ->whereHas('user', fn (Builder $q) => $q->approved());
    }
}
