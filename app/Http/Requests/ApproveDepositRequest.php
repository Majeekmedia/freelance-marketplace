<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Approve a deposit (FR-040).
 *
 * `credited_amount` is optional: when the admin leaves it blank the requested
 * amount is credited unchanged, which is the common case. When they fill it in,
 * it overrides — because a bank deducted a fee.
 */
class ApproveDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'credited_amount' => ['nullable', 'string', 'max:20', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'credited_amount.regex' => 'Enter an amount such as 250 or 250.00.',
            'reason.required' => 'Record why you are crediting this amount — it goes into the audit log.',
            'reason.min' => 'Give a reason of at least 5 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('credited_amount')) {
            $digits = preg_replace('/[^0-9.]/', '', (string) $this->input('credited_amount')) ?? '';
            $this->merge(['credited_amount' => trim($digits)]);
        }
    }

    /** The override amount as a Money value, or null to use the requested amount. */
    public function creditedAmount(): ?\App\Domain\Money
    {
        if (! $this->filled('credited_amount')) {
            return null;
        }

        return \App\Domain\Money::parse((string) $this->input('credited_amount'));
    }
}
