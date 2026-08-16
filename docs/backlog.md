# Backlog

Work required to move Hirely from the current mock-interview + job-board app to the 23-capability product. Ordered by dependency, not by effort.

Update this list when items ship. Status of the product itself lives in [PRODUCT_STATUS.md](./PRODUCT_STATUS.md).

## P0 — Foundations (unblock everything else)

1. **Role authorization**  
   Add middleware or policies so job-seeker, HR, and admin routes cannot be called cross-role. Scope HR queries to `company_id`.

2. **Fix the duplicate `payments` route**  
   Job-seeker/HR and admin payment indexes currently share `GET /payments` and the `payments` name.

3. **Registration roles (or invite-only HR)**  
   Either collect role at sign-up with admin approval for HR, or keep public sign-up as job seeker and invite HR.

4. **Recruitment interview domain**  
   Introduce templates and interviews linked to `job_applications`, separate from `mock_interview_sessions`. Mock interviews can remain as practice.

5. **Stripe webhooks**  
   Persist subscription/payment state from Stripe instead of only Checkout success redirects.

## P1 — CV and candidate truth

6. **CV upload to storage** with validation (PDF/DOCX, size, virus scanning later).

7. **CV analysis pipeline** using the existing OpenAI integration (or a document parser + LLM):  
   identity, education, skills, experience, qualifications, projects, certifications, relevant technologies, relevant experience.

8. **Persist extractions** on a candidate profile and show them to HR.

9. **Wire Filter Candidates** to extracted skills and experience (the UI already collects those query params).

10. **Replace CV Review / ATS Scoring placeholders** with real results.

## P2 — Configurable, explainable interviews

11. **Interview configuration**  
    Question count, duration, difficulty, category mix (% technical / behavioral / scenario / CV), evaluation criteria, weights.

12. **Question generation that uses job + CV + portfolio**  
    Cover the missing types: skill, experience, CV, project, scenario, problem-solving, role-specific.

13. **Dynamic interviews in both modes**  
    Follow-ups and time-aware conclusion for text as well as voice.

14. **Answer analysis, strengths/weaknesses, scoring, explanations**  
    Structured JSON: dimension scores, evidence snippets, strengths[], weaknesses[], overall score, human-readable rationale.

15. **Interview result page for HR and candidate**  
    Attach results to the application; keep mock results on the seeker practice page.

## P3 — Hiring decisions

16. **Human-in-the-loop**  
    Recruiter accepts, edits, or rejects AI scores; required note; audit log.

17. **Automatic ranking** per job from weighted CV + interview + application signals.

18. **Candidate comparison** (two or more applicants, shared criteria).

19. **Live HR and candidate dashboards** from real queries (replace hardcoded zeros).

20. **Recruitment reports** (funnel, time in stage, interview volume, score distributions).

21. **Notification system**  
    In-app + email for application status, interview scheduled/completed, ranking ready, HITL tasks.

## P4 — Hardening

22. **Enforce plan limits** that are already advertised on Stripe plans.

23. **Tests** for jobs, applications, interviews, AI failure fallbacks, and authorization.

24. **Production infrastructure**  
    Managed database, queue workers, object storage, mail, secrets, deploy pipeline (see [infrastructure.md](./infrastructure.md)).

25. **Remove or integrate unused Google Cloud TTS**; align `APP_NAME` / TalentTune / Hirely branding.

## Suggested implementation sequence

```text
AuthZ + interview domain
        → CV upload/parse
        → job-aware question generation + config
        → scoring / XAI / HITL
        → ranking + comparison
        → dashboards, reports, notifications
```

Do not build ranking or comparison before persisted evaluations exist. Do not treat mock-interview score as a hiring score.
