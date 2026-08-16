<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\JobApplication;
use App\Services\AIService;
use App\Services\RecruitmentNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InterviewController extends Controller
{
    public function store(Request $request, JobApplication $application, AIService $aiService)
    {
        $user = Auth::user();
        $application->load('job', 'user.latestProcessedCv');

        if (! $user->canAccessJob($application->job)) {
            abort(403);
        }

        $validated = $request->validate([
            'interview_template_id' => 'required|exists:interview_templates,id',
        ]);

        $template = InterviewTemplate::visibleTo($user)
            ->where('is_active', true)
            ->findOrFail($validated['interview_template_id']);

        $questions = $aiService->generateConfiguredQuestions(
            $template->difficulty,
            $template->categoryCounts(),
            $application->job->title,
            $application->job->description,
            $application->user->candidateContext(),
            $template->evaluation_criteria ?? [],
        );

        $criteria = array_values(array_filter(
            $template->evaluation_criteria ?? [],
            fn ($item) => is_string($item) && trim($item) !== ''
        ));

        $interview = Interview::create([
            'interview_template_id' => $template->id,
            'job_application_id' => $application->id,
            'job_id' => $application->job_id,
            'candidate_id' => $application->user_id,
            'assigned_by' => $user->id,
            'difficulty' => $template->difficulty,
            'mode' => $template->mode,
            'status' => 'pending',
            'questions' => $questions,
            'criteria' => $criteria === [] ? Interview::DEFAULT_CRITERIA : $criteria,
            'question_weights' => $template->question_weights,
        ]);

        app(RecruitmentNotifier::class)->interviewAssigned($interview);

        return redirect()->route('review-candidates')->with('success', 'Interview assigned to candidate.');
    }
}
