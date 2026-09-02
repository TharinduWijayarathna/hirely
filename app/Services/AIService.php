<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $apiKey;

    protected string $model;

    protected string $baseUrl;

    protected int $timeout;

    protected int $documentTimeout;

    protected ?string $lastFailure = null;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.api_key') ?? '');
        $this->model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $this->baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->timeout = max(5, (int) config('services.gemini.timeout', 20));
        $this->documentTimeout = max($this->timeout, (int) config('services.gemini.document_timeout', 90));
    }

    /**
     * Generate categorized recruitment interview questions from a template mix.
     *
     * @return array<int, array{category: string, text: string}>
     */
    public function generateConfiguredQuestions(
        string $difficulty,
        array $categoryCounts,
        ?string $jobTitle = null,
        ?string $jobDescription = null,
        ?string $candidateContext = null,
        array $evaluationCriteria = [],
    ): array {
        $labels = [
            'technical' => 'technical programming and software engineering',
            'behavioral' => 'behavioral and situational',
            'scenario' => 'realistic on-the-job scenario and problem-solving',
            'cv' => 'candidate CV, projects, skills, and past experience',
        ];

        $requested = [];
        foreach ($categoryCounts as $category => $count) {
            if ($count > 0 && isset($labels[$category])) {
                $requested[$category] = (int) $count;
            }
        }

        if ($requested === []) {
            return [];
        }

        $difficultyLabels = [
            'beginner' => 'beginner level (fundamental concepts)',
            'intermediate' => 'intermediate level (moderate complexity)',
            'advanced' => 'advanced level (complex scenarios and deep knowledge)',
        ];

        $context = '';
        if ($jobTitle) {
            $context .= "Job title: {$jobTitle}\n";
        }
        if ($jobDescription) {
            $context .= "Job description: {$jobDescription}\n";
        }
        if ($candidateContext) {
            $context .= "Candidate background: {$candidateContext}\n";
        }
        if ($evaluationCriteria !== []) {
            $context .= 'Evaluation criteria: '.implode(', ', $evaluationCriteria)."\n";
        }

        $spec = collect($requested)
            ->map(fn (int $count, string $category) => "{$count} {$labels[$category]} questions (key \"{$category}\")")
            ->implode(', ');

        $systemPrompt = "You are an expert technical interviewer. In a SINGLE response, generate the complete interview question set for a {$difficultyLabels[$difficulty]} candidate. Do not generate extra questions later. Return ONLY valid JSON with this exact shape: {\"technical\": [\"...\"], \"behavioral\": [\"...\"], \"scenario\": [\"...\"], \"cv\": [\"...\"]}. Include only the keys that were requested, with exactly the requested counts. CV questions MUST name specific companies, roles, projects, or skills from the candidate background. Technical questions should use the candidate's listed stack. No generic prompts like \"tell me about a project\" when named items exist. No markdown.";

        $userPrompt = "Generate the full interview now: {$spec}. Ground every question in the role and CV context below. Return the complete set in this one response.\n{$context}";

        try {
            $response = $this->makeRequest($systemPrompt, $userPrompt, 2048, true);
            $content = $this->responseText($response);

            if ($content !== null) {
                $decoded = $this->decodeJsonObject($content);

                if (is_array($decoded)) {
                    return $this->flattenCategorizedQuestions($decoded, $requested);
                }
            }
        } catch (\Exception $e) {
            Log::error('AI configured question generation error: '.$e->getMessage());
        }

        return $this->flattenCategorizedQuestions(
            $this->getDefaultCategorizedQuestions($difficulty),
            $requested
        );
    }

    /**
     * Local question bank used when Gemini is unavailable or too slow.
     *
     * @param  array<string, int>  $categoryCounts
     * @return array<int, array{category: string, text: string}>
     */
    public function fallbackConfiguredQuestions(string $difficulty, array $categoryCounts): array
    {
        return $this->flattenCategorizedQuestions(
            $this->getDefaultCategorizedQuestions($difficulty),
            $categoryCounts
        );
    }

    /**
     * Generate interview questions based on type and difficulty
     */
    public function generateQuestions(string $type, string $difficulty, int $count = 5): array
    {
        $typeLabels = [
            'technical' => 'technical programming and software engineering',
            'behavioral' => 'behavioral and situational',
            'mixed' => 'combination of technical and behavioral',
        ];

        $difficultyLabels = [
            'beginner' => 'beginner level (fundamental concepts)',
            'intermediate' => 'intermediate level (moderate complexity)',
            'advanced' => 'advanced level (complex scenarios and deep knowledge)',
        ];

        $systemPrompt = "You are an expert interview coach. Generate exactly {$count} {$typeLabels[$type]} interview questions suitable for {$difficultyLabels[$difficulty]} candidates. Each question should be realistic, practical, and help assess the candidate's skills. Return ONLY a valid JSON array of strings with the questions, no additional text, markdown, or formatting.";

        $userPrompt = "Generate exactly {$count} {$typeLabels[$type]} interview questions for {$difficultyLabels[$difficulty]} level candidates. Return as a valid JSON array format: [\"Question 1?\", \"Question 2?\", \"Question 3?\"]";

        try {
            $response = $this->makeRequest($systemPrompt, $userPrompt, 1024, true);
            $content = $this->responseText($response);

            if ($content !== null) {
                $content = $this->stripMarkdown($content);

                $questions = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($questions)) {
                    return $questions;
                }

                // Fallback: try to parse as a list
                $questions = $this->parseQuestionsFromText($content);
                if (! empty($questions)) {
                    return array_slice($questions, 0, $count);
                }
            }
        } catch (\Exception $e) {
            Log::error('AI question generation error: '.$e->getMessage());
        }

        // Fallback to default questions
        return $this->getDefaultQuestions($type, $difficulty, $count);
    }

    public function generateFollowUpQuestion(string $question, string $answer, string $difficulty, ?string $jobTitle = null): ?string
    {
        $answer = trim($answer);

        if ($answer === '') {
            return 'Please share a bit more detail so I can follow up on that question.';
        }

        $systemPrompt = 'You are an expert interviewer. The full question list already exists. Only ask a clarification if the candidate answer is clearly incomplete, vague, or only a couple of words. If the answer is usable, return the exact word NONE. Return ONLY one short follow-up question as plain text, or NONE. No quotes or markdown.';
        $jobLine = $jobTitle ? "Job title: {$jobTitle}\n" : '';

        try {
            $response = $this->makeRequest(
                $systemPrompt,
                "{$jobLine}Difficulty: {$difficulty}\nOriginal question: {$question}\nCandidate answer: {$answer}\nIf this answer needs clarification, write one follow-up. Otherwise return NONE.",
                200
            );

            $content = $this->responseText($response);

            if ($content !== null) {
                $followUp = trim($this->stripMarkdown($content));
                $followUp = trim($followUp, " \t\n\r\0\x0B\"'");

                if ($followUp === '' || strcasecmp($followUp, 'NONE') === 0) {
                    return null;
                }

                return $followUp;
            }
        } catch (\Exception $e) {
            Log::error('Follow-up generation error: '.$e->getMessage());
        }

        return $this->heuristicFollowUp($question, $answer);
    }

    protected function heuristicFollowUp(string $question, string $answer): ?string
    {
        if (str_word_count($answer) >= 12 || mb_strlen($answer) >= 80) {
            return null;
        }

        if (mb_strlen($answer) < 40) {
            return 'Can you give a specific example that supports your answer?';
        }

        return null;
    }

    /**
     * Get conversational response from AI interviewer
     */
    public function getConversationalResponse(array $conversationHistory, string $type, string $difficulty, bool $isInitial = false, ?string $extraContext = null): ?string
    {
        $typeLabels = [
            'technical' => 'technical programming and software engineering',
            'behavioral' => 'behavioral and situational',
            'mixed' => 'combination of technical and behavioral',
        ];

        $difficultyLabels = [
            'beginner' => 'beginner level',
            'intermediate' => 'intermediate level',
            'advanced' => 'advanced level',
        ];

        $systemPrompt = "You are a friendly and professional interviewer conducting a {$typeLabels[$type]} interview for a {$difficultyLabels[$difficulty]} candidate. Your goal is to have a natural, conversational interview.

Guidelines:
- Ask one question at a time
- After the candidate answers, provide brief acknowledgment or ask a follow-up question to probe deeper
- Be encouraging and supportive
- Move to the next topic naturally when you've gathered enough information
- Keep responses concise (1-2 sentences)
- Make it feel like a real conversation, not a scripted interview
- Use the candidate's answers to ask relevant follow-ups
- When you've covered enough topics, naturally conclude the interview

Keep your responses natural and conversational.";

        if ($extraContext) {
            $systemPrompt .= "\n\nAdditional context:\n{$extraContext}";
        }

        // Build messages array from conversation history
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history
        if (! empty($conversationHistory)) {
            foreach ($conversationHistory as $message) {
                $messages[] = [
                    'role' => $message['role'] ?? 'user',
                    'content' => $message['content'] ?? '',
                ];
            }
        } elseif ($isInitial) {
            // Initial greeting and first question
            $messages[] = [
                'role' => 'user',
                'content' => 'Start the interview. Greet the candidate and ask the first question.',
            ];
        }

        try {
            $response = $this->makeConversationalRequest($messages);

            $content = $this->responseText($response);

            if ($content !== null) {
                return $content;
            }
        } catch (\Exception $e) {
            Log::error('AI conversational response error: '.$e->getMessage());
        }

        // Fallback response
        if ($isInitial) {
            return "Hello! Thank you for taking the time to interview with us today. Let's start with a question: Can you tell me about your background and experience in this field?";
        }

        return 'Thank you for that answer. Could you tell me more about that?';
    }

    /**
     * @param  array<int, array{role?: string, content?: string}>  $messages
     */
    protected function makeConversationalRequest(array $messages): ?array
    {
        $systemInstruction = '';
        $contents = [];

        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = (string) ($message['content'] ?? '');

            if ($role === 'system') {
                $systemInstruction .= ($systemInstruction === '' ? '' : "\n\n").$content;

                continue;
            }

            $geminiRole = $role === 'assistant' ? 'model' : 'user';

            if ($contents !== [] && $contents[array_key_last($contents)]['role'] === $geminiRole) {
                $contents[array_key_last($contents)]['parts'][0]['text'] .= "\n\n".$content;

                continue;
            }

            $contents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => $content]],
            ];
        }

        return $this->generateContent($systemInstruction, $contents, 300, 0.8);
    }

    /**
     * Generate feedback and score for interview answers
     */
    public function generateFeedback(array $questions, array $answers, string $type, string $difficulty): array
    {
        $evaluation = $this->evaluateInterview($questions, $answers, $difficulty);

        $feedback = [];
        foreach ($evaluation['answers'] as $row) {
            $feedback[$row['question']] = $row['feedback'];
        }

        return [
            'feedback' => $feedback,
            'overall_score' => $evaluation['overall_score'],
            'overall_feedback' => $evaluation['rationale'],
            'evaluation' => $evaluation,
        ];
    }

    /**
     * Structured, explainable evaluation for a recruitment (or mock) interview.
     *
     * @param  array<int, mixed>  $questions
     * @param  array<string, mixed>  $answers
     * @param  array<int, string>  $criteria
     * @return array<string, mixed>
     */
    public function evaluateInterview(
        array $questions,
        array $answers,
        string $difficulty,
        array $criteria = [],
        ?string $jobTitle = null,
    ): array {
        $criteria = $criteria === []
            ? ['Technical depth', 'Communication', 'Problem solving', 'Role fit']
            : array_values($criteria);

        $transcript = '';
        foreach ($questions as $index => $question) {
            $questionText = $this->questionText($question);
            $category = is_array($question) ? ($question['category'] ?? 'general') : 'general';
            $answer = $answers[$questionText] ?? $answers[$index] ?? 'No answer provided';
            $transcript .= 'Question '.($index + 1)." [{$category}]: {$questionText}\nAnswer: {$answer}\n\n";
        }

        $criteriaList = implode(', ', $criteria);
        $jobLine = $jobTitle ? "Job title: {$jobTitle}\n" : '';

        $systemPrompt = 'You are an explainable interview evaluator. Score the candidate fairly. Return ONLY valid JSON: {"overall_score":0,"rationale":"","confidence":0.0,"strengths":[""],"weaknesses":[""],"dimensions":[{"name":"","score":0,"weight":1,"evidence":"short quote from the answer","comment":""}],"answers":[{"question":"","category":"","score":0,"feedback":"","evidence":""}]}. overall_score and dimension/answer scores are 0-100. confidence is 0-1. Include every criterion in dimensions: '.$criteriaList.'. No markdown.';

        $response = null;

        try {
            $response = $this->makeRequest(
                $systemPrompt,
                "{$jobLine}Difficulty: {$difficulty}\nCriteria: {$criteriaList}\n\n{$transcript}",
                8192,
                true,
                null,
                max($this->timeout, 60),
            );
            $content = $this->responseText($response);

            if ($content !== null) {
                $decoded = $this->decodeJsonObject($content);

                if (is_array($decoded)) {
                    return $this->normalizeEvaluation($decoded, $questions, $answers, $criteria);
                }
            }
        } catch (\Exception $e) {
            Log::error('Interview evaluation error: '.$e->getMessage());

            throw new \RuntimeException(
                $this->lastFailure ?: 'Gemini could not evaluate this interview. Check your API key and try again.',
                previous: $e,
            );
        }

        if (data_get($response ?? null, 'candidates.0.finishReason') === 'MAX_TOKENS') {
            throw new \RuntimeException('Gemini evaluation was cut off because the response was too long. Please try again.');
        }

        throw new \RuntimeException(
            $this->lastFailure ?: 'Gemini returned an invalid evaluation response. Please try again.',
        );
    }

    public function lastFailureReason(): ?string
    {
        return $this->lastFailure;
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @param  array<int, mixed>  $questions
     * @param  array<string, mixed>  $answers
     * @param  array<int, string>  $criteria
     * @return array<string, mixed>
     */
    protected function normalizeEvaluation(array $decoded, array $questions, array $answers, array $criteria): array
    {
        $dimensions = [];
        $rawDimensions = $decoded['dimensions'] ?? [];

        if (is_array($rawDimensions) && $rawDimensions !== [] && array_is_list($rawDimensions) === false) {
            $converted = [];

            foreach ($rawDimensions as $name => $value) {
                if (is_array($value)) {
                    $converted[] = [
                        'name' => (string) ($value['name'] ?? $name),
                        'score' => $value['score'] ?? 0,
                        'weight' => $value['weight'] ?? 1,
                        'evidence' => $value['evidence'] ?? '',
                        'comment' => $value['comment'] ?? '',
                    ];

                    continue;
                }

                $converted[] = [
                    'name' => (string) $name,
                    'score' => $value,
                    'weight' => 1,
                    'evidence' => '',
                    'comment' => '',
                ];
            }

            $rawDimensions = $converted;
        }

        foreach ($criteria as $name) {
            $match = collect($rawDimensions)->first(
                fn ($dimension) => strcasecmp((string) ($dimension['name'] ?? ''), $name) === 0
            );

            $dimensions[] = [
                'name' => $name,
                'score' => max(0, min(100, (int) ($match['score'] ?? 0))),
                'weight' => max(1, (int) ($match['weight'] ?? 1)),
                'evidence' => (string) ($match['evidence'] ?? ''),
                'comment' => (string) ($match['comment'] ?? ''),
            ];
        }

        $answerRows = [];
        foreach ($questions as $index => $question) {
            $questionText = $this->questionText($question);
            $category = is_array($question) ? ($question['category'] ?? 'general') : 'general';
            $match = collect($decoded['answers'] ?? [])->first(
                fn ($row) => ($row['question'] ?? '') === $questionText
            ) ?? ($decoded['answers'][$index] ?? []);

            $answerRows[] = [
                'question' => $questionText,
                'category' => $category,
                'score' => max(0, min(100, (int) ($match['score'] ?? 0))),
                'feedback' => (string) ($match['feedback'] ?? ''),
                'evidence' => (string) ($match['evidence'] ?? ''),
                'answer' => $answers[$questionText] ?? $answers[$index] ?? '',
            ];
        }

        $overall = isset($decoded['overall_score'])
            ? max(0, min(100, (int) $decoded['overall_score']))
            : (int) round(collect($dimensions)->avg('score') ?: 0);

        return [
            'overall_score' => $overall,
            'rationale' => (string) ($decoded['rationale'] ?? ''),
            'confidence' => $this->normalizeConfidence($decoded['confidence'] ?? 0.5),
            'strengths' => array_values(array_filter($decoded['strengths'] ?? [])),
            'weaknesses' => array_values(array_filter($decoded['weaknesses'] ?? [])),
            'dimensions' => $dimensions,
            'answers' => $answerRows,
        ];
    }

    protected function normalizeConfidence(mixed $confidence): float
    {
        if (is_numeric($confidence)) {
            $value = (float) $confidence;

            return $value > 1 ? max(0, min(1, $value / 100)) : max(0, min(1, $value));
        }

        if (is_string($confidence)) {
            return match (strtolower(trim($confidence))) {
                'high', 'very high' => 0.9,
                'medium', 'moderate' => 0.65,
                'low' => 0.35,
                default => 0.5,
            };
        }

        return 0.5;
    }

    /**
     * @param  array<int, mixed>  $questions
     * @param  array<string, mixed>  $answers
     * @param  array<int, string>  $criteria
     * @return array<string, mixed>
     */
    protected function heuristicEvaluation(array $questions, array $answers, array $criteria): array
    {
        $answerRows = [];
        $scores = [];

        foreach ($questions as $index => $question) {
            $questionText = $this->questionText($question);
            $answer = trim((string) ($answers[$questionText] ?? $answers[$index] ?? ''));
            $length = mb_strlen($answer);
            $score = $answer === '' ? 20 : min(88, 45 + (int) floor($length / 18));
            $scores[] = $score;
            $snippet = mb_substr($answer, 0, 120);

            $answerRows[] = [
                'question' => $questionText,
                'category' => is_array($question) ? ($question['category'] ?? 'general') : 'general',
                'score' => $score,
                'feedback' => $answer === '' ? 'No answer was provided.' : 'Answer length and relevance were estimated without the AI provider.',
                'evidence' => $snippet,
                'answer' => $answer,
            ];
        }

        $overall = $scores === [] ? 50 : (int) round(array_sum($scores) / count($scores));
        $dimensions = array_map(fn (string $name) => [
            'name' => $name,
            'score' => $overall,
            'weight' => 1,
            'evidence' => $answerRows[0]['evidence'] ?? '',
            'comment' => 'Heuristic score pending a full AI evaluation.',
        ], $criteria);

        return [
            'overall_score' => $overall,
            'rationale' => 'Automatic evaluation generated without the AI provider. Configure Gemini for criterion-level explanations.',
            'confidence' => 0.35,
            'strengths' => $overall >= 60 ? ['The candidate provided substantive written answers.'] : [],
            'weaknesses' => $overall < 70 ? ['Answers would benefit from more concrete examples and measurable outcomes.'] : [],
            'dimensions' => $dimensions,
            'answers' => $answerRows,
        ];
    }

    protected function makeRequest(
        string $systemPrompt,
        string $userPrompt,
        int $maxTokens = 2000,
        bool $json = false,
        ?array $inlineFile = null,
        ?int $timeout = null,
    ): ?array {
        $parts = [];

        if ($inlineFile !== null && ($inlineFile['contents'] ?? '') !== '') {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $inlineFile['mime_type'] ?? 'application/pdf',
                    'data' => base64_encode($inlineFile['contents']),
                ],
            ];
        }

        $parts[] = ['text' => $userPrompt];

        return $this->generateContent(
            $systemPrompt,
            [
                [
                    'role' => 'user',
                    'parts' => $parts,
                ],
            ],
            $maxTokens,
            0.7,
            $json,
            $inlineFile !== null ? $this->documentTimeout : ($timeout ?? $this->timeout),
        );
    }

    /**
     * @param  array<int, array{role: string, parts: array<int, array<string, mixed>>}>  $contents
     */
    protected function generateContent(
        string $systemInstruction,
        array $contents,
        int $maxTokens,
        float $temperature,
        bool $json = false,
        ?int $timeout = null,
    ): ?array {
        $this->lastFailure = null;

        if ($this->apiKey === '') {
            $this->lastFailure = 'GEMINI_API_KEY is not set in .env.';
            Log::error('Gemini API key is not configured');

            return null;
        }

        $hasFile = $this->contentsHaveInlineData($contents);
        $timeout ??= $hasFile ? $this->documentTimeout : $this->timeout;

        if ($hasFile) {
            set_time_limit($timeout + 20);
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ],
        ];

        if ($systemInstruction !== '') {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        if ($json) {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
        }

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(10)
                ->acceptJson()
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent", $payload);

            if ($response->successful()) {
                $this->lastFailure = null;

                return $response->json();
            }

            $message = (string) data_get($response->json(), 'error.message', 'HTTP '.$response->status());
            $this->lastFailure = mb_substr($message, 0, 180);

            Log::error('Gemini API request failed', [
                'status' => $response->status(),
                'message' => $this->lastFailure,
                'model' => $this->model,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->lastFailure = "Gemini timed out after {$timeout}s. Try again or use a smaller request.";
            Log::error('Gemini API request timed out', ['seconds' => $timeout, 'model' => $this->model]);
        } catch (\Throwable $e) {
            $this->lastFailure = 'Gemini request failed.';
            Log::error('Gemini API request exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * @param  array<int, array{role: string, parts: array<int, array<string, mixed>>}>  $contents
     */
    protected function contentsHaveInlineData(array $contents): bool
    {
        foreach ($contents as $content) {
            foreach ($content['parts'] ?? [] as $part) {
                if (isset($part['inlineData'])) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function responseText(?array $response): ?string
    {
        if ($response === null) {
            return null;
        }

        $text = '';

        foreach ($response['candidates'][0]['content']['parts'] ?? [] as $part) {
            if (! empty($part['thought'])) {
                continue;
            }

            $text .= (string) ($part['text'] ?? '');
        }

        $text = trim($text);

        return $text === '' ? null : $text;
    }

    /**
     * Parse questions from text response
     */
    protected function parseQuestionsFromText(string $text): array
    {
        $questions = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Remove list markers
            $line = preg_replace('/^(\d+[\.\)]\s*|[-*]\s*)/', '', $line);

            // Check if it looks like a question
            if (strlen($line) > 10 && (str_contains($line, '?') || strlen($line) > 30)) {
                $questions[] = $line;
            }
        }

        return $questions;
    }

    /**
     * Get default questions as fallback
     */
    protected function getDefaultQuestions(string $type, string $difficulty, int $count): array
    {
        $defaultQuestions = [
            'technical' => [
                'beginner' => [
                    'What is the difference between a variable and a constant?',
                    'Explain what a function is in programming.',
                    'What is the purpose of an if statement?',
                    'What is object-oriented programming?',
                    'Explain the concept of loops in programming.',
                ],
                'intermediate' => [
                    'Explain the difference between REST and GraphQL APIs.',
                    'What is the difference between SQL JOIN types?',
                    'How does garbage collection work in programming languages?',
                    'Explain the difference between async and sync programming.',
                    'What are design patterns? Give an example.',
                ],
                'advanced' => [
                    'Explain the trade-offs between microservices and monolithic architecture.',
                    'How would you design a distributed caching system?',
                    'Explain the CAP theorem and its implications.',
                    'How would you optimize a slow database query?',
                    'Explain database sharding strategies.',
                ],
            ],
            'behavioral' => [
                'beginner' => [
                    'Tell me about yourself.',
                    'Why are you interested in this role?',
                    'What are your greatest strengths?',
                    'Where do you see yourself in 5 years?',
                    'Why should we hire you?',
                ],
                'intermediate' => [
                    'Describe a time when you had to work under pressure.',
                    'Tell me about a challenge you faced and how you overcame it.',
                    'Give an example of when you worked effectively in a team.',
                    'Describe a situation where you had to learn something new quickly.',
                    'Tell me about a time you had to adapt to change.',
                ],
                'advanced' => [
                    'Describe a situation where you had to make a difficult decision with limited information.',
                    'Tell me about a time you had to convince others of your idea.',
                    'Describe a conflict you resolved in a professional setting.',
                    'Give an example of when you had to lead without authority.',
                    'Tell me about a complex problem you solved.',
                ],
            ],
            'mixed' => [
                'beginner' => [
                    'What is your biggest technical achievement?',
                    'How do you approach learning new technologies?',
                    'Describe a project you are proud of.',
                    'How do you stay updated with technology trends?',
                    'What motivates you in your work?',
                ],
                'intermediate' => [
                    'How do you balance technical requirements with business needs?',
                    'Describe your experience with agile development.',
                    'How do you handle technical debt in your projects?',
                    'Tell me about a time you had to explain a complex technical concept to a non-technical person.',
                    'How do you ensure code quality in your projects?',
                ],
                'advanced' => [
                    'Describe a complex technical problem you solved and the approach you took.',
                    'How do you mentor junior developers?',
                    'Explain a time you had to make a technical decision that affected the entire team.',
                    'Describe your approach to system architecture and design.',
                    'Tell me about a time you had to refactor a large codebase.',
                ],
            ],
        ];

        $questions = $defaultQuestions[$type][$difficulty] ?? [];

        return array_slice($questions, 0, $count);
    }

    /**
     * Review a CV by sending the original file to Gemini.
     *
     * @return array<string, mixed>
     */
    public function analyzeCurriculumVitae(string $contents, string $mimeType): array
    {
        if (strlen($contents) > 12_000_000) {
            throw new \RuntimeException('This CV is too large for Gemini. Upload a PDF under 10 MB.');
        }

        $systemPrompt = 'You are an expert recruiter. Read the attached resume file directly. Return ONLY valid JSON with this exact shape: {"extraction":{"full_name":"","email":"","phone":"","location":"","summary":"","education":[{"institution":"","degree":"","field":"","start_date":"","end_date":""}],"skills":[""],"experience":[{"company":"","title":"","start_date":"","end_date":"","description":""}],"qualifications":[""],"projects":[{"name":"","description":"","technologies":[""]}],"certifications":[{"name":"","issuer":"","date":""}],"technologies":[""],"relevant_experience":[""],"experience_years":0,"experience_level":"entry|mid|senior"},"review":{"score":0,"summary":"","strengths":[""],"improvements":[""]}}. experience_level must be one of entry, mid, senior. score is 0-100. No markdown.';

        $response = $this->makeRequest(
            $systemPrompt,
            'Parse the attached resume file and return the JSON review.',
            4096,
            true,
            [
                'contents' => $contents,
                'mime_type' => $this->geminiDocumentMime($mimeType),
            ],
        );
        $content = $this->responseText($response);

        if ($content !== null) {
            $decoded = $this->decodeJsonObject($content);

            if (is_array($decoded) && isset($decoded['extraction'])) {
                return $this->normalizeCvAnalysis($decoded);
            }
        }

        throw new \RuntimeException($this->lastFailure ?: 'Gemini could not review this CV. Try a smaller text-based PDF.');
    }

    /**
     * @param  array<string, mixed>|null  $extraction
     * @return array{score: int, analysis: array<string, mixed>}
     */
    public function scoreAtsCompatibility(string $jobDescription, ?array $extraction = null, ?string $fileContents = null, ?string $mimeType = null): array
    {
        $jobDescription = mb_substr($jobDescription, 0, 6000);
        $skills = implode(', ', $extraction['skills'] ?? []);

        $systemPrompt = 'You are an ATS scoring engine. Read the attached resume if provided. Return ONLY valid JSON: {"score":0,"summary":"","matched_skills":[""],"missing_skills":[""],"recommendations":[""]}. score is 0-100. No markdown.';
        $userPrompt = "Job description:\n{$jobDescription}\n\nKnown skills: {$skills}\n\nScore the attached resume against the job.";

        $inlineFile = null;
        if ($fileContents) {
            $inlineFile = [
                'contents' => $fileContents,
                'mime_type' => $this->geminiDocumentMime($mimeType ?: 'application/pdf'),
            ];
        }

        $response = $this->makeRequest($systemPrompt, $userPrompt, 2048, true, $inlineFile);
        $content = $this->responseText($response);

        if ($content !== null) {
            $decoded = $this->decodeJsonObject($content);

            if (is_array($decoded) && isset($decoded['score'])) {
                return [
                    'score' => max(0, min(100, (int) $decoded['score'])),
                    'analysis' => [
                        'summary' => $decoded['summary'] ?? '',
                        'matched_skills' => array_values($decoded['matched_skills'] ?? []),
                        'missing_skills' => array_values($decoded['missing_skills'] ?? []),
                        'recommendations' => array_values($decoded['recommendations'] ?? []),
                    ],
                ];
            }
        }

        return $this->heuristicAtsScore($jobDescription, $extraction);
    }

    protected function geminiDocumentMime(?string $mimeType): string
    {
        $mimeType = strtolower((string) $mimeType);

        return match (true) {
            str_contains($mimeType, 'pdf') => 'application/pdf',
            str_contains($mimeType, 'jpeg'), str_contains($mimeType, 'jpg') => 'image/jpeg',
            str_contains($mimeType, 'png') => 'image/png',
            str_contains($mimeType, 'webp') => 'image/webp',
            str_contains($mimeType, 'wordprocessingml'), str_contains($mimeType, 'msword') => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/pdf',
        };
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>
     */
    protected function normalizeCvAnalysis(array $decoded): array
    {
        $extraction = $decoded['extraction'] ?? [];
        $review = $decoded['review'] ?? [];
        $level = $extraction['experience_level'] ?? $this->levelFromYears((int) ($extraction['experience_years'] ?? 0));

        if (! in_array($level, ['entry', 'mid', 'senior'], true)) {
            $level = $this->levelFromYears((int) ($extraction['experience_years'] ?? 0));
        }

        $extraction['experience_level'] = $level;
        $extraction['skills'] = array_values(array_filter($extraction['skills'] ?? []));
        $extraction['technologies'] = array_values(array_filter($extraction['technologies'] ?? []));

        $score = max(0, min(100, (int) ($review['score'] ?? 0)));

        return [
            'extraction' => $extraction,
            'review' => [
                'score' => $score,
                'summary' => $review['summary'] ?? '',
                'strengths' => array_values($review['strengths'] ?? []),
                'improvements' => array_values($review['improvements'] ?? []),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $extraction
     * @return array{score: int, analysis: array<string, mixed>}
     */
    protected function heuristicAtsScore(string $jobDescription, ?array $extraction): array
    {
        $cvSkills = array_map('strtolower', $extraction['skills'] ?? []);
        $tokens = preg_split('/[^a-z0-9+#]+/i', strtolower($jobDescription)) ?: [];
        $jobTokens = array_values(array_unique(array_filter($tokens, fn ($token) => strlen($token) > 2)));
        $matched = array_values(array_intersect($cvSkills, $jobTokens));
        $score = $jobTokens === [] ? 50 : (int) min(95, round((count($matched) / max(8, min(20, count($jobTokens)))) * 100));

        return [
            'score' => $score,
            'analysis' => [
                'summary' => 'Compatibility estimated from extracted skills. Configure Gemini for a deeper ATS review.',
                'matched_skills' => $matched,
                'missing_skills' => [],
                'recommendations' => ['Mirror important keywords from the job description in your CV.'],
            ],
        ];
    }

    protected function levelFromYears(int $years): string
    {
        return match (true) {
            $years >= 6 => 'senior',
            $years >= 3 => 'mid',
            default => 'entry',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeJsonObject(string $content): ?array
    {
        $decoded = json_decode($this->stripMarkdown($content), true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $matches) === 1) {
            $decoded = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function stripMarkdown(string $content): string
    {
        $content = trim($content);
        $content = preg_replace('/^```json\s*/', '', $content);
        $content = preg_replace('/^```\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        return trim($content);
    }

    protected function questionText(mixed $question): string
    {
        if (is_array($question)) {
            return (string) ($question['text'] ?? '');
        }

        return (string) $question;
    }

    /**
     * @param  array<string, array<int, string>>  $grouped
     * @param  array<string, int>  $requested
     * @return array<int, array{category: string, text: string}>
     */
    protected function flattenCategorizedQuestions(array $grouped, array $requested): array
    {
        $questions = [];

        foreach ($requested as $category => $count) {
            $items = [];
            foreach ($grouped[$category] ?? [] as $text) {
                if (is_string($text) && $text !== '') {
                    $items[] = $text;
                }
            }

            $defaults = $this->getDefaultCategorizedQuestions('intermediate')[$category] ?? [];
            foreach ($defaults as $text) {
                if (count($items) >= $count) {
                    break;
                }
                if (is_string($text) && $text !== '' && ! in_array($text, $items, true)) {
                    $items[] = $text;
                }
            }

            $source = $items !== [] ? $items : array_values(array_filter($defaults, fn ($text) => is_string($text) && $text !== ''));
            $index = 0;
            while (count($items) < $count && $source !== []) {
                $items[] = $source[$index % count($source)];
                $index++;
            }

            foreach (array_slice($items, 0, $count) as $text) {
                $questions[] = [
                    'category' => $category,
                    'text' => $text,
                ];
            }
        }

        return $questions;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function getDefaultCategorizedQuestions(string $difficulty): array
    {
        return [
            'technical' => $this->getDefaultQuestions('technical', $difficulty, 5),
            'behavioral' => $this->getDefaultQuestions('behavioral', $difficulty, 5),
            'scenario' => [
                'Walk me through how you would diagnose a production outage affecting a core user flow.',
                'A stakeholder wants to ship a feature this week that would create significant technical debt. What do you do?',
                'How would you design a rollout plan for a breaking API change?',
                'A teammate disagrees with your technical approach close to a deadline. How do you resolve it?',
                'Users report intermittent failures that you cannot reproduce locally. What is your investigation plan?',
            ],
            'cv' => [
                'Tell me about a project on your profile that is most relevant to this role and why.',
                'Which skill on your background was hardest to develop, and how did you practice it?',
                'Describe a technical decision you made on a past project and what you would change now.',
                'Walk me through the most complex problem you solved in one of your listed projects.',
                'How does your recent experience prepare you for the requirements of this role?',
            ],
        ];
    }
}
