<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:2000'],

            // Restricted to http/https so a `javascript:` link cannot be stored
            // and later rendered as an anchor (stored XSS).
            'external_url' => ['nullable', 'url:http,https', 'max:500'],

            'image' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ];
    }

    /**
     * The item attributes, excluding the uploaded file.
     *
     * @return array<string, mixed>
     */
    public function validatedItem(): array
    {
        $data = $this->safe()->only(['title', 'description', 'external_url']);

        if ($this->hasFile('image')) {
            $data['image_path'] = $this->file('image')->store('portfolio', 'public');
        }

        return $data;
    }
}
