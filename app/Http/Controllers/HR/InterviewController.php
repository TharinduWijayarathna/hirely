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
            'interview_template_id' => 'nullable|exists:interview_templates,id',
        ]);

        if (! empty($validated['interview_template_id'])) {
            $template = InterviewTemplate::visibleTo($user)
                ->where('is_active', true)
                ->findOrFail($validated['interview_template_id']);
        } else {
            $template = $assignments->defaultTemplateForJob($user, $application->job);

            if (! $template) {
                return back()->withErrors([
                    'interview_template_id' => 'Please select an interview template.',
                ]);
            }
        }

        try {
            $assignments->assign($application, $template, $user);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors([
                'interview_template_id' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Interview assigned to candidate.');
    }
}
