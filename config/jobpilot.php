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
    | then, nothing leaves the app unless send_emails is true. The message is
    | sent from the user's verified login email through their own mailbox,
    | which they connect in Settings with an app password for one of the
    | providers below. Laravel's own mailer (MAIL_MAILER) is only used for
    | account emails such as verification links and password resets.
    |
    */

    'send_emails' => (bool) env('JOBPILOT_SEND_EMAILS', false),

    'mail_providers' => [
        'gmail' => [
            'label' => 'Gmail / Google Workspace',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'app_password_url' => 'https://myaccount.google.com/apppasswords',
        ],
        'yahoo' => [
            'label' => 'Yahoo Mail',
            'host' => 'smtp.mail.yahoo.com',
            'port' => 465,
            'app_password_url' => 'https://login.yahoo.com/account/security',
        ],
        'icloud' => [
            'label' => 'iCloud Mail',
            'host' => 'smtp.mail.me.com',
            'port' => 587,
            'app_password_url' => 'https://account.apple.com/account/manage',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CV Storage
    |--------------------------------------------------------------------------
    */

    'cv_disk' => env('JOBPILOT_CV_DISK', 'local'),
    'cv_directory' => env('JOBPILOT_CV_DIRECTORY', 'cvs'),

];
