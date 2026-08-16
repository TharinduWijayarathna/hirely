<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;

class InterviewAssignmentService
{
    public function __construct(protected AIService $ai) {}

    public function assign(
        JobApplication $application,
        InterviewTemplate $template,
        ?User $assignedBy = null,
    ): Interview {
        $application->loadMissing(['job', 'user']);

        $existing = Interview::where('job_application_id', $application->id)->first();
        if ($existing) {
            return $existing;
        }

        $criteria = array_values(array_filter(
            $template->evaluation_criteria ?? [],
            fn ($item) => is_string($item) && trim($item) !== ''
        ));

        $interview = Interview::create([
            'interview_template_id' => $template->id,
            'job_application_id' => $application->id,
            'job_id' => $application->job_id,
            'candidate_id' => $application->user_id,
            'assigned_by' => $assignedBy?->id ?? $application->job?->user_id,
            'difficulty' => $template->difficulty,
            'mode' => $template->mode,
            'status' => 'pending',
            'questions' => $this->ai->generateConfiguredQuestions(
                $template->difficulty,
                $template->categoryCounts(),
                $application->job?->title,
                $application->job?->description,
                $application->user?->candidateContext() ?? '',
                $template->evaluation_criteria ?? [],
            ),
            'criteria' => $criteria === [] ? Interview::DEFAULT_CRITERIA : $criteria,
            'question_weights' => $template->question_weights,
        ]);

        app(RecruitmentNotifier::class)->interviewAssigned($interview);

        return $interview;
    }

    public function templateForJob(Job $job): ?InterviewTemplate
    {
        $query = InterviewTemplate::query()
            ->where('is_active', true)
            ->where('company_id', $job->company_id);

        $forJob = (clone $query)->where('job_id', $job->id)->latest()->first();
        if ($forJob) {
            return $forJob;
        }

        return $query->whereNull('job_id')->latest()->first();
    }
}
