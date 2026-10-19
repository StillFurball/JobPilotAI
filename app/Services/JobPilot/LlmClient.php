<?php

namespace App\Services\JobPilot;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reusable LLM abstraction (port of app/llm/client.py).
 *
 * Talks to a local Ollama server and falls back to a deterministic offline
 * engine whenever Ollama is unreachable, so the app keeps working end-to-end.
 */
class LlmClient
{
    public function __construct(
        protected string $driver,
        protected string $baseUrl,
        protected string $model,
        protected int $timeout,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            driver: (string) config('jobpilot.llm.driver', 'ollama'),
            baseUrl: rtrim((string) config('jobpilot.llm.base_url'), '/'),
            model: (string) config('jobpilot.llm.model'),
            timeout: (int) config('jobpilot.llm.timeout', 120),
        );
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Send a chat completion request.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, model: string|null, fallback: bool}
     */
    public function chat(array $messages, float $temperature = 0.3, int $maxTokens = 4096): array
    {
        if ($this->driver === 'mock') {
            return $this->chatMock($messages);
        }

        try {
            return $this->chatOllama($messages, $temperature, $maxTokens);
        } catch (Throwable $e) {
            Log::info('Ollama unavailable at {url} ({error}). Falling back to offline engine.', [
                'url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);

            return $this->chatMock($messages);
        }
    }

    /**
     * Convenience wrapper that returns the assistant text directly.
     */
    public function generate(string $prompt, float $temperature = 0.3, int $maxTokens = 4096): string
    {
        return $this->chat([['role' => 'user', 'content' => $prompt]], $temperature, $maxTokens)['content'];
    }

    /**
     * Health-check the LLM backend.
     *
     * @return array<string, mixed>
     */
    public function ping(): array
    {
        if ($this->driver === 'mock') {
            return ['status' => 'ok', 'provider' => 'mock', 'model' => $this->model];
        }

        try {
            $response = Http::connectTimeout(2)->timeout(4)
                ->get("{$this->baseUrl}/api/tags")
                ->throw();

            /** @var array<int, array{name: string}> $tags */
            $tags = $response->json('models', []);
            $models = array_column($tags, 'name');

            return [
                'status' => 'ok',
                'provider' => 'ollama',
                'base_url' => $this->baseUrl,
                'model' => $this->model,
                'model_loaded' => in_array($this->model, $models, true),
                'available_models' => $models,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'offline',
                'provider' => 'ollama',
                'base_url' => $this->baseUrl,
                'model' => $this->model,
                'error' => $e->getMessage(),
                'fallback_mode' => 'mock',
            ];
        }
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, model: string|null, fallback: bool}
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    protected function chatOllama(array $messages, float $temperature, int $maxTokens): array
    {
        $response = Http::connectTimeout(2.5)
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/api/chat", [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
                'options' => ['temperature' => $temperature, 'num_predict' => $maxTokens],
            ])
            ->throw();

        return [
            'content' => (string) $response->json('message.content', ''),
            'model' => $response->json('model'),
            'fallback' => false,
        ];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, model: string|null, fallback: bool}
     */
    protected function chatMock(array $messages): array
    {
        $last = $messages === [] ? '' : (string) end($messages)['content'];

        return [
            'content' => $this->contextualMock($last),
            'model' => 'mock-fallback',
            'fallback' => true,
        ];
    }

    protected function contextualMock(string $prompt): string
    {
        $lower = Str::lower($prompt);

        if (str_contains($lower, 'evaluating job-candidate') || str_contains($lower, 'match score')) {
            return 'The candidate demonstrates strong technical alignment with the position requirements. '
                .'Core background in software engineering, API architecture, and database management '
                .'provides a solid foundation. Recommend proceeding with tailored application.';
        }

        if (str_contains($lower, 'application email') || str_contains($lower, 'write a professional job')) {
            $jobTitle = 'the Open Position';
            foreach (explode("\n", $prompt) as $line) {
                if (str_starts_with($line, 'Job:')) {
                    $jobTitle = trim(Str::after($line, 'Job:'));
                    break;
                }
            }

            return "Subject: Application for {$jobTitle}\n\n"
                ."Dear Hiring Team,\n\n"
                ."I am writing to express my enthusiastic interest in the {$jobTitle} opportunity. "
                .'With my experience in software development and full-stack engineering, '
                ."I am eager to contribute effectively to your team's mission.\n\n"
                .'In my previous projects, I have designed scalable backend APIs and high-performance '
                .'frontend architectures. My technical qualifications and collaborative mindset '
                ."position me well to deliver immediate value.\n\n"
                ."Thank you for considering my application. I welcome the opportunity to discuss my qualifications further.\n\n"
                ."Sincerely,\nCandidate";
        }

        if (str_contains($lower, 'tailor') || str_contains($lower, 'curriculum vitae') || str_contains($lower, 'resume')) {
            return 'Targeted software engineer offering strong expertise in modern frameworks, '
                .'scalable cloud services, and clean architecture principles. Demonstrated track record '
                .'of delivering robust applications and collaborating across cross-functional teams.';
        }

        return '[AI Assistant] Processed request successfully for prompt: '.Str::limit($prompt, 80);
    }
}
