<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class PublicOrganizationController extends Controller
{
    use PasswordValidationRules;

    public function index(): Response
    {
        $organizations = Company::query()
            ->withCount(['jobs as open_jobs_count' => fn ($jobs) => $jobs->publiclyListed()])
            ->orderByDesc('open_jobs_count')
            ->orderBy('name')
            ->get();

        return Inertia::render('public/Organizations', [
            'organizations' => $organizations,
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    }

    public function show(Company $company): Response
    {
        $jobs = $company->jobs()
            ->publiclyListed()
            ->latest()
            ->get(['id', 'company_id', 'title', 'slug', 'location', 'type', 'remote', 'description']);

        return Inertia::render('public/OrganizationShow', [
            'organization' => $company->only(['id', 'name', 'slug', 'description', 'location', 'industry', 'website', 'size']),
            'jobs' => $jobs,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('public/OrganizationRegister');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_name' => 'required|string|max:255|unique:companies,name',
            'organization_location' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ]);

        $user = DB::transaction(function () use ($validated) {
            $company = Company::create([
                'name' => $validated['organization_name'],
                'location' => $validated['organization_location'] ?? null,
                'industry' => $validated['industry'] ?? null,
                'is_verified' => false,
            ]);

            return User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => User::ROLE_HR_PROFESSIONAL,
                'company_id' => $company->id,
            ]);
        });

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
