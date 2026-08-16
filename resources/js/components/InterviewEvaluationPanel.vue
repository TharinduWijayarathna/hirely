<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export type EvaluationAnswer = {
    question: string;
    category?: string;
    score: number;
    feedback: string;
    evidence?: string;
    answer?: string;
};

export type EvaluationDimension = {
    name: string;
    score: number;
    weight?: number;
    evidence?: string;
    comment?: string;
};

export type InterviewEvaluation = {
    overall_score?: number;
    rationale?: string;
    confidence?: number;
    strengths?: string[];
    weaknesses?: string[];
    dimensions?: EvaluationDimension[];
    answers?: EvaluationAnswer[];
};

defineProps<{
    evaluation?: InterviewEvaluation | null;
    score?: number | null;
    aiScore?: number | null;
    humanScore?: number | null;
    reviewStatus?: string | null;
}>();

const reviewLabel = (status?: string | null) => {
    const labels: Record<string, string> = {
        pending_review: 'Pending HR review',
        accepted: 'HR accepted AI score',
        edited: 'HR adjusted score',
        rejected: 'HR rejected AI score',
    };

    return status ? labels[status] || status.replace('_', ' ') : 'Not reviewed';
};
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <Card class="shadow-sm">
                <CardHeader>
                    <CardDescription>Effective score</CardDescription>
                    <CardTitle class="text-3xl">{{ score != null ? `${score}/100` : '—' }}</CardTitle>
                </CardHeader>
            </Card>
            <Card class="shadow-sm">
                <CardHeader>
                    <CardDescription>AI score</CardDescription>
                    <CardTitle class="text-3xl">{{ aiScore != null ? `${aiScore}/100` : '—' }}</CardTitle>
                </CardHeader>
            </Card>
            <Card class="shadow-sm">
                <CardHeader>
                    <CardDescription>Review</CardDescription>
                    <CardTitle class="text-lg">{{ reviewLabel(reviewStatus) }}</CardTitle>
                    <CardDescription v-if="humanScore != null">Human score {{ humanScore }}/100</CardDescription>
                </CardHeader>
            </Card>
        </div>

        <Card v-if="evaluation" class="shadow-sm">
            <CardHeader>
                <CardTitle>Summary</CardTitle>
                <CardDescription v-if="evaluation.confidence != null">
                    Model confidence {{ Math.round(evaluation.confidence * 100) }}%
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <p class="text-sm leading-relaxed">{{ evaluation.rationale || 'No rationale recorded.' }}</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-semibold">Strengths</h3>
                        <ul v-if="evaluation.strengths?.length" class="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                            <li v-for="item in evaluation.strengths" :key="item">{{ item }}</li>
                        </ul>
                        <p v-else class="text-muted-foreground text-sm">None recorded.</p>
                    </div>
                    <div>
                        <h3 class="mb-2 text-sm font-semibold">Weaknesses</h3>
                        <ul v-if="evaluation.weaknesses?.length" class="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                            <li v-for="item in evaluation.weaknesses" :key="item">{{ item }}</li>
                        </ul>
                        <p v-else class="text-muted-foreground text-sm">None recorded.</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-if="evaluation?.dimensions?.length" class="shadow-sm">
            <CardHeader>
                <CardTitle>Criteria</CardTitle>
                <CardDescription>Dimension scores with evidence from the answers.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-for="dimension in evaluation.dimensions"
                    :key="dimension.name"
                    class="rounded-md border p-4"
                >
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-medium">{{ dimension.name }}</h3>
                        <span class="text-sm font-semibold">{{ dimension.score }}/100</span>
                    </div>
                    <p v-if="dimension.comment" class="text-muted-foreground mt-2 text-sm">{{ dimension.comment }}</p>
                    <p v-if="dimension.evidence" class="mt-2 text-sm italic">“{{ dimension.evidence }}”</p>
                </div>
            </CardContent>
        </Card>

        <Card v-if="evaluation?.answers?.length" class="shadow-sm">
            <CardHeader>
                <CardTitle>Answers</CardTitle>
                <CardDescription>Per-question feedback and quoted evidence.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-for="(answer, index) in evaluation.answers"
                    :key="`${index}-${answer.question}`"
                    class="rounded-md border p-4"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-muted-foreground text-xs uppercase">
                                {{ answer.category || 'question' }} {{ index + 1 }}
                            </p>
                            <h3 class="mt-1 font-medium">{{ answer.question }}</h3>
                        </div>
                        <span class="text-sm font-semibold">{{ answer.score }}/100</span>
                    </div>
                    <p v-if="answer.answer" class="mt-3 text-sm">{{ answer.answer }}</p>
                    <p class="text-muted-foreground mt-2 text-sm">{{ answer.feedback }}</p>
                    <p v-if="answer.evidence" class="mt-2 text-sm italic">“{{ answer.evidence }}”</p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
