<?php

namespace App\Http\Controllers\JobPilot;

use App\Http\Controllers\Controller;
use App\Services\JobPilot\ApplicationDispatcher;
use App\Services\JobPilot\LlmClient;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "System & Local AI" page: configuration health and Ollama diagnostics.
 */
class AssistantController extends Controller
{
    public function index(LlmClient $llm, ApplicationDispatcher $dispatcher): Response
    {
        return Inertia::render('assistant/index', [
            'health' => [
                'env' => app()->environment(),
                'llm_driver' => config('jobpilot.llm.driver'),
                'ollama_url' => config('jobpilot.llm.base_url'),
                'ollama_model' => config('jobpilot.llm.model'),
                'adzuna_configured' => config('jobpilot.adzuna.app_id') !== '' && config('jobpilot.adzuna.app_key') !== '',
                'send_enabled' => $dispatcher->enabled(),
                'mailer' => config('mail.default'),
                'queue' => config('queue.default'),
                'pdf_engine' => class_exists(Dompdf::class) ? 'dompdf' : null,
            ],
            'llm' => Inertia::defer(fn (): array => $llm->ping()),
        ]);
    }

    public function test(Request $request, LlmClient $llm): RedirectResponse
    {
        $validated = $request->validate(['prompt' => ['required', 'string', 'max:2000']]);

        $result = $llm->chat([['role' => 'user', 'content' => $validated['prompt']]], maxTokens: 400);

        Inertia::flash('llmTest', [
            'prompt' => $validated['prompt'],
            'response' => $result['content'],
            'model' => $result['model'],
            'fallback' => $result['fallback'],
        ]);

        return back();
    }
}
