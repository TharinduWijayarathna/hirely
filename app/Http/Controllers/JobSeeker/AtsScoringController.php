<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\AtsAnalysis;
use App\Models\Job;
use App\Services\CvAnalysisService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AtsScoringController extends Controller
{
    public function index(PlanLimitService $limits): Response
    {
        $user = Auth::user();
        $cv = $user->latestProcessedCv;
        $analyses = AtsAnalysis::where('user_id', $user->id)
            ->with('job')
            ->latest()
            ->take(10)
            ->get();

        $jobs = Job::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderBy('title')
            ->limit(50)
            ->get(['id', 'title']);

        return Inertia::render('job-seeker/ATSScoring', [
            'cv' => $cv,
            'jobs' => $jobs,
            'analyses' => $analyses,
            'quota' => $limits->quota($user, 'ats'),
        ]);
    }

    public function store(Request $request, CvAnalysisService $analysis, PlanLimitService $limits)
    {
        if ($message = $limits->denyMessage($request->user(), 'ats')) {
            return redirect()->route('ats-scoring')->withErrors(['plan' => $message]);
        }

        $validated = $request->validate([
            'job_id' => 'nullable|exists:job_postings,id',
            'job_description' => 'required_without:job_id|nullable|string|min:40|max:15000',
        ]);

        try {
            $analysis->scoreAgainstJob(
                $request->user(),
                $validated['job_description'] ?? '',
                $validated['job_id'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('ats-scoring')->withErrors(['cv' => $e->getMessage()]);
        }

        return redirect()->route('ats-scoring')->with('success', 'ATS compatibility scored.');
    }
}
