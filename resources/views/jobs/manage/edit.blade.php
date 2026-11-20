@extends('layouts.app')

@section('title', 'Edit job')

@section('content')
<a href="{{ route('jobs.manage.show', $job) }}"
   style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--ink-3);margin-bottom:.9rem">
    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="m15 18-6-6 6-6"/></svg>
    Back to job
</a>

<h1>Edit job</h1>
<p class="sub">Changes are live immediately if the job is already public.</p>

<form method="POST" action="{{ route('jobs.manage.update', $job) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="field">
            <label for="title">Job title</label>
            <input id="title" name="title" type="text" required minlength="8" maxlength="160"
                   value="{{ old('title', $job->title) }}">
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="8" required minlength="40" maxlength="8000">{{ old('description', $job->description) }}</textarea>
        </div>

        <div class="duo field">
            <div>
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $job->category_id) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="deadline">Deadline <span class="optional">(optional)</span></label>
                <input id="deadline" name="deadline" type="date"
                       value="{{ old('deadline', $job->deadline?->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="duo field" style="max-width:520px">
            <div>
                <label for="budget">Total budget</label>
                <input id="budget" name="budget" type="text" inputmode="decimal" required
                       value="{{ old('budget', $job->budget()->toDecimal()) }}">
                @if ($job->isPublished())
                    <p class="hint" style="color:var(--amber)">
                        This job is already public. Changing the budget here does not change what is held in escrow.
                    </p>
                @endif
            </div>
            <div>
                <label for="currency">Currency</label>
                <select id="currency" name="currency" required>
                    <option value="USD" selected>USD</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">Save changes</button>
            <a class="btn btn-secondary" href="{{ route('jobs.manage.show', $job) }}">Cancel</a>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    .field{margin-bottom:1rem}
    .field:last-of-type{margin-bottom:1.1rem}
    .field label{display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem}
    .field .optional{color:var(--ink-3);font-weight:400}
    .field input,.field select,.field textarea{
        width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;
        font:inherit;font-size:.9rem;background:#fff;color:var(--ink);min-width:0;
    }
    .field textarea{resize:vertical;line-height:1.6}
    .field input:focus,.field select:focus,.field textarea:focus{
        outline:2px solid var(--forest);outline-offset:1px;border-color:var(--forest);
    }
    .hint{font-size:.75rem;color:var(--ink-3);margin:.3rem 0 0;line-height:1.5}
    .form-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.1rem}
    @media (max-width:560px){
        .form-actions .btn{flex:1}
    }
</style>
@endpush
