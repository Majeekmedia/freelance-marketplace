<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'title',
        'description',
        'external_url',
        'image_path',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path === null) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path);
    }

    /**
     * Only allow http(s) links to be rendered as anchors.
     *
     * A `javascript:` URL in a portfolio entry would be a stored-XSS vector,
     * and the value is user-supplied.
     */
    public function safeExternalUrl(): ?string
    {
        if ($this->external_url === null || $this->external_url === '') {
            return null;
        }

        $scheme = strtolower((string) parse_url($this->external_url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $this->external_url : null;
    }
}
