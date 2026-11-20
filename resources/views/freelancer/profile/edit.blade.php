@extends('layouts.app')

@section('title', 'Edit profile')

@section('content')
<h1>Your profile</h1>
<p class="sub">This is what job posters see when they consider you.</p>

<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div style="margin-bottom:1rem">
            <label for="headline" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem">Headline</label>
            <input id="headline" name="headline" type="text" maxlength="140"
                   value="{{ old('headline', $profile->headline) }}"
                   placeholder="Product designer specialising in SaaS dashboards"
                   style="width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.9rem;background:#fff">
        </div>

        <div style="margin-bottom:1rem">
            <label for="bio" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem">About you</label>
            <textarea id="bio" name="bio" rows="6" maxlength="4000"
                      style="width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.9rem;background:#fff;resize:vertical">{{ old('bio', $profile->bio) }}</textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
            <div>
                <label for="skills" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem">Skills</label>
                <input id="skills" name="skills" type="text" value="{{ old('skills', $skills) }}"
                       placeholder="Figma, Laravel, Copywriting"
                       style="width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.9rem;background:#fff">
                <p style="font-size:.75rem;color:var(--ink-3);margin:.3rem 0 0">Comma separated, up to 20.</p>
            </div>
            <div>
                <label for="location" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem">Location</label>
                <input id="location" name="location" type="text" maxlength="120"
                       value="{{ old('location', $profile->location) }}"
                       placeholder="Remote · Berlin"
                       style="width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.9rem;background:#fff">
            </div>
        </div>

        <div style="margin-bottom:1.1rem;max-width:220px">
            <label for="hourly_rate" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem">Hourly rate (USD)</label>
            <input id="hourly_rate" name="hourly_rate" type="text" inputmode="decimal"
                   value="{{ old('hourly_rate', $profile->hourly_rate_cents ? number_format($profile->hourly_rate_cents / 100, 2, '.', '') : '') }}"
                   placeholder="45.00"
                   style="width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.9rem;background:#fff">
        </div>

        <label style="display:inline-flex;align-items:center;gap:.5rem;font-size:.875rem;color:var(--ink-2)">
            <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $profile->is_public ?? true))>
            Show my profile in the public directory
        </label>
        @unless (auth()->user()->canAct())
            <p style="font-size:.78rem;color:var(--amber);margin:.5rem 0 0">
                Publishing requires an approved account. You can save a private draft now.
            </p>
        @endunless
    </div>

    <div style="margin-top:1rem">
        <button class="btn" type="submit">Save profile</button>
        @if (auth()->user()->freelancerProfile?->is_public && auth()->user()->canAct())
            <a class="btn btn-secondary" href="{{ route('freelancers.show', auth()->user()) }}">View public page</a>
        @endif
        <a class="btn btn-secondary" href="{{ route('portfolio.index') }}">Portfolio</a>
    </div>
</form>

<form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" style="margin-top:1.5rem">
    @csrf
    <div class="card">
        <label for="avatar" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem">Profile photo</label>
        <input id="avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp"
               style="font-size:.85rem">
        <p style="font-size:.75rem;color:var(--ink-3);margin:.4rem 0 .7rem">PNG, JPEG or WebP, up to 2 MB.</p>
        <button class="btn btn-secondary btn-sm" type="submit">Upload photo</button>
    </div>
</form>
@endsection
