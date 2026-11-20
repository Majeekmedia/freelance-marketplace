<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create or edit a payment method (FR-038d).
 *
 * The validation is conditional on the type, because the two types need
 * different fields. Submitting a crypto method without a network is rejected
 * here rather than saved as a half-configured row that a poster cannot use.
 *
 * `required_if`/`required_unless` are used rather than building rules in a
 * closure so the error messages point at the field the admin actually missed.
 */
class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $bank = PaymentMethodType::BANK_TRANSFER->value;
        $crypto = PaymentMethodType::CRYPTO->value;

        return [
            'label' => ['required', 'string', 'min:3', 'max:80'],

            'type' => ['required', Rule::enum(PaymentMethodType::class)],

            // --- Bank transfer ---
            'bank_name' => ["required_if:type,{$bank}", 'nullable', 'string', 'max:120'],
            'account_name' => ["required_if:type,{$bank}", 'nullable', 'string', 'max:120'],
            'account_number' => ["required_if:type,{$bank}", 'nullable', 'string', 'max:60'],
            'routing_detail' => ['nullable', 'string', 'max:160'],

            // --- Crypto transfer ---
            'crypto_asset' => ["required_if:type,{$crypto}", 'nullable', 'string', 'max:20'],
            'crypto_network' => ["required_if:type,{$crypto}", 'nullable', 'string', 'max:40'],
            // A wallet address is alphanumeric with limited punctuation; the
            // pattern rejects whitespace and quotes, which are never valid and
            // are the shape of an injection attempt.
            'crypto_address' => [
                "required_if:type,{$crypto}",
                'nullable',
                'string',
                'max:120',
                'regex:/^[A-Za-z0-9:_\-]{16,120}$/',
            ],

            'is_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bank_name.required_if' => 'A bank transfer method needs a bank name.',
            'account_name.required_if' => 'A bank transfer method needs an account name.',
            'account_number.required_if' => 'A bank transfer method needs an account number.',
            'crypto_asset.required_if' => 'A crypto method needs an asset, such as USDT.',
            'crypto_network.required_if' => 'A crypto method needs a network, such as ERC-20 or TRC-20.',
            'crypto_address.required_if' => 'A crypto method needs a wallet address.',
            'crypto_address.regex' => 'That does not look like a wallet address. Use letters, digits, colons, hyphens or underscores only.',
        ];
    }

    /**
     * Attributes ready to save.
     *
     * The type-specific irrelevant fields are nulled so a method that changed
     * type cannot keep a stale address from its previous shape.
     *
     * @return array<string, mixed>
     */
    public function methodAttributes(): array
    {
        $type = PaymentMethodType::from((string) $this->input('type'));

        $data = $this->safe()->only([
            'label',
            'bank_name',
            'account_name',
            'account_number',
            'routing_detail',
            'crypto_asset',
            'crypto_network',
            'crypto_address',
            'is_enabled',
            'sort_order',
        ]);

        $data['type'] = $type;

        foreach ($type->irrelevantFields() as $field) {
            $data[$field] = null;
        }

        return $data;
    }
}
