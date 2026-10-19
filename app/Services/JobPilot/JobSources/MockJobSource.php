<?php

namespace App\Services\JobPilot\JobSources;

use Illuminate\Support\Str;

/**
 * Generates realistic job listings for development and testing.
 * No external credentials required.
 */
class MockJobSource implements JobSource
{
    /** @var array<int, string> */
    protected const COMPANIES = [
        'Tokopedia', 'Gojek', 'Bukalapak', 'Traveloka', 'Ruangguru',
        'Shopee Indonesia', 'Grab Indonesia', 'OVO', 'Dana', 'LinkAja',
        'Accenture Indonesia', 'Deloitte Indonesia', 'IBM Indonesia',
        'Microsoft Indonesia', 'Google Indonesia',
    ];

    /** @var array<int, string> */
    protected const EMPLOYMENT_TYPES = ['full_time', 'part_time', 'internship', 'contract'];

    /** @var array<int, string> */
    protected const LOCATIONS = ['Jakarta', 'Bandung', 'Surabaya', 'Yogyakarta', 'Bali', 'Remote'];

    /** @var array<int, array{title: string, description: string}> */
    protected const TEMPLATES = [
        [
            'title' => 'Software Engineer',
            'description' => "We are looking for a passionate Software Engineer to design, develop, and maintain high-quality software solutions. You will work in an agile team on exciting products used by millions of users.\n\nRequirements:\n- Bachelor's degree in Computer Science or related field\n- Proficiency in Python, JavaScript, or Java\n- Experience with REST APIs and microservices\n- Familiarity with Git, CI/CD pipelines\n- Strong problem-solving skills",
        ],
        [
            'title' => 'Data Science Intern',
            'description' => "Join our data team and work on real-world machine learning problems. This internship is perfect for students or fresh graduates.\n\nRequirements:\n- Currently pursuing or recently graduated in Statistics, CS, or Math\n- Proficiency in Python (pandas, scikit-learn, numpy)\n- Understanding of ML fundamentals\n- Good analytical and communication skills",
        ],
        [
            'title' => 'Frontend Developer',
            'description' => "Build beautiful and performant user interfaces for our web platform.\n\nRequirements:\n- 1+ years experience with React or Vue.js\n- Proficiency in HTML, CSS, JavaScript/TypeScript\n- Understanding of responsive design\n- Experience with REST API integration\n- Attention to detail and pixel-perfect execution",
        ],
        [
            'title' => 'Backend Engineer (PHP / Laravel)',
            'description' => "Design and implement scalable backend services.\n\nRequirements:\n- 2+ years PHP or Python experience\n- Experience with Laravel, FastAPI or Django\n- Database design skills (MySQL, PostgreSQL)\n- Understanding of system design and scalability\n- Experience with Docker and cloud services",
        ],
        [
            'title' => 'Machine Learning Engineer',
            'description' => "Build and deploy ML models at scale.\n\nRequirements:\n- Experience with TensorFlow or PyTorch\n- Strong Python skills\n- Understanding of ML Ops and model deployment\n- Experience with feature engineering and model evaluation",
        ],
        [
            'title' => 'Product Manager Intern',
            'description' => "Work closely with engineering and design teams to define and ship products.\n\nRequirements:\n- Strong analytical thinking\n- Good communication skills (Bahasa Indonesia + English)\n- Basic understanding of software development\n- Passion for technology products",
        ],
        [
            'title' => 'DevOps Engineer',
            'description' => "Maintain and improve our infrastructure and deployment pipelines.\n\nRequirements:\n- Experience with Linux, bash scripting\n- Kubernetes, Docker expertise\n- CI/CD (GitHub Actions, Jenkins)\n- Cloud platforms (AWS/GCP/Azure)\n- Infrastructure-as-code (Terraform)",
        ],
        [
            'title' => 'Full Stack Developer',
            'description' => "Work on both frontend and backend of our platform.\n\nRequirements:\n- 2+ years full stack experience\n- React or Next.js for frontend\n- Node.js, PHP or Python for backend\n- Database experience (SQL/NoSQL)\n- API design and integration",
        ],
    ];

    public function name(): string
    {
        return 'mock';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function supportsCountry(string $country): bool
    {
        return true;
    }

    public function search(
        array $keywords,
        string $country = 'id',
        ?string $location = null,
        ?string $employmentType = null,
        int $maxResults = 20,
    ): array {
        $templates = self::TEMPLATES;

        if ($keywords !== []) {
            $needles = array_map(fn (string $k): string => Str::lower($k), $keywords);
            $filtered = array_values(array_filter($templates, function (array $t) use ($needles): bool {
                $haystack = Str::lower($t['title'].' '.$t['description']);

                return collect($needles)->contains(fn (string $kw): bool => str_contains($haystack, $kw));
            }));

            if ($filtered !== []) {
                $templates = $filtered;
            }
        }

        $jobs = [];
        $count = min($maxResults, 20);

        for ($i = 0; $i < $count; $i++) {
            $template = $templates[$i % count($templates)];
            $company = self::COMPANIES[$i % count(self::COMPANIES)];
            $type = $employmentType ?: self::EMPLOYMENT_TYPES[$i % count(self::EMPLOYMENT_TYPES)];
            $sourceId = sprintf('mock-%s-%s-%04d', Str::lower($country), Str::slug($template['title']), $i);
            $domain = Str::of($company)->lower()->replace(' ', '')->append('.co.id');

            $jobs[] = new NormalizedJob(
                source: 'mock',
                sourceId: $sourceId,
                title: $template['title'],
                company: $company,
                location: $location ?: self::LOCATIONS[$i % count(self::LOCATIONS)],
                country: Str::upper($country),
                employmentType: $type,
                description: $template['description'],
                salaryMin: $i % 2 === 0 ? 4_000_000 : 6_000_000,
                salaryMax: $i % 2 === 0 ? 10_000_000 : 15_000_000,
                salaryCurrency: 'IDR',
                applicationUrl: "https://jobs.example.com/{$sourceId}",
                applicationEmail: "jobs@{$domain}",
                postedAt: now()->subDays($i % 30),
                rawData: ['template' => $template['title']],
            );
        }

        return $jobs;
    }
}
