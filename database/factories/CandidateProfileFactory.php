<?php

namespace Database\Factories;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateProfile>
 */
class CandidateProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '+62 812-3456-7890',
            'location' => 'Jakarta, Indonesia',
            'professional_summary' => 'Full-stack engineer working with PHP, Laravel and React.',
            'skills' => ['Python', 'FastAPI', 'React'],
            'education' => [['degree' => 'Bachelor of Computer Science', 'institution' => 'UI', 'year' => '2020 - 2024']],
            'experience' => [['title' => 'SWE', 'company' => 'Acme', 'period' => '2023 - Present']],
            'projects' => [],
            'certifications' => [],
            'preferred_locations' => ['Jakarta', 'Remote'],
            'preferred_countries' => ['id'],
            'preferred_employment_types' => ['full_time'],
            'preferred_keywords' => ['python', 'backend'],
        ];
    }
}
