<?php

use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\JobListing;
use App\Models\MailAccount;
use App\Models\User;
use App\Models\WorkflowRun;
use App\Services\JobPilot\UserMailer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

test('guests cannot reach jobpilot pages', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['jobs.index', 'drafts.index', 'applications.index', 'candidate.edit', 'assistant.index']);

test('authenticated users can open every jobpilot page', function (string $route, string $component) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    ['jobs.index', 'jobs/index'],
    ['drafts.index', 'review/index'],
    ['applications.index', 'applications/index'],
    ['candidate.edit', 'candidate/edit'],
    ['assistant.index', 'assistant/index'],
]);

test('a candidate profile is created from the account on first visit', function () {
    $user = User::factory()->create(['name' => 'Budi', 'email' => 'budi@example.com']);

    $this->actingAs($user)->get(route('candidate.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.full_name', 'Budi')
            ->where('profile.email', 'budi@example.com'));

    expect($user->candidateProfile()->count())->toBe(1);
});

test('users can update their candidate profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('candidate.update'), [
        'full_name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'skills' => ['Laravel', 'React'],
        'preferred_keywords' => ['Laravel', 'laravel', 'PHP'],
        'preferred_countries' => ['ID'],
        'education' => [['degree' => 'S.Kom', 'institution' => 'ITS', 'year' => '2023 - 2027']],
        'experience' => [],
        'min_salary' => 5_000_000,
        'max_salary' => 9_000_000,
    ])->assertRedirect(route('candidate.edit'));

    $profile = $user->candidateProfile()->first();

    expect($profile->full_name)->toBe('Budi Santoso')
        ->and($profile->skills)->toBe(['Laravel', 'React'])
        ->and($profile->preferred_keywords)->toBe(['laravel', 'php'])
        ->and($profile->preferred_countries)->toBe(['id'])
        ->and($profile->education[0]['institution'])->toBe('ITS');
});

test('the workflow discovers shared jobs and matches them for the user', function () {
    $user = User::factory()->create();
    $user->profileOrCreate()->update(['skills' => ['Python', 'React'], 'preferred_keywords' => ['python']]);

    $this->actingAs($user)->post(route('workflows.store'), [
        'keywords' => 'python, react',
        'countries' => ['id'],
        'employment_types' => [],
        'max_jobs' => 6,
        'job_source' => 'mock',
    ])->assertRedirect();

    $run = WorkflowRun::sole();

    expect($run->status)->toBe('completed')
        ->and($run->jobs_discovered)->toBe(6)
        ->and($run->jobs_new)->toBe(6)
        ->and($run->jobs_matched)->toBe(6)
        ->and(JobListing::count())->toBe(6)
        ->and($user->jobMatches()->count())->toBe(6);

    // A second user running the same search reuses the shared vacancies but gets their own matches.
    $other = User::factory()->create();
    $this->actingAs($other)->post(route('workflows.store'), [
        'keywords' => 'python, react', 'countries' => ['id'], 'max_jobs' => 6, 'job_source' => 'mock',
    ]);

    expect(JobListing::count())->toBe(6)
        ->and($other->jobMatches()->count())->toBe(6)
        ->and($other->workflowRuns()->sole()->jobs_new)->toBe(0);
});

test('countries adzuna does not cover fall back to sample listings with a warning', function () {
    config(['jobpilot.adzuna.app_id' => 'test-id', 'jobpilot.adzuna.app_key' => 'test-key']);
    Http::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('workflows.store'), [
        'keywords' => 'php, laravel',
        'countries' => ['id'],
        'max_jobs' => 4,
        'job_source' => 'auto',
    ])->assertRedirect();

    $run = WorkflowRun::sole();

    expect($run->status)->toBe('completed')
        ->and($run->jobs_discovered)->toBe(4)
        ->and($run->error_message)->toBe('Adzuna does not cover ID; showing sample listings instead.')
        ->and(JobListing::pluck('source')->unique()->all())->toBe(['mock']);

    Http::assertNothingSent();
});

test('jobs page shows only the current users match scores', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $job = JobListing::factory()->create();

    $other->jobMatches()->create(['job_listing_id' => $job->id, 'score' => 0.9]);

    $this->actingAs($user)->get(route('jobs.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('jobs.data', 1)
            ->where('jobs.data.0.user_match', null));

    $user->jobMatches()->create(['job_listing_id' => $job->id, 'score' => 0.42]);

    $this->actingAs($user)->get(route('jobs.index'))
        ->assertInertia(fn (Assert $page) => $page->where('jobs.data.0.user_match.score', 0.42));

    $this->actingAs($user)->get(route('jobs.index', ['min_score' => 0.5]))
        ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 0));
});

/**
 * Create a draft through the real CV + email agents.
 */
function generateDraft(User $user, ?JobListing $job = null): ApplicationDraft
{
    $job ??= JobListing::factory()->create();
    test()->actingAs($user)->post(route('jobs.drafts.store', $job));

    return $user->applicationDrafts()->latest('id')->firstOrFail();
}

test('generating a draft tailors a cv and drafts an email with a verified recipient', function () {
    $user = User::factory()->create();
    $user->profileOrCreate()->update(['full_name' => 'Budi', 'skills' => ['Python', 'Laravel']]);
    $job = JobListing::factory()->create();

    $response = $this->actingAs($user)->post(route('jobs.drafts.store', $job));

    $draft = $user->applicationDrafts()->sole();
    $response->assertRedirect(route('drafts.index', ['draft' => $draft->id]));

    expect($draft->status)->toBe('draft')
        ->and($draft->recipient_email)->toBe('jobs@techcorp.co.id')
        ->and($draft->recipient_verified)->toBeTrue()
        ->and($draft->subject)->not->toBeEmpty()
        ->and($draft->body)->toContain('Budi')
        ->and($draft->content_hash)->toBe($draft->computeContentHash());

    Storage::disk('local')->assertExists($draft->cv_file_path);
    expect(hash('sha256', Storage::disk('local')->get($draft->cv_file_path)))->toBe($draft->cv_file_hash);

    // Requesting again while still pending reuses the same draft.
    $this->actingAs($user)->post(route('jobs.drafts.store', $job));
    expect($user->applicationDrafts()->count())->toBe(1);

    $this->actingAs($user)->get(route('drafts.cv', $draft))->assertOk();
});

test('editing a draft bumps the version and invalidates the old hash', function () {
    $user = User::factory()->create();
    $draft = generateDraft($user);
    $oldHash = $draft->content_hash;

    $this->actingAs($user)->patch(route('drafts.update', $draft), [
        'recipient_email' => 'hr@techcorp.co.id',
        'subject' => 'New subject',
        'body' => 'New body',
    ])->assertRedirect();

    $draft->refresh();
    expect($draft->version)->toBe(2)
        ->and($draft->content_hash)->not->toBe($oldHash);

    $this->actingAs($user)
        ->post(route('drafts.approve', $draft), ['content_hash' => $oldHash])
        ->assertSessionHasErrors('content_hash');

    expect($draft->refresh()->status)->toBe('draft')
        ->and(Application::count())->toBe(0);
});

test('approving with the current hash records the approval and creates an application', function () {
    $user = User::factory()->create();
    $draft = generateDraft($user);

    $this->actingAs($user)
        ->post(route('drafts.approve', $draft), ['content_hash' => $draft->content_hash, 'notes' => 'Looks good'])
        ->assertSessionHasNoErrors();

    $draft->refresh();
    $application = Application::sole();

    expect($draft->status)->toBe('approved')
        ->and($draft->approvalRecords()->sole()->notes)->toBe('Looks good')
        ->and($application->status)->toBe('approved')
        ->and($application->sent_content_hash)->toBe($draft->content_hash)
        ->and($application->user_id)->toBe($user->id);

    // Approved drafts are locked against edits.
    $this->actingAs($user)
        ->patch(route('drafts.update', $draft), ['subject' => 'x', 'body' => 'y'])
        ->assertSessionHasErrors('draft');
});

test('rejecting a draft marks it rejected', function () {
    $user = User::factory()->create();
    $draft = generateDraft($user);

    $this->actingAs($user)->post(route('drafts.reject', $draft), ['content_hash' => $draft->content_hash]);

    expect($draft->refresh()->status)->toBe('rejected')
        ->and($draft->approvalRecords()->sole()->action)->toBe('rejected');
});

test('sending is blocked in safe mode', function () {
    Mail::fake();
    config(['jobpilot.send_emails' => false]);

    $user = User::factory()->create();
    $draft = generateDraft($user);
    $this->actingAs($user)->post(route('drafts.approve', $draft), ['content_hash' => $draft->content_hash]);

    $this->actingAs($user)->post(route('applications.send', Application::sole()))->assertRedirect();

    expect(Application::sole()->status)->toBe('approved');
    Mail::assertNothingOutgoing();
});

test('approved applications are emailed from the users own mailbox with the cv', function () {
    config(['jobpilot.send_emails' => true]);

    $user = User::factory()->create(['name' => 'Budi', 'email' => 'budi@gmail.com']);
    $account = MailAccount::factory()->for($user)->create();
    $mailbox = app('mail.manager')->mailer('array');
    $this->partialMock(UserMailer::class)
        ->shouldReceive('mailerFor')
        ->withArgs(fn (MailAccount $a) => $a->is($account))
        ->andReturn($mailbox);

    $draft = generateDraft($user);
    $this->actingAs($user)->post(route('drafts.approve', $draft), ['content_hash' => $draft->content_hash]);

    $this->actingAs($user)->post(route('applications.send', Application::sole()))->assertSessionHasNoErrors();

    $application = Application::sole();
    expect($application->status)->toBe('sent')
        ->and($application->provider_message_id)->not->toBeEmpty()
        ->and($draft->refresh()->status)->toBe('sent');

    $sent = $mailbox->getSymfonyTransport()->messages();
    $message = $sent->first()->getOriginalMessage();
    expect($sent)->toHaveCount(1)
        ->and($message->getFrom()[0]->getAddress())->toBe('budi@gmail.com')
        ->and($message->getAttachments())->toHaveCount(1);
});

test('unverified users cannot send applications', function () {
    config(['jobpilot.send_emails' => true]);

    $user = User::factory()->unverified()->create();
    MailAccount::factory()->for($user)->create();
    $draft = generateDraft($user);
    $this->actingAs($user)->post(route('drafts.approve', $draft), ['content_hash' => $draft->content_hash]);

    $this->actingAs($user)
        ->post(route('applications.send', Application::sole()))
        ->assertSessionHasErrors('application');

    expect(Application::sole()->status)->toBe('approved');
});

test('sending requires a connected mailbox', function () {
    config(['jobpilot.send_emails' => true]);

    $user = User::factory()->create();
    $draft = generateDraft($user);
    $this->actingAs($user)->post(route('drafts.approve', $draft), ['content_hash' => $draft->content_hash]);

    $this->actingAs($user)
        ->post(route('applications.send', Application::sole()))
        ->assertSessionHasErrors('application');

    expect(Application::sole()->status)->toBe('approved');
});

test('sending fails if the draft changed after approval', function () {
    config(['jobpilot.send_emails' => true]);

    $user = User::factory()->create();
    $draft = generateDraft($user);
    $this->actingAs($user)->post(route('drafts.approve', $draft), ['content_hash' => $draft->content_hash]);

    // Tamper with the stored content behind the gate's back.
    ApplicationDraft::whereKey($draft->id)->update(['body' => 'tampered']);

    $this->actingAs($user)
        ->post(route('applications.send', Application::sole()))
        ->assertSessionHasErrors('application');

    expect(Application::sole()->status)->toBe('approved');
});

test('users cannot access another users drafts or applications', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $draft = generateDraft($owner);
    $this->actingAs($owner)->post(route('drafts.approve', $draft), ['content_hash' => $draft->content_hash]);

    $this->actingAs($intruder)->get(route('drafts.cv', $draft))->assertForbidden();
    $this->actingAs($intruder)->patch(route('drafts.update', $draft), ['subject' => 'x', 'body' => 'y'])->assertForbidden();
    $this->actingAs($intruder)->post(route('drafts.approve', $draft), ['content_hash' => $draft->content_hash])->assertForbidden();
    $this->actingAs($intruder)->post(route('drafts.reject', $draft), ['content_hash' => $draft->content_hash])->assertForbidden();
    $this->actingAs($intruder)->post(route('applications.send', Application::sole()))->assertForbidden();

    $this->actingAs($intruder)->get(route('drafts.index'))
        ->assertInertia(fn (Assert $page) => $page->has('drafts', 0)->where('selected', null));
});

test('dashboard shows the users jobpilot stats', function () {
    $user = User::factory()->create();
    $job = JobListing::factory()->create();
    $user->jobMatches()->create(['job_listing_id' => $job->id, 'score' => 0.8]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.matchedJobs', 1)
            ->where('stats.highMatches', 1)
            ->has('topMatches', 1)
            ->where('topMatches.0.job.title', 'Python Backend Developer'));
});
