<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    public function edit(): Response
    {
        $company = Auth::user()->company;

        return Inertia::render('hr/CompanySettings', [
            'company' => $company,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        if (! $company) {
            return redirect()->route('company-settings')->withErrors([
                'company' => 'Your account is not linked to a company. Ask an admin to assign one.',
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:companies,name,'.$company->id,
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $company->update($validated);

        return redirect()->route('company-settings')->with('success', 'Company profile updated.');
    }
}
