<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\InterviewTemplate;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InterviewTemplateController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $templates = InterviewTemplate::visibleTo($user)
            ->with('job')
            ->latest()
            ->get();

        $jobs = Job::visibleTo($user)->orderBy('title')->get(['id', 'title']);

        return Inertia::render('hr/InterviewTemplates', [
            'templates' => $templates,
            'jobs' => $jobs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $user = Auth::user();

        $validated['user_id'] = $user->id;
        $validated['company_id'] = $user->company_id;
        $validated['job_id'] = $this->resolveJobId($validated['job_id'] ?? null);

        InterviewTemplate::create($validated);

        return redirect()->route('interview-templates')->with('success', 'Interview template created.');
    }

    public function update(Request $request, InterviewTemplate $interviewTemplate)
    {
        $this->authorizeTemplate($interviewTemplate);

        $validated = $this->validated($request);
        $validated['job_id'] = $this->resolveJobId($validated['job_id'] ?? null);

        $interviewTemplate->update($validated);

        return redirect()->route('interview-templates')->with('success', 'Interview template updated.');
    }

    public function destroy(InterviewTemplate $interviewTemplate)
    {
        $this->authorizeTemplate($interviewTemplate);

        $interviewTemplate->delete();

        return redirect()->route('interview-templates')->with('success', 'Interview template deleted.');
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'job_id' => 'nullable|exists:job_postings,id',
            'question_count' => 'required|integer|min:1|max:20',
            'duration_minutes' => 'nullable|integer|min:5|max:180',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'mode' => 'required|in:text,voice',
            'technical_percentage' => 'required|integer|min:0|max:100',
            'behavioral_percentage' => 'required|integer|min:0|max:100',
            'scenario_percentage' => 'required|integer|min:0|max:100',
            'cv_percentage' => 'required|integer|min:0|max:100',
            'evaluation_criteria' => 'nullable|array',
            'evaluation_criteria.*' => 'string|max:255',
            'question_weights' => 'nullable|array',
            'question_weights.*' => 'integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        $request->validate([
            'technical_percentage' => [
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $total = (int) $request->input('technical_percentage')
                        + (int) $request->input('behavioral_percentage')
                        + (int) $request->input('scenario_percentage')
                        + (int) $request->input('cv_percentage');

                    if ($total !== 100) {
                        $fail('Question mix percentages must add up to 100.');
                    }
                },
            ],
        ]);

        $criteria = array_values(array_filter(
            $validated['evaluation_criteria'] ?? [],
            fn ($item) => is_string($item) && trim($item) !== ''
        ));
        $validated['evaluation_criteria'] = $criteria;

        $weights = $validated['question_weights'] ?? [];
        if ($criteria !== []) {
            $equal = (int) max(1, intdiv(100, count($criteria)));
            $normalized = [];
            foreach ($criteria as $name) {
                $normalized[$name] = max(1, (int) ($weights[$name] ?? $equal));
            }
            $validated['question_weights'] = $normalized;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    protected function resolveJobId(mixed $jobId): ?int
    {
        if (! $jobId) {
            return null;
        }

        $job = Job::visibleTo(Auth::user())->find($jobId);
        if (! $job) {
            abort(403);
        }

        return $job->id;
    }

    protected function authorizeTemplate(InterviewTemplate $template): void
    {
        $user = Auth::user();

        if ($user->company_id && (int) $template->company_id === (int) $user->company_id) {
            return;
        }

        if ((int) $template->user_id === (int) $user->id) {
            return;
        }

        abort(403);
    }
}
