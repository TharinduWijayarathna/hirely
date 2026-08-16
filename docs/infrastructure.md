# Infrastructure

## Runtime

| Concern | Current default | Production / Compose |
| --- | --- | --- |
| App server | `php artisan serve` via Composer `dev` script | `Dockerfile` serves on port 8080; `docker compose up` |
| PHP | 8.2+ locally, 8.4 in GitHub Actions and the image | |
| Node | 22 in CI and the image | Vite 7 frontend build |
| Queue | `database` in `.env.example` | Redis worker service in Compose; `sync` in tests |
| Cache | `database` | Redis in Compose; `array` in tests |
| Session | `database`, 120 minutes | Redis in Compose |
| Filesystem | `local` | Set `CV_DISK=s3` / `FILESYSTEM_DISK=s3` for private CV object storage |
| Mail | `log` | SMTP to Mailpit in Compose; SES/Postmark/Resend in production |
| Scheduler | `routes/console.php` | `schedule:work` service in Compose |
| Health | `GET /up` | Laravel 12 health endpoint |

## Data stores

Local default is **SQLite** (`DB_CONNECTION=sqlite`). Docker Compose and production use **MySQL 8.4**. Redis is used for cache, queue, and session when Compose (or equivalent) is running.

Laravel also uses:

- `jobs` / `job_batches` / `failed_jobs` (database queue fallback)
- `cache` / `cache_locks`
- `sessions`

Application tables are listed in [data-model.md](./data-model.md).

## Third-party services

| Service | Config | Used? |
| --- | --- | --- |
| OpenAI | `OPENAI_API_KEY`, `OPENAI_MODEL` (default `gpt-4o-mini`), `OPENAI_BASE_URL` | Yes — questions, conversation, feedback, CV analysis, ATS scoring |
| Stripe | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Checkout, Billing Portal, and `POST /stripe/webhook` |
| AWS S3 | `AWS_*`, `CV_DISK` / `FILESYSTEM_DISK` | Optional private CV storage (`league/flysystem-aws-s3-v3`) |
| Mail | `MAIL_*` | Notifications; `log` locally, SMTP/SES in production |

Voice interviews use the **browser** (`speechSynthesis` + Web Speech Recognition). Google Cloud TTS packages were removed.

## Environment checklist

From `.env.example`:

```
APP_NAME=Hirely
APP_URL=http://localhost
DB_CONNECTION=sqlite
OPENAI_API_KEY=key
STRIPE_KEY=...
STRIPE_SECRET=...
STRIPE_WEBHOOK_SECRET=...
```

Required for a working local demo:

1. `APP_KEY`
2. Migrated SQLite (or MySQL) database
3. `OPENAI_API_KEY` for live questions/feedback (otherwise fallback question banks and generic feedback)
4. Stripe keys for paid checkout (free plans can be granted in-app)

Production extras: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, MySQL, Redis, a mail provider, and `CV_DISK=s3` with an AWS bucket. URLs are forced to HTTPS when `APP_ENV=production`. Proxies are trusted for TLS termination.

## Docker

```bash
cp .env.example .env
php artisan key:generate
docker compose up --build
```

Services: `app` (http://localhost:8080), `queue`, `scheduler`, `mysql`, `redis`, `mailpit` (http://localhost:8025). Then:

```bash
docker compose exec app php artisan migrate --seed
```

Seed logins: `admin@hirely.test`, `hr@hirely.test`, `jobseeker@hirely.test` (password `password`).

Image builds can be verified with `.github/workflows/docker.yml` (`workflow_dispatch`).

## CI

`.github/workflows/tests.yml`

- On push/PR to `main` and `develop`
- PHP 8.4, Node 22
- `npm ci`, `composer install`, `npm run build`, Pest

`.github/workflows/lint.yml`

- Pint, Prettier (`npm run format`), ESLint (`npm run lint`)

## Local development

```bash
composer setup    # install, env, key, migrate, npm install, build
composer dev      # serve + queue + logs + vite
composer test     # pest
```

Optional Inertia SSR: `composer dev:ssr`.

## Security-related infrastructure

| Control | Present |
| --- | --- |
| HTTPS termination | Forced HTTPS in production; trust proxies |
| Secrets management | `.env` / Compose `env_file` |
| CSRF | Laravel default (Stripe webhook excluded) |
| Login / 2FA rate limits | Fortify |
| Role-based route protection | `role` middleware on job-seeker, HR, and admin groups |
| File malware scanning | Not included |
| Stripe webhook signatures | `POST /stripe/webhook` with Stripe signature verification |
| Object storage for CVs | S3-ready via `CV_DISK` |

## What to add at the hosting provider

- Managed MySQL backups
- Object-storage bucket and IAM credentials
- Mail provider API keys (SES, Postmark, or Resend)
- TLS certificate in front of port 8080 (or swap `artisan serve` for php-fpm/nginx)
- Environment secrets outside the image
