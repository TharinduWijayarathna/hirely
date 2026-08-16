# Infrastructure

## Runtime

| Concern | Current default | Notes |
| --- | --- | --- |
| App server | `php artisan serve` via Composer `dev` script | Concurrently runs queue listener, Pail, and Vite |
| PHP | 8.2+ locally, 8.4 in GitHub Actions | |
| Node | 22 in CI | Vite 7 frontend build |
| Queue | `database` in `.env.example` | `sync` in tests |
| Cache | `database` | `array` in tests |
| Session | `database`, 120 minutes | Encryption off by default |
| Filesystem | `local` | No CV/object-storage pipeline yet |
| Mail | `log` | Auth notifications only |
| Broadcasting | `log` | Unused |

There is **no Docker Compose or Dockerfile** in the repo. Laravel Sail is a Composer dev dependency but is not wired up with a compose file.

There is **no production deploy config** in-repo (no Elastic Beanstalk, Forge, Kubernetes, or Terraform).

## Data stores

Default connection is **SQLite** (`DB_CONNECTION=sqlite`). MySQL/Postgres can be enabled through standard Laravel env vars.

Laravel also uses:

- `jobs` / `job_batches` / `failed_jobs` (queue)
- `cache` / `cache_locks`
- `sessions`

Application tables are listed in [data-model.md](./data-model.md).

## Third-party services

| Service | Config | Used? |
| --- | --- | --- |
| OpenAI | `OPENAI_API_KEY`, `OPENAI_MODEL` (default `gpt-4o-mini`), `OPENAI_BASE_URL` | Yes — questions, conversation, feedback |
| Stripe | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Partial — Checkout and Billing Portal; no webhook endpoint |
| Google Cloud Text-to-Speech | Composer + npm packages | **No** — voice interviews use `window.speechSynthesis` and Web Speech Recognition |
| AWS SES | `config/services.php` `ses` | Not used; mailer is `log` |
| Redis | Env placeholders | Not the default cache/queue/session driver |

## Environment checklist

From `.env.example`:

```
APP_NAME=Laravel          # still the Laravel default; UI says TalentTune
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

## CI

`.github/workflows/tests.yml`

- On push/PR to `main` and `develop`
- PHP 8.4, Node 22
- `npm ci`, `composer install`, `npm run build`, Pest

`.github/workflows/lint.yml`

- Pint, Prettier (`npm run format`), ESLint (`npm run lint`)
- Auto-commit of style fixes is commented out

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
| HTTPS termination | Not defined (local HTTP) |
| Secrets management | `.env` only |
| CSRF | Laravel default |
| Login / 2FA rate limits | Fortify |
| Role-based route protection | Missing |
| File malware scanning | N/A (no uploads) |
| Stripe webhook signatures | Secret configured, handler missing |
| Object storage for CVs | Not started |

## What to add before production

- A real database (MySQL/Postgres) and backups
- Queue worker and scheduler as always-on processes
- Stripe webhooks
- Object storage (S3 or equivalent) for CVs
- Role-aware authorization
- Mail provider (SES, Postmark, or Resend) instead of `log`
- Deployment target and environment secrets
- Decide whether Google Cloud TTS is required; if not, remove the unused packages
