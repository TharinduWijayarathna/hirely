<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { rankings } from '@/routes';
import interviewResultsRoutes from '@/routes/interview-results';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

type Dimension = {
    score?: number | null;
    evidence?: string | null;
    comment?: string | null;
};

type Candidate = {
    application_id: number;
    position: number;
    score: number;
    rationale: string;
    status: string;
    interview_id?: number | null;
    candidate?: { name?: string; email?: string } | null;
    skills?: string[];
    experience_level?: string | null;
    experience_years?: number | null;
    summary?: string | null;
    education?: Array<{ institution?: string; degree?: string }>;
    strengths?: string[];
    weaknesses?: string[];
    dimensions?: Record<string, Dimension>;
    cover_letter?: string | null;
    signals: {
        interview: { score?: number | null; available?: boolean };
        cv: { score?: number | null; available?: boolean; source?: string };
        application: { score?: number | null };
    };
};

const props = defineProps<{
    job: { id: number; title: string };
    candidates: Candidate[];
    criteria: string[];
    weights?: { interview: number; cv: number; application: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rankings', href: rankings({ query: { job_id: props.job.id } }).url },
    { title: 'Compare', href: '#' },
];

const dimensionScore = (candidate: Candidate, name: string) => {
    const value = candidate.dimensions?.[name]?.score;
    return value == null ? '—' : value;
};

const signal = (value?: number | null) => (value == null ? '—' : value);
</script>

<template>
    <Head :title="`Compare · ${job.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Compare candidates</h1>
                    <p class="text-muted-foreground mt-2">
                        {{ job.title }} · shared interview criteria, CV signals, and ranking scores
                    </p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="rankings({ query: { job_id: job.id } }).url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to ranking
                    </Link>
                </Button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <th class="text-muted-foreground w-48 border-b p-3 text-left font-medium">Signal</th>
                            <th
                                v-for="person in candidates"
                                :key="person.application_id"
                                class="border-b p-3 text-left"
                            >
                                <p class="font-semibold">#{{ person.position }} {{ person.candidate?.name }}</p>
                                <p class="text-muted-foreground font-normal">{{ person.candidate?.email }}</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border-b p-3 font-medium">Composite</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-score`" class="border-b p-3 text-lg font-semibold">
                                {{ person.score }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border-b p-3">Status</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-status`" class="border-b p-3 capitalize">
                                {{ person.status.replace('_', ' ') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border-b p-3">Interview</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-int`" class="border-b p-3">
                                {{ signal(person.signals.interview.score) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border-b p-3">CV / ATS</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-cv`" class="border-b p-3">
                                {{ signal(person.signals.cv.score) }}
                                <span v-if="person.signals.cv.source === 'ats'" class="text-muted-foreground"> (ATS)</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="border-b p-3">Application</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-app`" class="border-b p-3">
                                {{ signal(person.signals.application.score) }}
                            </td>
                        </tr>
                        <tr v-for="name in criteria" :key="name">
                            <td class="border-b p-3">{{ name }}</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-${name}`" class="border-b p-3">
                                {{ dimensionScore(person, name) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border-b p-3">Experience</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-exp`" class="border-b p-3">
                                {{ person.experience_level || '—' }}
                                <span v-if="person.experience_years"> · {{ person.experience_years }} yrs</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top border-b p-3">Skills</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-skills`" class="align-top border-b p-3">
                                {{ person.skills?.length ? person.skills.join(', ') : '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top border-b p-3">Strengths</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-str`" class="align-top border-b p-3">
                                {{ person.strengths?.length ? person.strengths.join('; ') : '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top border-b p-3">Weaknesses</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-wk`" class="align-top border-b p-3">
                                {{ person.weaknesses?.length ? person.weaknesses.join('; ') : '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top p-3">Why this rank</td>
                            <td v-for="person in candidates" :key="`${person.application_id}-why`" class="text-muted-foreground align-top p-3">
                                {{ person.rationale }}
                                <div v-if="person.interview_id" class="mt-2">
                                    <Link
                                        class="text-primary underline"
                                        :href="interviewResultsRoutes.show(person.interview_id).url"
                                    >
                                        Open interview
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Card v-for="person in candidates" :key="`${person.application_id}-summary`" class="shadow-sm">
                    <CardHeader>
                        <CardTitle>{{ person.candidate?.name }}</CardTitle>
                        <CardDescription>{{ person.summary || 'No CV summary' }}</CardDescription>
                    </CardHeader>
                    <CardContent class="text-muted-foreground text-sm">
                        {{ person.cover_letter || 'No cover letter.' }}
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
