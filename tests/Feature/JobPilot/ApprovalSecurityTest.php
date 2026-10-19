<?php

use App\Models\ApplicationDraft;
use App\Services\JobPilot\JobSources\MockJobSource;
use App\Services\JobPilot\LlmClient;

function makeDraft(array $overrides = []): ApplicationDraft
{
    $draft = new ApplicationDraft([
        'version' => 1,
        'recipient_email' => 'jobs@company.com',
        'subject' => 'Application for SWE',
        'body' => 'Dear Hiring Manager, ...',
        'cv_file_hash' => 'abc123',
        ...$overrides,
    ]);
    $draft->refreshContentHash();

    return $draft;
}

test('content hash is stable', function () {
    $draft = makeDraft();

    expect($draft->computeContentHash())->toBe($draft->computeContentHash())
        ->and($draft->content_hash)->toHaveLength(64);
});

test('content hash changes when any bound field changes', function (string $field, string $value) {
    $draft = makeDraft();
    $before = $draft->computeContentHash();

    $draft->{$field} = $value;

    expect($draft->computeContentHash())->not->toBe($before);
})->with([
    'subject' => ['subject', 'MODIFIED SUBJECT'],
    'body' => ['body', 'Completely different body'],
    'recipient' => ['recipient_email', 'different@company.com'],
    'cv' => ['cv_file_hash', 'newcvhash456'],
]);

test('mock job source returns normalized jobs', function () {
    $jobs = (new MockJobSource)->search(['python'], 'id', maxResults: 5);

    expect($jobs)->toHaveCount(5);

    foreach ($jobs as $job) {
        expect($job->source)->toBe('mock')
            ->and($job->sourceId)->not->toBeEmpty()
            ->and($job->title)->not->toBeEmpty();
    }
});

test('mock job source ids are deterministic and respect max results', function () {
    $source = new MockJobSource;

    expect($source->search(['python'], 'id', maxResults: 10)[0]->sourceId)
        ->toBe($source->search(['python'], 'id', maxResults: 10)[0]->sourceId)
        ->and($source->search([], 'id', maxResults: 3))->toHaveCount(3);
});

test('mock llm driver answers chat, generate and ping', function () {
    $llm = new LlmClient('mock', 'http://127.0.0.1:11434', 'qwen3:8b', 5);

    expect($llm->chat([['role' => 'user', 'content' => 'Hello']])['content'])->not->toBeEmpty()
        ->and($llm->generate('Test prompt'))->toBeString()->not->toBeEmpty()
        ->and($llm->ping())->toMatchArray(['status' => 'ok', 'provider' => 'mock']);
});

test('ollama driver falls back to the offline engine when unreachable', function () {
    Http::fake(['*' => Http::response('down', 500)]);

    $result = (new LlmClient('ollama', 'http://127.0.0.1:11434', 'qwen3:8b', 5))
        ->chat([['role' => 'user', 'content' => 'Write a professional job application email.\nJob: Tester']]);

    expect($result['fallback'])->toBeTrue()
        ->and($result['content'])->toContain('Subject:');
});
