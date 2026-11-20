<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * What a user IS on the platform.
 *
 * Fixed at registration and not user-changeable (requirements assumption A5).
 * The type determines which dashboard the user sees and which actions are
 * available; it is never inferred from what the user happens to be doing.
 */
enum UserType: string
{
    case FREELANCER = 'freelancer';
    case POSTER = 'poster';
    case ADMIN = 'admin';

    /**
     * Types a visitor may choose at registration.
     *
     * `admin` is deliberately absent. Registration must never be able to create
     * an admin, and the safest way to guarantee that is for the value not to be
     * offered at all (FR-003).
     *
     * @return list<string>
     */
    public static function registrable(): array
    {
        return [self::FREELANCER->value, self::POSTER->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::FREELANCER => 'Freelancer',
            self::POSTER => 'Job poster',
            self::ADMIN => 'Administrator',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
