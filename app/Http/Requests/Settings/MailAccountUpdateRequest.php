<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MailAccountUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(array_keys(config('jobpilot.mail_providers')))],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * App passwords are often shown in groups ("abcd efgh ijkl mnop"); providers expect them without spaces.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->password)) {
            $this->merge(['password' => preg_replace('/\s+/', '', $this->password)]);
        }
    }
}
