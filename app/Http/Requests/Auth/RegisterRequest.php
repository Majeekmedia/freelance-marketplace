<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Registration request (FR-003).
 *
 * The `type` rule is a whitelist from UserType::registrable(), which contains
 * only freelancer and poster. An attempt to register as admin fails validation,
 * so privilege escalation never reaches the controller.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'type' => ['required', 'string', Rule::in(UserType::registrable())],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'type.in' => 'Choose whether you are joining as a freelancer or a job poster.',
        ];
    }

    /** The validated account type, as an enum. */
    public function accountType(): UserType
    {
        return UserType::from($this->string('type')->toString());
    }
}
