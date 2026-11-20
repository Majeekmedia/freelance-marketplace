<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isFreelancer();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:140'],
            'bio' => ['nullable', 'string', 'max:4000'],
            'location' => ['nullable', 'string', 'max:120'],

            // Comma-separated in the form; split and bounded in skillsList().
            'skills' => ['nullable', 'string', 'max:400'],

            // Money is parsed into integer minor units; reject anything that
            // does not look like an amount rather than coercing it.
            'hourly_rate' => ['nullable', 'string', 'max:20', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],

            'is_public' => ['sometimes', 'boolean'],

            'avatar' => ['sometimes', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    /** @return list<string> */
    public function skillsList(): array
    {
        $raw = $this->string('skills')->toString();

        if ($raw === '') {
            return [];
        }

        $skills = array_map(static fn (string $s): string => trim($s), explode(',', $raw));
        $skills = array_filter($skills, static fn (string $s): bool => $s !== '');
        $skills = array_values(array_unique($skills));

        // Bound the list so a profile cannot become a keyword-spam page.
        return array_slice($skills, 0, 20);
    }
}
