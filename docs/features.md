# Feature inventory

Detailed mapping of the 23 product capabilities to the current codebase. Status labels match [PRODUCT_STATUS.md](./PRODUCT_STATUS.md).

---

## 1. User Authentication & Role Management — Partial

**Implemented**

- Login, logout, registration, password reset, email verification (Fortify)
- TOTP two-factor authentication with confirmation and recovery codes
- Profile, password, and appearance settings
- `users.role` enum: `job_seeker`, `hr_professional`, `admin`
- Admin CRUD for admins, HR professionals (with `company_id`), and job seekers
- Sidebar and dashboard copy switch on `auth.user.role`

**Not implemented**

- Role selection at registration (always defaults to job seeker)
- Server-side role middleware or policies
- Permission model finer than the three roles
- Impersonation, SSO, or social login

**Key files:** `config/fortify.php`, `app/Actions/Fortify/CreateNewUser.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Controllers/Admin/UserManagementController.php`, `database/migrations/2025_10_31_213306_add_role_to_users_table.php`

---

## 2. Organization Management — Partial

**Implemented**

- `companies` table and admin CRUD (name, slug, industry, size, location, contact, verification flag)
- HR users belong to a company
- Jobs may reference a company

**Not implemented**

- HR creating or editing their own organization
- Multi-user org roles (owner, recruiter, hiring manager)
- Tenant isolation: HR still loads `Company::all()` when posting jobs
- Logo file upload (logo is a string field)

**Key files:** `app/Models/Company.php`, `app/Http/Controllers/Admin/CompanyController.php`, `resources/js/pages/admin/CompanyManagement.vue`

---

## 3. Job Vacancy Management — Implemented

**Implemented**

- Full CRUD for postings by the authenticated HR user
- Employment type, remote mode, salary range, skill tags, draft/active/closed/expired, expiry date
- Job-seeker browse with search, type, and remote filters
- Apply once per job; withdraw application

**Gaps**

- Not scoped to the HR user’s company
- No workflow for publishing approval
- `resume_path` on apply is a string, not a stored file
- Plan limits (“post up to 5 jobs”) are not enforced

**Key files:** `app/Http/Controllers/HR/JobController.php`, `app/Http/Controllers/JobSeeker/JobApplicationController.php`, `app/Models/Job.php`, `resources/js/pages/hr/PostJobs.vue`

---

## 4. Candidate Management — Partial

**Implemented**

- List applications for jobs the HR user posted
- Update status and notes
- List all `job_seeker` users on Filter Candidates

**Not implemented**

- Skill / experience / qualification filters (controller has empty stubs)
- Candidate profile assembled from CV extraction
- Shortlist as a first-class entity
- Assignment of candidates to interviews

**Key files:** `app/Http/Controllers/HR/CandidateController.php`, `resources/js/pages/hr/ReviewCandidates.vue`, `resources/js/pages/hr/FilterCandidates.vue`

---

## 5. CV Upload & CV Analysis — Placeholder

**UI only:** `resources/js/pages/job-seeker/CVReview.vue` and `ATSScoring.vue` show upload dropzones and fake scores. Routes are closures with no persistence.

| Required extraction | Status |
| --- | --- |
| Candidate information | Not started |
| Education | Not started |
| Skills | Not started |
| Experience | Not started |
| Qualifications | Not started |
| Projects | Not started (manual portfolio is separate) |
| Certifications | Not started |
| Relevant technologies | Not started |
| Relevant experience | Not started |

Related but not CV analysis: portfolio CRUD and skill-expectation CRUD are manual forms.

---

## 6. AI-Based Interview Question Generation — Partial

`AIService::generateQuestions($type, $difficulty, $count)` asks OpenAI for a JSON array. Types are only `technical`, `behavioral`, `mixed`. A static fallback bank exists if the API fails.

| Question kind | Status |
| --- | --- |
| Technical | Partial (generic) |
| Skill-based | Not started |
| Experience-based | Not started |
| CV-based | Not started |
| Project-based | Not started |
| Scenario-based | Not started as its own type |
| Problem-solving | Not started as its own type |
| Role-specific | Not started |
| Follow-up | Partial in voice conversation |

**Key files:** `app/Services/AIService.php`, `app/Http/Controllers/JobSeeker/MockInterviewController.php`

---

## 7. AI Interview Configuration — Partial

On start, the seeker chooses `mode` (text/voice), `type`, and `difficulty`. Count is always `5`. Duration is computed when the session completes.

Missing: category mix percentages, evaluation criteria, question weights, target duration, per-job templates, HR-authored configs.

---

## 8. Text-Based Interview — Partial

Working mock-interview flow: sequential questions, textarea answers, complete → AI feedback and score (`MockInterviewSession.vue`).

This is practice for the job seeker, not an HR-scheduled candidate interview.

---

## 9. Voice-Based Interview — Partial

`MockInterviewSessionVoice.vue` uses Web Speech Recognition and `speechSynthesis`. Laravel stores conversation history and returns the next AI line.

Google Cloud TTS packages are unused. No server-side audio storage or playback for HR review.

---

## 10. AI Dynamic Interview — Partial

Voice mode is conversational: acknowledgements and follow-ups from history. Text mode is a fixed list generated up front. Neither path adapts to a CV, job description, or remaining time budget.

---

## 11. AI Answer Analysis — Partial

On text completion, `generateFeedback` returns per-question comments plus `overall_feedback`. Voice sessions do not automatically run the same structured analysis on every turn.

No scoring rubric (correctness, communication, depth, relevance) as separate fields.

---

## 12. AI Strength & Weakness Analysis — Placeholder

No structured strengths/weaknesses. Any mention is incidental prose inside `overall_feedback`.

---

## 13. AI Scoring System — Partial

One numeric `score` 0–100 stored on `mock_interview_sessions`. Fallback is `70` if the model fails. No weighted dimensions, no calibration, no job-relative scoring.

---

## 14. Explainable AI Evaluation — Placeholder

Per-question text is a weak form of explanation. Missing: criterion-level scores, evidence quotes from the answer, model confidence, and a human-readable “why this rank” report for HR.

---

## 15. Automatic Candidate Ranking — Not started

No aggregation of interview + CV + application scores, no ordering per job.

---

## 16. Candidate Comparison — Not started

No two- (or n-) candidate comparison UI or API.

---

## 17. HR Dashboard — Placeholder

`Dashboard.vue` HR cards show `0`. Real work happens on Post Jobs and Review Candidates. No pipeline funnel, time-to-hire, or interview-load widgets.

---

## 18. Candidate Dashboard — Placeholder

Same dashboard file; job-seeker cards are hardcoded. Live stats exist only on the Mock Interview index (`total`, `average_score`, `total_time`).

---

## 19. Interview Result — Partial

Job seekers open a dialog of score and feedback for completed mock sessions. HR cannot see those sessions, and results are not attached to applications. Status `interviewed` on an application is a manual label only.

---

## 20. Recruitment Reports — Placeholder

`admin/Analytics.vue` is empty metrics. Admin Payments reports revenue and subscription counts (commercial, not hiring). No export of hiring funnel, source, or interviewer performance.

---

## 21. Notification System — Placeholder

Bell icon in `AppSidebarHeader.vue` with a static red dot. User model uses `Notifiable` for password reset and email verification only. No database notifications, no application-status emails, no interview invites.

---

## 22. Security — Partial

**Present:** hashed passwords, CSRF, session auth, optional 2FA, email verification, login/2FA rate limiting, some owner checks (jobs, applications, mock sessions).

**Missing:** role middleware, policies, company scoping, Stripe webhooks, CV upload validation, audit logging, production HTTPS/secrets story (see [infrastructure.md](./infrastructure.md)).

---

## 23. Human-in-the-Loop — Partial

HR can override application pipeline status and write notes. There is no queue of AI recommendations to accept/reject, no score override field, no question-bank approval, and no audit of who changed an AI evaluation.

---

## Out-of-scope (but present) features

Documented so they are not mistaken for the 23 capabilities:

- **Billing:** Stripe Checkout, customer portal, cancel/resume, admin payment stats
- **Portfolio & skill goals:** seeker-authored, not extracted from CVs
- **Admin user administration:** separate pages for job seekers vs HR/admins
