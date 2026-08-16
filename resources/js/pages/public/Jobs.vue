<script setup lang="ts">
import PublicJobCard from '@/components/PublicJobCard.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

type PublicJob = {
    id: number;
    slug: string;
    title: string;
    description: string;
    location?: string | null;
    type: string;
    remote: string;
    company?: {
        name: string;
        slug?: string;
    } | null;
};

const props = defineProps<{
    jobs: {
        data: PublicJob[];
        links?: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters?: {
        search?: string;
        company?: string;
    };
    companies?: Array<{
        id: number;
        name: string;
        slug: string;
    }>;
}>();

const search = ref(props.filters?.search || '');
const company = ref(props.filters?.company || '');

const applyFilters = () => {
    router.get(
        '/jobs',
        {
            search: search.value || undefined,
            company: company.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <PublicLayout title="Jobs" description="Find jobs on Hirely.">
        <section class="pub-hero rounded-3xl px-6 py-10 sm:px-10">
            <h1 class="display text-[clamp(2.2rem,4.5vw,3.6rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                Jobs
            </h1>
            <p class="mt-3 max-w-2xl text-lg leading-8 text-white/85">
                Search live openings and apply from the job page.
            </p>
            <form class="mt-8 grid gap-3 md:grid-cols-[1fr_16rem_auto]" @submit.prevent="applyFilters">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search title, company, or location"
                    class="pub-field border-0"
                />
                <select v-model="company" class="pub-field border-0">
                    <option value="">All organizations</option>
                    <option v-for="item in companies" :key="item.id" :value="item.slug">
                        {{ item.name }}
                    </option>
                </select>
                <button type="submit" class="pub-cta">Filter</button>
            </form>
        </section>

        <section v-if="jobs.data.length" class="mt-8 space-y-4">
            <PublicJobCard
                v-for="job in jobs.data"
                :key="job.id"
                :job="job"
                apply-label="View and apply"
            />
        </section>
        <p v-else class="pub-card mt-8 p-8 text-lg text-muted-foreground">
            No live postings match that filter.
        </p>

        <nav v-if="jobs.links && jobs.links.length > 3" class="mt-8 flex flex-wrap gap-2">
            <button
                v-for="(link, index) in jobs.links"
                :key="index"
                type="button"
                class="pub-chip disabled:opacity-40"
                :class="link.active ? 'bg-primary text-primary-foreground' : ''"
                :disabled="!link.url || link.active"
                @click="link.url && router.get(link.url)"
                v-html="link.label"
            />
        </nav>
    </PublicLayout>
</template>
