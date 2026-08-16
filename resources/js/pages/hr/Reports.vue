<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { reports } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { computed } from 'vue';

type FunnelItem = { status: string; count: number };
type StageItem = { status: string; count: number; avg_days?: number | null };
type Bucket = { label: string; count: number };

const props = defineProps<{
    jobs?: Array<{ id: number; title: string; status: string }>;
    selected_job_id?: number | null;
    funnel?: FunnelItem[];
    time_in_stage?: StageItem[];
    interview_volume?: {
        assigned: number;
        completed: number;
        pending_review: number;
        this_month: number;
        avg_duration_minutes?: number | null;
        avg_score?: number | null;
    };
    score_distribution?: {
        interview: Bucket[];
        ranking: Bucket[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: reports().url },
];

const funnelTotal = (props.funnel || []).reduce((sum, item) => sum + item.count, 0);

const barWidth = (count: number, total: number) => {
    if (total === 0) {
        return '0%';
    }

    return `${Math.max(4, Math.round((count / total) * 100))}%`;
};

const changeJob = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;
    router.get(reports().url, value ? { job_id: value } : {}, { preserveState: false });
};

const interviewBuckets = props.score_distribution?.interview || [];
const rankingBuckets = props.score_distribution?.ranking || [];
const interviewMax = Math.max(1, ...interviewBuckets.map((item) => item.count));
const rankingMax = Math.max(1, ...rankingBuckets.map((item) => item.count));

const exportUrl = computed(() => {
    const params = props.selected_job_id ? `?job_id=${props.selected_job_id}` : '';
    return `/reports/export${params}`;
});
</script>

<template>
    <Head title="Recruitment Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Recruitment Reports</h1>
                    <p class="text-muted-foreground mt-2">
                        Funnel, time since applied, interview volume, and score distributions.
                        Time in stage is days since applied for the current status.
                    </p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <select
                        class="border-input bg-background h-9 min-w-[220px] rounded-md border px-3 text-sm"
                        :value="selected_job_id ?? ''"
                        @change="changeJob"
                    >
                        <option value="">All jobs</option>
                        <option v-for="job in jobs" :key="job.id" :value="job.id">{{ job.title }}</option>
                    </select>
                    <Button as-child variant="outline">
                        <a :href="exportUrl">
                            <Download class="mr-2 h-4 w-4" />
                            Download CSV
                        </a>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardDescription>Assigned interviews</CardDescription>
                        <CardTitle class="text-3xl">{{ interview_volume?.assigned || 0 }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardDescription>Completed</CardDescription>
                        <CardTitle class="text-3xl">{{ interview_volume?.completed || 0 }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardDescription>Pending review</CardDescription>
                        <CardTitle class="text-3xl">{{ interview_volume?.pending_review || 0 }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardDescription>Avg interview score</CardDescription>
                        <CardTitle class="text-3xl">{{ interview_volume?.avg_score ?? '—' }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Hiring funnel</CardTitle>
                        <CardDescription>{{ funnelTotal }} applications</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-for="item in funnel" :key="item.status" class="flex items-center gap-3 text-sm">
                            <span class="w-28 capitalize">{{ item.status.replace('_', ' ') }}</span>
                            <div class="bg-muted h-2 flex-1 overflow-hidden rounded">
                                <div class="bg-primary h-2" :style="{ width: barWidth(item.count, funnelTotal) }" />
                            </div>
                            <span class="w-8 text-right">{{ item.count }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Time in current stage</CardTitle>
                        <CardDescription>Average days since the candidate applied</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-for="item in time_in_stage" :key="item.status" class="flex items-center justify-between text-sm">
                            <span class="capitalize">{{ item.status.replace('_', ' ') }}</span>
                            <span>{{ item.count }} · {{ item.avg_days == null ? '—' : `${item.avg_days}d` }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Interview scores</CardTitle>
                        <CardDescription>Usable recruitment scores only (rejected reviews excluded)</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-for="item in interviewBuckets" :key="item.label" class="flex items-center gap-3 text-sm">
                            <span class="w-16">{{ item.label }}</span>
                            <div class="bg-muted h-2 flex-1 overflow-hidden rounded">
                                <div class="bg-primary h-2" :style="{ width: barWidth(item.count, interviewMax) }" />
                            </div>
                            <span class="w-8 text-right">{{ item.count }}</span>
                        </div>
                    </CardContent>
                </Card>
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Ranking scores</CardTitle>
                        <CardDescription>Composite scores last computed on Rankings</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-for="item in rankingBuckets" :key="item.label" class="flex items-center gap-3 text-sm">
                            <span class="w-16">{{ item.label }}</span>
                            <div class="bg-muted h-2 flex-1 overflow-hidden rounded">
                                <div class="bg-primary h-2" :style="{ width: barWidth(item.count, rankingMax) }" />
                            </div>
                            <span class="w-8 text-right">{{ item.count }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
