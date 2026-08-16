<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Services\JobApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class JobApplicationController extends Controller
{
    public function index(): Response
    {
        $applications = JobApplication::where('user_id', Auth::id())
            ->with('job.company')
            ->latest()
            ->get();

        $stats = [
            'total' => $applications->count(),
            'pending' => $applications->whereIn('status', ['pending', 'reviewing', 'shortlisted', 'interviewed'])->count(),
            'accepted' => $applications->where('status', 'accepted')->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
        ];

        return Inertia::render('job-seeker/JobApplications', [
            'applications' => $applications,
            'stats' => $stats,
        ]);
    }

    public function browse(Request $request): Response
    {
        $userApplicationIds = JobApplication::where('user_id', Auth::id())
            ->pluck('job_id')
            ->toArray();

        $query = Job::publiclyListed()->with('company');

        // Filter by search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%')
                    ->orWhere('location', 'like', '%'.$request->search.'%');
            });
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by remote
        if ($request->has('remote') && $request->remote) {
            $query->where('remote', $request->remote);
        }

        $jobs = $query->latest()->paginate(12);

        return Inertia::render('job-seeker/BrowseJobs', [
            'jobs' => $jobs,
            'appliedJobIds' => $userApplicationIds,
            'filters' => $request->only(['search', 'type', 'remote']),
        ]);
    }

    public function store(Request $request, JobApplicationService $applications)
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:job_postings,id',
            'cover_letter' => 'nullable|string|max:5000',
            'resume_path' => 'nullable|string|max:255',
        ]);

        $job = Job::findOrFail($validated['job_id']);
        $result = $applications->apply(Auth::user(), $job, $validated['cover_letter'] ?? null);

        if ($result['interview']) {
            return redirect()
                ->route('interviews.show', $result['interview'])
                ->with('success', 'Application submitted. Your interview is ready.');
        }

        return redirect()->route('browse-jobs')->with('success', 'Application submitted successfully.');
    }

    public function destroy(JobApplication $jobApplication)
    {
        if ($jobApplication->user_id !== Auth::id()) {
            abort(403);
        }

        $jobApplication->delete();

        return redirect()->route('job-applications')->with('success', 'Application withdrawn successfully.');
    }
}
