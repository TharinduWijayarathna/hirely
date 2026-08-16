<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use App\Services\JobApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PublicJobController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Job::publiclyListed()->with('company')->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%')
                    ->orWhereHas('company', fn ($company) => $company->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($request->filled('company')) {
            $query->whereHas('company', fn ($company) => $company->where('slug', $request->string('company')));
        }

        return Inertia::render('public/Jobs', [
            'jobs' => $query->paginate(12)->withQueryString(),
            'filters' => $request->only(['search', 'company']),
            'companies' => Company::query()
                ->whereHas('jobs', fn ($jobs) => $jobs->publiclyListed())
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    public function show(Job $job): Response
    {
        abort_unless($job->isPubliclyListed(), 404);

        $job->load('company');
        $user = Auth::user();
        $application = null;
        $interview = null;

        if ($user?->isJobSeeker()) {
            $application = $job->applications()->where('user_id', $user->id)->first();
            if ($application) {
                $interview = $application->interviews()->latest()->first();
            }
        }

        return Inertia::render('public/JobShow', [
            'job' => $job,
            'share_url' => $job->publicUrl(),
            'has_applied' => $application !== null,
            'interview_id' => $interview?->id,
            'can_apply' => $user?->isJobSeeker() === true && $application === null,
            'success' => session('success'),
        ]);
    }

    public function start(Job $job)
    {
        abort_unless($job->isPubliclyListed(), 404);

        return redirect()->route('jobs.show', $job);
    }

    public function apply(Request $request, Job $job, JobApplicationService $applications)
    {
        abort_unless($job->isPubliclyListed(), 404);

        $validated = $request->validate([
            'cover_letter' => 'nullable|string|max:5000',
        ]);

        $result = $applications->apply(Auth::user(), $job, $validated['cover_letter'] ?? null);

        if ($result['interview']) {
            return redirect()
                ->route('interviews.show', $result['interview'])
                ->with('success', 'Application submitted. Your interview is ready.');
        }

        return redirect()
            ->route('jobs.show', $job)
            ->with('success', 'Application submitted. The company will assign an interview when they are ready.');
    }
}
