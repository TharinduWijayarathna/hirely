<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { rankings } from '@/routes';
import rankingsRoutes from '@/routes/rankings';
import interviewResultsRoutes from '@/routes/interview-results';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ListOrdered } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type RankingSignal = {
    score?: number | null;
    available?: boolean;
    source?: string;
    status?: string | null;
};

type RankingRow = {
    application_id: number;
    position: number;
    score: number;
    rationale: string;
    status: string;
    interview_id?: number | null;
    candidate?: { id?: number; name?: string; email?: string } | null;
    signals: {
        interview: RankingSignal;
        cv: RankingSignal;
        application: RankingSignal;
    };
};

const props = defineProps<{
    jobs?: Array<{ id: number; title: string; status: string }>;
    selected_job_id?: number | null;
    rankings?: RankingRow[];
    weights?: { interview: number; cv: number; application: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rankings', href: rankings().url },
];

const selected = ref<number[]>([]);

const selectedJobId = computed(() => props.selected_job_id ?? props.jobs?.[0]?.id ?? null);

const percent = (weight?: number) => Math.round((weight ?? 0) * 100);

const toggle = (id: number) => {
    if (selected.value.includes(id)) {
        selected.value = selected.value.filter((item) => item !== id);
        return;
    }

    if (selected.value.length >= 4) {
        return;
    }

    selected.value = [...selected.value, id];
};

const changeJob = (event: Event) => {
    const value = Number((event.target as HTMLSelectElement).value);
    selected.value = [];
    router.get(rankings().url, { job_id: value }, { preserveState: false });
};

const compare = () => {
    if (!selectedJobId.value || selected.value.length < 2) {
        return;
    }

    router.get(rankingsRoutes.compare(selectedJobId.value).url, {
        applications: selected.value,
    });
};

const signalLabel = (signal?: RankingSignal) => {
    if (!signal?.available || signal.score == null) {
        return signal?.status === 'rejected' ? 'Excluded' : '—';
    }

    return `${signal.score}`;
};
</script>

<template>
    <Head title="Rankings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Candidate Rankings</h1>
                    <p class="text-muted-foreground mt-2">
                        Ordered per job from interview ({{ percent(weights?.interview) }}%),
                        CV/ATS ({{ percent(weights?.cv) }}%), and application stage
                        ({{ percent(weights?.application) }}%). Mock interview scores are not used.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <select
                        class="border-input bg-background h-9 min-w-[220px] rounded-md border px-3 text-sm"
                        :value="selectedJobId ?? ''"
                        @change="changeJob"
                    >
                        <option v-if="!jobs?.length" value="">No jobs yet</option>
                        <option v-for="job in jobs" :key="job.id" :value="job.id">
                            {{ job.title }}
                        </option>
                    </select>
                    <Button :disabled="selected.length < 2" @click="compare">
                        Compare ({{ selected.length }})
                    </Button>
                </div>
            </div>

            <div v-if="rankings && rankings.length > 0" class="space-y-3">
                <Card v-for="row in rankings" :key="row.application_id" class="shadow-sm">
                    <CardHeader class="flex flex-row items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <input
                                type="checkbox"
                                class="mt-1"
                                :checked="selected.includes(row.application_id)"
                                @change="toggle(row.application_id)"
                            />
                            <div>
                                <CardTitle>
                                    #{{ row.position }} · {{ row.candidate?.name || 'Candidate' }}
                                </CardTitle>
                                <CardDescription>
                                    {{ row.candidate?.email }} · {{ row.status.replace('_', ' ') }}
                                </CardDescription>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-semibold">{{ row.score }}</p>
                            <p class="text-muted-foreground text-xs">composite / 100</p>
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <p class="text-muted-foreground text-sm">{{ row.rationale }}</p>
                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <span>Interview {{ signalLabel(row.signals.interview) }}</span>
                            <span>CV/ATS {{ signalLabel(row.signals.cv) }}</span>
                            <span>Application {{ signalLabel(row.signals.application) }}</span>
                            <Button
                                v-if="row.interview_id"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="interviewResultsRoutes.show(row.interview_id).url">
                                    Interview
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <Card v-else class="shadow-sm">
                <CardContent class="flex flex-col items-center justify-center py-12 text-center">
                    <ListOrdered class="text-muted-foreground mb-4 h-12 w-12" />
                    <p class="text-muted-foreground text-sm">
                        {{ jobs?.length ? 'No applications for this job yet.' : 'Post a job to rank candidates.' }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
