@extends('layouts.app')

@section('title', 'Portfolio')

@section('content')
<h1>Portfolio</h1>
<p class="sub">Work samples shown on your public profile. Strongest first.</p>

<div class="card">
    <h2 style="font-family:var(--serif);font-weight:400;font-size:1.1rem;margin:0 0 .8rem">Add an item</h2>

    <form method="POST" action="{{ route('portfolio.store') }}" enctype="multipart/form-data">
        @csrf

        <div style="display:grid;gap:.8rem;max-width:640px">
            <div>
                <label for="title" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.3rem">Title</label>
                <input id="title" name="title" type="text" maxlength="140" required
                       style="width:100%;padding:.55rem .7rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.9rem">
            </div>
            <div>
                <label for="description" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.3rem">Description</label>
                <textarea id="description" name="description" rows="3" maxlength="2000"
                          style="width:100%;padding:.55rem .7rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.9rem;resize:vertical"></textarea>
            </div>
            <div>
                <label for="external_url" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.3rem">Link to the work</label>
                <input id="external_url" name="external_url" type="url" maxlength="500"
                       placeholder="https://"
                       style="width:100%;padding:.55rem .7rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.9rem">
                <p style="font-size:.75rem;color:var(--ink-3);margin:.3rem 0 0">http or https only.</p>
            </div>
            <div>
                <label for="image" style="display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.3rem">Thumbnail (optional)</label>
                <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp" style="font-size:.85rem">
                <p style="font-size:.75rem;color:var(--ink-3);margin:.3rem 0 0">Up to 4 MB.</p>
            </div>
            <div><button class="btn" type="submit">Add item</button></div>
        </div>
    </form>
</div>

@if ($items->isEmpty())
    <div class="card" style="text-align:center;padding:2.5rem 1.5rem;margin-top:1rem">
        <p style="margin:0;color:var(--ink-3)">No portfolio items yet.</p>
    </div>
@else
    <div class="cardlist" style="margin-top:1rem">
        @foreach ($items as $item)
            <div class="card pf-card">
                <div class="pf-card-head">
                    <div style="min-width:0">
                        <div class="pf-card-title">{{ $item->title }}</div>
                        @if ($item->description)
                            <p class="pf-card-desc">{{ \Illuminate\Support\Str::limit($item->description, 180) }}</p>
                        @endif
                        @if ($item->safeExternalUrl())
                            <a class="pf-card-link" href="{{ $item->safeExternalUrl() }}"
                               rel="nofollow noopener noreferrer" target="_blank">
                                View work
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6"/><path d="M20 4 10 14"/><path d="M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Inline edit. <details> keeps this working with no JS, and the
                     form posts to the same authorised route a direct request would
                     hit, so the policy is exercised by a real user rather than only
                     by a test. --}}
                <details class="pf-edit">
                    <summary>Edit details</summary>
                    <form method="POST" action="{{ route('portfolio.update', $item) }}">
                        @csrf
                        @method('PUT')
                        <label for="title-{{ $item->id }}" class="sr-only">Title</label>
                        <input id="title-{{ $item->id }}" name="title" type="text" required maxlength="140"
                               value="{{ $item->title }}" placeholder="Title">
                        <label for="description-{{ $item->id }}" class="sr-only">Description</label>
                        <textarea id="description-{{ $item->id }}" name="description" rows="3" maxlength="2000"
                                  placeholder="Description">{{ $item->description }}</textarea>
                        <label for="external_url-{{ $item->id }}" class="sr-only">Link to the work</label>
                        <input id="external_url-{{ $item->id }}" name="external_url" type="url" maxlength="500"
                               value="{{ $item->external_url }}" placeholder="https://">
                        <div class="pf-edit-actions">
                            <button class="btn btn-sm" type="submit">Save changes</button>
                        </div>
                    </form>
                </details>

                <div class="pf-card-foot">
                    <form method="POST" action="{{ route('portfolio.destroy', $item) }}" style="margin:0">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection

@push('styles')
<style>
    .pf-card{padding:1.15rem 1.25rem}
    .pf-card-title{font-weight:600;font-size:.95rem;line-height:1.35}
    .pf-card-desc{font-size:.845rem;color:var(--ink-2);margin:.4rem 0 0;line-height:1.55}
    .pf-card-link{
        display:inline-flex;align-items:center;gap:.3rem;
        font-size:.82rem;color:var(--forest);margin-top:.6rem;
        text-decoration:underline;
    }
    .pf-card-link svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

    .pf-edit{margin-top:.75rem}
    .pf-edit summary{
        cursor:pointer;font-size:.78rem;color:var(--ink-3);
        list-style:none;display:inline-flex;align-items:center;gap:.3rem;
    }
    .pf-edit summary::-webkit-details-marker{display:none}
    .pf-edit summary::before{content:"✎";font-size:.8rem}
    .pf-edit summary:hover{color:var(--forest)}
    .pf-edit[open] summary{margin-bottom:.7rem}
    .pf-edit form{display:grid;gap:.5rem;max-width:460px}
    .pf-edit input,.pf-edit textarea{
        width:100%;padding:.45rem .6rem;border:1px solid var(--rule-2);border-radius:6px;
        font:inherit;font-size:.85rem;background:#fff;color:var(--ink);min-width:0;
    }
    .pf-edit textarea{resize:vertical;line-height:1.55}
    .pf-edit-actions{display:flex;gap:.4rem}

    .pf-card-foot{
        margin-top:.9rem;padding-top:.85rem;border-top:1px solid var(--rule);
        display:flex;justify-content:flex-end;
    }

    @media (max-width:560px){
        .pf-card-foot{justify-content:stretch}
        .pf-card-foot form{width:100%}
        .pf-card-foot .btn{width:100%}
        .pf-edit-actions .btn{width:100%}
    }
</style>
@endpush
