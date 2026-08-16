# Product status

Living tracker for Hirely against the agreed product capabilities.

**Last reviewed:** 16 August 2026  
**Codebase snapshot:** Laravel 12 + Vue 3 / Inertia 2 application with job-seeker mock interviews, HR job posting, admin company/user management, and Stripe billing. Most AI recruitment capabilities beyond mock interviews are not built yet.

## Snapshot

| Area | Count |
| --- | ---: |
| Implemented | 3 |
| Partial | 11 |
| Placeholder | 6 |
| Not started | 3 |
| **Total capabilities** | **23** |

Overall product completeness versus the 23-item vision: **approximately 30%**.

The strongest areas today are authentication, job vacancies, and job-seeker mock interviews (text and voice). The largest gaps are CV analysis, configurable recruitment interviews, ranking/comparison, reports, notifications, and human-in-the-loop AI review.

## Capability tracker

| # | Capability | Status | Est. | Notes |
| ---: | --- | --- | ---: | --- |
| 1 | User Authentication & Role Management | Partial | 70% | Fortify auth, 2FA, email verification, and three roles work. Registration always defaults to `job_seeker`. Routes are not role-gated on the server. |
| 2 | Organization Management | Partial | 60% | Admin CRUD for companies; HR users can be assigned a company. No tenant isolation or HR self-service org settings. |
| 3 | Job Vacancy Management | Implemented | 80% | HR can create, update, close, and expire postings. Job seekers can browse and apply. Not company-scoped. |
| 4 | Candidate Management | Partial | 45% | HR can review applications and change pipeline status. Filter-by-skills/experience is stubbed. |
| 5 | CV Upload & CV Analysis | Placeholder | 5% | CV Review and ATS Scoring pages are static UI. No file parse, no extraction of education/skills/experience/etc. |
| 6 | AI-Based Interview Question Generation | Partial | 40% | OpenAI generates technical / behavioral / mixed questions at 3 difficulty levels. Not CV-, job-, project-, or skill-aware. |
| 7 | AI Interview Configuration | Partial | 25% | User picks mode, type, and difficulty. Question count is hardcoded to 5. No mix percentages, weights, criteria, or duration target. |
| 8 | Text-Based Interview | Partial | 75% | Working typed Q&A for **job-seeker mock interviews**. Not tied to a job application or HR-run interview. |
| 9 | Voice-Based Interview | Partial | 70% | Working conversational voice mock interview via browser speech APIs. Google Cloud TTS is installed but unused. |
| 10 | AI Dynamic Interview | Partial | 50% | Voice mode asks follow-ups from conversation history. Text mode is a fixed question list. |
| 11 | AI Answer Analysis | Partial | 50% | Per-question feedback on mock-interview completion. No rubric, no recruitment interview object. |
| 12 | AI Strength & Weakness Analysis | Placeholder | 10% | Only unstructured `overall_feedback` text. No structured strengths/weaknesses model. |
| 13 | AI Scoring System | Partial | 40% | Single 0–100 score from the model. No weighted criteria or multi-dimension scores. |
| 14 | Explainable AI Evaluation | Placeholder | 15% | Free-text comments per question. No criterion scores, evidence spans, or confidence. |
| 15 | Automatic Candidate Ranking | Not started | 0% | No ranking model, score aggregation, or ordered shortlist. |
| 16 | Candidate Comparison | Not started | 0% | No side-by-side candidate view. |
| 17 | HR Dashboard | Placeholder | 25% | Role-specific dashboard shell with hardcoded zeros. Job posting and candidate review pages work separately. |
| 18 | Candidate Dashboard | Placeholder | 25% | Job-seeker dashboard shell with hardcoded zeros. Mock interview, portfolio, and applications work on their own pages. |
| 19 | Interview Result | Partial | 50% | Job seekers can open mock-session score and feedback. HR has no interview-result view. |
| 20 | Recruitment Reports | Placeholder | 10% | Admin Analytics is a static shell. Admin Payments has real revenue/subscription stats (billing, not recruitment). |
| 21 | Notification System | Placeholder | 5% | Header bell icon only. Auth emails exist (reset/verify). No application or interview notifications. |
| 22 | Security | Partial | 55% | Fortify, hashed passwords, 2FA, CSRF, login rate limits. Missing role middleware, policies, and Stripe webhooks. |
| 23 | Human-in-the-Loop | Partial | 20% | HR can manually set application status and notes. No AI-score override, question approval, or review workflow. |

## Sub-feature status (CV analysis and interview AI)

These are called out because they are listed as first-class product requirements.

### 5. CV Upload & CV Analysis

| Sub-feature | Status |
| --- | --- |
| CV file upload | Not started (UI only) |
| Extract candidate information | Not started |
| Extract education | Not started |
| Extract skills | Not started |
| Extract experience | Not started |
| Extract qualifications | Not started |
| Extract projects | Not started (manual portfolio CRUD exists separately) |
| Extract certifications | Not started |
| Identify relevant technologies | Not started |
| Identify relevant experience | Not started |

### 6. AI-Based Interview Question Generation

| Sub-feature | Status |
| --- | --- |
| Technical questions | Partial (generic, not job/CV specific) |
| Skill-based questions | Not started |
| Experience-based questions | Not started |
| CV-based questions | Not started |
| Project-based questions | Not started |
| Scenario-based questions | Not started as a category (some mixed/behavioral overlap) |
| Problem-solving questions | Not started as a category |
| Role-specific questions | Not started |
| Follow-up questions | Partial (voice conversation only) |

### 7. AI Interview Configuration

| Sub-feature | Status |
| --- | --- |
| Number of questions | Hardcoded to 5 |
| Question categories | Partial (`technical`, `behavioral`, `mixed`) |
| Difficulty level | Implemented (`beginner`, `intermediate`, `advanced`) |
| Technical / behavioral / scenario / CV mix % | Not started |
| Evaluation criteria | Not started |
| Question weightings | Not started |
| Interview duration | Recorded after the fact; not configurable |

## Extra capabilities already in the codebase

These are implemented (or partial) but were not in the original 23-item list. Track them so they are not lost.

| Capability | Status | Notes |
| --- | --- | --- |
| Stripe subscriptions & payments | Partial | Checkout, customer portal, cancel/resume. No webhook handler. Plan limits are not enforced. |
| Job-seeker portfolio | Implemented | Manual CRUD for projects, technologies, dates, featured flag. |
| Skill expectations | Implemented | Self-reported skill goals with current/target level. |
| Browse & apply to jobs | Implemented | Search, type/remote filters, cover letter, withdraw. Resume path is a string, not a file upload. |
| Admin user / HR / job-seeker management | Implemented | CRUD with search. No role middleware protecting these routes. |
| Admin payments dashboard | Implemented | Revenue, monthly breakdown, subscription counts. |
| Profile, password, appearance settings | Implemented | Inertia settings pages. |

## Architectural gap that affects many items

Interviews today are **job-seeker mock interviews** (`mock_interview_sessions`). They are not linked to a job posting, application, or HR reviewer.

Until a real **recruitment interview** entity exists, capabilities 6–16, 19, and 23 can only be partially delivered. Mock-interview work is still valuable as a candidate-practice product, but it is not the hiring pipeline.

## Next documentation updates

Update this file whenever a capability moves between **Not started**, **Placeholder**, **Partial**, and **Implemented**. Keep the estimate honest: UI shells without persistence should stay **Placeholder**.
