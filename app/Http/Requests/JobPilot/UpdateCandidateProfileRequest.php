<?php

namespace App\Http\Requests\JobPilot;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateProfileRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'github_url' => ['nullable', 'url', 'max:500'],
            'portfolio_url' => ['nullable', 'url', 'max:500'],
            'professional_summary' => ['nullable', 'string', 'max:5000'],

            'skills' => ['array', 'max:100'],
            'skills.*' => ['string', 'max:100'],

            'education' => ['array', 'max:20'],
            'education.*.degree' => ['required', 'string', 'max:255'],
            'education.*.institution' => ['required', 'string', 'max:255'],
            'education.*.year' => ['nullable', 'string', 'max:50'],
            'education.*.gpa' => ['nullable', 'string', 'max:50'],

            'experience' => ['array', 'max:30'],
            'experience.*.title' => ['required', 'string', 'max:255'],
            'experience.*.company' => ['required', 'string', 'max:255'],
            'experience.*.location' => ['nullable', 'string', 'max:255'],
            'experience.*.period' => ['nullable', 'string', 'max:100'],
            'experience.*.description' => ['nullable', 'string', 'max:3000'],

            'projects' => ['array', 'max:30'],
            'projects.*.name' => ['required', 'string', 'max:255'],
            'projects.*.description' => ['nullable', 'string', 'max:3000'],
            'projects.*.tech' => ['nullable', 'string', 'max:500'],
            'projects.*.url' => ['nullable', 'url', 'max:500'],

            'certifications' => ['array', 'max:30'],
            'certifications.*.name' => ['required', 'string', 'max:255'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:255'],
            'certifications.*.year' => ['nullable', 'string', 'max:20'],

            'preferred_locations' => ['array', 'max:20'],
            'preferred_locations.*' => ['string', 'max:100'],
            'preferred_countries' => ['array', 'max:10'],
            'preferred_countries.*' => ['string', 'size:2'],
            'preferred_employment_types' => ['array'],
            'preferred_employment_types.*' => ['string', 'in:full_time,part_time,internship,contract'],
            'preferred_keywords' => ['array', 'max:30'],
            'preferred_keywords.*' => ['string', 'max:100'],
            'min_salary' => ['nullable', 'integer', 'min:0'],
            'max_salary' => ['nullable', 'integer', 'min:0', 'gte:min_salary'],
        ];
    }
}
