<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single site setting.
 *
 * Only ever read and written through App\Support\SiteSettings so that defaults,
 * caching and validation live in one place.
 */
class Setting extends Model
{
    use HasFactory;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'key',
        'value',
    ];
}
