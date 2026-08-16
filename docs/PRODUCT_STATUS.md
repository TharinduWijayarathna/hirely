# Product status

Living tracker for Hirely against the agreed product capabilities.

**Last reviewed:** 16 August 2026  
**Codebase snapshot:** Laravel 12 + Vue 3 / Inertia 2. Role-gated routes, company-scoped HR jobs, Stripe webhooks, interview templates with criterion weights, recruitment interviews (text follow-ups and voice), structured evaluation and HR review, CV upload/extraction, ATS scoring, ranking, comparison, live dashboards, recruitment reports with CSV export, HR company settings, in-app/email notifications, plan-limit enforcement, Hirely branding, and Docker/S3-ready production config are in place.

## Snapshot

| Area | Count |
| --- | ---: |
| Implemented | 1 |
| Partial | 21 |
| Placeholder | 1 |
| Not started | 0 |
| **Total capabilities** | **23** |

Overall product completeness versus the 23-item vision: **approximately 90%**.

The strongest areas today are authentication, jobs, CV extraction, explainable interviews (including text follow-ups, criterion weights, and recruitment voice), ranking, comparison, live dashboards, reports with CSV, notifications, billed plan limits, and Hirely-branded production-ready local/Compose runtime. Remaining product gaps are hosting-provider cutover (TLS, backups, mail/S3 credentials), virus scanning, SSO, SMS, and multi-role organizations.

## Capability tracker

| # | Capability | Status | Est. | Notes |
| ---: | --- | --- | ---: | --- |
| 1 | User Authentication & Role Management | Partial | 85% | Fortify auth, 2FA, and three roles. Public registration is always `job_seeker`. `role` middleware gates job-seeker, HR, and admin routes. |
| 2 | Organization Management | Partial | 82% | Admin CRUD for companies. HR jobs and templates are scoped to `company_id`. HR can edit their linked company profile; verification stays admin-only. |
| 3 | Job Vacancy Management | Implemented | 85% | HR CRUD for postings, now company-scoped. Job seekers browse and apply. |
| 4 | Candidate Management | Partial | 70% | HR reviews applications, sees extracted CV data, assigns interviews, and filters by skills/experience. |
| 5 | CV Upload & CV Analysis | Partial | 80% | PDF/DOCX upload, text parse, structured extraction, and CV quality review. Virus scanning not included. |
| 6 | AI-Based Interview Question Generation | Partial | 65% | Templates generate technical / behavioral / scenario / CV-based questions using job + parsed CV/portfolio context. |
| 7 | AI Interview Configuration | Partial | 88% | HR templates support question count, duration, difficulty, mode, mix percentages, evaluation criteria, and criterion weights. Weights are snapshotted onto assigned interviews and used as a weighted average of dimension scores. |
| 8 | Text-Based Interview | Partial | 88% | Job-seeker mock interviews and assigned recruitment interviews both work in text mode, including up to three follow-up probes. |
| 9 | Voice-Based Interview | Partial | 82% | Conversational voice works for mock practice and assigned recruitment interviews via browser speech APIs. Unused Google Cloud TTS packages were removed. No server-side audio storage. |
| 10 | AI Dynamic Interview | Partial | 80% | Voice conversation follow-ups plus text-mode probes (max 3) after each answered question. Does not yet adapt to remaining time budget. |
| 11 | AI Answer Analysis | Partial | 75% | Per-question scores, feedback, and evidence on recruitment completion. Mock completion uses the same evaluator mapped to legacy feedback. |
| 12 | AI Strength & Weakness Analysis | Partial | 80% | Structured `strengths[]` and `weaknesses[]` stored on `interviews.evaluation`. |
| 13 | AI Scoring System | Partial | 82% | Overall 0–100 plus criterion dimensions. Template weights recompute overall as a weighted average. Effective `score` is the AI score until HR edits it. |
| 14 | Explainable AI Evaluation | Partial | 75% | Dimension scores, evidence snippets, rationale, and confidence. Heuristic fallback when OpenAI is unset. |
| 15 | Automatic Candidate Ranking | Partial | 80% | Per-job weighted ranking from interview (50%), CV/ATS (30%), and application stage (20%). Rejected interviews are excluded. Positions persist on `job_applications`. |
| 16 | Candidate Comparison | Partial | 80% | Side-by-side view of 2–4 applicants on shared interview criteria, CV skills, and ranking rationale. |
| 17 | HR Dashboard | Partial | 80% | Live job, applicant, review-queue, subscription, and pipeline counts from company-scoped queries. |
| 18 | Candidate Dashboard | Partial | 80% | Live CV, ATS, application, recruitment/mock interview, and profile-score cards plus recent activity. |
| 19 | Interview Result | Partial | 85% | Candidate and HR result pages. Mock results remain on the practice page. |
| 20 | Recruitment Reports | Partial | 85% | HR Reports: funnel, days since applied by status, interview volume, interview and ranking score buckets, plus CSV export. Admin Analytics now uses live platform counts. |
| 21 | Notification System | Partial | 80% | Database + email notifications for applications, interview assigned/completed, HITL review, and ranking updates. Header bell lists unread items. |
| 22 | Security | Partial | 78% | Fortify, hashed passwords, 2FA, CSRF, login rate limits, role middleware, company-scoped HR jobs, owner checks, Stripe webhook signatures, trusted proxies, HTTPS in production. |
| 23 | Human-in-the-Loop | Partial | 75% | HR accepts, edits, or rejects AI interview scores with required notes and an audit log. |

## Sub-feature status (CV analysis and interview AI)

These are called out because they are listed as first-class product requirements.

### 5. CV Upload & CV Analysis

| Sub-feature | Status |
| --- | --- |
| CV file upload | Implemented (PDF/DOCX, 10MB, private disk) |
| Extract candidate information | Partial (name, email, phone, location, summary) |
| Extract education | Partial |
| Extract skills | Partial |
| Extract experience | Partial |
| Extract qualifications | Partial |
| Extract projects | Partial |
| Extract certifications | Partial |
| Identify relevant technologies | Partial |
| Identify relevant experience | Partial |

### 6. AI-Based Interview Question Generation

| Sub-feature | Status |
| --- | --- |
| Technical questions | Partial (job-aware when assigned from a template) |
| Skill-based questions | Partial (via CV/portfolio context when present) |
| Experience-based questions | Partial (via candidate context) |
| CV-based questions | Partial (uses portfolio/skills until CV parse exists) |
| Project-based questions | Partial (portfolio projects included in prompt context) |
| Scenario-based questions | Partial (own mix percentage on templates) |
| Problem-solving questions | Partial (covered by scenario mix) |
| Role-specific questions | Partial (job title and description are passed into generation) |
| Follow-up questions | Partial (text probes + voice conversation; max 3 in text mode) |

### 7. AI Interview Configuration

| Sub-feature | Status |
| --- | --- |
| Number of questions | Configurable on interview templates (1–20) |
| Question categories | Partial (`technical`, `behavioral`, `scenario`, `cv` mix) |
| Difficulty level | Implemented (`beginner`, `intermediate`, `advanced`) |
| Technical / behavioral / scenario / CV mix % | Implemented on templates (must total 100) |
| Evaluation criteria | Partial (stored on the template, snapshotted onto interviews, used as score dimensions) |
| Question weightings | Implemented on templates; snapshotted onto interviews; weighted average of criterion scores |
| Interview duration | Configurable target on templates; actual duration recorded on completion |

## Extra capabilities already in the codebase

These are implemented (or partial) but were not in the original 23-item list. Track them so they are not lost.

| Capability | Status | Notes |
| --- | --- | --- |
| Stripe subscriptions & payments | Partial | Checkout, customer portal, cancel/resume, webhook sync. Structured `limits` on plans are enforced for job posts, mock interviews, CV storage, ATS, and HR reports. |
| Job-seeker portfolio | Implemented | Manual CRUD for projects, technologies, dates, featured flag. |
| Skill expectations | Implemented | Self-reported skill goals with current/target level. |
| Browse & apply to jobs | Implemented | Search, type/remote filters, cover letter, withdraw. Resume path is a string, not a file upload. |
| Admin user / HR / job-seeker management | Implemented | CRUD with search. No role middleware protecting these routes. |
| Admin payments dashboard | Implemented | Revenue, monthly breakdown, subscription counts. |
| Feature tests | Partial | Pest covers auth, jobs, applications, interviews (follow-ups, weights, voice), AI fallbacks, ranking, reports/CSV, company settings, notifications, Stripe webhooks, plan limits, and Hirely branding. |
| Production runtime | Partial | Dockerfile + Compose (MySQL, Redis, queue, scheduler, Mailpit). CVs can use S3. Host TLS/backups/mail credentials are still at the provider. |

## Architectural gap that still remains

Mock interviews (`mock_interview_sessions`) remain a job-seeker practice product.

Recruitment interviews exist as `interview_templates` + `interviews` linked to `job_applications`, with structured evaluation, criterion weights, text follow-ups, recruitment voice, HR review, ranking/comparison, live dashboards, recruitment reports (including CSV), HR company settings, notifications, plan-limit enforcement, Hirely branding, and Docker/S3-ready runtime config. Remaining work is hosting-provider cutover.

## Next documentation updates

Update this file whenever a capability moves between **Not started**, **Placeholder**, **Partial**, and **Implemented**. Keep the estimate honest: UI shells without persistence should stay **Placeholder**.
