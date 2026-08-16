<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileScoreController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user()->loadCount(['portfolioProjects', 'skillExpectations']);
        $cv = $user->latestProcessedCv;

        $cvScore = $cv?->review_score;
        $portfolioScore = min(100, $user->portfolio_projects_count * 20);
        $skillsScore = min(100, $user->skill_expectations_count * 15);
        $parts = array_filter([$cvScore, $portfolioScore ?: null, $skillsScore ?: null], fn ($score) => $score !== null);
        $overall = $parts === [] ? null : (int) round(array_sum($parts) / count($parts));

        return Inertia::render('job-seeker/ProfileScore', [
            'cv' => $cv,
            'scores' => [
                'overall' => $overall,
                'cv' => $cvScore,
                'portfolio' => $user->portfolio_projects_count > 0 ? $portfolioScore : null,
                'skills' => $user->skill_expectations_count > 0 ? $skillsScore : null,
            ],
        ]);
    }
}
