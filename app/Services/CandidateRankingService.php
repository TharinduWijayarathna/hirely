<?php

namespace App\Services;

use App\Models\AtsAnalysis;
use App\Models\Interview;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Support\Collection;

class CandidateRankingService
{
    public const WEIGHTS = [
        'interview' => 0.50,
        'cv' => 0.30,
        'application' => 0.20,
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rankJob(Job $job): Collection
    {
        $applications = JobApplication::query()
            ->where('job_id', $job->id)
            ->with([
                'user.latestProcessedCv',
                'cvDocument',
                'interviews' => fn ($query) => $query->where('status', 'completed')->latest('completed_at'),
            ])
            ->get();

        $atsByUser = AtsAnalysis::query()
            ->where('job_id', $job->id)
            ->whereIn('user_id', $applications->pluck('user_id'))
            ->latest()
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $ranked = $applications
            ->map(fn (JobApplication $application) => $this->scoreApplication(
                $application,
                $atsByUser->get($application->user_id),
            ))
            ->sort(function (array $left, array $right): int {
                if ($left['rejected'] !== $right['rejected']) {
                    return $left['rejected'] <=> $right['rejected'];
                }

                return $right['score'] <=> $left['score']
                    ?: ($right['signals']['interview']['score'] ?? -1) <=> ($left['signals']['interview']['score'] ?? -1)
                    ?: $left['applied_at'] <=> $right['applied_at'];
            })
            ->values();

        return $ranked->map(function (array $row, int $index) {
            $row['position'] = $index + 1;

            JobApplication::where('id', $row['application_id'])->update([
                'ranking_score' => $row['score'],
                'ranking_position' => $row['position'],
                'ranking_breakdown' => [
                    'score' => $row['score'],
                    'rationale' => $row['rationale'],
                    'signals' => $row['signals'],
                    'weights' => self::WEIGHTS,
                ],
                'ranked_at' => now(),
            ]);

            return $row;
        });
    }

    /**
     * @param  array<int, int>  $applicationIds
     * @return array{candidates: array<int, array<string, mixed>>, criteria: array<int, string>}
     */
    public function compare(Job $job, array $applicationIds): array
    {
        $applicationIds = array_values(array_unique(array_map('intval', $applicationIds)));
        $ranked = $this->rankJob($job)->keyBy('application_id');

        $missing = array_values(array_filter(
            $applicationIds,
            fn (int $id) => ! $ranked->has($id)
        ));

        if ($missing !== []) {
            abort(422, 'Applications must belong to this job.');
        }

        $candidates = [];
        $criteria = [];

        foreach ($applicationIds as $id) {
            $row = $ranked->get($id);
            $application = JobApplication::with(['user.latestProcessedCv', 'cvDocument', 'interviews'])->findOrFail($id);
            $interview = $this->usableInterview($application);
            $cv = $application->cvDocument ?? $application->user?->latestProcessedCv;
            $extraction = $cv?->extraction ?? [];
            $evaluation = $interview?->evaluation ?? [];

            foreach ($evaluation['dimensions'] ?? [] as $dimension) {
                if (! empty($dimension['name'])) {
                    $criteria[] = $dimension['name'];
                }
            }

            $candidates[] = [
                ...$row,
                'cover_letter' => $application->cover_letter,
                'notes' => $application->notes,
                'skills' => $cv?->skills() ?? [],
                'experience_level' => $extraction['experience_level'] ?? null,
                'experience_years' => $extraction['experience_years'] ?? null,
                'summary' => $extraction['summary'] ?? null,
                'education' => $extraction['education'] ?? [],
                'strengths' => $evaluation['strengths'] ?? [],
                'weaknesses' => $evaluation['weaknesses'] ?? [],
                'dimensions' => collect($evaluation['dimensions'] ?? [])
                    ->mapWithKeys(fn ($dimension) => [
                        (string) ($dimension['name'] ?? '') => [
                            'score' => isset($dimension['score']) ? (int) $dimension['score'] : null,
                            'evidence' => $dimension['evidence'] ?? null,
                            'comment' => $dimension['comment'] ?? null,
                        ],
                    ])
                    ->all(),
                'interview_id' => $interview?->id,
                'interview_review_status' => $interview?->review_status,
            ];
        }

        $candidates = collect($candidates)
            ->sortBy('position')
            ->values()
            ->all();

        return [
            'candidates' => $candidates,
            'criteria' => array_values(array_unique($criteria)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function scoreApplication(JobApplication $application, ?AtsAnalysis $ats): array
    {
        $interviewSignal = $this->interviewSignal($application);
        $cvSignal = $this->cvSignal($application, $ats);
        $applicationSignal = $this->applicationSignal($application);

        $signals = [
            'interview' => $interviewSignal,
            'cv' => $cvSignal,
            'application' => $applicationSignal,
        ];

        $score = 0.0;
        foreach (self::WEIGHTS as $key => $weight) {
            $score += ($signals[$key]['score'] ?? 0) * $weight;
        }
        $score = round($score, 2);

        $parts = [];
        if ($interviewSignal['available']) {
            $parts[] = 'interview '.$interviewSignal['score'].'/100';
        } elseif ($interviewSignal['status'] === 'rejected') {
            $parts[] = 'rejected interview score excluded';
        } else {
            $parts[] = 'no usable interview yet';
        }

        if ($cvSignal['available']) {
            $parts[] = ($cvSignal['source'] === 'ats' ? 'ATS ' : 'CV review ').$cvSignal['score'].'/100';
        } else {
            $parts[] = 'no CV score';
        }

        $parts[] = 'application stage '.$application->status;

        $interview = $this->usableInterview($application);

        return [
            'application_id' => $application->id,
            'candidate' => [
                'id' => $application->user?->id,
                'name' => $application->user?->name,
                'email' => $application->user?->email,
            ],
            'status' => $application->status,
            'rejected' => $application->status === 'rejected',
            'applied_at' => $application->applied_at?->toIso8601String(),
            'score' => $score,
            'rationale' => 'Weighted from '.implode(', ', $parts).'.',
            'signals' => $signals,
            'interview_id' => $interview?->id,
        ];
    }

    /**
     * @return array{score: float|null, available: bool, source: string, status: string|null}
     */
    protected function interviewSignal(JobApplication $application): array
    {
        $interview = $this->usableInterview($application);

        if (! $interview) {
            $rejected = $application->interviews
                ->first(fn (Interview $item) => $item->status === 'completed' && $item->review_status === Interview::REVIEW_REJECTED);

            return [
                'score' => null,
                'available' => false,
                'source' => 'interview',
                'status' => $rejected ? 'rejected' : 'missing',
            ];
        }

        return [
            'score' => round((float) $interview->score, 2),
            'available' => true,
            'source' => 'interview',
            'status' => $interview->review_status,
        ];
    }

    /**
     * @return array{score: float|null, available: bool, source: string, status: string|null}
     */
    protected function cvSignal(JobApplication $application, ?AtsAnalysis $ats): array
    {
        if ($ats && $ats->score !== null) {
            return [
                'score' => round((float) $ats->score, 2),
                'available' => true,
                'source' => 'ats',
                'status' => 'available',
            ];
        }

        $cv = $application->cvDocument ?? $application->user?->latestProcessedCv;
        if ($cv && $cv->review_score !== null) {
            return [
                'score' => round((float) $cv->review_score, 2),
                'available' => true,
                'source' => 'cv_review',
                'status' => 'available',
            ];
        }

        return [
            'score' => null,
            'available' => false,
            'source' => 'cv',
            'status' => 'missing',
        ];
    }

    /**
     * @return array{score: float, available: bool, source: string, status: string}
     */
    protected function applicationSignal(JobApplication $application): array
    {
        $statusScores = [
            'pending' => 30,
            'reviewing' => 45,
            'shortlisted' => 70,
            'interviewed' => 85,
            'accepted' => 100,
            'rejected' => 0,
        ];

        $score = $statusScores[$application->status] ?? 30;
        if (filled($application->cover_letter)) {
            $score += 8;
        }
        if ($application->cv_document_id || $application->user?->latestProcessedCv) {
            $score += 7;
        }

        return [
            'score' => (float) min(100, $score),
            'available' => true,
            'source' => 'application',
            'status' => $application->status,
        ];
    }

    protected function usableInterview(JobApplication $application): ?Interview
    {
        return $application->interviews->first(fn (Interview $interview) => $interview->isUsableForRanking());
    }
}
