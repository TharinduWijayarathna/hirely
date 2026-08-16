<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\PlanLimitService;
use App\Services\RecruitmentReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __invoke(Request $request, RecruitmentReportService $reports, PlanLimitService $limits): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($message = $limits->denyMessage($user, 'reports')) {
            return redirect()->route('subscriptions')->withErrors(['plan' => $message]);
        }

        return Inertia::render('hr/Reports', $reports->forHr(
            $user,
            $request->filled('job_id') ? $request->integer('job_id') : null,
        ));
    }

    public function export(Request $request, RecruitmentReportService $reports, PlanLimitService $limits): StreamedResponse|RedirectResponse
    {
        $user = Auth::user();

        if ($message = $limits->denyMessage($user, 'reports')) {
            return redirect()->route('subscriptions')->withErrors(['plan' => $message]);
        }

        $payload = $reports->forHr(
            $user,
            $request->filled('job_id') ? $request->integer('job_id') : null,
        );

        $filename = 'hirely-reports-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($payload) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['section', 'label', 'value']);

            foreach ($payload['funnel'] ?? [] as $row) {
                fputcsv($handle, ['funnel', $row['status'], $row['count']]);
            }

            foreach ($payload['time_in_stage'] ?? [] as $row) {
                fputcsv($handle, ['time_in_stage_days', $row['status'], $row['avg_days'] ?? '']);
            }

            foreach ($payload['interview_volume'] ?? [] as $key => $value) {
                fputcsv($handle, ['interview_volume', $key, is_scalar($value) ? $value : json_encode($value)]);
            }

            foreach ($payload['score_distribution']['interview'] ?? [] as $row) {
                fputcsv($handle, ['interview_score', $row['label'], $row['count']]);
            }

            foreach ($payload['score_distribution']['ranking'] ?? [] as $row) {
                fputcsv($handle, ['ranking_score', $row['label'], $row['count']]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
