<?php

namespace App\Http\Controllers\JobPilot;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobPilot\UpdateCandidateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CandidateProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('candidate/edit', [
            'profile' => $request->user()->profileOrCreate(),
        ]);
    }

    public function update(UpdateCandidateProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (['skills', 'education', 'experience', 'projects', 'certifications', 'preferred_locations', 'preferred_countries', 'preferred_employment_types', 'preferred_keywords'] as $list) {
            $data[$list] = array_values($data[$list] ?? []);
        }

        $data['preferred_keywords'] = array_values(array_unique(array_map('mb_strtolower', $data['preferred_keywords'])));
        $data['preferred_countries'] = array_map('mb_strtolower', $data['preferred_countries']);

        $request->user()->profileOrCreate()->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Candidate profile saved. Re-run discovery to refresh match scores.')]);

        return to_route('candidate.edit');
    }
}
