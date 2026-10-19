<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\JobPilot\JobDiscoveryAgent;
use App\Services\JobPilot\JobMatchingAgent;
use Illuminate\Database\Seeder;

/**
 * Demo data ported from JobPilotAI's seed.py: a filled-in candidate profile
 * for the first user, plus a batch of built-in vacancies with match scores.
 */
class JobPilotSeeder extends Seeder
{
    public function run(JobDiscoveryAgent $discovery, JobMatchingAgent $matcher): void
    {
        $user = User::query()->oldest('id')->first();

        if (! $user) {
            $this->command->warn('No users yet — register an account first, then run: php artisan db:seed --class=JobPilotSeeder');

            return;
        }

        $profile = $user->profileOrCreate();
        $profile->update([
            'phone' => '+62 812-3456-7890',
            'location' => 'Jakarta, Indonesia',
            'linkedin_url' => 'https://linkedin.com/in/aziz-pratama',
            'github_url' => 'https://github.com/azizpratama',
            'portfolio_url' => 'https://azizpratama.dev',
            'professional_summary' => 'Passionate Full-Stack & AI Software Engineer with strong background in Python, FastAPI, React, TypeScript, and local LLM integrations. Experienced in building scalable web applications, automated workflows, and data pipelines.',
            'skills' => ['Python', 'FastAPI', 'React', 'TypeScript', 'SQL', 'PostgreSQL', 'Docker', 'Git', 'REST APIs', 'Tailwind CSS', 'Ollama', 'Machine Learning'],
            'education' => [
                ['degree' => 'Bachelor of Computer Science (S.Kom)', 'institution' => 'Universitas Indonesia', 'year' => '2020 - 2024', 'gpa' => '3.85 / 4.00'],
            ],
            'experience' => [
                ['title' => 'Junior Full Stack Developer', 'company' => 'Nusantara Digital Solusindo', 'location' => 'Jakarta, Indonesia', 'period' => '2023 - Present', 'description' => 'Developed backend microservices using FastAPI and interactive frontend dashboards using React and Vite.'],
                ['title' => 'Software Engineering Intern', 'company' => 'Inovasi Karya Cloud', 'location' => 'Remote', 'period' => '2022 - 2023', 'description' => 'Integrated third-party APIs and automated daily ETL pipelines using Python and SQL.'],
            ],
            'projects' => [
                ['name' => 'JobPilot AI', 'description' => 'Local-first multi-agent job application orchestrator with local LLM integration and tailored CV rendering.', 'tech' => 'Laravel, React, Ollama'],
                ['name' => 'HealthPulse Analytics', 'description' => 'Real-time telemetry and patient metrics dashboard with predictive alerting.', 'tech' => 'Python, React, WebSockets'],
            ],
            'certifications' => [
                ['name' => 'AWS Certified Cloud Practitioner', 'issuer' => 'Amazon Web Services', 'year' => '2023'],
                ['name' => 'Python Professional Developer', 'issuer' => 'Python Institute', 'year' => '2022'],
            ],
            'preferred_locations' => ['Jakarta', 'Bandung', 'Remote'],
            'preferred_countries' => ['id', 'sg'],
            'preferred_employment_types' => ['full_time', 'internship', 'contract'],
            'preferred_keywords' => ['python', 'fastapi', 'react', 'software engineer', 'full stack', 'ai engineer'],
            'min_salary' => 8_000_000,
            'max_salary' => 20_000_000,
        ]);

        $result = $discovery->run(
            keywords: ['python', 'full stack', 'software engineer', 'frontend'],
            countries: ['id'],
            employmentTypes: [],
            maxJobs: 12,
            sourcePreference: 'mock',
        );

        $matcher->run($profile->refresh(), $result['jobs'], withLlm: false);

        $this->command->info("Seeded JobPilot profile for {$user->email} with {$result['jobs']->count()} jobs.");
    }
}
