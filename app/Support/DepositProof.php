<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Deposit proof storage (FR-039c, FR-039e).
 *
 * A proof is a photograph of someone's bank transfer. It is personal financial
 * evidence, so the rules are stricter than for a profile image:
 *
 *  - stored on the `proofs` disk, whose root is outside the web root and whose
 *    `serve` flag is false, so there is no route that can hand it out
 *  - filename randomised; the client's filename is never used, so a file called
 *    `../../.env` cannot traverse anywhere
 *  - MIME checked by the `image` rule, which inspects content rather than
 *    trusting the extension
 *  - deliberately no SVG. An uploaded SVG can carry script, and a proof is
 *    rendered in the admin's own browser session.
 *  - reads are streamed through an authorised controller, never a public URL
 */
class DepositProof
{
    public const DISK = 'proofs';

    public const DIRECTORY = 'deposits';

    public const MAX_KILOBYTES = 5120; // 5 MB

    /** Accepted MIME types. SVG is intentionally absent. */
    public const ALLOWED_MIMES = [
        'image/png',
        'image/jpeg',
        'image/webp',
    ];

    /**
     * Validation rules for a proof upload.
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
        ];
    }

    /**
     * Store a proof and return its path.
     *
     * `store()` generates a random filename, so the returned path leaks nothing
     * about the uploader or the original name.
     */
    public static function store(UploadedFile $file): string
    {
        $path = $file->store(self::DIRECTORY, self::DISK);

        if ($path === false) {
            throw new \RuntimeException('The deposit proof could not be stored.');
        }

        return $path;
    }

    /** Remove a stored proof. Safe to call with a null path. */
    public static function remove(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public static function exists(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        return Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Stream a proof to an already-authorised caller.
     *
     * Authorisation is the caller's job, deliberately: this method has no idea
     * who is asking, so it cannot accidentally become the place where access is
     * half-checked. The controller must prove ownership first.
     */
    public static function response(?string $path, string $downloadName = 'deposit-proof'): StreamedResponse
    {
        if (! self::exists($path)) {
            abort(404);
        }

        return Storage::disk(self::DISK)->response($path, $downloadName, [
            // Never let a browser sniff this into something executable.
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
