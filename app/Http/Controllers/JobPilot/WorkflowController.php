<?php

namespace App\Http\Controllers\JobPilot;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobPilot\StoreWorkflowRequest;
use App\Jobs\RunJobWorkflow;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    /**
     * Queue a discovery + matching run for the authenticated user.
     */
    public function store(StoreWorkflowRequest $request): RedirectResponse
    {
        $run = $request->user()->workflowRuns()->create([
            'status' => 'pending',
            'stage' => 'queued',
            'search_params' => [...$request->validated(), 'run_matching' => $request->boolean('run_matching', true)],
            'started_at' => now(),
        ]);

        RunJobWorkflow::dispatch($run);

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Workflow #:id started.', ['id' => $run->id])]);

        return back();
    }
}
