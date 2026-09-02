<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\InterviewAssignmentService;
use App\Services\RecruitmentNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CandidateController extends Controller
{
    public function index(Request $request): Response
    {
        $jobs = Job::visibleTo(Auth::user())->pluck('id');

        $user = Auth::user();
        $assignments = app(InterviewAssignmentService::class);

        $applications = JobApplication::whereIn('job_id', $jobs)
            ->with(['user.latestProcessedCv', 'job.company', 'interviews'])
            ->latest()
            ->get()
            ->map(function (JobApplication $application) use ($assignments, $user) {
                $application->setAttribute(
                    'suggested_interview_template_id',
                    $assignments->defaultTemplateForJob($user, $application->job)?->id,
                );

                return $application;
            });

        $templates = \App\Models\InterviewTemplate::visibleTo($user)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'job_id', 'difficulty', 'mode']);

        return Inertia::render('hr/ReviewCandidates', [
            'applications' => $applications,
            'templates' => $templates,
        ]);
    }

    public function filter(Request $request): Response
    {
        $query = User::where('role', 'job_seeker')->with('latestProcessedCv');

        if ($request->filled('skills')) {
            $skill = $request->string('skills')->trim()->value();
            $query->whereHas('cvDocuments', function ($documents) use ($skill) {
                $documents->where('status', 'processed')
                    ->where('extraction', 'like', '%'.$skill.'%');
            });
        }

        if ($request->filled('experience') && $request->experience !== 'any') {
            $query->whereHas('cvDocuments', function ($documents) use ($request) {
                $documents->where('status', 'processed')
                    ->where('extraction->experience_level', $request->experience);
            });
        }

        $candidates = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('hr/FilterCandidates', [
            'candidates' => $candidates,
            'filters' => $request->only(['skills', 'experience']),
        ]);
    }

    public function updateApplication(Request $request, JobApplication $application)
    {
        $job = $application->job;
        if (! Auth::user()->canAccessJob($job)) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,reviewing,shortlisted,interviewed,accepted,rejected',
            'notes' => 'nullable|string',
        ]);

        $previous = $application->status;
        $application->update($validated);

        if ($previous !== $validated['status']) {
            app(RecruitmentNotifier::class)->applicationStatusChanged($application->fresh(['job', 'user']));
        }

        return redirect()->route('review-candidates')->with('success', 'Application updated successfully.');
    }
}
