<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { interviews } from '@/routes';
import interviewsRoutes from '@/routes/interviews';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, Eye, Play } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    interviews?: Array<{
        id: number;
        status: string;
        difficulty: string;
        mode: string;
        score?: number | null;
        created_at: string;
        job?: { title: string };
        template?: { name: string } | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Interviews',
        href: interviews().url,
    },
];

const statusFilter = ref('all');
const statusOptions = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'completed', label: 'Completed' },
];

const filteredInterviews = computed(() => {
    if (statusFilter.value === 'all') {
        return props.interviews || [];
    }

    return (props.interviews || []).filter((interview) => interview.status === statusFilter.value);
});

const statusBadge = (status: string) => `dash-badge dash-badge-${status}`;
</script>

<template>
    <Head title="Interviews" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Assigned Interviews</h1>
                <p class="text-muted-foreground mt-2">
                    Complete interviews assigned by recruiters for jobs you applied to.
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
                            <h3 class="font-medium">{{ interview.job?.title || 'Interview' }}</h3>
                            <span :class="statusBadge(interview.status)">
                                {{ interview.status.replace('_', ' ') }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ interview.template?.name || 'Recruitment interview' }} ·
                            {{ interview.difficulty }} · voice assistant
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Assigned {{ new Date(interview.created_at).toLocaleDateString() }}
                            <span v-if="interview.score != null"> · Score {{ interview.score }}/100</span>
                        </p>
                    </div>
                    <Button v-if="interview.status === 'completed'" variant="outline" size="sm" as-child>
                        <Link :href="interviewsRoutes.show(interview.id).url">
                            <Eye class="h-4 w-4" />
                            Results
                        </Link>
                    </Button>
                    <Button v-else size="sm" as-child>
                        <Link :href="interviewsRoutes.show(interview.id).url">
                            <Play class="h-4 w-4" />
                            {{ interview.status === 'pending' ? 'Start' : 'Continue' }}
                        </Link>
                    </Button>
                </div>
            </div>
            <div v-else class="dash-empty">
                <ClipboardList class="mb-3 h-8 w-8" />
                <p class="text-sm">No interviews in this view yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
