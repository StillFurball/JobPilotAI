<?php

namespace App\Http\Requests\JobPilot;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Approve / reject payload. The content hash must be the one the reviewer saw,
 * which prevents approving content that changed after it was loaded.
 */
class ReviewDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('draft')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content_hash' => ['required', 'string', 'size:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
