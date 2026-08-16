<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Link, router } from '@inertiajs/vue3';
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

const typeLabel = (type: string) =>
    type.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
</script>

<template>
    <PublicLayout
        title="Jobs"
        description="Find jobs on Hirely."
    >
        <section class="px-6 py-12 sm:px-10">
            <h1 class="display mt-3 max-w-3xl text-[clamp(2.2rem,4.5vw,3.8rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                Jobs
            </h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 opacity-80">
                Search live openings. Apply from the job page. Interviews are assigned by the organization after you apply.
            </p>
        </section>

        <section class="rule-t px-6 py-8 sm:px-10">
            <form class="grid gap-4 md:grid-cols-[1fr_16rem_auto]" @submit.prevent="applyFilters">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search title, company, or location"
                    class="h-12 border border-current/20 bg-transparent px-4"
                />
                <select v-model="company" class="h-12 border border-current/20 bg-transparent px-3">
                    <option value="">All organizations</option>
                    <option v-for="item in companies" :key="item.id" :value="item.slug">
                        {{ item.name }}
                    </option>
                </select>
                <button type="submit" class="cta h-12 px-6">Filter</button>
            </form>
        </section>

        <section v-if="jobs.data.length" class="rule-t">
            <article
                v-for="job in jobs.data"
                :key="job.id"
                class="rule-t grid gap-4 px-6 py-10 sm:px-10 lg:grid-cols-12 lg:items-end"
            >
                <div class="lg:col-span-8">
                    <p v-if="job.company" class="text-sm tracking-wide uppercase opacity-70">
                        <Link
                            v-if="job.company.slug"
                            :href="`/organization/${job.company.slug}`"
                            class="hover:underline"
                        >
                            {{ job.company.name }}
                        </Link>
                        <template v-else>{{ job.company.name }}</template>
                    </p>
                    <h2 class="display mt-2 text-3xl leading-tight">{{ job.title }}</h2>
                    <p class="mt-3 max-w-2xl leading-7 opacity-80 line-clamp-2">{{ job.description }}</p>
                    <p class="mt-4 text-sm opacity-70">
                        {{ job.location || 'Location open' }} · {{ typeLabel(job.type) }} ·
                        {{ job.remote.replaceAll('_', ' ') }}
                    </p>
                </div>
                <div class="lg:col-span-4 lg:text-right">
                    <Link :href="`/jobs/${job.slug}`" class="cta inline-block">View and apply</Link>
                </div>
            </article>
        </section>
        <section v-else class="rule-t px-6 py-16 sm:px-10">
            <p class="max-w-xl text-lg leading-8 opacity-80">
                No live postings match that filter. Organizations publish roles from Post Jobs; drafts
                and expired listings stay off this page.
            </p>
        </section>

        <nav v-if="jobs.links && jobs.links.length > 3" class="rule-t flex flex-wrap gap-3 px-6 py-6 sm:px-10">
            <button
                v-for="(link, index) in jobs.links"
                :key="index"
                type="button"
                class="border-b border-current pb-0.5 text-sm disabled:opacity-40"
                :disabled="!link.url || link.active"
                @click="link.url && router.get(link.url)"
                v-html="link.label"
            />
        </nav>
    </PublicLayout>
</template>

<style scoped>
.display {
    font-family: Fraunces, 'Times New Roman', serif;
}

.cta {
    background: #1c1915;
    color: #f4efe4;
    padding: 0.75rem 1.5rem;
}

.cta:hover {
    background: #3a342c;
}

.rule-t {
    border-top: 1px solid rgb(28 25 21 / 0.15);
}

.dark .cta {
    background: #f4efe4;
    color: #161410;
}
</style>
