<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    organization: {
        id: number;
        name: string;
        slug: string;
        description?: string | null;
        location?: string | null;
        industry?: string | null;
        website?: string | null;
        size?: string | null;
    };
    jobs: Array<{
        id: number;
        slug: string;
        title: string;
        description?: string | null;
        location?: string | null;
        type: string;
        remote: string;
    }>;
}>();

const typeLabel = (type: string) =>
    type.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
</script>

<template>
    <PublicLayout :title="organization.name" :description="`Jobs at ${organization.name}`">
        <section class="px-6 py-12 sm:px-10">
            <Link href="/organization" class="text-sm hover:underline">All organizations</Link>
            <h1 class="display mt-6 text-[clamp(2.2rem,4.5vw,3.6rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                {{ organization.name }}
            </h1>
            <p class="mt-4 text-lg opacity-80">
                {{ organization.location || 'Location open' }}
                <template v-if="organization.industry"> · {{ organization.industry }}</template>
                <template v-if="organization.size"> · {{ organization.size }}</template>
            </p>
            <p v-if="organization.description" class="mt-6 max-w-2xl leading-8">
                {{ organization.description }}
            </p>
            <a
                v-if="organization.website"
                :href="organization.website"
                class="mt-4 inline-block text-sm underline"
                target="_blank"
                rel="noreferrer"
            >
                Website
            </a>
        </section>

        <section>
            <div class="rule-t px-6 py-8 sm:px-10">
                <h2 class="display text-3xl">Jobs</h2>
            </div>
            <article
                v-for="job in jobs"
                :key="job.id"
                class="rule-t grid gap-4 px-6 py-8 sm:px-10 lg:grid-cols-12 lg:items-center"
            >
                <div class="lg:col-span-8">
                    <h3 class="display text-2xl leading-tight">
                        <Link :href="`/jobs/${job.slug}`" class="hover:underline">{{ job.title }}</Link>
                    </h3>
                    <p class="mt-2 text-sm opacity-70">
                        {{ job.location || 'Location open' }} · {{ typeLabel(job.type) }} ·
                        {{ job.remote.replaceAll('_', ' ') }}
                    </p>
                </div>
                <div class="lg:col-span-4 lg:text-right">
                    <Link :href="`/jobs/${job.slug}`" class="cta inline-block">Apply</Link>
                </div>
            </article>
            <p v-if="!jobs.length" class="rule-t px-6 py-12 text-lg leading-8 opacity-80 sm:px-10">
                This organization has no live jobs right now.
            </p>
        </section>
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
