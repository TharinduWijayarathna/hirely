<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { interviews } from '@/routes';
import interviewsRoutes from '@/routes/interviews';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, Eye, Play } from 'lucide-vue-next';

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

const statusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || colors.pending;
};
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

            <div v-if="props.interviews && props.interviews.length > 0" class="space-y-4">
                <Card v-for="interview in props.interviews" :key="interview.id" class="shadow-sm">
                    <CardHeader class="flex flex-row items-start justify-between">
                        <div>
                            <CardTitle>{{ interview.job?.title || 'Interview' }}</CardTitle>
                            <CardDescription>
                                {{ interview.template?.name || 'Recruitment interview' }} ·
                                {{ interview.difficulty }} · {{ interview.mode }}
                            </CardDescription>
                        </div>
                        <span :class="['rounded px-2 py-1 text-xs', statusColor(interview.status)]">
                            {{ interview.status.replace('_', ' ') }}
                        </span>
                    </CardHeader>
                    <CardContent class="flex items-center justify-between">
                        <p class="text-muted-foreground text-sm">
                            Assigned {{ new Date(interview.created_at).toLocaleDateString() }}
                            <span v-if="interview.score != null"> · Score {{ interview.score }}/100</span>
                        </p>
                        <Button v-if="interview.status === 'completed'" variant="outline" as-child>
                            <Link :href="interviewsRoutes.show(interview.id).url">
                                <Eye class="mr-2 h-4 w-4" />
                                View results
                            </Link>
                        </Button>
                        <Button v-else as-child>
                            <Link :href="interviewsRoutes.show(interview.id).url">
                                <Play class="mr-2 h-4 w-4" />
                                {{ interview.status === 'pending' ? 'Start' : 'Continue' }}
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
            <Card v-else class="shadow-sm">
                <CardContent class="flex flex-col items-center justify-center py-12 text-center">
                    <ClipboardList class="text-muted-foreground mb-4 h-12 w-12" />
                    <p class="text-muted-foreground text-sm">No interviews have been assigned to you yet.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
