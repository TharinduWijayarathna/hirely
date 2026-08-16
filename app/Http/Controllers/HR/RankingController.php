<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\CandidateRankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(Request $request, CandidateRankingService $ranking): Response
    {
        $user = Auth::user();
        $jobs = Job::visibleTo($user)
            ->orderBy('title')
            ->get(['id', 'title', 'status']);

        $job = null;
        if ($request->filled('job_id')) {
            $job = Job::visibleTo($user)->findOrFail($request->integer('job_id'));
        } elseif ($jobs->isNotEmpty()) {
            $job = Job::visibleTo($user)->find($jobs->first()->id);
        }

        return Inertia::render('hr/Rankings', [
            'jobs' => $jobs,
            'selected_job_id' => $job?->id,
            'rankings' => $job ? $ranking->rankJob($job)->values() : [],
            'weights' => CandidateRankingService::WEIGHTS,
        ]);
    }

    public function compare(Request $request, Job $job, CandidateRankingService $ranking): Response
    {
        if (! Auth::user()->canAccessJob($job)) {
            abort(403);
        }

        $validated = $request->validate([
            'applications' => 'required|array|min:2|max:4',
            'applications.*' => 'integer|exists:job_applications,id',
        ]);

        $comparison = $ranking->compare($job, $validated['applications']);

        return Inertia::render('hr/CandidateComparison', [
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
            ],
            'candidates' => $comparison['candidates'],
            'criteria' => $comparison['criteria'],
            'weights' => CandidateRankingService::WEIGHTS,
        ]);
    }
}
