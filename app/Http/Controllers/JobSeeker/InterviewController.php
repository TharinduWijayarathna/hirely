<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Services\AIService;
use App\Services\CandidateRankingService;
use App\Services\GoogleTextToSpeechService;
use App\Services\InterviewEvaluationService;
use App\Services\InterviewMediaService;
use App\Services\RecruitmentNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    public function index(): Response
    {
        $interviews = Interview::where('candidate_id', Auth::id())
            ->with(['job', 'template'])
            ->latest()
            ->get();

        return Inertia::render('job-seeker/Interviews', [
            'interviews' => $interviews,
        ]);
    }

    public function show(Interview $interview, AIService $aiService): Response
    {
        $this->authorizeCandidate($interview);

        $interview->load(['job', 'template', 'candidate']);

        if ($interview->status === 'completed') {
            return Inertia::render('job-seeker/InterviewResult', [
                'interview' => $interview->toResultPayload(false),
            ]);
        }

        if ($interview->status === 'pending') {
            $interview->update([
                'status' => 'in_progress',
                'started_at' => $interview->started_at ?? now(),
            ]);
        }

        if (($interview->questions ?? []) === []) {
            $template = $interview->template;
            $count = max(1, (int) ($template?->question_count ?? 5));
            $interview->update([
                'questions' => $aiService->fallbackConfiguredQuestions(
                    $interview->difficulty,
                    $template?->categoryCounts($count) ?? [
                        'technical' => 4,
                        'behavioral' => 3,
                        'scenario' => 2,
                        'cv' => 1,
                    ],
                ),
            ]);
        }

        $fresh = $interview->fresh(['job', 'template']);

        return Inertia::render('job-seeker/InterviewSessionVoice', [
            'interview' => $fresh,
        ]);
    }

    public function followUp(Request $request, Interview $interview, AIService $aiService)
    {
        $this->authorizeCandidate($interview);
        $interview->loadMissing('job');

        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string|min:2',
            'answers' => 'nullable|array',
        ]);

        $answers = array_merge($interview->answers ?? [], $validated['answers'] ?? [
            $validated['question'] => $validated['answer'],
        ]);

        $questions = $interview->questions ?? [];
        $followUps = collect($questions)->where('follow_up', true)->count();
        $alreadyClarified = collect($questions)->contains(
            fn ($question) => is_array($question) && ($question['parent'] ?? null) === $validated['question']
        );
        $followUp = null;

        if ($followUps < 3 && ! $alreadyClarified) {
            $followUp = $aiService->generateFollowUpQuestion(
                $validated['question'],
                $validated['answer'],
                $interview->difficulty,
                $interview->job?->title,
            );
        }

        if ($followUp) {
            $questions = $this->insertFollowUp($questions, $validated['question'], [
                'category' => 'follow_up',
                'text' => $followUp,
                'follow_up' => true,
                'parent' => $validated['question'],
            ]);
        }

        $interview->update([
            'answers' => $answers,
            'questions' => $questions,
            'status' => 'in_progress',
        ]);

        return redirect()->route('interviews.show', $interview);
    }

    public function conversation(Request $request, Interview $interview, AIService $aiService)
    {
        $this->authorizeCandidate($interview);
        $interview->loadMissing(['job', 'candidate']);

        $validated = $request->validate([
            'user_message' => 'required|string',
        ]);

        $history = $interview->conversation_history ?? [];
        $history[] = [
            'role' => 'user',
            'content' => $validated['user_message'],
            'timestamp' => now()->toIso8601String(),
        ];

        $context = trim(($interview->job?->title ? 'Job: '.$interview->job->title : '')."\n".$interview->candidate?->candidateContext());
        $aiResponse = $aiService->getConversationalResponse(
            $history,
            'mixed',
            $interview->difficulty,
            false,
            $context !== '' ? $context : null,
        );

        if ($aiResponse) {
            $history[] = [
                'role' => 'assistant',
                'content' => $aiResponse,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        $interview->update([
            'conversation_history' => $history,
            'status' => 'in_progress',
        ]);

        return redirect()->route('interviews.show', $interview);
    }

    public function initial(Interview $interview, AIService $aiService)
    {
        $this->authorizeCandidate($interview);
        $interview->loadMissing(['job', 'candidate']);

        $history = $interview->conversation_history ?? [];

        if ($history === []) {
            $context = trim(($interview->job?->title ? 'Job: '.$interview->job->title : '')."\n".$interview->candidate?->candidateContext());
            $initial = $aiService->getConversationalResponse(
                [],
                'mixed',
                $interview->difficulty,
                true,
                $context !== '' ? $context : null,
            );

            if ($initial) {
                $history[] = [
                    'role' => 'assistant',
                    'content' => $initial,
                    'timestamp' => now()->toIso8601String(),
                ];
                $interview->update([
                    'conversation_history' => $history,
                    'status' => 'in_progress',
                    'started_at' => $interview->started_at ?? now(),
                ]);
            }
        }

        return redirect()->route('interviews.show', $interview);
    }

    public function speech(Request $request, Interview $interview, GoogleTextToSpeechService $tts)
    {
        $this->authorizeCandidate($interview);

        $validated = $request->validate([
            'text' => 'required|string|max:4500',
        ]);

        $audio = $tts->synthesize($validated['text']);

        if ($audio === null) {
            return response()->json(['fallback' => true], 422);
        }

        return response($audio, 200, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function storeScreenshot(Request $request, Interview $interview, InterviewMediaService $media)
    {
        $this->authorizeCandidate($interview);

        $validated = $request->validate([
            'screenshot' => 'required|file|mimes:jpeg,jpg,png,webp|max:4096',
            'label' => 'nullable|string|max:80',
        ]);

        $media->storeScreenshot($interview, $request->file('screenshot'), $validated['label'] ?? null);

        return response()->json(['ok' => true]);
    }

    public function storeRecording(Request $request, Interview $interview, InterviewMediaService $media)
    {
        $this->authorizeCandidate($interview);

        $request->validate([
            'recording' => 'required|file|mimes:webm,mp4,mov,bin|max:102400',
        ]);

        $media->storeRecording($interview, $request->file('recording'));

        return response()->json(['ok' => true]);
    }

    public function update(
        Request $request,
        Interview $interview,
        InterviewEvaluationService $evaluationService,
        RecruitmentNotifier $notifier,
        CandidateRankingService $ranking,
    ) {
        $this->authorizeCandidate($interview);

        $validated = $request->validate([
            'answers' => 'nullable|array',
            'conversation_history' => 'nullable|array',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
        ]);

        if (($validated['status'] ?? null) === 'completed') {
            $validated['completed_at'] = now();
            if ($interview->started_at) {
                $validated['duration_minutes'] = max(0, (int) round($interview->started_at->diffInMinutes(now())));
            }

            if (! empty($validated['answers']) && $interview->questions) {
                try {
                    $evaluated = $evaluationService->complete($interview, $validated['answers']);
                    $validated = array_merge($validated, $evaluated);
                } catch (\RuntimeException $e) {
                    return redirect()->back()->withErrors([
                        'ai' => $e->getMessage(),
                    ]);
                }
            } else {
                $history = $validated['conversation_history'] ?? $interview->conversation_history ?? [];
                $answers = $this->answersFromConversation(is_array($history) ? $history : []);
                if ($answers !== []) {
                    try {
                        $evaluated = $evaluationService->complete($interview, $answers);
                        $validated = array_merge($validated, $evaluated);
                    } catch (\RuntimeException $e) {
                        return redirect()->back()->withErrors([
                            'ai' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $interview->jobApplication()->update(['status' => 'interviewed']);
        }

        $interview->update($validated);

        if (($validated['status'] ?? null) === 'completed') {
            $interview = $interview->fresh(['job', 'candidate']);
            $notifier->interviewCompleted($interview);

            if ($interview->job) {
                $ranking->rankJob($interview->job);
                $notifier->rankingReady($interview->job);
            }

            return redirect()->route('interviews.show', $interview)->with('success', 'Interview completed. Your results are ready.');
        }

        return redirect()->back();
    }

    protected function authorizeCandidate(Interview $interview): void
    {
        if ((int) $interview->candidate_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    /**
     * @param  array<int, mixed>  $questions
     * @param  array<string, mixed>  $followUp
     * @return array<int, mixed>
     */
    protected function insertFollowUp(array $questions, string $parent, array $followUp): array
    {
        $insertAt = count($questions);

        foreach ($questions as $index => $question) {
            $text = is_array($question) ? (string) ($question['text'] ?? '') : (string) $question;

            if ($text === $parent) {
                $insertAt = $index + 1;
                break;
            }
        }

        array_splice($questions, $insertAt, 0, [$followUp]);

        return array_values($questions);
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @return array<string, string>
     */
    protected function answersFromConversation(array $history): array
    {
        $answers = [];
        $lastQuestion = null;

        foreach ($history as $turn) {
            if (($turn['role'] ?? '') === 'assistant') {
                $lastQuestion = $turn['content'] ?? null;
            } elseif (($turn['role'] ?? '') === 'user' && $lastQuestion) {
                $answers[$lastQuestion] = $turn['content'] ?? '';
            }
        }

        return $answers;
    }
}
