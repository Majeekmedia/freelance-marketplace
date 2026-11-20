<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Logo upload handling.
 *
 * Requirements (FR-035):
 *  - admin can upload a logo and it replaces the text wordmark everywhere
 *  - removing it restores the wordmark
 *  - a non-image or oversized file is rejected with a clear message
 *
 * Security notes that matter here:
 *  - SVG is REJECTED. An SVG can carry <script>, and a logo is served from the
 *    site's own origin, so an uploaded SVG is a stored-XSS vector.
 *  - the stored filename is randomised; the client filename is never used.
 *  - the file is stored on the public disk deliberately: a logo is marketing
 *    material, not private data.
 */
class LogoUploader
{
    public const DISK = 'public';

    public const DIRECTORY = 'brand';

    public const MAX_KILOBYTES = 2048; // 2 MB

    /** Accepted MIME types. SVG is intentionally absent. */
    public const ALLOWED_MIMES = [
        'image/png',
        'image/jpeg',
        'image/webp',
    ];

    /**
     * Laravel validation rules for a logo upload.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return [
            'required',
            'file',
            'image',
            'mimes:png,jpg,jpeg,webp',
            'max:'.self::MAX_KILOBYTES,
            'dimensions:max_width=2000,max_height=2000',
        ];
    }

    /**
     * Store an uploaded logo and point the setting at it.
     *
     * The previous logo is removed only after the new one is stored, so a failed
     * upload never leaves the site with no logo at all.
     */
    public static function store(UploadedFile $file): string
    {
        self::assertAllowed($file);

        $previous = SiteSettings::logoPath();

        $path = $file->store(self::DIRECTORY, self::DISK);

        SiteSettings::set('brand_logo_path', $path);

        if ($previous !== null && $previous !== $path) {
            Storage::disk(self::DISK)->delete($previous);
        }

        return $path;
    }

    /** Remove the logo entirely, restoring the text wordmark. */
    public static function remove(): void
    {
        $path = SiteSettings::logoPath();

        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }

        SiteSettings::set('brand_logo_path', null);
    }

    /** Public URL for the current logo, or null when there is none. */
    public static function url(): ?string
    {
        $path = SiteSettings::logoPath();

        return $path === null ? null : Storage::disk(self::DISK)->url($path);
    }

    /**
     * Defence in depth: validation rules protect the HTTP layer, but this method
     * is also called directly, so the type and size are re-checked here.
     */
    private static function assertAllowed(UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException(
                'Logo must be a PNG, JPEG or WebP image. SVG is not accepted.'
            );
        }

        if ($file->getSize() > self::MAX_KILOBYTES * 1024) {
            throw new \InvalidArgumentException(
                'Logo must be 2 MB or smaller.'
            );
        }
    }
}
