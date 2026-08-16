<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InterviewRecordingPanel from '@/components/InterviewRecordingPanel.vue';
import InterviewEvaluationPanel, { type InterviewEvaluation } from '@/components/InterviewEvaluationPanel.vue';
import { interviews } from '@/routes';
import interviewsRoutes from '@/routes/interviews';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
    interview: {
        id: number;
        score?: number | null;
        ai_score?: number | null;
        human_score?: number | null;
        review_status?: string | null;
        evaluation?: InterviewEvaluation | null;
        job?: { title: string } | null;
        template?: { name: string } | null;
        recording_url?: string | null;
        screenshots?: Array<{ url: string; label?: string; captured_at?: string | null }>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Interviews', href: interviews().url },
    { title: 'Results', href: interviewsRoutes.show(props.interview.id).url },
];
</script>

<template>
    <Head title="Interview Results" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ interview.job?.title || 'Interview results' }}</h1>
                    <p class="text-muted-foreground mt-2">
                        {{ interview.template?.name || 'Recruitment interview' }}
                    </p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="interviews().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Link>
                </Button>
            </div>

            <InterviewRecordingPanel
                :recording-url="interview.recording_url"
                :screenshots="interview.screenshots"
            />

            <InterviewEvaluationPanel
                :evaluation="interview.evaluation"
                :score="interview.score"
                :ai-score="interview.ai_score"
                :human-score="interview.human_score"
                :review-status="interview.review_status"
            />
        </div>
    </AppLayout>
</template>
