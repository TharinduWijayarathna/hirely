<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\CvDocument;
use App\Services\CvAnalysisService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CvController extends Controller
{
    public function index(PlanLimitService $limits): Response
    {
        $user = Auth::user();
        $documents = CvDocument::where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('job-seeker/CVReview', [
            'documents' => $documents,
            'quota' => $limits->quota($user, 'cv_documents'),
        ]);
    }

    public function store(Request $request, CvAnalysisService $analysis, PlanLimitService $limits)
    {
        if ($message = $limits->denyMessage($request->user(), 'cv_documents')) {
            return redirect()->route('cv-review')->withErrors(['plan' => $message]);
        }

        $request->validate([
            'cv' => ['required', 'file', 'max:10240', 'mimes:pdf,docx'],
        ]);

        $document = $analysis->storeAndAnalyze($request->user(), $request->file('cv'));

        if ($document->status === 'failed') {
            return redirect()->route('cv-review')->withErrors([
                'cv' => $document->error_message ?: 'The CV could not be processed.',
            ]);
        }

        return redirect()->route('cv-review')->with('success', 'CV uploaded and analyzed.');
    }

    public function destroy(CvDocument $cvDocument, CvAnalysisService $analysis)
    {
        if ((int) $cvDocument->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $analysis->delete($cvDocument);

        return redirect()->route('cv-review')->with('success', 'CV removed.');
    }
}
