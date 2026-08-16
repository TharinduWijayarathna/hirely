# Application

## What the app is today

Hirely is a Laravel + Inertia SPA. Authenticated users land on a role-specific dashboard and sidebar.

Public branding on `/` is **Hirely**. Seed users use `@hirely.test` addresses.

## Stack

| Layer | Choice |
| --- | --- |
| PHP | ^8.2 (CI runs 8.4) |
| Backend | Laravel 12, Fortify, Inertia Laravel 2 |
| Frontend | Vue 3, TypeScript, Vite 7, Tailwind 4, Reka UI |
| Routing helpers | Laravel Wayfinder |
| Auth | Laravel Fortify (registration, reset, email verification, 2FA) |
| AI | Gemini `generateContent` via `App\Services\AIService` (`gemini-2.5-flash` default) |
| Payments | Stripe PHP SDK via `App\Services\StripeService` |
| Tests | Pest 4 |
| Style | Laravel Pint, ESLint, Prettier |

## User journeys that work

### Job seeker

1. Register (always stored as `job_seeker`) or log in.
2. Verify email.
3. Start a **text** or **voice** mock interview, or complete an assigned recruitment interview (including follow-ups).
4. Complete the session and view AI score plus feedback.
5. Maintain a portfolio and skill-expectation list.
6. Browse active jobs and apply with an optional cover letter.
7. Track and withdraw applications.
8. Subscribe to a Stripe plan (free or premium).

### HR professional

1. Account is created by an admin and linked to a company.
2. Create and manage job postings.
3. Review applications, add notes, assign interviews, rank/compare candidates, and export reports.
4. Filter candidates by extracted skills/experience and edit the company profile.
5. Manage a Stripe subscription.

### Admin

1. CRUD companies.
2. CRUD admins and HR users; CRUD job seekers on a separate page.
3. View payment and subscription statistics.
4. Open live Analytics.

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
| `job-seeker/CVReview` | `CvController` | Yes |
| `job-seeker/ATSScoring` | `AtsScoringController` | Yes |
| `job-seeker/ProfileScore` | `ProfileScoreController` | Yes |
| `Dashboard` | `DashboardController` | Yes |
| `admin/Analytics` | `Admin\AnalyticsController` | Yes |
| `hr/Reports` | `HR\ReportController` | Yes |
| `hr/CompanySettings` | `HR\CompanySettingsController` | Yes |

## Route map (authenticated)

Defined in `routes/web.php`. Authenticated routes sit behind `auth` + `verified` and are split by `role` middleware.

**Job seeker**

- `GET /cv-review`, `/ats-scoring`, `/profile-score`
- `GET|POST /mock-interview`, `GET|PUT /mock-interview/{session}`, follow-up + conversation + initial-message endpoints
- `GET /interviews`, session/result, follow-up, conversation, initial
- Portfolio, skill-expectations, job-applications, browse-jobs CRUD/list routes
- `/payments` plus shared checkout/portal routes

**HR**

- `GET|POST|PUT|DELETE /post-jobs`
- Review/filter candidates, interview templates, interview results, rankings, reports + CSV export
- `/company-settings`, `/subscriptions`

**Admin**

- `/analytics`, `/company-management`, `/user-management`, `/job-seeker-management`, `/hr-management`
- `/admin/payments`

Settings routes live in `routes/settings.php` (profile, password, appearance, 2FA).

## AI behaviour

`App\Services\AIService` is the only AI integration.

| Method | Used for | Input | Output |
| --- | --- | --- | --- |
| `generateQuestions` | New mock session | type, difficulty, count=5 | JSON array of strings; hardcoded fallback bank if the API fails |
| `generateFollowUpQuestion` | Text-mode Next | question + answer | One probe, or heuristic fallback |
| `getConversationalResponse` | Voice turns | conversation history + optional job/CV context | Short interviewer utterance |
| `generateFeedback` | Text session completion | questions + answers | `{ feedback, overall_score, overall_feedback }` |

Prompts for configured recruitment interviews receive job title/description and candidate CV/portfolio context. Mock type/difficulty interviews remain generic unless a follow-up or voice turn adds context.

## Billing behaviour

Plans are seeded in `PaymentPlanSeeder`:

- HR: Basic $0, Professional $49/mo, Enterprise $99/mo
- Job seeker: Free $0, Premium $19.99/mo

Checkout creates a Stripe customer and Checkout Session. Free plans skip Stripe prices. Structured `limits` on `payment_plans` are enforced by `PlanLimitService` (users without a subscription use the free/basic defaults for their role; admins bypass):

- HR Basic: 5 job listings, no recruitment reports
- HR Professional / Enterprise: unlimited jobs, reports enabled
- Seeker Free: 3 mock interviews per calendar month, 1 stored CV, no ATS scoring
- Seeker Premium: unlimited mock interviews and CVs, ATS scoring enabled

`POST /stripe/webhook` syncs subscription and payment state after Checkout and the customer portal.

## Tests

Pest feature tests cover Fortify auth and settings, role and resource authorization, job postings, applications, CV/ATS, recruitment interviews (weights, follow-ups, voice), ranking, reports/CSV, company settings, Stripe webhooks, notifications, plan-limit enforcement, and AI fallbacks when Gemini is unset or the API fails.
