<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PaymentMethod;
use App\Support\DepositProof;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Submit a deposit request (FR-039).
 *
 * Named `StoreDepositRequest` rather than `DepositRequest` because the model is
 * already called that; two classes with one name in the same namespace chain is
 * a fatal error waiting to be written.
 *
 * The interesting rule is `payment_method_id`: it must exist **and be enabled**.
 * Validating existence alone would let a poster post the id of a method the admin
 * has since disabled, which is exactly the case the requirement calls out.
 *
 * `amount` is validated as a decimal string and converted to integer minor units
 * in `amountCents()`, so no float ever touches money.
 */
class StoreDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isPoster();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'payment_method_id' => [
                'required',
                'integer',
                // Existence AND enabled, checked in one place. A disabled method
                // must be rejected even if the id is valid (FR-039b).
                Rule::exists('payment_methods', 'id')
                    ->where('is_enabled', true)
                    ->whereNull('deleted_at'),
            ],

            'amount' => ['required', 'string', 'max:20', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],

            'transfer_reference' => ['required', 'string', 'min:4', 'max:255'],

            'proof' => DepositProof::rules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'payment_method_id.exists' => 'That payment method is no longer available. Choose another.',
            'amount.regex' => 'Enter an amount such as 250 or 250.00.',
            'transfer_reference.min' => 'Enter the bank reference or transaction hash so we can match your transfer.',
            'proof.image' => 'Upload a screenshot or photo of the transfer (PNG, JPEG or WebP).',
            'proof.mimes' => 'The proof must be a PNG, JPEG or WebP image.',
            'proof.max' => 'The proof must be under '.DepositProof::MAX_KILOBYTES.' KB.',
        ];
    }

    /** Normalise a human-typed amount before validation. */
    protected function prepareForValidation(): void
    {
        if (! $this->has('amount')) {
            return;
        }

        $raw = (string) $this->input('amount');
        $digits = preg_replace('/[^0-9.]/', '', $raw) ?? '';

        $this->merge(['amount' => trim($digits)]);
    }

    /**
     * The chosen method, guaranteed enabled at validation time.
     *
     * Re-fetched here so the controller cannot accidentally use a different row.
     */
    public function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::query()
            ->whereKey($this->integer('payment_method_id'))
            ->where('is_enabled', true)
            ->firstOrFail();
    }

    /** Requested amount in integer minor units. */
    public function amountCents(): int
    {
        return \App\Domain\Money::parse((string) $this->input('amount'))->minorUnits;
    }
}
