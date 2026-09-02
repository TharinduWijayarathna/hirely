<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { mockInterview } from '@/routes';
import mockInterviewRoutes from '@/routes/mock-interview';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, Mic, MicOff, Play, Volume2 } from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';
import { useGoogleTts } from '@/composables/useGoogleTts';

type InterviewQuestion = string | { category?: string; text: string; follow_up?: boolean };

const props = defineProps<{
    session?: {
        id: number;
        type: string;
        difficulty: string;
        mode: string;
        status: string;
        questions?: InterviewQuestion[];
        answers?: Record<string, string>;
        conversation_history?: Array<{
            role: string;
            content: string;
            timestamp?: string;
        }>;
        feedback?: Record<string, any>;
        score?: number;
        started_at?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mock Interview',
        href: mockInterview().url,
    },
    {
        title: 'Voice Interview Session',
        href: mockInterviewRoutes.session(props.session?.id || 0).url,
    },
];

const questionText = (question: InterviewQuestion): string => {
    return typeof question === 'string' ? question : question.text;
};

const questionCategory = (question: InterviewQuestion | null): string | null => {
    if (!question || typeof question === 'string') {
        return null;
    }

    return question.category || null;
};

const questions = ref<InterviewQuestion[]>([...(props.session?.questions || [])]);
const answers = ref<Record<string, string>>({ ...(props.session?.answers || {}) });
const conversationHistory = ref(props.session?.conversation_history || []);
const currentIndex = ref(0);
const started = ref(false);
const isListening = ref(false);
const isProcessing = ref(false);
const waitingForAnswer = ref(false);
const paused = ref(false);
const typedFallback = ref('');
const committedText = ref('');
const interimText = ref('');
const speechSupported = ref(true);

const transcribedText = computed(() => `${committedText.value} ${interimText.value}`.replace(/\s+/g, ' ').trim());
const wordCount = computed(() => transcribedText.value.split(/\s+/).filter(Boolean).length);

const currentQuestion = computed(() => questions.value[currentIndex.value] ?? null);
const currentText = computed(() => (currentQuestion.value ? questionText(currentQuestion.value) : ''));
const totalQuestions = computed(() => questions.value.length);
const isLastQuestion = computed(() => currentIndex.value >= totalQuestions.value - 1);
const answeredCount = computed(() => Object.values(answers.value).filter((value) => value.trim() !== '').length);

const { isSpeaking, ttsError, activateTTS, speakText: playSpeech, stopSpeaking: stopTts } = useGoogleTts(
    `/mock-interview/${props.session?.id}/speech`,
);

let recognition: any = null;
let silenceTimer: number | null = null;
let restartTimer: number | null = null;
const SILENCE_MS = 5000;
const MIN_AUTO_SUBMIT_WORDS = 12;

const getTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        technical: 'Technical',
        behavioral: 'Behavioral',
        mixed: 'Mixed',
    };
    return labels[type] || type;
};

const clearSilenceTimer = () => {
    if (silenceTimer) {
        window.clearTimeout(silenceTimer);
        silenceTimer = null;
    }
};

const clearRestartTimer = () => {
    if (restartTimer) {
        window.clearTimeout(restartTimer);
        restartTimer = null;
    }
};

const initSpeechRecognition = () => {
    const SpeechRecognitionApi = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;

    if (!SpeechRecognitionApi) {
        speechSupported.value = false;
        return;
    }

    recognition = new SpeechRecognitionApi();
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = 'en-US';

    recognition.onresult = (event: any) => {
        let interim = '';

        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = event.results[i][0].transcript.trim();
            if (!transcript) {
                continue;
            }

            if (event.results[i].isFinal) {
                committedText.value = `${committedText.value} ${transcript}`.replace(/\s+/g, ' ').trim();
            } else {
                interim += ` ${transcript}`;
            }
        }

        interimText.value = interim.trim();
        armSilenceTimer();
    };

    recognition.onerror = (event: any) => {
        if (event.error === 'no-speech' || event.error === 'aborted') {
            return;
        }
        isListening.value = false;
    };

    recognition.onend = () => {
        isListening.value = false;

        if (
            waitingForAnswer.value &&
            !paused.value &&
            started.value &&
            !isProcessing.value &&
            !isSpeaking.value
        ) {
            clearRestartTimer();
            restartTimer = window.setTimeout(() => startListening(), 300);
        }
    };
};

const armSilenceTimer = () => {
    clearSilenceTimer();
    silenceTimer = window.setTimeout(() => {
        if (!waitingForAnswer.value || isProcessing.value || isSpeaking.value) {
            return;
        }

        if (wordCount.value >= MIN_AUTO_SUBMIT_WORDS) {
            void submitAnswer(transcribedText.value);
        }
    }, SILENCE_MS);
};

const speakThenListen = (text: string) => {
    waitingForAnswer.value = false;
    committedText.value = '';
    interimText.value = '';
    typedFallback.value = '';
    clearSilenceTimer();
    playSpeech(text, () => {
        waitingForAnswer.value = true;
        startListening();
    });
};

const startListening = () => {
    if (!recognition || isListening.value || isSpeaking.value || isProcessing.value || !started.value || !waitingForAnswer.value) {
        return;
    }

    paused.value = false;
    interimText.value = '';
    isListening.value = true;

    try {
        recognition.start();
    } catch {
        isListening.value = false;
    }
};

const pauseListening = () => {
    paused.value = true;
    clearRestartTimer();
    clearSilenceTimer();
    if (recognition && isListening.value) {
        try {
            recognition.stop();
        } catch {
            // Already stopped.
        }
    }
    isListening.value = false;
};

const stopListening = () => {
    waitingForAnswer.value = false;
    pauseListening();
};

const toggleMic = () => {
    if (isListening.value) {
        pauseListening();
        return;
    }

    waitingForAnswer.value = true;
    startListening();
};

const finishAnswering = () => {
    const spoken = transcribedText.value || typedFallback.value;
    if (spoken.trim().length < 2) {
        return;
    }
    void submitAnswer(spoken);
};

const speakCurrentQuestion = () => {
    if (!currentText.value) {
        return;
    }

    const category = questionCategory(currentQuestion.value);
    const prefix = category ? `This is a ${category.replace('_', ' ')} question. ` : '';
    speakThenListen(`${prefix}${currentText.value}`);
};

const startInterview = () => {
    activateTTS();

    if (!recognition) {
        initSpeechRecognition();
    }

    started.value = true;
    const firstUnanswered = questions.value.findIndex((question) => !answers.value[questionText(question)]?.trim());
    currentIndex.value = firstUnanswered === -1 ? 0 : firstUnanswered;

    const intro = `Welcome to your ${getTypeLabel(props.session?.type || '')} mock interview. I will ask ${totalQuestions.value} questions generated from your CV. Please answer out loud after each one. Let's begin.`;
    playSpeech(intro, () => speakCurrentQuestion());
};

const submitAnswer = async (message: string) => {
    const question = currentText.value;
    const answer = message.trim();

    if (!question || answer.length < 2 || isProcessing.value) {
        return;
    }

    isProcessing.value = true;
    waitingForAnswer.value = false;
    stopListening();
    stopTts();
    typedFallback.value = '';
    committedText.value = '';
    interimText.value = '';
    answers.value[question] = answer;
    conversationHistory.value = [
        ...conversationHistory.value,
        { role: 'assistant', content: question, timestamp: new Date().toISOString() },
        { role: 'user', content: answer, timestamp: new Date().toISOString() },
    ];

    router.post(
        `/mock-interview/${props.session?.id}/follow-up`,
        {
            question,
            answer,
            answers: answers.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                const updated = (page.props as { session?: { questions?: InterviewQuestion[]; answers?: Record<string, string> } }).session;
                if (updated?.questions) {
                    questions.value = updated.questions;
                }
                if (updated?.answers) {
                    answers.value = { ...updated.answers };
                }

                isProcessing.value = false;

                if (isLastQuestion.value) {
                    void completeInterview();
                    return;
                }

                currentIndex.value += 1;
                speakCurrentQuestion();
            },
            onError: () => {
                isProcessing.value = false;
                if (!isLastQuestion.value) {
                    currentIndex.value += 1;
                    speakCurrentQuestion();
                    return;
                }
                void completeInterview();
            },
        },
    );
};

const completeInterview = () => {
    stopTts();
    stopListening();
    started.value = false;
    isProcessing.value = true;

    router.put(mockInterviewRoutes.update(props.session?.id || 0).url, {
        answers: answers.value,
        conversation_history: conversationHistory.value,
        status: 'completed',
    });
};

onUnmounted(() => {
    waitingForAnswer.value = false;
    stopTts();
    stopListening();
    recognition?.abort();
});
</script>

<template>
    <Head title="Mock Voice Interview" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ getTypeLabel(session?.type || '') }} mock interview</h1>
                    <p class="text-muted-foreground mt-2">
                        {{ session?.difficulty }} · {{ totalQuestions }} CV-based questions · voice assistant
                    </p>
                </div>
                <span v-if="started" class="dash-badge dash-badge-in_progress">
                    Question {{ Math.min(currentIndex + 1, totalQuestions) }} of {{ totalQuestions }}
                </span>
            </div>

            <Card class="flex flex-1 flex-col shadow-sm">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Volume2 class="h-5 w-5" />
                        AI voice interviewer
                    </CardTitle>
                    <CardDescription>
                        The assistant asks the prepared CV questions out loud. Keep talking until you are finished, then click I’m done.
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-1 flex-col space-y-5">
                    <p v-if="ttsError" class="text-sm text-destructive">{{ ttsError }}</p>
                    <div v-if="!started" class="flex flex-1 flex-col items-center justify-center gap-4 py-10 text-center">
                        <p class="max-w-md text-sm text-muted-foreground">
                            The interviewer will speak {{ totalQuestions }} questions generated from your CV. Allow the microphone, then start.
                        </p>
                        <Button size="lg" class="gap-2" @click="startInterview">
                            <Play class="h-5 w-5" />
                            Start voice interview
                        </Button>
                    </div>

                    <template v-else>
                        <div class="rounded-2xl border border-border bg-secondary/40 p-5">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ questionCategory(currentQuestion) || 'Question' }}
                                <span v-if="typeof currentQuestion !== 'string' && currentQuestion?.follow_up"> · Follow-up</span>
                            </p>
                            <p class="mt-2 text-lg font-medium">{{ currentText }}</p>
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                :variant="isListening ? 'destructive' : 'default'"
                                size="lg"
                                class="h-16 w-16 flex-shrink-0 rounded-full"
                                :disabled="isSpeaking || isProcessing"
                                @click="toggleMic"
                            >
                                <Mic v-if="!isListening" class="h-6 w-6" />
                                <MicOff v-else class="h-6 w-6" />
                            </Button>
                            <div>
                                <p v-if="isSpeaking" class="text-sm text-muted-foreground">The interviewer is speaking…</p>
                                <p v-else-if="isListening" class="animate-pulse text-sm font-medium text-primary">
                                    Listening… take your time. Click I’m done when you have finished.
                                </p>
                                <p v-else-if="isProcessing" class="text-sm text-muted-foreground">Saving your answer and preparing the next question…</p>
                                <p v-else class="text-sm text-muted-foreground">Click the microphone to keep answering, then I’m done.</p>
                                <p v-if="transcribedText" class="mt-1 text-sm italic">{{ transcribedText }}</p>
                            </div>
                        </div>

                        <Button
                            size="lg"
                            :disabled="(!transcribedText && typedFallback.trim().length < 2) || isProcessing || isSpeaking"
                            @click="finishAnswering"
                        >
                            I’m done answering
                        </Button>

                        <div class="grid gap-2">
                            <textarea
                                v-model="typedFallback"
                                class="border-input bg-background min-h-[90px] w-full rounded-md border px-3 py-2 text-sm"
                                :placeholder="speechSupported ? 'Or type your answer if the microphone misses you' : 'Type your answer and submit'"
                            />
                        </div>

                        <div class="mt-auto flex items-center justify-between border-t pt-4">
                            <p class="text-xs text-muted-foreground">{{ answeredCount }} of {{ totalQuestions }} answered</p>
                            <Button variant="outline" :disabled="isSpeaking || isProcessing" @click="completeInterview">
                                <CheckCircle2 class="mr-2 h-4 w-4" />
                                End interview
                            </Button>
                        </div>
                    </template>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
