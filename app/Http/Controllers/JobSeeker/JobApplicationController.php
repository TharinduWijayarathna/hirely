<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Services\RecruitmentNotifier;
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

        $query = Job::where('status', 'active')
            ->with('company')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:job_postings,id',
            'cover_letter' => 'nullable|string|max:5000',
            'resume_path' => 'nullable|string|max:255',
        ]);

        // Check if already applied
        $existingApplication = JobApplication::where('user_id', Auth::id())
            ->where('job_id', $validated['job_id'])
            ->first();

        if ($existingApplication) {
            return redirect()->back()->withErrors(['job_id' => 'You have already applied for this job.']);
        }

        $validated['user_id'] = Auth::id();
        $validated['applied_at'] = now();
        $validated['status'] = 'pending';
        $validated['cv_document_id'] = Auth::user()->latestProcessedCv?->id;
        $validated['resume_path'] = Auth::user()->latestProcessedCv?->path;

        $application = JobApplication::create($validated);

        app(RecruitmentNotifier::class)->applicationSubmitted($application->load(['job', 'user']));

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
