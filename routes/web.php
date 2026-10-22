<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobPilot\ApplicationController;
use App\Http\Controllers\JobPilot\ApplicationDraftController;
use App\Http\Controllers\JobPilot\AssistantController;
use App\Http\Controllers\JobPilot\CandidateProfileController;
use App\Http\Controllers\JobPilot\JobListingController;
use App\Http\Controllers\JobPilot\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| JobPilot AI
|--------------------------------------------------------------------------
|
| Job-seeking data (profile, matches, drafts, applications) is owned by the
| authenticated user; discovered vacancies are shared across all users.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('candidate-profile', [CandidateProfileController::class, 'edit'])->name('candidate.edit');
    Route::put('candidate-profile', [CandidateProfileController::class, 'update'])->name('candidate.update');

    Route::post('workflows', [WorkflowController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('workflows.store');

    Route::get('jobs', [JobListingController::class, 'index'])->name('jobs.index');
    Route::post('jobs/{jobListing}/drafts', [ApplicationDraftController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('jobs.drafts.store');

    Route::get('review', [ApplicationDraftController::class, 'index'])->name('drafts.index');
    Route::patch('drafts/{draft}', [ApplicationDraftController::class, 'update'])->name('drafts.update');
    Route::post('drafts/{draft}/approve', [ApplicationDraftController::class, 'approve'])->name('drafts.approve');
    Route::post('drafts/{draft}/reject', [ApplicationDraftController::class, 'reject'])->name('drafts.reject');
    Route::get('drafts/{draft}/cv', [ApplicationDraftController::class, 'cv'])->name('drafts.cv');

    Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::post('applications/{application}/send', [ApplicationController::class, 'send'])
        ->middleware('throttle:10,1')
        ->name('applications.send');

    Route::get('assistant', [AssistantController::class, 'index'])->name('assistant.index');
    Route::post('assistant/test', [AssistantController::class, 'test'])
        ->middleware('throttle:10,1')
        ->name('assistant.test');
});

require __DIR__.'/settings.php';
