@extends('layouts.app')

@section('title', $job->title)

{{--
    Block form only — never the single-line php directive on its own line.

    The inline form compiles to an opening PHP tag with no closing tag and no
    semicolon, which swallows the entire rest of the template into one broken
    expression and 500s the page. `BladeCompilationTest` now forbids it.

    (Written without the literal directive here, because Blade's comment
    stripping runs after directive compilation and would still parse it.)
--}}
@php
    $poster = $job->poster;
    $currency = $job->budget()->currency;
@endphp

@section('content')
{{-- Back link: on a phone there is no visible breadcrumb, and this page is
     almost always reached from the listing. --}}
<a href="{{ route('jobs.index') }}"
   style="display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--ink-3);margin-bottom:.9rem">
    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round"><path d="m15 18-6-6 6-6"/></svg>
    All jobs
</a>

<h1>{{ $job->title }}</h1>
<p class="sub">
    @if ($job->isFunded())
        <span class="badge badge-approved">Escrow funded</span>
    @else
        <span class="badge badge-pending">Awaiting escrow</span>
    @endif
    <span style="margin:0 .4rem;color:var(--rule-2)">·</span>
    {{ $job->category?->name ?? 'Uncategorised' }}
    @if ($job->published_at)
        <span style="margin:0 .4rem;color:var(--rule-2)">·</span>
        posted {{ $job->published_at->diffForHumans() }}
    @endif
</p>

{{--
    The budget is moved into a small "facts" card that is placed FIRST in the
    DOM, because on a phone it is the single most useful thing on the page and
    should not require scrolling past the whole brief.

    The desktop grid then restores the expected reading order (brief left,
    facts right) via the small stylesheet at the bottom of this view.
--}}
<div class="split">
    <div>
        <div class="card">
            <dl class="facts">
                <div>
                    <dt>Budget held in escrow</dt>
                    <dd style="font-family:var(--serif);font-size:1.7rem;line-height:1.15">{{ $job->budget()->format() }}</dd>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
                    <div>
                        <dt>Deadline</dt>
                        <dd>{{ $job->deadline?->format('j M Y') ?? 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Posted by</dt>
                        <dd>{{ $poster?->name ?? 'A poster' }}</dd>
                    </div>
                </div>
            </dl>

            {{-- Contact details are deliberately absent. A public job page must
                 never expose the poster's email (FR-011, NFR-009). --}}
            <p style="font-size:.78rem;color:var(--ink-3);margin:1.1rem 0 0;line-height:1.5;border-top:1px solid var(--rule);padding-top:.9rem">
                Contact details are exchanged after you are hired and a conversation is opened.
            </p>
        </div>
    </div>

    <div>
        <div class="card">
            <h2 style="font-family:var(--serif);font-weight:400;font-size:1.1rem;margin:0 0 .7rem">The work</h2>
            <div style="font-size:.92rem;color:var(--ink-2);white-space:pre-line;line-height:1.7">{{ $job->description }}</div>
        </div>

        @if ($job->milestones->isNotEmpty())
            @php
                $currency = $job->budget()->currency;
                $milestoneTotal = \App\Domain\Money::fromMinorUnits(
                    (int) $job->milestones->sum('amount_cents'),
                    $currency,
                );
                $matchesBudget = $milestoneTotal->minorUnits === $job->budget()->minorUnits;
            @endphp

            <div class="card" style="padding:0;overflow:hidden">
                <h2 style="font-family:var(--serif);font-weight:400;font-size:1.1rem;margin:0;padding:1.2rem 1.4rem .2rem">Milestones</h2>
                <p style="font-size:.78rem;color:var(--ink-3);margin:0;padding:0 1.4rem .9rem">
                    {{ $job->milestones->count() }} stage{{ $job->milestones->count() === 1 ? '' : 's' }}, paid out as each is accepted
                </p>

                {{--
                    Two columns, two headers, two cells per row.

                    The previous version declared three <th> but emitted two <td>,
                    so the browser laid the table out with the title under "#" and
                    the amount under "Milestone", leaving "Amount" above nothing.
                    Every header now has exactly one matching cell.

                    The sequence number lives inside the primary cell rather than
                    in its own column: it is decoration, and a separate narrow
                    column is the first thing to break on a phone.
                --}}
                <table class="responsive ms-table">
                    <thead>
                        <tr>
                            <th>Milestone</th>
                            <th class="ms-amount-col">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($job->milestones as $milestone)
                            <tr>
                                <td class="cell-primary" data-label="Milestone">
                                    <span class="ms-seq">{{ $milestone->sequence }}</span>
                                    <span class="ms-title">{{ $milestone->title }}</span>
                                </td>
                                <td class="ms-amount" data-label="Amount">
                                    {{ \App\Domain\Money::fromMinorUnits($milestone->amount_cents, $currency)->format() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="ms-total-label" data-label="">
                                <span class="ms-total-text">Total</span>
                                @if ($matchesBudget)
                                    <span class="ms-total-note">matches the escrowed budget</span>
                                @else
                                    <span class="ms-total-note ms-total-warn">does not match the budget</span>
                                @endif
                            </td>
                            <td class="ms-amount ms-total-value" data-label="Total">
                                {{ $milestoneTotal->format() }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        <div class="card" style="background:var(--paper-2);border-color:var(--rule-2)">
            <p style="font-size:.84rem;color:var(--ink-2);margin:0 0 .9rem;line-height:1.55">
                Applying opens once milestone hiring is live.
            </p>
            <a class="btn btn-secondary" href="{{ route('jobs.index') }}" style="width:100%">Browse other jobs</a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Restore prose-left / facts-right once there really are two columns. */
    @media (min-width:860px){
        .split > div:first-child{order:2}
        .split > div:last-child{order:1}
    }

    /* ---------------- Milestone table ---------------- */

    .ms-table th:first-child,
    .ms-table td:first-child{padding-left:1.4rem}
    .ms-table th:last-child,
    .ms-table td:last-child{padding-right:1.4rem}

    /* A fixed amount column keeps every figure on one vertical line; without it
       the column width tracks the longest title and the numbers wander. */
    .ms-table .ms-amount-col,
    .ms-table .ms-amount{
        width:1%;
        white-space:nowrap;
        text-align:right;
        font-variant-numeric:tabular-nums;
    }

    .ms-table tbody tr td{padding-top:.85rem;padding-bottom:.85rem}
    .ms-table tbody tr:last-child td{border-bottom:0}

    .ms-seq{
        display:inline-grid;
        place-items:center;
        width:1.4rem;height:1.4rem;
        margin-right:.6rem;
        border-radius:50%;
        background:var(--paper-2);
        color:var(--ink-3);
        font-size:.68rem;
        font-weight:600;
        font-variant-numeric:tabular-nums;
        vertical-align:middle;
        flex:none;
    }
    .ms-title{vertical-align:middle}

    /* The total is a real row, not a floating caption, so the arithmetic is
       visible: the stages above it add up to the budget held in escrow. */
    .ms-table tfoot td{
        border-top:1px solid var(--rule-2);
        border-bottom:0;
        padding-top:.9rem;
        padding-bottom:1.15rem;
        background:var(--paper);
    }
    .ms-total-text{font-weight:600;font-size:.82rem;letter-spacing:.01em}
    .ms-total-note{
        display:block;
        font-size:.72rem;
        color:var(--ink-3);
        margin-top:.15rem;
        font-weight:400;
    }
    .ms-total-warn{color:var(--amber)}
    .ms-total-value{
        font-family:var(--serif);
        font-size:1.05rem;
        font-weight:400;
    }

    /*
     * Below 760px the shared `.responsive` rules turn each row into a card.
     * The sequence badge and the amount still need to line up inside it, so the
     * card's own padding replaces the table's gutter padding.
     */
    @media (max-width:760px){
        .ms-table th:first-child,
        .ms-table td:first-child,
        .ms-table th:last-child,
        .ms-table td:last-child{padding-left:0;padding-right:0}

        .ms-table{margin:0 1rem .25rem}
        .ms-table tbody tr{
            margin-left:0;margin-right:0;
            border-color:var(--rule);
        }
        .ms-table td.cell-primary{
            display:flex;
            align-items:center;
            gap:0;
            text-align:left;
            padding-bottom:.35rem;
            margin-bottom:0;
            border-bottom:0;
        }
        /* Amount sits on its own line in the card, right-aligned against the
           card edge so the figures still form a column down the stack.
           `width:1%` from the desktop rule must be undone here: on a block
           element it collapses the cell to ~3px and the number spills out. */
        .ms-table td.ms-amount{
            display:block;
            width:auto;
            white-space:normal;
            text-align:right;
            padding-top:.1rem;
            font-size:.9rem;
        }
        .ms-table td.ms-amount::before{
            content:attr(data-label);
            float:left;
            font-size:.7rem;
            letter-spacing:.07em;
            text-transform:uppercase;
            color:var(--ink-3);
            font-weight:600;
        }
        .ms-table tfoot{margin:0 1rem .9rem;display:block}
        .ms-table tfoot tr{
            display:flex;
            align-items:baseline;
            justify-content:space-between;
            gap:1rem;
            border:0;
            border-top:1px solid var(--rule-2);
            border-radius:0;
            padding:.75rem 0 0;
            margin:0;
            background:transparent;
        }
        .ms-table tfoot td{
            display:block;
            background:transparent;
            border:0;
            padding:0;
            text-align:left;
        }
        .ms-table tfoot td.ms-total-value{text-align:right}
        .ms-table tfoot td::before{content:none}
    }
</style>
@endpush
