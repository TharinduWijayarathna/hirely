<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { interviewResults } from '@/routes';
import interviewResultsRoutes from '@/routes/interview-results';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardCheck } from 'lucide-vue-next';

const props = defineProps<{
    interviews?: Array<{
        id: number;
        score?: number | null;
        ai_score?: number | null;
        review_status?: string | null;
        completed_at?: string | null;
        job?: { title: string } | null;
        candidate?: { name: string; email: string } | null;
        template?: { name: string } | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Interview Results',
        href: interviewResults().url,
    },
];

const statusColor = (status?: string | null) => {
    const colors: Record<string, string> = {
        pending_review: 'bg-yellow-100 text-yellow-800',
        accepted: 'bg-green-100 text-green-800',
        edited: 'bg-blue-100 text-blue-800',
        rejected: 'bg-red-100 text-red-800',
    };

    return colors[status || 'pending_review'] || colors.pending_review;
};

const statusLabel = (status?: string | null) => {
    return (status || 'pending_review').replace('_', ' ');
};
</script>

<template>
    <Head title="Interview Results" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Interview Results</h1>
                <p class="text-muted-foreground mt-2">
                    Review completed interviews, inspect explainable scores, and accept or override AI evaluations.
                </p>
            </div>

            <div v-if="props.interviews && props.interviews.length > 0" class="space-y-4">
                <Card v-for="interview in props.interviews" :key="interview.id" class="shadow-sm">
                    <CardHeader class="flex flex-row items-start justify-between">
                        <div>
                            <CardTitle>{{ interview.candidate?.name || 'Candidate' }}</CardTitle>
                            <CardDescription>
                                {{ interview.job?.title || 'Interview' }} ·
                                {{ interview.template?.name || 'Template' }}
                            </CardDescription>
                        </div>
                        <span :class="['rounded px-2 py-1 text-xs', statusColor(interview.review_status)]">
                            {{ statusLabel(interview.review_status) }}
                        </span>
                    </CardHeader>
                    <CardContent class="flex items-center justify-between">
                        <p class="text-muted-foreground text-sm">
                            Score {{ interview.score != null ? `${interview.score}/100` : '—' }}
                            <span v-if="interview.ai_score != null"> · AI {{ interview.ai_score }}</span>
                        </p>
                        <Button as-child>
                            <Link :href="interviewResultsRoutes.show(interview.id).url">Open</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
            <Card v-else class="shadow-sm">
                <CardContent class="flex flex-col items-center justify-center py-12 text-center">
                    <ClipboardCheck class="text-muted-foreground mb-4 h-12 w-12" />
                    <p class="text-muted-foreground text-sm">No completed interviews yet.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
