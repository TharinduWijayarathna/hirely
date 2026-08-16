<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function index(PlanLimitService $limits): Response
    {
        $user = Auth::user();
        $jobs = Job::visibleTo($user)
            ->with('company')
            ->latest()
            ->get();

        $companies = $user->company_id
            ? Company::where('id', $user->company_id)->get()
            : collect();

        return Inertia::render('hr/PostJobs', [
            'jobs' => $jobs,
            'companies' => $companies,
            'quota' => $limits->quota($user, 'jobs'),
        ]);
    }

    public function store(Request $request, PlanLimitService $limits)
    {
        $user = Auth::user();

        if (! $user->company_id) {
            return redirect()->route('post-jobs')->withErrors([
                'company' => 'Your account must belong to an organization before you can post jobs.',
            ]);
        }

        if ($message = $limits->denyMessage($user, 'jobs')) {
            return redirect()->route('post-jobs')->withErrors(['plan' => $message]);
        }

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'type' => 'required|in:full_time,part_time,contract,freelance,internship',
            'remote' => 'required|in:on_site,remote,hybrid',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'salary_currency' => 'nullable|string|max:3',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'status' => 'required|in:draft,active,closed,expired',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $validated['user_id'] = $user->id;
        $validated['company_id'] = $user->company_id;

        Job::create($validated);

        return redirect()->route('post-jobs')->with('success', 'Job posted successfully.');
    }

    public function update(Request $request, Job $job)
    {
        if (! Auth::user()->canAccessJob($job)) {
            abort(403);
        }

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'type' => 'required|in:full_time,part_time,contract,freelance,internship',
            'remote' => 'required|in:on_site,remote,hybrid',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'salary_currency' => 'nullable|string|max:3',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'status' => 'required|in:draft,active,closed,expired',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $validated['company_id'] = Auth::user()->company_id ?: ($validated['company_id'] ?? $job->company_id);

        $job->update($validated);

        return redirect()->route('post-jobs')->with('success', 'Job updated successfully.');
    }

    public function destroy(Job $job)
    {
        if (! Auth::user()->canAccessJob($job)) {
            abort(403);
        }

        $job->delete();

        return redirect()->route('post-jobs')->with('success', 'Job deleted successfully.');
    }
}
