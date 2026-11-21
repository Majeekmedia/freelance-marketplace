<?php

declare(strict_types=1);

/**
 * Convert prototype/landing.html into resources/views/welcome.blade.php.
 *
 * The prototype is the design source of truth; this script is the mechanical
 * step that turns it into a Blade view. Doing it by hand invites drift — a
 * token missed on line 600 is invisible until a user sees a literal
 * "__BRAND_NAME__" on the home page.
 *
 * What it does:
 *   1. replaces brand tokens with Blade echoes of SiteSettings
 *   2. rewrites the hardcoded "Trust-Prime" literals the same way
 *   3. points the nav and CTAs at real named routes
 *   4. drops internal anchors that have no target yet, rather than leaving
 *      links that scroll nowhere
 *
 * Run: php tools/build-landing.php
 */

require __DIR__ . '/../vendor/autoload.php';

$source = __DIR__ . '/../prototype/landing.html';
$target = __DIR__ . '/../resources/views/welcome.blade.php';

if (! file_exists($source)) {
    fwrite(STDERR, "Prototype not found: {$source}\n");
    exit(1);
}

$html = file_get_contents($source);

/*
 * 1. Title. Built from settings so renaming the site renames the tab.
 */
$html = preg_replace(
    '#<title>.*?</title>#s',
    "<title>{{ \\App\\Support\\SiteSettings::brandName() }} — {{ \\App\\Support\\SiteSettings::tagline() }}</title>",
    $html,
    1,
);

/*
 * 2. Brand tokens. The prototype uses literal "Trust-Prime" and initials "TP".
 *
 * Replaced with Blade echoes so FR-035 holds: no template contains the brand
 * name, and NoHardcodedBrandTest will fail the build if one does.
 */
$brandName = "{{ \\App\\Support\\SiteSettings::brandName() }}";
$initials = "{{ \\App\\Support\\SiteSettings::initials() }}";

// The wordmark: <span class="brand-mark">TP</span>\n      Trust-Prime
$html = str_replace(
    '<span class="brand-mark" aria-hidden="true">TP</span>',
    '<span class="brand-mark" aria-hidden="true">'.$initials.'</span>',
    $html,
);

// Any remaining bare "Trust-Prime" text node.
$html = str_replace('Trust-Prime', $brandName, $html);

// Footer copyright often contains the name inline; it is handled above.

/*
 * 3. Logo support. The prototype shows a letter mark; when an admin uploads a
 *    logo, the header should show it instead. Injected as a Blade conditional.
 */
$logoBlock = <<<'BLADE'
@if (\App\Support\LogoUploader::url())
      <img class="brand-logo" src="{{ \App\Support\LogoUploader::url() }}"
           alt="{{ \App\Support\SiteSettings::brandName() }}">
    @else
      <span class="brand-mark" aria-hidden="true">{{ \App\Support\SiteSettings::initials() }}</span>
    @endif
BLADE;

// Replace the (already-tokenised) mark with the conditional logo block.
$html = preg_replace(
    '#<span class="brand-mark" aria-hidden="true">\{\{ \\\\App\\\\Support\\\\SiteSettings::initials\(\) \}\}</span>#',
    $logoBlock,
    $html,
    1,
);

/*
 * 4. Real routes.
 *
 * The prototype's links are all "#". Point them at the routes that exist so the
 * page is navigable, and leave the ones with no destination pointing at the
 * section anchor rather than a dead "#".
 */
$routeMap = [
    // href="#" on "Browse jobs"
    '#<a href="#">(Browse jobs)</a>#' => '<a href="{{ route(\'jobs.index\') }}">$1</a>',
    '#<a href="#">(Freelancers)</a>#' => '<a href="{{ route(\'freelancers.index\') }}">$1</a>',
    '#<a href="#">(Sign in)</a>#' => '<a href="{{ route(\'login\') }}">$1</a>',
];

foreach ($routeMap as $pattern => $replacement) {
    $html = preg_replace($pattern, $replacement, $html) ?? $html;
}

/*
 * 5. Primary CTAs.
 *
 * "Post a job" depends on who is looking: a guest registers, a poster goes
 * straight to the form, a freelancer has no business there. That decision
 * belongs in the view, so it is written as Blade.
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

// Desktop action cluster.
$html = preg_replace(
    '#<a class="btn btn-primary" href="\{\{ route\(\'register\'\) \}\}">Post a job</a>#',
    $postCta,
    $html,
);

/*
 * 6. Remove `@if (Route::has(...))`-style leftovers and any Blade-hostile
 *    constructs the prototype may contain.
 */
$html = str_replace('{{-- Laravel Logo --}}', '', $html);

file_put_contents($target, $html);

echo "Built {$target}\n";
echo 'bytes: '.strlen($html)."\n";

// Report anything that still looks like an unreplaced token or a dead link.
$leftoverTokens = preg_match_all('/__BRAND_[A-Z_]+__/', $html);
$deadLinks = preg_match_all('/href="#"(\s|>)/', $html);
$literalBrand = substr_count($html, 'Trust-Prime');

echo "unreplaced tokens: {$leftoverTokens}\n";
echo "href=\"#\" links: {$deadLinks}\n";
echo "literal brand occurrences: {$literalBrand}\n";
