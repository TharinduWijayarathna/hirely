<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\InterviewTemplate;
use App\Models\JobApplication;
use App\Services\InterviewAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InterviewController extends Controller
{
    public function store(Request $request, JobApplication $application, InterviewAssignmentService $assignments)
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

        $assignments->assign($application, $template, $user);

        return redirect()->route('review-candidates')->with('success', 'Interview assigned to candidate.');
    }
}
