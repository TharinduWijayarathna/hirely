<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { mockInterview } from '@/routes';
import mockInterviewRoutes from '@/routes/mock-interview';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CheckCircle2, ArrowRight, ArrowLeft, Mic, Loader2 } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

type InterviewQuestion = string | { category?: string; text: string; follow_up?: boolean };

const props = defineProps<{
    session?: {
        id: number;
        type: string;
        difficulty: string;
        status: string;
        questions?: InterviewQuestion[];
        answers?: Record<string, string>;
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
        title: 'Interview Session',
        href: mockInterviewRoutes.session(props.session?.id || 0).url,
    },
];

const page = usePage();
const errors = computed(() => page.props.errors || {});

const currentQuestionIndex = ref(0);
const answers = ref<Record<string, string>>({ ...(props.session?.answers || {}) });
const requestingFollowUp = ref(false);

const questionText = (question?: InterviewQuestion): string => {
    if (!question) {
        return '';
    }

    return typeof question === 'string' ? question : question.text;
};

const questionCategory = (question?: InterviewQuestion): string | null => {
    if (!question || typeof question === 'string') {
        return null;
    }

    return question.category || null;
};

const questions = computed(() => props.session?.questions || []);

const currentQuestion = computed(() => questions.value[currentQuestionIndex.value]);
const currentText = computed(() => questionText(currentQuestion.value));

const totalQuestions = computed(() => questions.value.length);

const isFirstQuestion = computed(() => currentQuestionIndex.value === 0);
const isLastQuestion = computed(() => currentQuestionIndex.value === totalQuestions.value - 1);
const currentAnswer = computed(() => answers.value[currentText.value] || '');
const isFollowUp = computed(() => typeof currentQuestion.value !== 'string' && !!currentQuestion.value?.follow_up);

watch(
    () => props.session?.answers,
    (value) => {
        answers.value = { ...(value || {}) };
    },
);

const getTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        technical: 'Technical',
        behavioral: 'Behavioral',
        mixed: 'Mixed',
    };
    return labels[type] || type;
};

const getDifficultyLabel = (difficulty: string) => {
    const labels: Record<string, string> = {
        beginner: 'Beginner',
        intermediate: 'Intermediate',
        advanced: 'Advanced',
    };
    return labels[difficulty] || difficulty;
};

const nextQuestion = () => {
    if (requestingFollowUp.value || currentQuestionIndex.value >= totalQuestions.value - 1) {
        return;
    }

    if (currentAnswer.value.trim().length < 2) {
        currentQuestionIndex.value++;
        return;
    }

    requestingFollowUp.value = true;
    router.post(
        `/mock-interview/${props.session?.id}/follow-up`,
        {
            question: currentText.value,
            answer: currentAnswer.value,
            answers: answers.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                requestingFollowUp.value = false;
                if (currentQuestionIndex.value < questions.value.length - 1) {
                    currentQuestionIndex.value++;
                }
            },
        },
    );
};

const previousQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
    }
};

const saveAnswer = () => {
    if (!currentText.value) return;
    answers.value[currentText.value] = answers.value[currentText.value] || '';
};

const completeInterview = () => {
    router.put(
        mockInterviewRoutes.update(props.session?.id || 0).url,
        {
            answers: answers.value,
            status: 'completed',
        },
        {
            onSuccess: () => {
                router.visit(mockInterview().url);
            },
        }
    );
};
</script>

<template>
    <Head title="Mock Interview Session" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Session Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ getTypeLabel(session?.type || '') }} Interview
                    </h1>
                    <p class="text-muted-foreground mt-2">
                        {{ getDifficultyLabel(session?.difficulty || '') }} · {{ totalQuestions }} questions from your CV
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-sm font-medium">
                        Question {{ currentQuestionIndex + 1 }} of {{ totalQuestions }}
                    </span>
                </div>
            </div>

            <!-- Interview Card -->
            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Mic class="h-5 w-5" />
                        Interview Question
                    </CardTitle>
                    <CardDescription>
                        {{ isFollowUp ? 'Follow-up based on your previous answer' : 'These questions were generated from your CV in one pass' }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Current Question -->
                    <div>
                        <Label class="mb-2 block text-base font-semibold">
                            {{ questionCategory(currentQuestion) || 'Question' }}
                            <span v-if="isFollowUp"> · Follow-up</span>
                        </Label>
                        <p class="text-lg">{{ currentText }}</p>
                    </div>

                    <!-- Answer Input -->
                    <div class="grid gap-2">
                        <Label for="answer">Your Answer</Label>
                        <textarea
                            id="answer"
                            :value="answers[currentText] || ''"
                            @input="answers[currentText] = ($event.target as HTMLTextAreaElement).value"
                            @blur="saveAnswer"
                            class="w-full min-h-[200px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Type your answer here..."
                        />
                        <p class="text-xs text-muted-foreground">
                            Your answer is automatically saved as you type
                        </p>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between pt-4 border-t">
                        <Button
                            variant="outline"
                            :disabled="isFirstQuestion"
                            @click="previousQuestion"
                        >
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous
                        </Button>

                        <div class="flex gap-2">
                            <span
                                v-for="(question, index) in questions"
                                :key="index"
                                class="h-2 w-2 rounded-full"
                                :class="index === currentQuestionIndex ? 'bg-primary' : index in answers ? 'bg-green-500' : 'bg-muted'"
                            />
                        </div>

                        <Button
                            v-if="!isLastQuestion"
                            :disabled="requestingFollowUp"
                            @click="nextQuestion"
                        >
                            <Loader2 v-if="requestingFollowUp" class="mr-2 h-4 w-4 animate-spin" />
                            Next
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                        <Button
                            v-else
                            @click="completeInterview"
                        >
                            <CheckCircle2 class="mr-2 h-4 w-4" />
                            Complete Interview
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Progress Summary -->
            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>Progress</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span>Questions Answered</span>
                            <span class="font-medium">{{ Object.keys(answers).length }} / {{ totalQuestions }}</span>
                        </div>
                        <div class="w-full bg-secondary rounded-full h-2">
                            <div
                                class="bg-primary h-2 rounded-full transition-all"
                                :style="{ width: `${(Object.keys(answers).length / totalQuestions) * 100}%` }"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

