<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\MockInterviewSession;
use App\Services\AIService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MockInterviewController extends Controller
{
    public function index(PlanLimitService $limits): Response
    {
        $user = Auth::user();
        $sessions = MockInterviewSession::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        $stats = [
            'total' => MockInterviewSession::where('user_id', $user->id)->count(),
            'average_score' => MockInterviewSession::where('user_id', $user->id)
                ->whereNotNull('score')
                ->avg('score') ?? 0,
            'total_time' => MockInterviewSession::where('user_id', $user->id)
                ->whereNotNull('duration_minutes')
                ->sum('duration_minutes') ?? 0,
        ];

        return Inertia::render('job-seeker/MockInterview', [
            'sessions' => $sessions,
            'stats' => $stats,
            'quota' => $limits->quota($user, 'mock_interviews'),
        ]);
    }

    public function store(Request $request, AIService $aiService, PlanLimitService $limits)
    {
        if ($message = $limits->denyMessage(Auth::user(), 'mock_interviews')) {
            return redirect()->route('mock-interview')->withErrors(['plan' => $message]);
        }

        $validated = $request->validate([
            'type' => 'required|in:technical,behavioral,mixed',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'mode' => 'required|in:text,voice',
        ]);

        // Generate questions using AI
        try {
            $questions = $aiService->generateQuestions(
                $validated['type'],
                $validated['difficulty'],
                5 // Number of questions
            );
        } catch (\Exception $e) {
            // If AI fails, questions will be generated on the frontend
            $questions = null;
        }

        $session = MockInterviewSession::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'difficulty' => $validated['difficulty'],
            'mode' => $validated['mode'],
            'status' => 'pending',
            'questions' => $questions,
            'started_at' => now(),
        ]);

        return redirect()->route('mock-interview.session', $session->id)
            ->with('success', 'Interview session created successfully.');
    }

    public function followUp(Request $request, MockInterviewSession $session, AIService $aiService)
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string|min:2',
            'answers' => 'nullable|array',
        ]);

        $answers = array_merge($session->answers ?? [], $validated['answers'] ?? [
            $validated['question'] => $validated['answer'],
        ]);
        $questions = array_values($session->questions ?? []);
        $followUpCount = collect($questions)->filter(function ($question) {
            if (is_array($question)) {
                return (bool) ($question['follow_up'] ?? false);
            }

            return str_starts_with((string) $question, 'Follow-up:');
        })->count();

        $followUp = null;
        if ($followUpCount < 3) {
            $followUp = $aiService->generateFollowUpQuestion(
                $validated['question'],
                $validated['answer'],
                $session->difficulty,
            );
        }

        if ($followUp) {
            $insertAt = count($questions);

            foreach ($questions as $index => $question) {
                $text = is_array($question) ? (string) ($question['text'] ?? '') : (string) $question;

                if ($text === $validated['question']) {
                    $insertAt = $index + 1;
                    break;
                }
            }

            array_splice($questions, $insertAt, 0, [[
                'text' => $followUp,
                'follow_up' => true,
                'parent' => $validated['question'],
            ]]);
            $questions = array_values($questions);
        }

        $session->update([
            'answers' => $answers,
            'questions' => $questions,
            'status' => 'in_progress',
        ]);

        return redirect()->route('mock-interview.session', $session);
    }

    public function session(MockInterviewSession $session): Response
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        // Update status if pending
        if ($session->status === 'pending') {
            $session->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        // Render different view based on mode
        $viewName = $session->mode === 'voice'
            ? 'job-seeker/MockInterviewSessionVoice'
            : 'job-seeker/MockInterviewSession';

        return Inertia::render($viewName, [
            'session' => $session,
        ]);
    }

    public function update(Request $request, MockInterviewSession $session, AIService $aiService)
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'answers' => 'nullable|array',
            'feedback' => 'nullable|array',
            'score' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
            if ($session->started_at) {
                $validated['duration_minutes'] = now()->diffInMinutes($session->started_at);
            }

            // Generate AI feedback and scoring if answers are provided
            if (isset($validated['answers']) && ! empty($validated['answers']) && $session->questions) {
                try {
                    $feedback = $aiService->generateFeedback(
                        $session->questions,
                        $validated['answers'],
                        $session->type,
                        $session->difficulty
                    );

                    if (isset($feedback['feedback'])) {
                        $validated['feedback'] = $feedback['feedback'];
                    }

                    if (isset($feedback['overall_score'])) {
                        $validated['score'] = $feedback['overall_score'];
                    }

                    // Store overall feedback in feedback array
                    if (isset($feedback['overall_feedback'])) {
                        $validated['feedback']['overall'] = $feedback['overall_feedback'];
                    }
                } catch (\Exception $e) {
                    // If AI feedback generation fails, continue without it
                    Log::error('AI feedback generation failed: '.$e->getMessage());
                }
            }
        }

        $session->update($validated);

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            return redirect()->route('mock-interview')
                ->with('success', 'Interview completed! Check your feedback and score.');
        }

        return redirect()->back()->with('success', 'Interview session updated successfully.');
    }

    public function processConversation(Request $request, MockInterviewSession $session, AIService $aiService)
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'user_message' => 'required|string',
        ]);

        // Get current conversation history
        $conversationHistory = $session->conversation_history ?? [];

        // Add user message
        $conversationHistory[] = [
            'role' => 'user',
            'content' => $validated['user_message'],
            'timestamp' => now()->toISOString(),
        ];

        // Also save to answers if it's answering a question
        $currentAnswers = $session->answers ?? [];
        $questions = $session->questions ?? [];

        // Try to match the answer to a question (for tracking)
        if (! empty($questions)) {
            // Get the last AI message to see what question was asked
            $lastAIMessage = null;
            for ($i = count($conversationHistory) - 2; $i >= 0; $i--) {
                if (isset($conversationHistory[$i]['role']) && $conversationHistory[$i]['role'] === 'assistant') {
                    $lastAIMessage = $conversationHistory[$i]['content'];
                    break;
                }
            }

            // Try to match to a question
            foreach ($questions as $question) {
                if ($lastAIMessage && str_contains(strtolower($lastAIMessage), strtolower($question))) {
                    $currentAnswers[$question] = $validated['user_message'];
                    break;
                }
            }
        }

        // Get AI response
        $aiResponse = $aiService->getConversationalResponse(
            $conversationHistory,
            $session->type,
            $session->difficulty
        );

        if ($aiResponse) {
            // Add AI response to conversation
            $conversationHistory[] = [
                'role' => 'assistant',
                'content' => $aiResponse,
                'timestamp' => now()->toISOString(),
            ];
        }

        // Update session
        $session->update([
            'conversation_history' => $conversationHistory,
            'answers' => $currentAnswers,
        ]);

        return Inertia::render('job-seeker/MockInterviewSessionVoice', [
            'session' => $session->fresh(),
        ])->with([
            'ai_response' => $aiResponse,
        ]);
    }

    public function getInitialMessage(MockInterviewSession $session, AIService $aiService)
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        // Initialize conversation if empty
        $conversationHistory = $session->conversation_history ?? [];

        if (empty($conversationHistory)) {
            $initialMessage = $aiService->getConversationalResponse(
                [],
                $session->type,
                $session->difficulty,
                true // isInitial
            );

            if ($initialMessage) {
                $conversationHistory[] = [
                    'role' => 'assistant',
                    'content' => $initialMessage,
                    'timestamp' => now()->toISOString(),
                ];

                $session->update([
                    'conversation_history' => $conversationHistory,
                ]);
            }
        }

        $session = $session->fresh();

        return Inertia::render('job-seeker/MockInterviewSessionVoice', [
            'session' => $session,
        ])->with([
            'conversation_history' => $session->conversation_history ?? [],
        ]);
    }
}
