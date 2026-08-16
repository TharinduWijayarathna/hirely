<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    job: {
        slug: string;
        title: string;
        location?: string | null;
        type: string;
        remote: string;
        description?: string | null;
        company?: {
            name: string;
            slug?: string;
        } | null;
    };
    applyLabel?: string;
}>();

const typeLabel = (type: string) =>
    type.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const remoteChip = (remote: string) => {
    if (remote === 'remote') {
        return 'pub-chip pub-chip-remote';
    }
    if (remote === 'hybrid') {
        return 'pub-chip pub-chip-hybrid';
    }
    return 'pub-chip pub-chip-onsite';
};
</script>

<template>
    <article class="pub-card grid gap-4 p-5 sm:grid-cols-12 sm:items-center sm:p-6">
        <div class="sm:col-span-8">
            <Link
                v-if="job.company?.slug"
                :href="`/organization/${job.company.slug}`"
                class="text-sm font-semibold text-primary hover:underline"
            >
                {{ job.company.name }}
            </Link>
            <p v-else-if="job.company" class="text-sm font-semibold text-primary">
                {{ job.company.name }}
            </p>
            <h2 class="display mt-1 text-xl leading-tight sm:text-2xl">
                <Link :href="`/jobs/${job.slug}`" class="hover:text-primary">{{ job.title }}</Link>
            </h2>
            <p v-if="job.description" class="mt-2 line-clamp-2 text-sm leading-6 text-muted-foreground">
                {{ job.description }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="pub-chip">{{ job.location || 'Location open' }}</span>
                <span class="pub-chip">{{ typeLabel(job.type) }}</span>
                <span :class="remoteChip(job.remote)">{{ job.remote.replaceAll('_', ' ') }}</span>
            </div>
        </div>
        <div class="sm:col-span-4 sm:text-right">
            <Link :href="`/jobs/${job.slug}`" class="pub-cta">{{ props.applyLabel || 'Apply' }}</Link>
        </div>
    </article>
</template>
