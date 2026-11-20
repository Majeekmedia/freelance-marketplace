@extends('layouts.app')

@section('title', 'Jobs')

@section('content')
<h1>Open jobs</h1>
<p class="sub">Every job listed here has its budget held in escrow.</p>

<form method="GET" action="{{ route('jobs.index') }}" class="filters">
    <label for="q" class="sr-only">Search jobs</label>
    <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Search jobs"
           style="flex:1;min-width:0;max-width:320px">

    <label for="category" class="sr-only">Category</label>
    <select id="category" name="category">
        <option value="">All categories</option>
        @foreach ($categories as $category)
            <option value="{{ $category->slug }}" @selected($activeCategory === $category->slug)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>

    <button class="btn" type="submit">Filter</button>
    @if (filled($search) || filled($activeCategory))
        <a class="btn btn-secondary" href="{{ route('jobs.index') }}">Clear</a>
    @endif
</form>

@if ($jobs->isEmpty())
    <div class="card" style="text-align:center;padding:3rem 1.5rem">
        <p style="margin:0;color:var(--ink-3)">
            @if (filled($search) || filled($activeCategory))
                No jobs match that filter.
            @else
                No jobs are open right now.
            @endif
        </p>
    </div>
@else
    {{--
        Cards rather than a table. A job listing is a browsing surface: each row
        carries a title, a two-line summary and a price. As cards this reads well
        at any width, and the money stays visible on a phone instead of sitting
        in an off-screen column.
    --}}
    <div class="cardlist">
        @foreach ($jobs as $job)
            <a class="card job-card" href="{{ route('jobs.show', $job) }}">
                <div class="job-card-top">
                    <div style="min-width:0">
                        <div class="job-card-badges">
                            {{-- The badge is driven by an escrow row, not by status.
                                 Claiming "escrow funded" on an unfunded job would be
                                 the exact kind of fake live data this project has
                                 already been bitten by (L-004). --}}
                            @if ($job->escrow !== null && $job->escrow->held_cents > 0)
                                <span class="badge badge-approved">Escrow funded</span>
                            @else
                                <span class="badge badge-pending">Awaiting escrow</span>
                            @endif
                            <span class="job-card-cat">{{ $job->category?->name ?? 'Uncategorised' }}</span>
                        </div>
                        <div class="job-card-title">{{ $job->title }}</div>
                        <p class="job-card-excerpt">{{ \Illuminate\Support\Str::limit($job->description, 165) }}</p>
                    </div>

                    <div class="job-card-money">
                        <div class="job-card-amount">{{ $job->budget()->format() }}</div>
                        @if ($job->deadline)
                            <div class="job-card-deadline">by {{ $job->deadline->format('j M Y') }}</div>
                        @endif
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    @if ($jobs->hasPages())
        <div style="margin-top:1.5rem">{{ $jobs->links() }}</div>
    @endif
@endif
@endsection

@push('styles')
<style>
    .job-card{display:block;transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease}
    .job-card:hover{border-color:var(--rule-2);box-shadow:0 3px 14px rgba(22,20,15,.07)}
    .job-card-top{display:flex;justify-content:space-between;gap:1.25rem;align-items:flex-start}
    .job-card-badges{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.4rem}
    .job-card-cat{font-size:.75rem;color:var(--ink-3)}
    .job-card-title{font-weight:500;font-size:1rem;margin-bottom:.3rem;line-height:1.35}
    .job-card-excerpt{font-size:.845rem;color:var(--ink-3);margin:0;line-height:1.55}
    .job-card-money{text-align:right;flex:none}
    .job-card-amount{font-family:var(--serif);font-size:1.25rem;line-height:1.2}
    .job-card-deadline{font-size:.75rem;color:var(--ink-3);margin-top:.2rem}

    /* On a phone the price moves under the text instead of fighting it for
       width — a right-aligned number beside a wrapped title looks accidental. */
    @media (max-width:560px){
        .job-card-top{flex-direction:column;gap:.7rem}
        .job-card-money{text-align:left;display:flex;align-items:baseline;gap:.6rem;flex-wrap:wrap}
        .job-card-amount{font-size:1.35rem}
        .job-card-deadline{margin-top:0}
    }
</style>
@endpush
