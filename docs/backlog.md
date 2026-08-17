# Backlog

Work required to move Hirely from the current mock-interview + job-board app to the 23-capability product. Ordered by dependency, not by effort.

Update this list when items ship. Status of the product itself lives in [PRODUCT_STATUS.md](./PRODUCT_STATUS.md).

## P0 — Foundations (done 16 August 2026)

1. **Role authorization** — `EnsureUserHasRole` middleware on job-seeker, HR, and admin route groups.
2. **Duplicate `payments` route** — job seekers use `/payments`, HR `/subscriptions`, admin `/admin/payments`.
3. **Registration roles** — public sign-up is always `job_seeker`; `role` from the form is ignored. HR remains admin-invited.
4. **Recruitment interview domain** — `interview_templates` and `interviews` linked to applications.
5. **Stripe webhooks** — `POST /stripe/webhook` syncs subscriptions and payments.

## P1 — CV and candidate truth (done 16 August 2026)

6. **CV upload** — PDF/DOCX to the private local disk, 10MB cap.
7. **CV analysis pipeline** — send the original CV file to Gemini for identity, education, skills, experience, qualifications, projects, certifications, technologies, relevant experience.
8. **Persist extractions** — `cv_documents` shown to HR on Review Candidates.
9. **Filter Candidates** — skills and experience level query extracted CVs.
10. **CV Review / ATS Scoring** — live pages; applications attach the latest processed CV.

## P2 — Configurable, explainable interviews (done 16 August 2026)

11. **Interview configuration**  
    Question count, duration, difficulty, category mix, evaluation criteria snapshotted onto assigned interviews.

12. **Question generation that uses job + CV + portfolio**  
    Template mix generates technical / behavioral / scenario / CV questions with job + candidate context.

13. **Answer analysis, strengths/weaknesses, scoring, explanations**  
    Structured JSON: dimension scores, evidence snippets, strengths[], weaknesses[], overall score, rationale, confidence.

14. **Interview result page for HR and candidate**  
    Candidate results on `interviews.show` after completion; HR list/detail at `interview-results`. Mock results stay on the practice page.

15. **Human-in-the-loop**  
    Recruiter accepts, edits, or rejects AI scores; required note; `review_audit` log.

26. **Text-mode follow-ups** — Next on mock and recruitment text sessions can insert up to 3 AI/heuristic probes after the answered question. *(done 16 August 2026)*

27. **Question-weight UI** — Template criterion weights are edited in the UI, snapshotted onto assigned interviews, and used as a weighted average of dimension scores. *(done 16 August 2026)*

28. **Voice recruitment interviews** — Assigned interviews with `mode=voice` use the same browser speech flow as mock voice, then evaluate from conversation history. *(done 16 August 2026)*

29. **HR company settings** — HR can edit their linked company profile; verification remains admin-only. *(done 16 August 2026)*

30. **Reports CSV export** — Professional+ HR can download funnel, time-in-stage, volume, and score buckets. *(done 16 August 2026)*

## P3 — Hiring decisions

16. **Automatic ranking** per job from weighted CV + interview + application signals. *(done 16 August 2026)*

17. **Candidate comparison** (two or more applicants, shared criteria). *(done 16 August 2026)*

18. **Live HR and candidate dashboards** from real queries (replace hardcoded zeros). *(done 16 August 2026)*

19. **Recruitment reports** (funnel, time in stage, interview volume, score distributions). *(done 16 August 2026)*

20. **Notification system**  
    In-app + email for application status, interview scheduled/completed, ranking ready, HITL tasks. *(done 16 August 2026)*

## P4 — Hardening

22. **Enforce plan limits** that are already advertised on Stripe plans. *(done 16 August 2026)*

23. **Tests** for jobs, applications, interviews, AI failure fallbacks, and authorization. *(done 16 August 2026)*

24. **Production infrastructure**  
    Docker Compose (MySQL, Redis, queue, scheduler, Mailpit), S3-ready CV disk, HTTPS in production. *(done 16 August 2026)*

25. **Remove unused Google Cloud TTS**; brand the product as **Hirely**. *(done 16 August 2026)*

## Suggested implementation sequence

```text
AuthZ + interview domain          ← done
        → CV upload/parse         ← done
        → richer scoring / XAI / HITL  ← done
        → ranking + comparison        ← done
        → dashboards, reports         ← done
        → notifications               ← done
        → plan limits                 ← done
        → jobs/applications/AI/auth tests ← done
        → Hirely branding + TTS removal   ← done
        → Docker / S3 / queue / mail      ← done
        → follow-ups, weights, voice recruitment, HR company, CSV ← done
```

Do not treat mock-interview score as a hiring score. Ranking uses recruitment interview scores, CV/ATS, and application stage only.
