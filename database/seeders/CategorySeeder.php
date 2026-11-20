<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Job categories.
 *
 * Safe to run in production: no accounts, no money, no personal data. It is
 * idempotent, so a redeploy cannot duplicate the list.
 */
class CategorySeeder extends Seeder
{
    /** @var list<string> */
    private const CATEGORIES = [
        'Web Development',
        'Mobile Development',
        'Product & UX Design',
        'Brand & Graphic Design',
        'Writing & Editing',
        'Data & Analytics',
        'DevOps & Infrastructure',
        'Marketing & SEO',
        'Video & Audio',
        'Translation',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
