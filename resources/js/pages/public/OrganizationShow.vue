<script setup lang="ts">
import PublicJobCard from '@/components/PublicJobCard.vue';
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
</script>

<template>
    <PublicLayout :title="organization.name" :description="`Jobs at ${organization.name}`">
        <section class="pub-hero rounded-3xl px-6 py-10 sm:px-10">
            <Link href="/organization" class="text-sm font-semibold text-white/80 hover:text-white">
                All organizations
            </Link>
            <h1 class="display mt-6 text-[clamp(2.2rem,4.5vw,3.6rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                {{ organization.name }}
            </h1>
            <p class="mt-4 text-lg text-white/85">
                {{ organization.location || 'Location open' }}
                <template v-if="organization.industry"> · {{ organization.industry }}</template>
                <template v-if="organization.size"> · {{ organization.size }}</template>
            </p>
            <p v-if="organization.description" class="mt-6 max-w-2xl leading-8 text-white/90">
                {{ organization.description }}
            </p>
            <a
                v-if="organization.website"
                :href="organization.website"
                class="mt-4 inline-block text-sm font-semibold text-white underline"
                target="_blank"
                rel="noreferrer"
            >
                Website
            </a>
        </section>

        <section class="mt-8 space-y-4">
            <h2 class="display text-3xl">Jobs</h2>
            <PublicJobCard
                v-for="job in jobs"
                :key="job.id"
                :job="{ ...job, company: organization }"
            />
            <p v-if="!jobs.length" class="pub-card p-8 text-lg text-muted-foreground">
                This organization has no live jobs right now.
            </p>
        </section>
    </PublicLayout>
</template>
