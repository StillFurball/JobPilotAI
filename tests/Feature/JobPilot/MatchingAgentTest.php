<?php

use App\Models\CandidateProfile;
use App\Models\JobListing;
use App\Services\JobPilot\JobMatchingAgent;

function makeProfile(array $overrides = []): CandidateProfile
{
    return new CandidateProfile([
        'full_name' => 'Test User',
        'email' => 'test@example.com',
        'skills' => ['Python', 'FastAPI', 'React'],
        'preferred_locations' => ['Jakarta', 'Remote'],
        'preferred_countries' => ['id'],
        'preferred_employment_types' => ['full_time'],
        'preferred_keywords' => ['python', 'backend'],
        'education' => [['degree' => 'Bachelor of Computer Science', 'institution' => 'UI']],
        'experience' => [['title' => 'SWE', 'company' => 'Acme']],
        ...$overrides,
    ]);
}

function makeJob(array $overrides = []): JobListing
{
    return new JobListing([
        'source' => 'mock',
        'source_id' => 'test-001',
        'title' => 'Python Backend Developer',
        'company' => 'TechCorp',
        'location' => 'Jakarta',
        'country' => 'ID',
        'employment_type' => 'full_time',
        'description' => 'Looking for a Python developer with FastAPI experience. Skills: Python, FastAPI, REST API, SQL. Bachelor degree preferred.',
        ...$overrides,
    ]);
}

beforeEach(function () {
    $this->agent = app(JobMatchingAgent::class);
});

test('a strong candidate scores above 50% with matched skills', function () {
    $result = $this->agent->calculateMatchScore(makeProfile(), makeJob());

    expect($result['score'])->toBeGreaterThan(0.5)
        ->and($result['matched_skills'])->toContain('python');
});

test('no overlapping skills gives a zero skill score', function () {
    $result = $this->agent->calculateMatchScore(makeProfile(['skills' => ['Rust', 'Haskell']]), makeJob());

    expect($result['skill_score'])->toBe(0.0)
        ->and($result['matched_skills'])->toBe([]);
});

test('preferred location gives a full location score', function () {
    $result = $this->agent->calculateMatchScore(makeProfile(['preferred_locations' => ['Jakarta']]), makeJob(['location' => 'Jakarta']));

    expect($result['location_score'])->toBe(1.0);
});

test('location mismatch scores low', function () {
    $result = $this->agent->calculateMatchScore(
        makeProfile(['preferred_locations' => ['Surabaya'], 'preferred_countries' => ['sg']]),
        makeJob(['location' => 'Jakarta', 'country' => 'id']),
    );

    expect($result['location_score'])->toBeLessThan(0.5);
});

test('remote jobs always get a full location score', function () {
    $result = $this->agent->calculateMatchScore(makeProfile(['preferred_locations' => ['Bandung']]), makeJob(['location' => 'Remote']));

    expect($result['location_score'])->toBe(1.0);
});

test('matching employment type gives a full score', function () {
    $result = $this->agent->calculateMatchScore(
        makeProfile(['preferred_employment_types' => ['internship']]),
        makeJob(['employment_type' => 'internship']),
    );

    expect($result['employment_type_score'])->toBe(1.0);
});

test('missing education is flagged when a degree is required', function () {
    $result = $this->agent->calculateMatchScore(
        makeProfile(['education' => []]),
        makeJob(['description' => 'Bachelor degree required. Python skills needed.']),
    );

    expect(collect($result['missing_requirements'])->contains(fn (string $r) => str_contains(strtolower($r), 'degree')))->toBeTrue();
});

test('scores stay within range and are reproducible', function () {
    $first = $this->agent->calculateMatchScore(makeProfile(), makeJob());
    $second = $this->agent->calculateMatchScore(makeProfile(), makeJob());

    expect($first['score'])->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual(1.0)
        ->and($first['score'])->toBe($second['score']);
});

test('skills stored as objects are supported', function () {
    $result = $this->agent->calculateMatchScore(makeProfile(['skills' => [['name' => 'Python'], ['name' => 'SQL']]]), makeJob());

    expect($result['matched_skills'])->toBe(['python', 'sql']);
});
