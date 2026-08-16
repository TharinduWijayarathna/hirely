# Feature inventory

Detailed mapping of the 23 product capabilities to the current codebase. Status labels match [PRODUCT_STATUS.md](./PRODUCT_STATUS.md).

---

## 1. User Authentication & Role Management — Partial

**Implemented**

- Login, logout, registration, password reset, email verification (Fortify)
- TOTP two-factor authentication with confirmation and recovery codes
- Profile, password, and appearance settings
- `users.role` enum: `job_seeker`, `hr_professional`, `admin`
- Server-side role middleware (`role:job_seeker`, `role:hr_professional`, `role:admin`)
- Public registration always stores `job_seeker` (role from the request is ignored)
- Admin CRUD for admins, HR professionals (with `company_id`), and job seekers
- Sidebar and dashboard copy switch on `auth.user.role`

**Not implemented**

- Permission model finer than the three roles
- Impersonation, SSO, or social login

**Key files:** `config/fortify.php`, `app/Actions/Fortify/CreateNewUser.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Controllers/Admin/UserManagementController.php`, `database/migrations/2025_10_31_213306_add_role_to_users_table.php`

---

## 2. Organization Management — Partial

**Implemented**

- `companies` table and admin CRUD (name, slug, industry, size, location, contact, verification flag)
- HR users belong to a company
- Jobs may reference a company
- HR self-service company profile (`/company-settings`); `is_verified` remains admin-only

**Not implemented**

- HR creating a new organization from scratch
- Multi-user org roles (owner, recruiter, hiring manager)
- Logo file upload (logo is a string field)

**Key files:** `app/Models/Company.php`, `app/Http/Controllers/Admin/CompanyController.php`, `app/Http/Controllers/HR/CompanySettingsController.php`, `resources/js/pages/hr/CompanySettings.vue`

---

## 3. Job Vacancy Management — Implemented

**Implemented**

- Full CRUD for postings by the authenticated HR user
- Employment type, remote mode, salary range, skill tags, draft/active/closed/expired, expiry date
- Job-seeker browse with search, type, and remote filters
- Apply once per job; withdraw application

**Gaps**

- No workflow for publishing approval
- `resume_path` on apply is a string, not a stored file

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

## 5. CV Upload & CV Analysis — Partial

Job seekers upload PDF/DOCX files to private storage. `CvTextExtractor` parses text; `AIService::analyzeCurriculumVitae` extracts structured fields and a quality review. ATS scoring compares the latest CV to a pasted job description or a live posting. Free seekers may store one CV; ATS scoring is Premium.

HR sees the extraction on Review Candidates and can filter by skills/experience. Applications attach `cv_document_id` from the latest processed CV.

**Key files:** `app/Http/Controllers/JobSeeker/CvController.php`, `AtsScoringController.php`, `app/Services/CvAnalysisService.php`, `app/Models/CvDocument.php`

---

## 6. AI-Based Interview Question Generation — Partial

`AIService::generateConfiguredQuestions` builds technical / behavioral / scenario / CV questions from the template mix, job, and candidate context. Mock `generateQuestions($type, $difficulty, $count)` still uses `technical`, `behavioral`, `mixed` with a static fallback bank.

| Question kind | Status |
| --- | --- |
| Technical | Partial (job-aware on assigned interviews) |
| Skill-based | Partial (via CV/portfolio context) |
| Experience-based | Partial (via candidate context) |
| CV-based | Partial (own mix percentage) |
| Project-based | Partial (portfolio projects in prompt context) |
| Scenario-based | Partial (own mix percentage) |
| Problem-solving | Partial (covered by scenario mix) |
| Role-specific | Partial (job title and description in generation) |
| Follow-up | Partial (text probes + voice conversation) |

**Key files:** `app/Services/AIService.php`, `app/Http/Controllers/JobSeeker/MockInterviewController.php`

---

## 7. AI Interview Configuration — Partial

HR interview templates configure question count, duration, difficulty, mode, mix percentages, evaluation criteria, and criterion weights (`InterviewTemplateController`). Criteria and weights are copied onto assigned interviews. Weights recompute overall score as a weighted average of dimension scores. Mock interviews still only pick type/difficulty/mode with a fixed count of 5.

---

## 8. Text-Based Interview — Partial

Working mock and assigned recruitment text interviews: sequential questions, textarea answers, optional follow-up probes on Next (max 3), complete → AI feedback and score (`InterviewSession.vue`, `MockInterviewSession.vue`).

---

## 9. Voice-Based Interview — Partial

`MockInterviewSessionVoice.vue` and `InterviewSessionVoice.vue` use Web Speech Recognition and `speechSynthesis`. Laravel stores conversation history and returns the next AI line. Assigned recruitment interviews with `mode=voice` use the same path. No server-side audio storage or playback for HR review.

---

## 10. AI Dynamic Interview — Partial

Voice mode is conversational: acknowledgements and follow-ups from history. Text mode generates a list up front, then can insert up to three follow-up probes after an answered question. Neither path adapts to a remaining time budget.

---

## 11. AI Answer Analysis — Partial

On recruitment completion, `InterviewEvaluationService` stores per-question scores, feedback, and evidence snippets. Mock text completion maps the same evaluator into the legacy `{ feedback, overall_score, overall_feedback }` shape.

Voice sessions do not automatically run the same structured analysis on every turn.

---

## 12. AI Strength & Weakness Analysis — Partial

Recruitment evaluations persist `strengths[]` and `weaknesses[]` on `interviews.evaluation`. Heuristic fallback still produces structured lists when Gemini is unset.

---

## 13. AI Scoring System — Partial

Overall 0–100 plus criterion dimensions (default: Technical depth, Communication, Problem solving, Role fit). Template criterion weights are snapshotted onto the interview and applied as a weighted average. Effective `score` is `ai_score` until HR sets `human_score` via an edit.

---

## 14. Explainable AI Evaluation — Partial

Criterion-level scores, evidence quotes, rationale, and confidence are stored and shown on candidate/HR result pages. Ranking includes a human-readable weighted rationale per applicant.

---

## 15. Automatic Candidate Ranking — Partial

`CandidateRankingService` orders applications per job. Weights: interview 50%, CV/ATS 30%, application stage 20%. Latest completed recruitment interview score is used unless HR rejected it. ATS score for that job beats generic CV review. Positions persist on `job_applications`. Mock interview scores are ignored.

---

## 16. Candidate Comparison — Partial

HR Rankings page can compare 2–4 applicants on the same job: composite score, interview dimensions, skills, strengths/weaknesses, and ranking rationale (`hr/CandidateComparison.vue`). Cross-job comparison is not supported.

---

## 17. HR Dashboard — Partial

`DashboardController` loads company-scoped job, applicant, review-queue, interview-review, and subscription stats. A pipeline bar shows application counts by status. Recent activity lists new applications and completed interviews.

---

## 18. Candidate Dashboard — Partial

Live cards for processed CVs, ATS runs, completed recruitment interviews, mock interviews, applications, and profile score. Recent activity covers applications and assigned interviews.

---

## 19. Interview Result — Partial

Job seekers open mock-session feedback dialogs and a dedicated result page for completed recruitment interviews. HR has Interview Results (list + detail) with explainable scores. Results stay on the `interviews` row linked to the application.

---

## 20. Recruitment Reports — Partial

HR `reports` page: hiring funnel, days since applied by current status, interview volume (assigned/completed/pending review/this month), score buckets for interviews and ranking composites, and CSV export. Admin Analytics uses live platform counts. Reports require an HR Professional or Enterprise plan.

---

## 21. Notification System — Partial

Laravel database notifications plus mail. Events: application submitted (candidate + company HR), application status change (candidate), interview assigned (candidate), interview completed / HITL queue (HR), ranking refreshed (HR), interview reviewed (candidate). Header bell shows unread count and recent items.

No preference center, no digest, no SMS.

---

## 22. Security — Partial

**Present:** hashed passwords, CSRF, session auth, optional 2FA, email verification, login/2FA rate limiting, `role` middleware, company-scoped HR jobs, owner checks (jobs, applications, CVs, mock sessions, recruitment interviews, interview templates), Stripe webhook signatures, trusted proxies, HTTPS URLs in production.

**Missing:** finer-grained policies, audit logging, file malware scanning, hosting-provider secrets and backups (see [infrastructure.md](./infrastructure.md)).

---

## 23. Human-in-the-Loop — Partial

HR accepts, edits, or rejects AI interview scores with a required note and JSON audit log. Application pipeline status/notes remain a separate HITL path. There is no question-bank approval queue.

---

## Out-of-scope (but present) features

Documented so they are not mistaken for the 23 capabilities:

- **Billing:** Stripe Checkout, customer portal, cancel/resume, webhook sync, and enforced plan limits (`PlanLimitService`)
- **Portfolio & skill goals:** seeker-authored, not extracted from CVs
- **Admin user administration:** separate pages for job seekers vs HR/admins
