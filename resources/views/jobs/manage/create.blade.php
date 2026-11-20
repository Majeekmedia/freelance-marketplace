@extends('layouts.app')

@section('title', 'Post a job')

@section('content')
<h1>Post a job</h1>
<p class="sub">Describe the work and set a budget. Nothing is public until the budget is funded.</p>

<form method="POST" action="{{ route('jobs.manage.store') }}">
    @csrf

    <div class="card">
        <div class="field">
            <label for="title">Job title</label>
            <input id="title" name="title" type="text" required minlength="8" maxlength="160"
                   value="{{ old('title') }}"
                   placeholder="Rebuild our checkout flow in Laravel">
            <p class="hint">At least 8 characters.</p>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="8" required minlength="40" maxlength="8000"
                      placeholder="What needs building, what exists already, what 'done' looks like, and any constraints.">{{ old('description') }}</textarea>
            <p class="hint">At least 40 characters. Freelancers quote from this text.</p>
        </div>

        <div class="duo field">
            <div>
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Choose a category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="deadline">Deadline <span class="optional">(optional)</span></label>
                <input id="deadline" name="deadline" type="date" value="{{ old('deadline') }}">
            </div>
        </div>

        <div class="duo field" style="max-width:520px">
            <div>
                <label for="budget">Total budget</label>
                <input id="budget" name="budget" type="text" inputmode="decimal" required
                       value="{{ old('budget') }}" placeholder="2,500.00">
            </div>
            <div>
                <label for="currency">Currency</label>
                {{-- Jobs are priced in USD only (FR-036). A real select, not a
                     hidden field, so the constraint is visible rather than
                     discovered on submit. --}}
                <select id="currency" name="currency" required>
                    <option value="USD" @selected(old('currency', 'USD') === 'USD')>USD</option>
                </select>
            </div>
        </div>

        <div class="alert alert-note" style="max-width:640px">
            <strong>This job stays private until it is funded.</strong>
            Once the budget is held in escrow the job appears publicly and freelancers can apply.
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">Save draft</button>
            <a class="btn btn-secondary" href="{{ route('jobs.manage.index') }}">Cancel</a>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    .field{margin-bottom:1rem}
    .field:last-of-type{margin-bottom:1.1rem}
    .field label{
        display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem;
    }
    .field .optional{color:var(--ink-3);font-weight:400}
    .field input,.field select,.field textarea{
        width:100%;padding:.6rem .7rem;border:1px solid var(--rule-2);border-radius:6px;
        font:inherit;font-size:.9rem;background:#fff;color:var(--ink);min-width:0;
    }
    .field textarea{resize:vertical;line-height:1.6}
    .field input:focus,.field select:focus,.field textarea:focus{
        outline:2px solid var(--forest);outline-offset:1px;border-color:var(--forest);
    }
    .hint{font-size:.75rem;color:var(--ink-3);margin:.3rem 0 0}
    .form-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.1rem}
    @media (max-width:560px){
        .form-actions .btn{flex:1}
    }
</style>
@endpush
