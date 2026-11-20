<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Currency;
use App\Domain\Money;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Freelancer profile (FR-007).
 *
 * Scope separation matters here:
 *   edits are made by the owner at /profile
 *   the public reads a *projection* via publicAttributes(), never the model
 *
 * The public page never receives a User instance, so it cannot accidentally
 * render an email, phone or balance — those columns are not in the array it gets.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->freelancerProfile ?? new FreelancerProfile();

        return view('freelancer.profile.edit', [
            'profile' => $profile,
            'skills' => implode(', ', $profile->skills ?? []),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $profile = $user->freelancerProfile()->firstOrNew([]);

        $profile->headline = $request->string('headline')->toString() ?: null;
        $profile->bio = $request->string('bio')->toString() ?: null;
        $profile->location = $request->string('location')->toString() ?: null;
        $profile->skills = $request->skillsList();

        if ($request->filled('hourly_rate')) {
            $rate = Money::parse($request->string('hourly_rate')->toString());
            $profile->hourly_rate_cents = $rate->minorUnits;
            $profile->hourly_rate_currency = Currency::base()->value;
        } else {
            $profile->hourly_rate_cents = null;
            $profile->hourly_rate_currency = null;
        }

        // Switching to public requires an approved account (see the policy).
        if ($request->boolean('is_public')) {
            $this->authorize('publish', $user);
            $profile->is_public = true;
        } else {
            $profile->is_public = false;
        }

        $profile->user_id = $user->id;
        $profile->save();

        return back()->with('status', 'Profile saved.');
    }

    /** Upload or replace the avatar. */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $profile = $user->freelancerProfile()->firstOrNew([]);
        $profile->user_id = $user->id;

        if ($profile->avatar_path !== null) {
            Storage::disk('public')->delete($profile->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $profile->avatar_path = $path;
        $profile->save();

        return back()->with('status', 'Photo updated.');
    }

    /**
     * Public profile page.
     *
     * A pending, suspended or deleted freelancer is not reachable, and a
     * private profile is only reachable by its owner.
     */
    public function show(Request $request, User $freelancer): View
    {
        abort_unless($freelancer->isFreelancer(), 404);

        $this->authorize('view', $freelancer);

        $profile = $freelancer->freelancerProfile;

        return view('freelancer.profile.show', [
            'freelancer' => $freelancer,
            'public' => $profile?->publicAttributes() ?? [],
            'portfolio' => $freelancer->portfolios()->get()
                ->map(fn ($item) => [
                    'title' => $item->title,
                    'description' => $item->description,
                    'url' => $item->safeExternalUrl(),
                    'image' => $item->imageUrl(),
                ]),
        ]);
    }
}
