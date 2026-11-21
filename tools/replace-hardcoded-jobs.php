<?php

declare(strict_types=1);

/**
 * Replace the prototype's hardcoded job cards with a real Blade loop.
 *
 * This is the L-004 fix at the design level. The prototype shipped six invented
 * jobs with stock photos, invented applicant counts and invented deadlines. On a
 * demo that reads as polish; on a live page it is a false claim about the
 * marketplace — "12 applicants" and "3 days left" are facts a visitor will act
 * on.
 *
 * The replacement renders real published, escrow-funded jobs and shows an honest
 * empty state when there are none. Fields the model genuinely has are used
 * (title, description, category, budget, deadline); fields it does not have
 * (applicant count, tag list, poster photo) are omitted rather than faked.
 *
 * Run: php tools/replace-hardcoded-jobs.php
 */

$target = __DIR__ . '/../resources/views/welcome.blade.php';

$html = file_get_contents($target);

$startMarker = '<ul class="jobs">';
$start = strpos($html, $startMarker);

if ($start === false) {
    fwrite(STDERR, "Could not find the job list marker.\n");
    exit(1);
}

$end = strpos($html, '</ul>', $start);

if ($end === false) {
    fwrite(STDERR, "Could not find the end of the job list.\n");
    exit(1);
}

$blade = <<<'BLADE'
<ul class="jobs">
      @forelse ($featuredJobs as $job)
        <li class="job-card">
          <div class="job-top">
            <span class="job-cat">{{ $job->category?->name ?? 'Uncategorised' }}</span>
            {{-- The badge reflects the escrow row, not a status field: a job is
                 only listed once money is actually held. --}}
            <span class="badge badge-funded">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              Funded
            </span>
          </div>

          <h3 class="job-title">
            <a href="{{ route('jobs.show', $job) }}">{{ $job->title }}</a>
          </h3>

          <p class="job-desc">{{ \Illuminate\Support\Str::limit($job->description, 150) }}</p>

          @if ($job->deadline)
            <div class="job-meta">
              <span>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                Due {{ $job->deadline->format('j M Y') }}
              </span>
            </div>
          @endif

          <div class="job-foot">
            <div class="poster">
              {{--
                Initials rather than a stock photo. The poster's real photo is not
                stored, and a random stranger's face presented as the person who
                posted the job is exactly the kind of false detail this project
                has already been bitten by.
              --}}
              <span class="face" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($job->poster?->name ?? 'A', 0, 1)) }}</span>
              <span class="poster-name">{{ $job->poster?->name ?? 'A poster' }}</span>
            </div>
            <p class="job-amt num">{{ $job->budget()->format() }}<small>In escrow</small></p>
          </div>
        </li>
      @empty
        {{--
          Honest empty state. An empty grid with a "browse all jobs" button that
          leads to another empty page is worse than saying so plainly.
        --}}
        <li class="job-card" style="grid-column:1/-1;text-align:center;padding:2.5rem 1.5rem">
          <p style="margin:0 0 .3rem;font-weight:600">No jobs are open yet</p>
          <p style="margin:0;font-size:.86rem;color:var(--ink-3);line-height:1.55">
            Every job here has its budget held in escrow. As soon as one is funded it
            appears on this page.
          </p>
        </li>
      @endforelse
    </ul>
BLADE;

$html = substr($html, 0, $start) . $blade . substr($html, $end + 5);

file_put_contents($target, $html);

echo "Replaced the hardcoded job list.\n";
echo 'remaining invented titles: '.substr_count($html, 'Redesign a B2B SaaS')."\n";
echo 'remaining stock photos: '.substr_count($html, 'randomuser.me')."\n";
