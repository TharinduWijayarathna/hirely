<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Services\CandidateRankingService;
use App\Services\InterviewEvaluationService;
use App\Services\RecruitmentNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InterviewResultsController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $interviews = Interview::query()
            ->where('status', 'completed')
            ->whereHas('job', fn ($query) => $query->visibleTo($user))
            ->with(['job', 'candidate', 'template', 'reviewer'])
            ->latest('completed_at')
            ->get()
            ->map(fn (Interview $interview) => $interview->toResultPayload(true));

        return Inertia::render('hr/InterviewResults', [
            'interviews' => $interviews,
        ]);
    }

    public function show(Interview $interview): Response
    {
        $this->authorizeHr($interview);

        $interview->load(['job', 'candidate', 'template', 'reviewer']);

        return Inertia::render('hr/InterviewResult', [
            'interview' => $interview->toResultPayload(true),
        ]);
    }

    public function review(
        Request $request,
        Interview $interview,
        InterviewEvaluationService $evaluationService,
        RecruitmentNotifier $notifier,
        CandidateRankingService $ranking,
    ) {
        $this->authorizeHr($interview);

        if ($interview->status !== 'completed') {
            abort(422, 'Only completed interviews can be reviewed.');
        }

        $validated = $request->validate([
            'action' => 'required|in:accepted,edited,rejected',
            'human_notes' => 'required|string|min:10|max:2000',
            'human_score' => 'required_if:action,edited|nullable|numeric|min:0|max:100',
        ]);

        $evaluationService->applyReview(
            $interview,
            Auth::user(),
            $validated['action'],
            $validated['human_notes'],
            isset($validated['human_score']) ? (float) $validated['human_score'] : null,
        );

        $interview = $interview->fresh(['job', 'candidate']);
        $notifier->interviewReviewed($interview);

        if ($interview->job) {
            $ranking->rankJob($interview->job);
            $notifier->rankingReady($interview->job);
        }

        return redirect()
            ->route('interview-results.show', $interview)
            ->with('success', 'Interview review saved.');
    }

    protected function authorizeHr(Interview $interview): void
    {
        if (! Auth::user()->canAccessInterview($interview)) {
            abort(403);
        }
    }
}
