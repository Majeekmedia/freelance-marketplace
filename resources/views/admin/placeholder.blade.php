@extends('layouts.admin')

@section('title', $detail['title'])

@section('content')
<h1>{{ $detail['title'] }}</h1>
<p class="sub">{{ $detail['intro'] }}</p>

<div class="card" style="border-left:3px solid var(--amber)">
    <div class="soon-head">
        <span class="badge badge-pending">Not built yet</span>
        <span class="soon-arrives">Arrives in {{ $detail['arrives'] }}</span>
    </div>

    {{--
        Deliberately not an empty table. "No rows" reads as a fact about the
        data; "not built" is a fact about the product, and conflating them makes
        an operator think they have no work to do.
    --}}
    <p class="soon-body">
        This section does not exist yet. Nothing on this page is missing data —
        the feature itself is not implemented.
    </p>
</div>

<div class="split" style="margin-top:1.4rem">
    <div>
        <div class="card">
            <h2 class="section-head">What this will do</h2>
            <ul class="plain-list">
                @foreach ($detail['missing'] as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div>
        <div class="card">
            <h2 class="section-head">What you can do now</h2>
            <ul class="plain-list plain-list-now">
                @foreach ($detail['available_now'] as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .soon-head{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.7rem}
    .soon-arrives{font-size:.79rem;color:var(--amber);font-weight:500}
    .soon-body{margin:0;font-size:.85rem;color:var(--ink-2);line-height:1.6;max-width:62ch}

    .section-head{font-family:var(--serif);font-weight:400;font-size:1.05rem;margin:0 0 .85rem}
    .plain-list{margin:0;padding-left:1.1rem;font-size:.83rem;color:var(--ink-2);line-height:1.65}
    .plain-list li{margin-bottom:.5rem}
    .plain-list li:last-child{margin-bottom:0}
    .plain-list-now{padding-left:1.1rem}
</style>
@endpush
