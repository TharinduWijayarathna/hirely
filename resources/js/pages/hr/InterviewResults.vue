<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { interviewResults } from '@/routes';
import interviewResultsRoutes from '@/routes/interview-results';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

const statusFilter = ref('all');
const statusOptions = [
    { value: 'all', label: 'All' },
    { value: 'pending_review', label: 'Needs review' },
    { value: 'accepted', label: 'Accepted' },
    { value: 'edited', label: 'Edited' },
    { value: 'rejected', label: 'Rejected' },
];

const filteredInterviews = computed(() => {
    if (statusFilter.value === 'all') {
        return props.interviews || [];
    }

    return (props.interviews || []).filter((interview) => (interview.review_status || 'pending_review') === statusFilter.value);
});

const statusBadge = (status?: string | null) => `dash-badge dash-badge-${status || 'pending_review'}`;

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

            <div class="dash-chips">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    type="button"
                    class="dash-chip"
                    :class="{ 'dash-chip-active': statusFilter === option.value }"
                    @click="statusFilter = option.value"
                >
                    {{ option.label }}
                </button>
            </div>

            <div v-if="filteredInterviews.length > 0" class="space-y-2">
                <div
                    v-for="interview in filteredInterviews"
                    :key="interview.id"
                    class="dash-row flex items-start justify-between gap-4"
                >
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center gap-2">
                            <h3 class="font-medium">{{ interview.candidate?.name || 'Candidate' }}</h3>
                            <span :class="statusBadge(interview.review_status)">
                                {{ statusLabel(interview.review_status) }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ interview.job?.title || 'Interview' }} ·
                            {{ interview.template?.name || 'Template' }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Score {{ interview.score != null ? `${interview.score}/100` : '—' }}
                            <span v-if="interview.ai_score != null"> · AI {{ interview.ai_score }}</span>
                        </p>
                    </div>
                    <Button size="sm" as-child>
                        <Link :href="interviewResultsRoutes.show(interview.id).url">Open</Link>
                    </Button>
                </div>
            </div>
            <div v-else class="dash-empty">
                <ClipboardCheck class="mb-3 h-8 w-8" />
                <p class="text-sm">No interviews in this view yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
