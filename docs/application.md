# Application

## What the app is today

Hirely is a Laravel + Inertia SPA. Authenticated users land on a role-specific dashboard and sidebar.

Public branding on `/` is **TalentTune** (“career preparation platform”). Seed users use `@talenttune.com` addresses. The repository name is Hirely.

## Stack

| Layer | Choice |
| --- | --- |
| PHP | ^8.2 (CI runs 8.4) |
| Backend | Laravel 12, Fortify, Inertia Laravel 2 |
| Frontend | Vue 3, TypeScript, Vite 7, Tailwind 4, Reka UI |
| Routing helpers | Laravel Wayfinder |
| Auth | Laravel Fortify (registration, reset, email verification, 2FA) |
| AI | OpenAI Chat Completions via `App\Services\AIService` (`gpt-4o-mini` default) |
| Payments | Stripe PHP SDK via `App\Services\StripeService` |
| Tests | Pest 4 |
| Style | Laravel Pint, ESLint, Prettier |

## User journeys that work

### Job seeker

1. Register (always stored as `job_seeker`) or log in.
2. Verify email.
3. Start a **text** or **voice** mock interview (technical / behavioral / mixed, beginner / intermediate / advanced).
4. Complete the session and view AI score plus feedback.
5. Maintain a portfolio and skill-expectation list.
6. Browse active jobs and apply with an optional cover letter.
7. Track and withdraw applications.
8. Subscribe to a Stripe plan (free or premium).

### HR professional

1. Account is created by an admin and linked to a company.
2. Create and manage job postings.
3. Review applications, add notes, and move status through pending → reviewing → shortlisted → interviewed → accepted / rejected.
4. Open Filter Candidates (lists job seekers; skill/experience filters do not apply).
5. Manage a Stripe subscription.

### Admin

1. CRUD companies.
2. CRUD admins and HR users; CRUD job seekers on a separate page.
3. View payment and subscription statistics.
4. Open Analytics (placeholder).

## Pages versus backend

| Page | Backend | Live data |
| --- | --- | --- |
| `job-seeker/MockInterview` + session pages | `MockInterviewController` + `AIService` | Yes |
| `job-seeker/Portfolio` | `PortfolioController` | Yes |
| `job-seeker/SkillExpectations` | `SkillExpectationController` | Yes |
| `job-seeker/BrowseJobs`, `JobApplications` | `JobApplicationController` | Yes |
| `hr/PostJobs` | `HR\JobController` | Yes |
| `hr/ReviewCandidates` | `HR\CandidateController` | Yes |
| `hr/FilterCandidates` | `CandidateController@filter` | Users listed; filters ignored |
| `admin/CompanyManagement` | `Admin\CompanyController` | Yes |
| `admin/UserManagement`, `HRManagement`, `JobSeekerManagement` | Admin controllers | Yes |
| `admin/Payments` | `Admin\PaymentController` | Yes |
| `hr/Subscriptions`, `job-seeker/Payments` | `Payment\PaymentController` | Yes |
| `job-seeker/CVReview` | Closure route, no controller | No (static cards) |
| `job-seeker/ATSScoring` | Closure route | No |
| `job-seeker/ProfileScore` | Closure route | No |
| `Dashboard` | Closure route | No (hardcoded zeros) |
| `admin/Analytics` | Closure route | No |

## Route map (authenticated)

Defined in `routes/web.php`. All of the following sit behind `auth` + `verified` and are **not** split by role.

**Job seeker**

- `GET /cv-review`, `/ats-scoring`, `/profile-score`
- `GET|POST /mock-interview`, `GET|PUT /mock-interview/{session}`, conversation + initial-message endpoints
- Portfolio, skill-expectations, job-applications, browse-jobs CRUD/list routes
- Payment checkout, success, cancel, billing portal, cancel/resume subscription

**HR**

- `GET|POST|PUT|DELETE /post-jobs`
- `GET /review-candidates`, `PUT /review-candidates/{application}`
- `GET /filter-candidates`
- `GET /subscriptions`

**Admin**

- `/analytics`, `/company-management`, `/user-management`, `/job-seeker-management`, `/hr-management`
- `/payments` and `/payments/{payment}`

Known issue: `payments` is registered twice in the same middleware group (job-seeker/HR payment index, then admin payment index). The later admin route wins for `GET /payments` and the `payments` name.

Settings routes live in `routes/settings.php` (profile, password, appearance, 2FA).

## AI behaviour

`App\Services\AIService` is the only AI integration.

| Method | Used for | Input | Output |
| --- | --- | --- | --- |
| `generateQuestions` | New mock session | type, difficulty, count=5 | JSON array of strings; hardcoded fallback bank if the API fails |
| `getConversationalResponse` | Voice turns | conversation history | Short interviewer utterance |
| `generateFeedback` | Text session completion | questions + answers | `{ feedback, overall_score, overall_feedback }` |

Prompts do not receive a CV, job description, portfolio, or skill list. There is no dedicated analysis pipeline for CVs.

## Billing behaviour

Plans are seeded in `PaymentPlanSeeder`:

- HR: Basic $0, Professional $49/mo, Enterprise $99/mo
- Job seeker: Free $0, Premium $19.99/mo

Checkout creates a Stripe customer and Checkout Session. Free plans skip Stripe prices. Feature lists on plans are marketing copy; the app does not enforce posting limits or “unlimited mock interviews”.

`STRIPE_WEBHOOK_SECRET` is configured, but no webhook route or listener exists. Subscription state after unpaid invoices or customer-portal changes will drift unless webhooks are added.

## Tests

Pest feature tests cover Fortify auth and settings (login, registration, password, 2FA, email verification, dashboard access). There are no feature tests for jobs, applications, mock interviews, AI, or Stripe.
