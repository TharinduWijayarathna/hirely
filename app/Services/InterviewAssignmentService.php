<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Collection;

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
            if ($existing->interview_template_id === $template->id) {
                return $existing;
            }

            if ($existing->status !== 'pending') {
                throw new \InvalidArgumentException('Only pending interviews can be reassigned to a different template.');
            }

            return $this->reassign($existing, $application, $template, $assignedBy);
        }

        $interview = Interview::create($this->interviewAttributes($application, $template, $assignedBy));

        app(RecruitmentNotifier::class)->interviewAssigned($interview);

        return $interview;
    }

    /**
     * @return array<string, mixed>
     */
    protected function interviewAttributes(
        JobApplication $application,
        InterviewTemplate $template,
        ?User $assignedBy = null,
    ): array {
        $criteria = array_values(array_filter(
            $template->evaluation_criteria ?? [],
            fn ($item) => is_string($item) && trim($item) !== ''
        ));

        $questionCount = max(10, (int) $template->question_count);

        return [
            'interview_template_id' => $template->id,
            'job_application_id' => $application->id,
            'job_id' => $application->job_id,
            'candidate_id' => $application->user_id,
            'assigned_by' => $assignedBy?->id ?? $application->job?->user_id,
            'difficulty' => $template->difficulty,
            'mode' => 'voice',
            'status' => 'pending',
            'questions' => $this->ai->generateConfiguredQuestions(
                $template->difficulty,
                $template->categoryCounts($questionCount),
                $application->job?->title,
                $application->job?->description,
                $application->user?->candidateContext() ?? '',
                $template->evaluation_criteria ?? [],
            ),
            'criteria' => $criteria === [] ? Interview::DEFAULT_CRITERIA : $criteria,
            'question_weights' => $template->question_weights,
        ];
    }

    protected function reassign(
        Interview $interview,
        JobApplication $application,
        InterviewTemplate $template,
        ?User $assignedBy = null,
    ): Interview {
        $interview->update($this->interviewAttributes($application, $template, $assignedBy));

        return $interview->fresh();
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

    /**
     * @return Collection<int, InterviewTemplate>
     */
    public function templatesForJob(User $user, Job $job): Collection
    {
        return InterviewTemplate::visibleTo($user)
            ->where('is_active', true)
            ->where(function ($query) use ($job) {
                $query->whereNull('job_id')->orWhere('job_id', $job->id);
            })
            ->orderBy('name')
            ->get();
    }

    public function defaultTemplateForJob(User $user, Job $job): ?InterviewTemplate
    {
        $templates = $this->templatesForJob($user, $job);

        $jobSpecific = $templates->where('job_id', $job->id);
        if ($jobSpecific->count() === 1) {
            return $jobSpecific->first();
        }

        if ($templates->count() === 1) {
            return $templates->first();
        }

        return null;
    }

    public function soleTemplateForJob(User $user, Job $job): ?InterviewTemplate
    {
        return $this->defaultTemplateForJob($user, $job);
    }
}
