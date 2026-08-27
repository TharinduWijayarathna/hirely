<script setup lang="ts">
import PublicJobCard from '@/components/PublicJobCard.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

withDefaults(
    defineProps<{
        canRegister: boolean;
        jobs?: Array<{
            id: number;
            slug: string;
            title: string;
            location?: string | null;
            type: string;
            remote: string;
            company?: {
                name: string;
                slug?: string;
            } | null;
        }>;
        jobCount?: number;
    }>(),
    {
        canRegister: true,
        jobs: () => [],
        jobCount: 0,
    },
);

const search = ref('');

const searchJobs = () => {
    router.get('/jobs', { search: search.value || undefined });
};
</script>

<template>
    <PublicLayout title="Hirely" description="Find jobs. Apply. Interview. Hirely is a public jobs board.">
        <section class="pub-hero overflow-hidden rounded-3xl px-6 py-12 sm:px-10 sm:py-16">
            <p class="text-sm font-semibold tracking-wide text-white/80 uppercase">Jobs board</p>
            <h1 class="display mt-3 max-w-3xl text-[clamp(2.4rem,5vw,4rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                Find your next job
            </h1>
            <p class="mt-4 max-w-xl text-lg leading-8 text-white/85">
                {{ jobCount }} {{ jobCount === 1 ? 'job' : 'jobs' }} from organizations hiring on Hirely.
            </p>
            <form class="mt-8 flex max-w-2xl flex-col gap-3 sm:flex-row" @submit.prevent="searchJobs">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Job title, company, or city"
                    class="pub-field h-12 flex-1 border-0 shadow-lg"
                />
                <button type="submit" class="pub-cta h-12 shrink-0 shadow-lg">Search</button>
            </form>
        </section>

        <section class="mt-16 sm:mt-24">
            <div class="mb-10 text-center">
                <h2 class="display text-3xl font-medium tracking-tight">Hirely Pro Features</h2>
                <p class="mt-4 text-lg text-muted-foreground">Unlock advanced tools for both job seekers and organizations to optimize the hiring process.</p>
            </div>
            
            <div class="grid gap-6 md:grid-cols-3">
                <div class="pub-card flex flex-col p-8">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">ATS Optimization</h3>
                    <p class="mt-2 text-muted-foreground">Score and optimize your CV against job descriptions to bypass applicant tracking systems.</p>
                </div>
                
                <div class="pub-card flex flex-col p-8">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Mock Interviews</h3>
                    <p class="mt-2 text-muted-foreground">Practice with our conversational AI interviewer and get detailed feedback and scoring.</p>
                </div>
                
                <div class="pub-card flex flex-col p-8">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">AI Candidate Ranking</h3>
                    <p class="mt-2 text-muted-foreground">For HR, instantly identify top talent using our proprietary AI evaluation and ranking models.</p>
                </div>
            </div>
        </section>

        <section class="mt-16 sm:mt-24 space-y-4">
            <h2 class="display text-2xl font-medium tracking-tight mb-6">Latest Opportunities</h2>
            <PublicJobCard v-for="job in jobs" :key="job.id" :job="job" />
            <p v-if="!jobs.length" class="pub-card p-8 text-lg text-muted-foreground">
                No jobs posted yet.
            </p>
            <div v-if="jobCount > jobs.length" class="pt-2 text-center">
                <Link href="/jobs" class="font-semibold text-primary hover:underline">See all jobs</Link>
            </div>
        </section>
    </PublicLayout>
</template>
