<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Create an account and sign the user in.
     *
     * Two deliberate behaviours:
     *  - type and status are NOT mass-assigned. `type` comes from the validated
     *    whitelist and is set explicitly; `status` is left to the database
     *    default of `pending` so no request can request approval (FR-003).
     *  - the user is signed in immediately so they can complete their profile
     *    while awaiting approval. AccountGate blocks actions, not login (A7).
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = new User([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
            ]);

            $user->setType($request->accountType());
            $user->status = UserStatus::initial();
            $user->save();

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Your account has been created and is awaiting approval. You can browse in the meantime.');
    }
}
