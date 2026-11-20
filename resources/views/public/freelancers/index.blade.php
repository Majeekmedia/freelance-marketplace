@extends('layouts.app')

@section('title', 'Freelancers')

@section('content')
<h1>Freelancers</h1>
<p class="sub">Available people, with work you can look at.</p>

@if ($profiles->isEmpty())
    <div class="card">
        <p style="margin:0;color:var(--ink-3);font-size:.9rem">No public profiles yet.</p>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem">
        @foreach ($profiles as $profile)
            <a class="card" href="{{ route('freelancers.show', $profile->user) }}" style="display:block">
                <p style="margin:0;font-family:var(--serif);font-size:1.15rem">{{ $profile->user->name }}</p>
                @if ($profile->headline)
                    <p style="margin:.3rem 0 0;font-size:.85rem;color:var(--ink-2)">{{ $profile->headline }}</p>
                @endif
                @if ($profile->hourlyRate())
                    <p style="margin:.7rem 0 0;font-family:var(--serif);font-size:1.1rem;color:var(--forest)">
                        {{ $profile->hourlyRate()->format() }}<span style="font-size:.65rem;font-family:var(--sans);color:var(--ink-3)"> /hr</span>
                    </p>
                @endif
                @if (! empty($profile->skills))
                    <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.7rem">
                        @foreach (array_slice($profile->skills, 0, 3) as $skill)
                            <span style="font-size:.7rem;padding:.15rem .45rem;border-radius:4px;background:var(--paper-2);color:var(--ink-2)">{{ $skill }}</span>
                        @endforeach
                    </div>
                @endif
            </a>
        @endforeach
    </div>

    <div style="margin-top:1.25rem">{{ $profiles->links() }}</div>
@endif
@endsection
