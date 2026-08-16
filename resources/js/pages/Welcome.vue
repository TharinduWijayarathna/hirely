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

        <section class="mt-8 space-y-4">
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
