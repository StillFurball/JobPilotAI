<?php

namespace Database\Factories;

use App\Models\JobListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobListing>
 */
class JobListingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => 'mock',
            'source_id' => 'test-'.fake()->unique()->numerify('####'),
            'title' => 'Python Backend Developer',
            'company' => 'TechCorp',
            'location' => 'Jakarta',
            'country' => 'ID',
            'employment_type' => 'full_time',
            'description' => 'Looking for a Python developer with FastAPI experience. Skills: Python, FastAPI, REST API, SQL. Bachelor degree preferred.',
            'application_email' => 'jobs@techcorp.co.id',
            'salary_min' => 8_000_000,
            'salary_max' => 15_000_000,
            'salary_currency' => 'IDR',
            'posted_at' => now()->subDays(2),
            'is_active' => true,
        ];
    }
}
