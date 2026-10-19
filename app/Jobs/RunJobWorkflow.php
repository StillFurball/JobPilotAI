<?php

namespace App\Jobs;

use App\Models\WorkflowRun;
use App\Services\JobPilot\JobDiscoveryAgent;
use App\Services\JobPilot\JobMatchingAgent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Background orchestrator — runs discovery, then matching, for one workflow run.
 * (Port of FastAPI's BackgroundTasks `_run_workflow`.)
 */
class RunJobWorkflow implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public WorkflowRun $run) {}

    public function handle(JobDiscoveryAgent $discovery, JobMatchingAgent $matcher): void
    {
        $run = $this->run;
        $params = $run->search_params ?? [];

        try {
            $run->update(['status' => 'running', 'stage' => 'discovery', 'started_at' => $run->started_at ?? now()]);

            $result = $discovery->run(
                keywords: $params['keywords'] ?? [],
                countries: $params['countries'] ?? ['id'],
                employmentTypes: $params['employment_types'] ?? [],
                maxJobs: (int) ($params['max_jobs'] ?? 20),
                sourcePreference: (string) ($params['job_source'] ?? 'auto'),
            );

            $run->update(['jobs_discovered' => $result['jobs']->count(), 'jobs_new' => $result['new']]);

            if ($params['run_matching'] ?? true) {
                $run->update(['stage' => 'matching']);
                $matches = $matcher->run($run->user->profileOrCreate(), $result['jobs']);
                $run->update(['jobs_matched' => $matches->count()]);
            }

            $run->update([
                'status' => 'completed',
                'stage' => 'done',
                'error_message' => $result['warnings'] !== [] ? implode(' ', array_unique($result['warnings'])) : null,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
            $this->failed($e);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->run->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'Unknown error',
            'completed_at' => now(),
        ]);
    }
}
