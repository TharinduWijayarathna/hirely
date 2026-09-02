<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { interviews } from '@/routes';
import interviewsRoutes from '@/routes/interviews';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Camera, CheckCircle2, Mic, MicOff, Play, Volume2 } from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';
import { useGoogleTts } from '@/composables/useGoogleTts';
import { useInterviewCapture } from '@/composables/useInterviewCapture';

type InterviewQuestion = string | { category?: string; text: string; follow_up?: boolean };

const props = defineProps<{
    interview: {
        id: number;
        difficulty: string;
        mode: string;
        status: string;
        questions?: InterviewQuestion[];
        answers?: Record<string, string>;
        conversation_history?: Array<{ role: string; content: string; timestamp?: string }>;
        job?: { title: string };
        template?: { name: string; duration_minutes?: number | null } | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Interviews', href: interviews().url },
    { title: 'Voice interview', href: interviewsRoutes.show(props.interview.id).url },
];

const questionText = (question: InterviewQuestion): string => {
    return typeof question === 'string' ? question : question.text;
};

const questionCategory = (question: InterviewQuestion): string | null => {
    return typeof question === 'string' ? null : question.category || null;
};

const questions = ref<InterviewQuestion[]>([...(props.interview.questions || [])]);
const answers = ref<Record<string, string>>({ ...(props.interview.answers || {}) });
const conversationHistory = ref(props.interview.conversation_history || []);
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
    `/interviews/${props.interview.id}/speech`,
);

const {
    videoRef,
    cameraReady,
    isRecording,
    screenshotCount,
    previews,
    flash,
    lastCapturedAt,
    error: cameraError,
    start: startCapture,
    takeScreenshot,
    stopAndUpload,
    flushPendingUploads,
} = useInterviewCapture({
    screenshotUrl: `/interviews/${props.interview.id}/screenshots`,
    recordingUrl: `/interviews/${props.interview.id}/recording`,
});

let recognition: any = null;
let silenceTimer: number | null = null;
let restartTimer: number | null = null;
const SILENCE_MS = 5000;
const MIN_AUTO_SUBMIT_WORDS = 12;

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
        void takeScreenshot('random');
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

    const category = currentQuestion.value ? questionCategory(currentQuestion.value) : null;
    const prefix = category ? `This is a ${category.replace('_', ' ')} question. ` : '';
    speakThenListen(`${prefix}${currentText.value}`);
};

const startInterview = async () => {
    activateTTS();

    try {
        await startCapture();
    } catch {
        cameraError.value = 'Camera and microphone access is required for this interview.';
        return;
    }

    if (!recognition) {
        initSpeechRecognition();
    }

    started.value = true;
    const firstUnanswered = questions.value.findIndex((question) => !answers.value[questionText(question)]?.trim());
    currentIndex.value = firstUnanswered === -1 ? 0 : firstUnanswered;

    const intro = `Welcome to your interview${props.interview.job?.title ? ` for ${props.interview.job.title}` : ''}. I will ask ${totalQuestions.value} questions. Please answer out loud after each one. Let's begin.`;
    playSpeech(intro, () => speakCurrentQuestion());
};

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

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

    try {
        const response = await fetch(`/interviews/${props.interview.id}/follow-up`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                question,
                answer,
                answers: answers.value,
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to save answer');
        }

        const updated = (await response.json()) as {
            questions?: InterviewQuestion[];
            answers?: Record<string, string>;
        };

        if (updated.questions) {
            questions.value = updated.questions;
        }
        if (updated.answers) {
            answers.value = { ...updated.answers };
        }

        isProcessing.value = false;

        if (isLastQuestion.value) {
            void completeInterview();
            return;
        }

        currentIndex.value += 1;
        speakCurrentQuestion();
    } catch {
        isProcessing.value = false;
        if (!isLastQuestion.value) {
            currentIndex.value += 1;
            speakCurrentQuestion();
            return;
        }
        void completeInterview();
    }
};

const completeInterview = async () => {
    stopTts();
    stopListening();
    started.value = false;
    isProcessing.value = true;

    try {
        await stopAndUpload();
        await flushPendingUploads();
    } catch {
        // Complete even if media upload fails.
    }

    router.put(interviewsRoutes.update(props.interview.id).url, {
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
    <Head title="Voice interview" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ interview.job?.title || 'Voice interview' }}</h1>
                    <p class="text-muted-foreground mt-2">
                        {{ interview.template?.name || 'Recruitment interview' }} · {{ interview.difficulty }} · voice assistant
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span v-if="started" class="dash-badge dash-badge-in_progress">
                        Question {{ Math.min(currentIndex + 1, totalQuestions) }} of {{ totalQuestions }}
                    </span>
                    <span v-if="isRecording" class="dash-badge dash-badge-rejected">Recording</span>
                    <span v-if="screenshotCount > 0" class="dash-badge dash-badge-reviewing">
                        {{ screenshotCount }} screenshots
                    </span>
                </div>
            </div>

            <div class="grid flex-1 gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
                <div class="space-y-3">
                    <div class="relative overflow-hidden rounded-2xl border border-border bg-black">
                        <video ref="videoRef" class="aspect-video w-full object-cover" autoplay muted playsinline />
                        <div
                            v-if="flash"
                            class="pointer-events-none absolute inset-0 bg-white/80 transition-opacity"
                        />
                        <div v-if="flash" class="absolute left-3 top-3 rounded-full bg-black/70 px-3 py-1 text-xs text-white">
                            Screenshot taken
                        </div>
                        <div v-else-if="isRecording" class="absolute left-3 top-3 flex items-center gap-1 rounded-full bg-red-600 px-3 py-1 text-xs text-white">
                            <Camera class="h-3 w-3" />
                            Live
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Random stills of you are taken throughout the interview and saved for HR review.
                        <span v-if="lastCapturedAt"> Last capture {{ lastCapturedAt }}.</span>
                    </p>
                    <p v-if="cameraError" class="text-sm text-destructive">{{ cameraError }}</p>
                    <p v-if="ttsError" class="text-sm text-destructive">{{ ttsError }}</p>
                    <div v-if="previews.length" class="grid grid-cols-4 gap-2">
                        <img
                            v-for="preview in previews"
                            :key="preview.url"
                            :src="preview.url"
                            :alt="preview.label"
                            class="aspect-video w-full rounded-lg border border-border object-cover"
                        />
                    </div>
                </div>

                <Card class="flex flex-1 flex-col shadow-sm">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Volume2 class="h-5 w-5" />
                            AI voice interviewer
                        </CardTitle>
                        <CardDescription>
                            The assistant asks the prepared questions out loud. Keep talking until you are finished, then click I’m done.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col space-y-5">
                        <div v-if="!started" class="flex flex-1 flex-col items-center justify-center gap-4 py-10 text-center">
                            <p class="max-w-md text-sm text-muted-foreground">
                                Allow camera and microphone. The interviewer will speak {{ totalQuestions }} questions,
                                record the session, and take random screenshots of you.
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
        </div>
    </AppLayout>
</template>
