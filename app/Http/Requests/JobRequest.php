<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Currency;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create or edit a job draft (FR-009, FR-036).
 *
 * The currency rule is the interesting one. It does not merely validate
 * "in:USD" — it names NGN explicitly so the error message tells the person what
 * the product supports instead of "the selected currency is invalid". A silent
 * rejection is how someone concludes the form is broken.
 *
 * `status` is deliberately absent from these rules. A poster may not set their
 * own job live; publishing is a consequence of funding escrow, not a form field.
 */
class JobRequest extends FormRequest
{
    /**
     * Authorisation here is only "is this person allowed to reach the action at
     * all". Ownership is enforced in the controller with a 404, because a 403
     * from this method would confirm that the job id exists to a stranger.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalise the budget before validation.
     *
     * Humans type "$1,250.50". Stripping the decoration here means the rules
     * below can stay strict about the canonical form, and `jobAttributes()`
     * never has to guess what it was given.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('budget')) {
            return;
        }

        $raw = (string) $this->input('budget');
        $digits = preg_replace('/[^0-9.]/', '', $raw) ?? '';

        $this->merge(['budget' => trim($digits)]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:8', 'max:160'],

            // A one-line brief is not enough for a freelancer to quote on, and
            // it is the single biggest cause of junk listings.
            'description' => ['required', 'string', 'min:40', 'max:8000'],

            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],

            // Integer minor units arrive as a decimal string; parse it in
            // budget() rather than trusting a cast.
            'budget' => ['required', 'string', 'max:20', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],

            'currency' => [
                'required',
                'string',
                // Only USD may be stored on a job (FR-036). The whitelist is
                // read from the enum so this cannot drift from the code.
                Rule::in([Currency::base()->value]),
            ],

            'deadline' => ['nullable', 'date', 'after:today'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'currency.in' => 'Jobs are priced in USD. '
                .'NGN is not a supported currency; display currencies are '
                .implode(', ', Currency::displayWhitelist()).'.',
            'category_id.exists' => 'Choose an active category.',
            'description.min' => 'Describe the work in at least 40 characters so freelancers can quote accurately.',
            'deadline.after' => 'The deadline must be in the future.',
        ];
    }

    /**
     * Validated data, with the budget converted to integer minor units.
     *
     * Kept separate from validated() so the conversion happens exactly once and
     * cannot be forgotten by a caller.
     *
     * @return array<string, mixed>
     */
    public function jobAttributes(): array
    {
        $data = $this->safe()->only([
            'title',
            'description',
            'category_id',
            'currency',
            'deadline',
        ]);

        $data['budget_cents'] = \App\Domain\Money::parse(
            (string) $this->input('budget'),
            Currency::fromCode((string) $this->input('currency')),
        )->minorUnits;

        // Jobs are USD, so the stored rate is 1. Kept explicit rather than
        // relying on the column default, so a future multi-currency change has
        // one obvious place to write the real rate.
        $data['fx_rate_to_usd'] = 1;

        return $data;
    }
}
