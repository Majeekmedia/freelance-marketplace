<?php

declare(strict_types=1);

/**
 * Point the landing page's remaining `href="#"` links at real destinations, and
 * repair encoding damage from the prototype conversion.
 *
 * A dead link on a landing page is not neutral: it teaches a visitor that the
 * product is unfinished, and it hides real bugs because nothing 404s.
 *
 * Where a destination genuinely does not exist yet (Terms, Privacy, About), the
 * link is REMOVED rather than pointed somewhere approximate. A footer entry that
 * promises a terms page and leads nowhere is worse than a shorter footer.
 *
 * Run: php tools/fix-landing-links.php
 */

$target = __DIR__ . '/../resources/views/welcome.blade.php';
$html = file_get_contents($target);
$original = $html;

/*
 * 1. Encoding damage. The prototype was authored in UTF-8; something in the
 *    conversion path wrote cp1252 bytes. Repair the characters that actually
 *    appear rather than guessing at the rest.
 */
$html = str_replace(
    ['â€”', 'â€“', 'â€™', 'â€œ', 'â€\x9d', 'â€¦', 'Ã©'],
    ['—', '–', '’', '“', '”', '…', 'é'],
    $html,
);

/*
 * 2. Brand links go to the home route.
 */
$html = str_replace(
    '<a class="brand" href="#">',
    '<a class="brand" href="{{ route(\'home\') }}">',
    $html,
);

/*
 * 3. Navigation and CTA destinations that exist.
 */
$map = [
    // Header / drawer
    '<a href="#">Browse jobs</a>' => '<a href="{{ route(\'jobs.index\') }}">Browse jobs</a>',
    '<a href="#">Freelancers</a>' => '<a href="{{ route(\'freelancers.index\') }}">Freelancers</a>',
    '<a class="btn btn-ghost" href="#">Sign in</a>' => '<a class="btn btn-ghost" href="{{ route(\'login\') }}">Sign in</a>',

    // Footer — freelancers
    '<li><a href="#">Build a profile</a></li>' => '<li><a href="{{ route(\'register\') }}">Build a profile</a></li>',

    // Footer — posters
    '<li><a href="#">Review applicants</a></li>' => '<li><a href="{{ route(\'jobs.manage.index\') }}">Review applicants</a></li>',
];

foreach ($map as $from => $to) {
    $html = str_replace($from, $to, $html);
}

/*
 * 4. Links with no destination yet. Removed, not left pointing at "#".
 *
 * Each of these is a page the product does not have. A visitor who clicks
 * "Terms" and lands back at the top of the same page has been misled about what
 * exists, and the same applies to "About us" and "Contact".
 */
$remove = [
    '<a href="#">About us</a>',
    '<a href="#">Contact</a>',
    '<li><a href="#">How payouts work</a></li>',
    '<li><a href="#">Fund a wallet</a></li>',
    '<li><a href="#">Terms</a></li>',
    '<li><a href="#">Privacy</a></li>',
    '<li><a href="#">Docs</a></li>',
    '<li><a href="#">Status</a></li>',
];

foreach ($remove as $snippet) {
    $html = str_replace($snippet, '', $html);
}

/*
 * 5. The remaining CTA buttons and the search form.
 *
 * "Post a job" is role-dependent, so it is written as Blade. Any leftover
 * plain button gets the register route, which is where a guest starts.
 */
$postCta = <<<'BLADE'
@auth
        @if (auth()->user()->isPoster())
          <a class="btn btn-primary" href="{{ route('jobs.manage.create') }}">Post a job</a>
        @else
          <a class="btn btn-primary" href="{{ route('jobs.index') }}">Browse jobs</a>
        @endif
      @else
        <a class="btn btn-primary" href="{{ route('register') }}">Post a job</a>
      @endauth
BLADE;

// Plain string replacement, not a regex: the target contains characters that
// are regex metacharacters and a silently-null preg_replace result would wipe
// the whole document.
$html = str_replace(
    '<a class="btn btn-primary" href="#">Post a job</a>',
    $postCta,
    $html,
);

$html = str_replace(
    '<a class="btn btn-ghost btn-lg" href="#">Post a job</a>',
    '<a class="btn btn-ghost btn-lg" href="{{ route(\'register\') }}">Create an account</a>',
    $html,
);

/*
 * 6. The hero search should submit to the real listing rather than do nothing.
 */
$html = str_replace(
    '<form class="search" role="search">',
    '<form class="search" role="search" method="GET" action="{{ route(\'jobs.index\') }}">',
    $html,
);

// Any search input needs a name to submit a value.
$html = preg_replace(
    '#(<input[^>]*type="search")#',
    '$1 name="q"',
    $html,
);

file_put_contents($target, $html);

echo "Fixed landing links\n";
echo 'changed: '.($html === $original ? 'no' : 'yes')."\n";
echo 'remaining href="#": '.preg_match_all('/href="#"/', $html)."\n";
echo 'remaining mojibake: '.preg_match_all('/â€|Ã©/', $html)."\n";
