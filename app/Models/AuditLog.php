<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Currency;
use App\Domain\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One auditable admin action (FR-028).
 *
 * Append-only. `update()`, `delete()` and the model events all refuse, and on
 * SQLite the database refuses too.
 *
 * Created exclusively through `AuditLogger`, which guarantees the actor comes
 * from the session rather than from request input.
 */
class AuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Nothing is mass assignable. Every field is written by `AuditLogger` so a
     * careless `create($request->all())` cannot forge an audit trail.
     *
     * @var list<string>
     */
    protected $fillable = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'amount_cents' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Write a row.
     *
     * The one door, for the same reason as `LedgerEntry::post()`.
     *
     * @param  array<string, mixed>|null  $context
     */
    public static function record(
        ?int $actorId,
        string $action,
        string $targetType,
        int $targetId,
        ?string $reason = null,
        ?int $amountCents = null,
        ?string $currency = null,
        ?array $context = null,
        ?string $ipAddress = null,
    ): self {
        $log = new self();

        $log->actor_id = $actorId;
        $log->action = $action;
        $log->target_type = $targetType;
        $log->target_id = $targetId;
        $log->reason = $reason;
        $log->amount_cents = $amountCents;
        $log->currency = $currency;
        $log->context = $context;
        $log->ip_address = $ipAddress;

        $log->save();

        return $log;
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \DomainException('Audit log entries are immutable.');
    }

    public function delete()
    {
        throw new \DomainException('Audit log entries cannot be deleted.');
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \DomainException('Audit log entries are immutable.');
        });

        static::deleting(function (): never {
            throw new \DomainException('Audit log entries cannot be deleted.');
        });
    }

    // ------------------------------------------------------------- relations

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // ---------------------------------------------------------------- money

    public function amount(): ?Money
    {
        if ($this->amount_cents === null) {
            return null;
        }

        return Money::fromMinorUnits(
            (int) $this->amount_cents,
            Currency::fromCode((string) ($this->currency ?? 'USD')),
        );
    }

    // --------------------------------------------------------------- scopes

    /** @param  Builder<AuditLog>  $query */
    public function scopeForTarget(Builder $query, string $type, int $id): void
    {
        $query->where('target_type', $type)->where('target_id', $id);
    }
}
