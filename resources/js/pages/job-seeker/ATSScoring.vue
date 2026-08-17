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
import { computed, ref, watch } from 'vue';

interface CvExtraction {
    full_name?: string | null;
    summary?: string | null;
    skills?: string[];
    technologies?: string[];
    experience_years?: number | null;
    experience_level?: string | null;
}

interface CurrentCv {
    id: number;
    original_name: string;
    review_score?: number | null;
    status?: string;
    created_at?: string;
    extraction?: CvExtraction | null;
    review?: { summary?: string | null } | null;
}

interface JobRole {
    id: number;
    title: string;
    location?: string | null;
    type?: string | null;
    remote?: string | null;
    skills?: string[];
    company?: string | null;
    preview?: string | null;
}

interface Analysis {
    summary?: string;
    matched_skills?: string[];
    missing_skills?: string[];
    recommendations?: string[];
}

const props = defineProps<{
    cv?: CurrentCv | null;
    cvs?: CurrentCv[];
    jobs?: JobRole[];
    analyses?: Array<{
        id: number;
        score: number;
        job_description: string;
        analysis?: Analysis | null;
        created_at: string;
        job?: { title: string } | null;
        cv_document?: { original_name: string } | null;
    }>;
    quota?: PlanQuota;
}>();

const canAnalyze = computed(() => props.quota?.allowed !== false);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'ATS Scoring', href: atsScoring().url }];
const page = usePage();
const errors = computed(() => page.props.errors || {});
const cvDocumentId = ref(props.cv?.id ? String(props.cv.id) : '');
const jobId = ref('');
const jobDescription = ref('');
const analyzing = ref(false);
const latest = computed(() => props.analyses?.[0] ?? null);
const processedCvs = computed(() => props.cvs ?? (props.cv ? [props.cv] : []));

const selectedCv = computed(
    () => processedCvs.value.find((document) => String(document.id) === cvDocumentId.value) ?? props.cv ?? null,
);

const selectedJob = computed(() => props.jobs?.find((job) => String(job.id) === jobId.value) ?? null);

const cvSkills = computed(() => {
    const extraction = selectedCv.value?.extraction;
    return [...(extraction?.skills ?? []), ...(extraction?.technologies ?? [])].filter(Boolean);
});

const canSubmit = computed(
    () => canAnalyze.value && Boolean(selectedCv.value) && (Boolean(jobId.value) || jobDescription.value.trim().length >= 40),
);

watch(
    () => props.cv?.id,
    (id) => {
        if (id && !cvDocumentId.value) {
            cvDocumentId.value = String(id);
        }
    },
);

const analyze = () => {
    if (!canSubmit.value) {
        return;
    }

    analyzing.value = true;
    router.post(
        atsScoringRoutes.store().url,
        {
            cv_document_id: cvDocumentId.value || null,
            job_id: jobId.value || null,
            job_description: jobId.value ? '' : jobDescription.value,
        },
        {
            onFinish: () => {
                analyzing.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="ATS Scoring" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">ATS Scoring</h1>
                <p class="text-muted-foreground mt-2">
                    Score the CV currently uploaded in CV Review against a job role.
                </p>
                <PlanQuotaNotice class="mt-3" :quota="quota" />
                <InputError class="mt-2" :message="errors.plan" />
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Current CV from Review</CardTitle>
                        <CardDescription>Uses the resume you uploaded and analyzed in CV Review</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="selectedCv" class="space-y-4">
                            <div v-if="processedCvs.length > 1" class="grid gap-2">
                                <Label for="cv_document_id">Choose a processed CV</Label>
                                <select
                                    id="cv_document_id"
                                    v-model="cvDocumentId"
                                    class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                                >
                                    <option v-for="document in processedCvs" :key="document.id" :value="String(document.id)">
                                        {{ document.original_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="rounded-lg border p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <FileText class="text-muted-foreground mt-0.5 h-8 w-8 shrink-0" />
                                        <div>
                                            <p class="font-medium">{{ selectedCv.original_name }}</p>
                                            <p class="text-muted-foreground text-sm">
                                                {{ selectedCv.extraction?.full_name || 'Processed CV' }}
                                                <span v-if="selectedCv.extraction?.experience_level">
                                                    · {{ selectedCv.extraction.experience_level }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-primary text-xl font-bold">{{ selectedCv.review_score ?? '—' }}/100</span>
                                </div>
                                <p class="text-muted-foreground mt-3 text-sm">
                                    {{ selectedCv.review?.summary || selectedCv.extraction?.summary || 'Ready to compare with a job role.' }}
                                </p>
                                <div v-if="cvSkills.length" class="mt-3 flex flex-wrap gap-2">
                                    <span v-for="skill in cvSkills.slice(0, 8)" :key="skill" class="bg-secondary rounded px-2 py-1 text-xs">
                                        {{ skill }}
                                    </span>
                                </div>
                            </div>
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="cvReview().url">Change CV in Review</Link>
                            </Button>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 text-center">
                            <FileText class="text-muted-foreground mb-4 h-10 w-10" />
                            <p class="text-sm font-medium">No processed CV yet</p>
                            <p class="text-muted-foreground mt-1 text-sm">
                                Upload a resume in CV Review first. ATS scoring uses that file against a job role.
                            </p>
                            <Button class="mt-4" as-child>
                                <Link :href="cvReview().url">Go to CV Review</Link>
                            </Button>
                        </div>
                        <InputError :message="errors.cv" />
                        <InputError :message="errors.cv_document_id" />
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Job Role</CardTitle>
                        <CardDescription>Pick a live posting or paste a description</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-4 grid gap-2">
                            <Label for="job_id">Job role</Label>
                            <select
                                id="job_id"
                                v-model="jobId"
                                class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                            >
                                <option value="">Paste a description instead</option>
                                <option v-for="job in jobs" :key="job.id" :value="String(job.id)">
                                    {{ job.title }}{{ job.company ? ` · ${job.company}` : '' }}
                                </option>
                            </select>
                        </div>
                        <div v-if="selectedJob" class="mb-4 rounded-lg border p-4 text-sm">
                            <p class="font-medium">{{ selectedJob.title }}</p>
                            <p class="text-muted-foreground mt-1">
                                {{ selectedJob.company || 'Company' }}
                                <span v-if="selectedJob.location"> · {{ selectedJob.location }}</span>
                                <span v-if="selectedJob.type"> · {{ selectedJob.type }}</span>
                            </p>
                            <p v-if="selectedJob.preview" class="text-muted-foreground mt-2">{{ selectedJob.preview }}</p>
                            <div v-if="selectedJob.skills?.length" class="mt-3 flex flex-wrap gap-2">
                                <span v-for="skill in selectedJob.skills" :key="skill" class="bg-secondary rounded px-2 py-1 text-xs">
                                    {{ skill }}
                                </span>
                            </div>
                        </div>
                        <textarea
                            v-else
                            v-model="jobDescription"
                            class="border-input bg-background min-h-[200px] w-full rounded-md border px-3 py-2 text-sm"
                            placeholder="Paste job description here..."
                        />
                        <InputError :message="errors.job_description" />
                        <InputError :message="errors.job_id" />
                        <Button class="mt-4 w-full" :disabled="!canSubmit || analyzing" @click="analyze">
                            <Target class="mr-2 h-4 w-4" />
                            {{ analyzing ? 'Analyzing…' : 'Analyze Compatibility' }}
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
                    <CardDescription>
                        {{ latest?.job?.title || 'Latest ATS run' }}
                        <span v-if="latest?.cv_document"> · {{ latest.cv_document.original_name }}</span>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-end gap-4">
                        <div class="text-6xl font-bold">{{ latest?.score ?? '--' }}</div>
                        <div class="text-muted-foreground mb-2 text-2xl">/100</div>
                    </div>
                    <p class="text-muted-foreground mt-4 text-sm">
                        {{
                            latest?.analysis?.summary ||
                            'Use your current CV Review file and a job role to get a compatibility score'
                        }}
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
