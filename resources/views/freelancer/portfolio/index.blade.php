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

<div class="card" style="padding:0;overflow:hidden;margin-top:1rem">
    <table>
        <thead>
            <tr><th>Title</th><th>Link</th><th style="text-align:right">Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>
                        {{ $item->title }}
                        {{-- Inline edit. <details> keeps this working with no JS,
                             and the form posts to the same authorised route a
                             direct request would hit, so the policy is exercised
                             by a real user rather than only by a test. --}}
                        <details style="margin-top:.5rem">
                            <summary style="cursor:pointer;font-size:.78rem;color:var(--forest)">Edit details</summary>
                            <form method="POST" action="{{ route('portfolio.update', $item) }}" style="margin-top:.6rem;display:grid;gap:.5rem;max-width:420px">
                                @csrf
                                @method('PUT')
                                <label for="title-{{ $item->id }}" class="sr-only">Title</label>
                                <input id="title-{{ $item->id }}" name="title" type="text" required maxlength="140"
                                       value="{{ $item->title }}"
                                       style="width:100%;padding:.45rem .6rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.85rem">
                                <label for="description-{{ $item->id }}" class="sr-only">Description</label>
                                <textarea id="description-{{ $item->id }}" name="description" rows="3" maxlength="2000"
                                          style="width:100%;padding:.45rem .6rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.85rem;resize:vertical">{{ $item->description }}</textarea>
                                <label for="external_url-{{ $item->id }}" class="sr-only">Link to the work</label>
                                <input id="external_url-{{ $item->id }}" name="external_url" type="url" maxlength="500"
                                       value="{{ $item->external_url }}" placeholder="https://"
                                       style="width:100%;padding:.45rem .6rem;border:1px solid var(--rule-2);border-radius:6px;font:inherit;font-size:.85rem">
                                <div><button class="btn btn-sm" type="submit">Save changes</button></div>
                            </form>
                        </details>
                    </td>
                    <td style="color:var(--ink-3);font-size:.82rem">
                        {{ $item->safeExternalUrl() ? 'Link' : '—' }}
                    </td>
                    <td>
                        <div class="actions">
                            <form method="POST" action="{{ route('portfolio.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="padding:2rem .5rem;text-align:center;color:var(--ink-3)">
                        No portfolio items yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
