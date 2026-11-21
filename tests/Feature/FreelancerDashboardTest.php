<?php

declare(strict_types=1);

use App\Domain\JobStatus;
use App\Domain\LedgerKind;
use App\Domain\Money;
use App\Models\FreelancerProfile;
use App\Models\Job;
use App\Models\User;
use App\Services\LedgerService;

/*
|--------------------------------------------------------------------------
| A freelancer's dashboard is not scanty
|--------------------------------------------------------------------------
| The design goal: a signed-in freelancer should see earnings, their work in two
| clearly separated states, a profile checklist, and real open jobs. None of that
| may be an empty panel with no heading.
*/

it('renders the freelancer dashboard with earnings and work sections', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Paid to you so far')
        ->assertSee('Active work')
        ->assertSee('Completed')
        ->assertSee('Open jobs');
});

it('shows real earnings derived from the ledger', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();

    app(LedgerService::class)->record(
        $freelancer,
        LedgerKind::ESCROW_RELEASE_CREDIT,
        Money::fromDecimal('750.00'),
        'Milestone released for dashboard test',
    );

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('$750.00');
});

it('counts active and completed work separately', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();
    $poster = User::factory()->approvedPoster()->create();

    Job::factory()->funded()->create([
        'poster_id' => $poster->id,
        'hired_freelancer_id' => $freelancer->id,
        'status' => JobStatus::IN_PROGRESS,
    ]);

    Job::factory()->funded()->create([
        'poster_id' => $poster->id,
        'hired_freelancer_id' => $freelancer->id,
        'status' => JobStatus::COMPLETED,
    ]);

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('See active work')
        ->assertSee('See completed work');
});

it('treats a delivered job as active, not completed', function (): void {
    // Work submitted is not work paid. Telling a freelancer it is finished while
    // the escrow is untouched would be the most damaging thing this page could do.
    $freelancer = User::factory()->approvedFreelancer()->create();
    $poster = User::factory()->approvedPoster()->create();

    Job::factory()->funded()->create([
        'poster_id' => $poster->id,
        'hired_freelancer_id' => $freelancer->id,
        'status' => JobStatus::DELIVERED,
        'title' => 'Delivered But Unpaid',
    ]);

    $this->actingAs($freelancer)
        ->get('/work/active')
        ->assertOk()
        ->assertSee('Delivered But Unpaid');

    $this->actingAs($freelancer)
        ->get('/work/completed')
        ->assertOk()
        ->assertDontSee('Delivered But Unpaid');
});

it('asks a freelancer to finish an incomplete profile', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Finish profile')
        ->assertSee('a headline');
});

it('stops asking once the profile is complete', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();
    FreelancerProfile::factory()->create([
        'user_id' => $freelancer->id,
        'headline' => 'Product designer',
        'bio' => 'I design things carefully.',
        'skills' => ['Figma', 'Laravel'],
    ]);

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Finish profile');
});

/*
|--------------------------------------------------------------------------
| Open jobs are real
|--------------------------------------------------------------------------
*/

it('lists real open jobs on the dashboard', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();
    Job::factory()->funded()->create(['title' => 'A Genuinely Funded Job']);

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('A Genuinely Funded Job');
});

it('never lists an unfunded job', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();
    Job::factory()->published()->create(['title' => 'Published But Unfunded']);

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Published But Unfunded');
});

it('prefers jobs matching the freelancer skills', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();
    FreelancerProfile::factory()->create([
        'user_id' => $freelancer->id,
        'skills' => ['Laravel'],
    ]);

    Job::factory()->funded()->create(['title' => 'Laravel API build', 'description' => 'A Laravel job']);
    Job::factory()->funded()->create(['title' => 'Illustration work', 'description' => 'Drawings only']);

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Laravel API build')
        ->assertDontSee('Illustration work');
});

it('shows an honest empty state when no jobs are open', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();

    $this->actingAs($freelancer)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('No open jobs right now');
});

it('never claims there are no jobs when jobs exist but do not match skills', function (): void {
    // Found in the browser: the freelancer had skills like "Figma" and the only
    // funded job was a Laravel dashboard, so the page said "No open jobs right
    // now" — hiding real work from the person who could apply for it.
    $freelancer = User::factory()->approvedFreelancer()->create();
    FreelancerProfile::factory()->create([
        'user_id' => $freelancer->id,
        'skills' => ['Figma', 'Design Systems'],
    ]);

    Job::factory()->funded()->create([
        'title' => 'Backend migration work',
        'description' => 'A PHP job with nothing design related',
    ]);

    $response = $this->actingAs($freelancer)->get('/dashboard')->assertOk();

    // It must not claim there is nothing.
    $response->assertDontSee('No open jobs right now');

    // It must say jobs exist and offer the way to see them.
    $response->assertSee('1 job open, none matching your skills')
        ->assertSee('See all open jobs');
});

/*
|--------------------------------------------------------------------------
| The two work pages
|--------------------------------------------------------------------------
*/

it('shows the active work page with its own empty state', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();

    $this->actingAs($freelancer)
        ->get('/work/active')
        ->assertOk()
        ->assertSee('Active work')
        ->assertSee('No active work right now');
});

it('shows the completed work page with its own empty state', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();

    $this->actingAs($freelancer)
        ->get('/work/completed')
        ->assertOk()
        ->assertSee('Completed work')
        ->assertSee('Nothing completed yet');
});

it('never shows another freelancers work', function (): void {
    $a = User::factory()->approvedFreelancer()->create();
    $b = User::factory()->approvedFreelancer()->create();
    $poster = User::factory()->approvedPoster()->create();

    Job::factory()->funded()->create([
        'poster_id' => $poster->id,
        'hired_freelancer_id' => $b->id,
        'status' => JobStatus::IN_PROGRESS,
        'title' => 'Belongs To B',
    ]);

    $this->actingAs($a)
        ->get('/work/active')
        ->assertOk()
        ->assertDontSee('Belongs To B');

    $this->actingAs($b)
        ->get('/work/active')
        ->assertOk()
        ->assertSee('Belongs To B');
});

it('requires authentication for the work pages', function (): void {
    $this->get('/work/active')->assertRedirect('/login');
    $this->get('/work/completed')->assertRedirect('/login');
});

/*
|--------------------------------------------------------------------------
| The poster dashboard is unchanged
|--------------------------------------------------------------------------
| The freelancer branch is new; it must not have swallowed the other roles.
*/

it('still renders the poster dashboard', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Your hiring')
        ->assertDontSee('Paid to you so far');
});

it('still renders the admin dashboard', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Administration')
        ->assertSee('Open admin console');
});
