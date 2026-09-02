<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InterviewEvaluationPanel, { type InterviewEvaluation } from '@/components/InterviewEvaluationPanel.vue';
import { mockInterview } from '@/routes';
import mockInterviewRoutes from '@/routes/mock-interview';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
    session: {
        id: number;
        type: string;
        difficulty: string;
        mode: string;
        score?: number | null;
        evaluation?: InterviewEvaluation | null;
        completed_at?: string | null;
        duration_minutes?: number | null;
    };
}>();

const typeLabels: Record<string, string> = {
    technical: 'Technical',
    behavioral: 'Behavioral',
    mixed: 'Mixed',
};

const difficultyLabels: Record<string, string> = {
    beginner: 'Beginner',
    intermediate: 'Intermediate',
    advanced: 'Advanced',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mock Interview', href: mockInterview().url },
    { title: 'Results', href: mockInterviewRoutes.results(props.session.id).url },
];
</script>

<template>
    <Head title="Mock Interview Results" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Interview Results</h1>
                    <p class="text-muted-foreground mt-2">
                        {{ typeLabels[session.type] || session.type }} Interview ·
                        {{ difficultyLabels[session.difficulty] || session.difficulty }} ·
                        {{ session.mode === 'voice' ? 'Voice' : 'Text' }} mode
                    </p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="mockInterview().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Link>
                </Button>
            </div>

            <InterviewEvaluationPanel
                :evaluation="session.evaluation"
                :score="session.score"
                :show-review="false"
            />
        </div>
    </AppLayout>
</template>
