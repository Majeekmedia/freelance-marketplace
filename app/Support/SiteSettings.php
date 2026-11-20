<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * The one place site-wide settings are read and written.
 *
 * Why this exists
 * ---------------
 * FR-035 requires the site name and logo to be editable by admin and to appear
 * everywhere. The failure mode that requirement guards against is a template
 * hardcoding "Trust-Prime" in one place and rendering a stale name in another.
 *
 * So: no blade file may contain the brand name literally. Everything reads from
 * here. A test greps the views to enforce it.
 *
 * Values are cached per request because the brand appears in the header, footer
 * and every page title — reading the database several times per render is waste
 * on shared hosting.
 */
class SiteSettings
{
    /** Keys this application knows about, with their defaults. */
    public const DEFAULTS = [
        'brand_name' => 'Trust-Prime',
        'brand_tagline' => 'Escrow-backed freelance work',
        'brand_logo_path' => null,
        'contact_email' => 'hello@trust-prime.test',
        'contact_phone' => null,
        'support_hours' => null,
    ];

    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    /** Forget the per-request cache. Called after any write, and in tests. */
    public static function flush(): void
    {
        self::$cache = null;
    }

    /** All known settings, defaults applied. */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $stored = Setting::query()->pluck('value', 'key')->all();

        $merged = [];
        foreach (self::DEFAULTS as $key => $default) {
            $value = $stored[$key] ?? null;
            $merged[$key] = ($value === null || $value === '') ? $default : $value;
        }

        self::$cache = $merged;

        return $merged;
    }

    public static function get(string $key, ?string $fallback = null): ?string
    {
        return self::all()[$key] ?? $fallback ?? self::DEFAULTS[$key] ?? null;
    }

    /**
     * Write a setting.
     *
     * Unknown keys are refused: this class exists so that the set of settings is
     * deliberate, and silently accepting arbitrary keys would turn it back into
     * an untyped key/value store.
     */
    public static function set(string $key, ?string $value): void
    {
        if (! array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException(sprintf('Unknown setting "%s".', $key));
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        self::flush();
    }

    /** @param array<string, string|null> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
    }

    // ----------------------------------------------------------------- brand

    public static function brandName(): string
    {
        return (string) self::get('brand_name');
    }

    public static function tagline(): string
    {
        return (string) self::get('brand_tagline');
    }

    public static function logoPath(): ?string
    {
        $path = self::get('brand_logo_path');

        return $path === null || $path === '' ? null : $path;
    }

    /**
     * Has an admin uploaded a logo?
     *
     * When false, the UI renders a text wordmark built from the brand name — so
     * the site is never brandless between first deploy and first upload.
     */
    public static function hasLogo(): bool
    {
        return self::logoPath() !== null;
    }

    /** Initials for the fallback wordmark, e.g. "Trust-Prime" → "TP". */
    public static function initials(): string
    {
        $words = preg_split('/[\s\-_]+/', self::brandName()) ?: [];

        $initials = '';
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return mb_substr($initials, 0, 2);
    }

    public static function contactEmail(): ?string
    {
        return self::get('contact_email');
    }

    public static function contactPhone(): ?string
    {
        return self::get('contact_phone');
    }
}
