<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { interviews } from '@/routes';
import interviewsRoutes from '@/routes/interviews';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, CheckCircle2, Loader2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

type InterviewQuestion = string | { category?: string; text: string; follow_up?: boolean };

const props = defineProps<{
    interview: {
        id: number;
        difficulty: string;
        mode: string;
        status: string;
        questions?: InterviewQuestion[];
        answers?: Record<string, string>;
        job?: { title: string };
        template?: { name: string; duration_minutes?: number | null } | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Interviews', href: interviews().url },
    { title: 'Session', href: interviewsRoutes.show(props.interview.id).url },
];

const currentQuestionIndex = ref(0);
const answers = ref<Record<string, string>>({ ...(props.interview.answers || {}) });
const requestingFollowUp = ref(false);

const questionText = (question: InterviewQuestion): string => {
    return typeof question === 'string' ? question : question.text;
};

const questionCategory = (question: InterviewQuestion): string | null => {
    return typeof question === 'string' ? null : question.category || null;
};

const questions = computed(() => props.interview.questions || []);
const currentQuestion = computed(() => questions.value[currentQuestionIndex.value]);
const currentText = computed(() => (currentQuestion.value ? questionText(currentQuestion.value) : ''));
const totalQuestions = computed(() => questions.value.length);
const isFirstQuestion = computed(() => currentQuestionIndex.value === 0);
const isLastQuestion = computed(() => currentQuestionIndex.value === totalQuestions.value - 1);
const currentAnswer = computed(() => answers.value[currentText.value] || '');

watch(
    () => props.interview.answers,
    (value) => {
        answers.value = { ...(value || {}) };
    },
);

const goNext = () => {
    if (requestingFollowUp.value) {
        return;
    }

    if (currentAnswer.value.trim().length < 2) {
        currentQuestionIndex.value++;
        return;
    }

    requestingFollowUp.value = true;
    router.post(
        `/interviews/${props.interview.id}/follow-up`,
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

const completeInterview = () => {
    router.put(interviewsRoutes.update(props.interview.id).url, {
        answers: answers.value,
        status: 'completed',
    });
};
</script>

<template>
    <Head title="Interview Session" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ interview.job?.title || 'Interview' }}</h1>
                    <p class="text-muted-foreground mt-2">
                        {{ interview.template?.name || 'Recruitment interview' }} · {{ interview.difficulty }}
                    </p>
                </div>
                <span class="bg-primary/10 text-primary rounded-full px-3 py-1 text-sm font-medium">
                    Question {{ currentQuestionIndex + 1 }} of {{ totalQuestions }}
                </span>
            </div>

            <Card v-if="currentQuestion" class="shadow-sm">
                <CardHeader>
                    <CardTitle>Interview Question</CardTitle>
                    <CardDescription v-if="questionCategory(currentQuestion) || (typeof currentQuestion !== 'string' && currentQuestion.follow_up)">
                        {{ typeof currentQuestion !== 'string' && currentQuestion.follow_up ? 'Follow-up' : questionCategory(currentQuestion) }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <p class="text-lg">{{ currentText }}</p>
                    <div class="grid gap-2">
                        <Label for="answer">Your Answer</Label>
                        <textarea
                            id="answer"
                            :value="answers[currentText] || ''"
                            class="border-input bg-background min-h-[200px] w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Type your answer here..."
                            @input="answers[currentText] = ($event.target as HTMLTextAreaElement).value"
                        />
                    </div>
                    <div class="flex items-center justify-between border-t pt-4">
                        <Button variant="outline" :disabled="isFirstQuestion" @click="currentQuestionIndex--">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous
                        </Button>
                        <Button v-if="!isLastQuestion" :disabled="requestingFollowUp" @click="goNext">
                            <Loader2 v-if="requestingFollowUp" class="mr-2 h-4 w-4 animate-spin" />
                            Next
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                        <Button v-else @click="completeInterview">
                            <CheckCircle2 class="mr-2 h-4 w-4" />
                            Submit interview
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
