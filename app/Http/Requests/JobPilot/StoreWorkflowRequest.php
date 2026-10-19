<?php

namespace App\Http\Requests\JobPilot;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreWorkflowRequest extends FormRequest
{
    /**
     * Accept comma-separated keywords as well as an array.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('keywords'))) {
            $this->merge([
                'keywords' => collect(explode(',', $this->input('keywords')))
                    ->map(fn (string $k): string => trim($k))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        }

        $this->merge([
            'countries' => array_map(fn ($c): string => Str::lower((string) $c), (array) $this->input('countries', ['id'])),
            'employment_types' => array_values(array_filter((array) $this->input('employment_types', []))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keywords' => ['required', 'array', 'min:1', 'max:20'],
            'keywords.*' => ['string', 'max:100'],
            'countries' => ['required', 'array', 'min:1', 'max:5'],
            'countries.*' => ['string', 'size:2'],
            'employment_types' => ['array'],
            'employment_types.*' => ['string', 'in:full_time,part_time,internship,contract'],
            'max_jobs' => ['required', 'integer', 'min:1', 'max:50'],
            'job_source' => ['required', 'string', 'in:auto,mock,adzuna'],
            'run_matching' => ['sometimes', 'boolean'],
        ];
    }
}
