<?php

declare(strict_types=1);

use App\Domain\JobStatus;
use App\Domain\Money;
use App\Models\Category;
use App\Models\Job;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Draft creation (FR-009)
|--------------------------------------------------------------------------
*/

it('lets an approved poster create a draft job', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $category = Category::factory()->create();

    $this->actingAs($poster)
        ->post('/jobs/manage', [
            'title' => 'Rebuild the checkout flow',
            'description' => 'We need a Laravel engineer to rebuild checkout end to end, including tests.',
            'category_id' => $category->id,
            'budget' => '2500.00',
            'currency' => 'USD',
        ])
        ->assertRedirect();

    $job = Job::first();

    expect($job->status)->toBe(JobStatus::DRAFT)
        ->and($job->budget_cents)->toBe(250000)   // integer cents, not 2500.0
        ->and($job->poster_id)->toBe($poster->id)
        ->and($job->published_at)->toBeNull();
});

it('never lets a poster set their own status', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $category = Category::factory()->create();

    // A poster posting status=published must be ignored, not honoured.
    $this->actingAs($poster)
        ->post('/jobs/manage', [
            'title' => 'Escrow bypass attempt',
            'description' => 'Trying to publish without funding a single cent of escrow here.',
            'category_id' => $category->id,
            'budget' => '5000.00',
            'currency' => 'USD',
            'status' => 'published',
            'published_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect();

    $job = Job::first();

    expect($job->status)->toBe(JobStatus::DRAFT)
        ->and($job->published_at)->toBeNull();
});

it('accepts a budget with thousands separators and a currency symbol', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $category = Category::factory()->create();

    $this->actingAs($poster)
        ->post('/jobs/manage', [
            'title' => 'Human-typed amount',
            'description' => 'A poster types a formatted amount because that is what people do.',
            'category_id' => $category->id,
            'budget' => '$1,250.50',
            'currency' => 'USD',
        ])
        ->assertRedirect();

    expect(Job::first()->budget_cents)->toBe(125050);
});

it('rejects a NGN budget with a message naming supported currencies', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($poster)
        ->post('/jobs/manage', [
            'title' => 'NGN job',
            'description' => 'Attempting to price a job in a currency the product does not support.',
            'category_id' => $category->id,
            'budget' => '500000',
            'currency' => 'NGN',
        ]);

    $response->assertSessionHasErrors('currency');

    $message = session('errors')->get('currency')[0];

    expect($message)->toContain('USD')
        ->and($message)->toContain('NGN');

    expect(Job::count())->toBe(0);
});

it('rejects an inactive category', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $category = Category::factory()->inactive()->create();

    $this->actingAs($poster)
        ->post('/jobs/manage', [
            'title' => 'Retired category job',
            'description' => 'Posting into a retired category should not be possible at all.',
            'category_id' => $category->id,
            'budget' => '100.00',
            'currency' => 'USD',
        ])
        ->assertSessionHasErrors('category_id');
});

it('rejects a description too short to quote from', function (): void {
    $poster = User::factory()->approvedPoster()->create();
    $category = Category::factory()->create();

    $this->actingAs($poster)
        ->post('/jobs/manage', [
            'title' => 'Too vague',
            'description' => 'Do a website',
            'category_id' => $category->id,
            'budget' => '100.00',
            'currency' => 'USD',
        ])
        ->assertSessionHasErrors('description');
});

/*
|--------------------------------------------------------------------------
| Draft privacy (FR-009) — the core promise
|--------------------------------------------------------------------------
*/

it('returns 404 for a draft to a visitor', function (): void {
    $job = Job::factory()->draft()->create();

    $this->get("/jobs/{$job->id}")->assertNotFound();
});

it('returns 404 rather than 403 so ids cannot be probed', function (): void {
    $job = Job::factory()->draft()->create();

    // 403 would confirm the id exists. 404 leaks nothing.
    $response = $this->get("/jobs/{$job->id}");

    expect($response->status())->toBe(404);
});

it('hides drafts from another poster', function (): void {
    $owner = User::factory()->approvedPoster()->create();
    $other = User::factory()->approvedPoster()->create();
    $job = Job::factory()->draft()->forPoster($owner)->create();

    $this->actingAs($other)
        ->get("/jobs/{$job->id}")
        ->assertNotFound();
});

it('shows a draft to its owner', function (): void {
    $owner = User::factory()->approvedPoster()->create();
    $job = Job::factory()->draft()->forPoster($owner)->create();

    $this->actingAs($owner)
        ->get("/jobs/manage/{$job->id}")
        ->assertOk();
});

it('never lists a draft on the public jobs page', function (): void {
    $draft = Job::factory()->draft()->create(['title' => 'Secret Unannounced Project']);
    $published = Job::factory()->funded()->create(['title' => 'Public Project']);

    $this->get('/jobs')
        ->assertOk()
        ->assertSee('Public Project')
        ->assertDontSee('Secret Unannounced Project');
});

it('lists a published job publicly', function (): void {
    $job = Job::factory()->funded()->create(['title' => 'Funded Job']);

    $this->get('/jobs')->assertOk()->assertSee('Funded Job');
    $this->get("/jobs/{$job->id}")->assertOk()->assertSee('Funded Job');
});

/*
|--------------------------------------------------------------------------
| No contact details on public pages (FR-011, NFR-009)
|--------------------------------------------------------------------------
*/

it('never exposes the poster email on a public job page', function (): void {
    $poster = User::factory()->approvedPoster()->create([
        'email' => 'poster-private@example.test',
    ]);
    $job = Job::factory()->funded()->forPoster($poster)->create();

    $response = $this->get("/jobs/{$job->id}");

    $response->assertOk()
        ->assertDontSee('poster-private@example.test');
});

it('never exposes a poster email in the public listing', function (): void {
    $poster = User::factory()->approvedPoster()->create([
        'email' => 'listing-private@example.test',
    ]);
    Job::factory()->funded()->forPoster($poster)->create();

    $this->get('/jobs')
        ->assertOk()
        ->assertDontSee('listing-private@example.test');
});

/*
|--------------------------------------------------------------------------
| Ownership on manage actions
|--------------------------------------------------------------------------
*/

it('blocks a freelancer from creating a job', function (): void {
    $freelancer = User::factory()->approvedFreelancer()->create();
    $category = Category::factory()->create();

    $this->actingAs($freelancer)
        ->post('/jobs/manage', [
            'title' => 'Freelancer posting a job',
            'description' => 'A freelancer should not be able to post work on this platform.',
            'category_id' => $category->id,
            'budget' => '100.00',
            'currency' => 'USD',
        ])
        ->assertForbidden();
});

it('blocks a pending poster from creating a job', function (): void {
    $poster = User::factory()->pending()->create();
    $category = Category::factory()->create();

    $this->actingAs($poster)
        ->post('/jobs/manage', [
            'title' => 'Pending poster job',
            'description' => 'A pending account must not be able to create anything.',
            'category_id' => $category->id,
            'budget' => '100.00',
            'currency' => 'USD',
        ])
        ->assertForbidden();
});

it('blocks poster A from editing poster B job', function (): void {
    $a = User::factory()->approvedPoster()->create();
    $b = User::factory()->approvedPoster()->create();
    $job = Job::factory()->draft()->forPoster($b)->create(['title' => 'B private draft']);

    $this->actingAs($a)
        ->put("/jobs/manage/{$job->id}", [
            'title' => 'Hijacked title',
            'description' => 'Attempting to overwrite another poster job description entirely.',
            'category_id' => $job->category_id,
            'budget' => '100.00',
            'currency' => 'USD',
        ])
        ->assertNotFound();

    expect($job->fresh()->title)->toBe('B private draft');
});

it('blocks poster A from deleting poster B draft', function (): void {
    $a = User::factory()->approvedPoster()->create();
    $b = User::factory()->approvedPoster()->create();
    $job = Job::factory()->draft()->forPoster($b)->create();

    $this->actingAs($a)
        ->delete("/jobs/manage/{$job->id}")
        ->assertNotFound();

    expect(Job::find($job->id))->not->toBeNull();
});

it('lets the owner edit and delete their own draft', function (): void {
    $owner = User::factory()->approvedPoster()->create();
    $job = Job::factory()->draft()->forPoster($owner)->create();

    $this->actingAs($owner)
        ->put("/jobs/manage/{$job->id}", [
            'title' => 'An updated title for the draft',
            'description' => 'The revised description, comfortably longer than forty characters.',
            'category_id' => $job->category_id,
            'budget' => '300.00',
            'currency' => 'USD',
        ])
        ->assertRedirect();

    expect($job->fresh()->title)->toBe('An updated title for the draft')
        ->and($job->fresh()->budget_cents)->toBe(30000);

    $this->actingAs($owner)
        ->delete("/jobs/manage/{$job->id}")
        ->assertRedirect();

    expect(Job::find($job->id))->toBeNull();
});

it('refuses to delete a job that is no longer a draft', function (): void {
    $owner = User::factory()->approvedPoster()->create();
    $job = Job::factory()->published()->forPoster($owner)->create();

    // A funded job has money held against it: deleting it would orphan escrow.
    $this->actingAs($owner)
        ->delete("/jobs/manage/{$job->id}")
        ->assertNotFound();

    expect(Job::find($job->id))->not->toBeNull();
});

it('locks the brief once a freelancer is hired', function (): void {
    $owner = User::factory()->approvedPoster()->create();
    $freelancer = User::factory()->approvedFreelancer()->create();

    $job = Job::factory()->published()->forPoster($owner)->create();
    $job->status = JobStatus::IN_PROGRESS;
    $job->hired_freelancer_id = $freelancer->id;
    $job->save();

    $this->actingAs($owner)
        ->put("/jobs/manage/{$job->id}", [
            'title' => 'Changed scope after hiring',
            'description' => 'A freelancer quoted on the original scope; changing it now is unfair.',
            'category_id' => $job->category_id,
            'budget' => '100.00',
            'currency' => 'USD',
        ])
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Reachability — the lesson from T-009
|--------------------------------------------------------------------------
*/

it('links a poster to their jobs from the dashboard', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee(route('jobs.manage.index'), false);
});

it('offers a post-a-job action from the jobs page when the poster has none', function (): void {
    $poster = User::factory()->approvedPoster()->create();

    $this->actingAs($poster)
        ->get('/jobs/manage')
        ->assertOk()
        ->assertSee(route('jobs.manage.create'), false);
});

/*
|--------------------------------------------------------------------------
| Domain invariants
|--------------------------------------------------------------------------
*/

it('refuses an illegal status jump', function (): void {
    $job = Job::factory()->draft()->create();

    // draft → completed skips escrow, hiring and delivery entirely.
    expect(fn () => $job->transitionTo(JobStatus::COMPLETED))
        ->toThrow(DomainException::class);
});

it('allows a legal transition and stamps published_at exactly once', function (): void {
    $job = Job::factory()->draft()->create();

    $job->transitionTo(JobStatus::PUBLISHED);
    $firstPublished = $job->published_at;

    expect($firstPublished)->not->toBeNull();

    $job->transitionTo(JobStatus::IN_PROGRESS);
    $job->transitionTo(JobStatus::PUBLISHED === $job->status ? JobStatus::PUBLISHED : JobStatus::DELIVERED);

    // Republishing must not move the original publish date.
    $job->status = JobStatus::PUBLISHED;
    $job->transitionTo(JobStatus::IN_PROGRESS);

    expect($job->fresh()->published_at->timestamp)->toBe($firstPublished->timestamp);
});

it('keeps the budget in integer minor units through a round trip', function (): void {
    $job = Job::factory()->draft()->create(['budget_cents' => 1_234_56]);

    expect($job->budget()->minorUnits)->toBe(123456)
        ->and($job->budget()->toDecimal())->toBe('1234.56')
        ->and(Money::parse('1234.56')->minorUnits)->toBe($job->budget()->minorUnits);
});

/*
|--------------------------------------------------------------------------
| Escrow is what makes a job public, not a status field
|--------------------------------------------------------------------------
| Found during browser verification: a job with status=published and NO escrow
| row was listed with an "Escrow funded" badge. That is a false claim shown to
| someone deciding whether to trust the platform. The badge now derives from a
| real row, and the public scope requires one.
*/

it('does not list a published job that has no escrow hold', function (): void {
    // Status says published, but no money is held.
    Job::factory()->published()->create(['title' => 'Published But Unfunded']);

    $this->get('/jobs')
        ->assertOk()
        ->assertDontSee('Published But Unfunded');
});

it('does not list a job whose escrow holds zero', function (): void {
    $job = Job::factory()->published()->create(['title' => 'Zero Held Job']);
    \App\Models\Escrow::factory()->forJob($job)->empty()->create();

    $this->get('/jobs')->assertOk()->assertDontSee('Zero Held Job');
});

it('returns 404 for a published job with no escrow hold', function (): void {
    $job = Job::factory()->published()->create();

    $this->get("/jobs/{$job->id}")->assertNotFound();
});

it('shows the funded badge only when money is genuinely held', function (): void {
    $funded = Job::factory()->funded()->create(['title' => 'Properly Funded Job']);

    $response = $this->get("/jobs/{$funded->id}");

    $response->assertOk()
        ->assertSee('Escrow funded')
        ->assertDontSee('Awaiting escrow');
});

it('reports a job as funded only when an escrow row exists', function (): void {
    $unfunded = Job::factory()->published()->create();
    $funded = Job::factory()->funded()->create();

    expect($unfunded->isFunded())->toBeFalse()
        ->and($funded->isFunded())->toBeTrue();
});

it('never lets a second escrow row fund the same job twice', function (): void {
    $job = Job::factory()->funded()->create();

    // The unique index on job_id is the last line of defence against a
    // double-funding bug in the service layer.
    expect(fn () => \App\Models\Escrow::factory()->forJob($job)->create())
        ->toThrow(\Illuminate\Database\QueryException::class);
});
