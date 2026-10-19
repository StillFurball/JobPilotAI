# JobPilot

An AI-assisted job seeker built with Laravel 13, Inertia v3 and React. JobPilot discovers vacancies, scores them against your candidate profile with a local LLM (Ollama), tailors a CV and drafts an application email — and nothing is sent without your explicit, hash-bound approval.

## Requirements

- PHP 8.3+ with the `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo` and `dom` extensions
- Composer 2
- Node.js 20.19+ (or 22+) and npm
- Git
- Optional: [Ollama](https://ollama.com) for local AI matching and drafting
- Optional: an [Adzuna](https://developer.adzuna.com) API key for real vacancies

> On Windows/macOS, [Laravel Herd](https://herd.laravel.com) provides PHP, Composer and Node in one install.

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/<your-username>/<your-repo>.git
cd <your-repo>
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell use `copy .env.example .env` instead of `cp`.

### 4. Create the database

The app uses SQLite by default.

```bash
# macOS / Linux / Git Bash
touch database/database.sqlite

# Windows PowerShell
New-Item database/database.sqlite
```

Then run the migrations:

```bash
php artisan migrate
```

### 5. Build the frontend

```bash
npm run build
```

> Steps 2–5 can also be run in one go with `composer run setup` (create `database/database.sqlite` first).

## Running the app

```bash
composer run dev
```

This starts the web server, the queue worker and the Vite dev server together. Open <http://localhost:8000>, register an account and you will land on your team dashboard.

The queue worker is required — the discovery agent runs as a background job, so runs stay "pending" if it is not running. To start the processes separately:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

## Optional services

### Local AI (Ollama)

Without Ollama, JobPilot falls back to a built-in offline engine, so the app works either way.

```bash
ollama pull qwen3:8b
```

```dotenv
JOBPILOT_LLM_DRIVER=ollama
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen3:8b
```

Check the connection on the **System & Local AI** page.

### Real vacancies (Adzuna)

Create a free app at <https://developer.adzuna.com> and add the credentials:

```dotenv
ADZUNA_APP_ID=your-app-id
ADZUNA_APP_KEY=your-app-key
ADZUNA_COUNTRY=sg
```

Adzuna only covers certain countries (e.g. `sg`, `us`, `gb`, `au`, `in` — not Indonesia or Malaysia). For uncovered countries, or when no key is set, JobPilot uses built-in **sample listings** and shows a warning on the run.

### Sending applications

Sending is disabled by default (safe mode): approval is verified but no email leaves the app. To send for real, configure a mailer and enable it:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=you@example.com

JOBPILOT_SEND_EMAILS=true
```

After changing `.env`, run `php artisan config:clear`.

## Testing

```bash
php artisan test --compact
```

Lint, static analysis and tests together:

```bash
composer test
```

## Troubleshooting

| Problem | Fix |
| --- | --- |
| `No application encryption key has been specified` | Run `php artisan key:generate`, then `php artisan config:clear`. |
| `Database file at path ... does not exist` | Create `database/database.sqlite`, then run `php artisan migrate`. |
| `Unable to locate file in Vite manifest` | Run `npm run build`, or keep `npm run dev` running. |
| Discovery run stuck on "pending" | Start the queue worker (`composer run dev` or `php artisan queue:listen`). |
| Run completes with 0 vacancies | Check `storage/logs/laravel.log`; verify your Adzuna keys and pick a supported country. |

## Security

Never commit `.env` — it holds your app key and API credentials and is already listed in `.gitignore`.
