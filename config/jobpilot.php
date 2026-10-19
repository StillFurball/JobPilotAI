<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local LLM (Ollama)
    |--------------------------------------------------------------------------
    |
    | driver: "ollama" talks to a local Ollama server and transparently falls
    | back to the built-in offline engine when it is unreachable. "mock" always
    | uses the offline engine (used by the test suite).
    |
    */

    'llm' => [
        'driver' => env('JOBPILOT_LLM_DRIVER', 'ollama'),
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:8b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Sources
    |--------------------------------------------------------------------------
    */

    'adzuna' => [
        'app_id' => env('ADZUNA_APP_ID', ''),
        'app_key' => env('ADZUNA_APP_KEY', ''),
        'country' => env('ADZUNA_COUNTRY', 'id'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound Applications
    |--------------------------------------------------------------------------
    |
    | Applications are only ever sent after explicit, hash-bound approval. Even
    | then, nothing leaves the app unless send_emails is true; the message goes
    | through Laravel's configured mailer (MAIL_MAILER) with the CV attached.
    |
    */

    'send_emails' => (bool) env('JOBPILOT_SEND_EMAILS', false),

    /*
    |--------------------------------------------------------------------------
    | CV Storage
    |--------------------------------------------------------------------------
    */

    'cv_disk' => env('JOBPILOT_CV_DISK', 'local'),
    'cv_directory' => env('JOBPILOT_CV_DIRECTORY', 'cvs'),

];
