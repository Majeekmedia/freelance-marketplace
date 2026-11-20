<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\UserStatus;
use App\Domain\UserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Mass assignable.
     *
     * `type` and `status` are deliberately NOT fillable. They are set explicitly
     * through dedicated methods so that a stray `User::create($request->all())`
     * can never mint an admin or self-approve an account (FR-003).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Hidden from serialisation.
     *
     * Never expose these through JSON: a leaked hash or remember token is a
     * session compromise.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => UserType::class,
            'status' => UserStatus::class,
            'deleted_at' => 'datetime',
        ];
    }

    // ------------------------------------------------------------- predicates

    public function isAdmin(): bool
    {
        return $this->type === UserType::ADMIN;
    }

    public function isFreelancer(): bool
    {
        return $this->type === UserType::FREELANCER;
    }

    public function isPoster(): bool
    {
        return $this->type === UserType::POSTER;
    }

    /** May this account perform gated actions (apply, post, fund, message)? */
    public function canAct(): bool
    {
        return $this->status->canAct();
    }

    /** May this account authenticate at all? */
    public function canAuthenticate(): bool
    {
        return $this->status->canAuthenticate();
    }

    public function isPending(): bool
    {
        return $this->status === UserStatus::PENDING;
    }

    // ------------------------------------------------------------- relations

    public function freelancerProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FreelancerProfile::class);
    }

    public function portfolios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Portfolio::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    // ---------------------------------------------------------------- scopes

    /** @param  Builder<User>  $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', UserStatus::APPROVED->value);
    }

    /** @param  Builder<User>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', UserStatus::PENDING->value);
    }

    /** @param  Builder<User>  $query */
    public function scopeOfType(Builder $query, UserType $type): void
    {
        $query->where('type', $type->value);
    }

    /** @param  Builder<User>  $query */
    public function scopeAdmins(Builder $query): void
    {
        $query->where('type', UserType::ADMIN->value);
    }

    // ------------------------------------------------------------- transitions

    /**
     * Change status, refusing an illegal transition.
     *
     * Status changes are the mechanism behind approval, suspension and
     * reinstatement, so allowing an arbitrary write would bypass the account
     * model entirely.
     */
    public function transitionTo(UserStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new \DomainException(sprintf(
                'Cannot move account from %s to %s.',
                $this->status->value,
                $target->value
            ));
        }

        $this->status = $target;

        if ($target === UserStatus::DELETED) {
            $this->deleted_at = now();
        }

        $this->save();
    }

    public function approve(): void
    {
        $this->transitionTo(UserStatus::APPROVED);
    }

    public function reject(): void
    {
        $this->transitionTo(UserStatus::REJECTED);
    }

    public function suspend(): void
    {
        $this->transitionTo(UserStatus::SUSPENDED);
    }

    /**
     * Set the account type.
     *
     * Only called at registration. Kept out of `$fillable` so it cannot be set
     * by mass assignment from request data.
     */
    public function setType(UserType $type): void
    {
        $this->type = $type;
    }
}
