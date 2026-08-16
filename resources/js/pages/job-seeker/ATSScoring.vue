<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { atsScoring } from '@/routes';
import atsScoringRoutes from '@/routes/ats-scoring';
import { cvReview } from '@/routes';
import InputError from '@/components/InputError.vue';
import PlanQuotaNotice from '@/components/PlanQuotaNotice.vue';
import { type PlanQuota } from '@/types/plan-quota';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { TrendingUp, FileText, Target } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Analysis {
    summary?: string;
    matched_skills?: string[];
    missing_skills?: string[];
    recommendations?: string[];
}

const props = defineProps<{
    cv?: { id: number; original_name: string; review_score?: number | null } | null;
    jobs?: Array<{ id: number; title: string }>;
    analyses?: Array<{
        id: number;
        score: number;
        job_description: string;
        analysis?: Analysis | null;
        created_at: string;
        job?: { title: string } | null;
    }>;
    quota?: PlanQuota;
}>();

const canAnalyze = computed(() => props.quota?.allowed !== false);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'ATS Scoring', href: atsScoring().url }];
const page = usePage();
const errors = computed(() => page.props.errors || {});
const jobId = ref('');
const jobDescription = ref('');
const latest = computed(() => props.analyses?.[0] ?? null);

const analyze = () => {
    if (!canAnalyze.value) {
        return;
    }

    router.post(atsScoringRoutes.store().url, {
        job_id: jobId.value || null,
        job_description: jobDescription.value,
    });
};
</script>

<template>
    <Head title="ATS Scoring" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">ATS Scoring</h1>
                <p class="text-muted-foreground mt-2">
                    Compare your analyzed CV against a job description.
                </p>
                <PlanQuotaNotice class="mt-3" :quota="quota" />
                <InputError class="mt-2" :message="errors.plan" />
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Current resume</CardTitle>
                        <CardDescription>Uses your latest processed CV</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-8">
                            <FileText class="text-muted-foreground mb-4 h-10 w-10" />
                            <p v-if="cv" class="text-sm">{{ cv.original_name }}</p>
                            <p v-else class="text-muted-foreground text-sm">No processed CV yet</p>
                            <Button variant="outline" size="sm" class="mt-4" as-child>
                                <Link :href="cvReview().url">Upload CV</Link>
                            </Button>
                        </div>
                        <InputError class="mt-2" :message="errors.cv" />
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Job Description</CardTitle>
                        <CardDescription>Pick a live posting or paste a description</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-4 grid gap-2">
                            <Label for="job_id">Job posting (optional)</Label>
                            <select
                                id="job_id"
                                v-model="jobId"
                                class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                            >
                                <option value="">Paste a description instead</option>
                                <option v-for="job in jobs" :key="job.id" :value="job.id">{{ job.title }}</option>
                            </select>
                        </div>
                        <textarea
                            v-model="jobDescription"
                            class="border-input bg-background min-h-[200px] w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Paste job description here..."
                            :disabled="Boolean(jobId)"
                        />
                        <InputError :message="errors.job_description" />
                        <Button class="mt-4 w-full" :disabled="!canAnalyze || !cv" @click="analyze">
                            <Target class="mr-2 h-4 w-4" />
                            Analyze Compatibility
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Card class="from-primary/5 to-primary/10 shadow-sm bg-gradient-to-br">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrendingUp class="h-5 w-5" />
                        Compatibility Score
                    </CardTitle>
                    <CardDescription>{{ latest?.job?.title || 'Latest ATS run' }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-end gap-4">
                        <div class="text-6xl font-bold">{{ latest?.score ?? '--' }}</div>
                        <div class="text-muted-foreground mb-2 text-2xl">/100</div>
                    </div>
                    <p class="text-muted-foreground mt-4 text-sm">
                        {{ latest?.analysis?.summary || 'Upload a resume and job description to get your compatibility score' }}
                    </p>
                    <div v-if="latest" class="mt-6 grid gap-4 md:grid-cols-2">
                        <div>
                            <h3 class="mb-2 text-sm font-semibold">Matched</h3>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="skill in latest.analysis?.matched_skills || []"
                                    :key="skill"
                                    class="rounded bg-green-100 px-2 py-1 text-xs text-green-800"
                                >
                                    {{ skill }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <h3 class="mb-2 text-sm font-semibold">Missing</h3>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="skill in latest.analysis?.missing_skills || []"
                                    :key="skill"
                                    class="rounded bg-red-100 px-2 py-1 text-xs text-red-800"
                                >
                                    {{ skill }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
