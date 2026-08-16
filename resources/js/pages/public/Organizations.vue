<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    organizations: Array<{
        id: number;
        name: string;
        slug: string;
        location?: string | null;
        industry?: string | null;
        open_jobs_count?: number;
    }>;
    canRegister: boolean;
}>();
</script>

<template>
    <PublicLayout title="Organizations" description="Companies hiring on Hirely.">
        <section class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="display text-[clamp(2.2rem,4.5vw,3.6rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                    Organizations
                </h1>
                <p class="mt-4 max-w-xl text-lg leading-8 text-muted-foreground">
                    Browse companies and their open jobs. Hiring? Register your organization and post roles.
                </p>
            </div>
            <Link href="/organization/register" class="pub-cta shrink-0">Register organization</Link>
        </section>

        <section v-if="organizations.length" class="mt-8 grid gap-4 md:grid-cols-2">
            <article
                v-for="organization in organizations"
                :key="organization.id"
                class="pub-card p-6"
            >
                <h2 class="display text-2xl leading-tight">
                    <Link :href="`/organization/${organization.slug}`" class="hover:text-primary">
                        {{ organization.name }}
                    </Link>
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ organization.location || 'Location open' }}
                    <template v-if="organization.industry"> · {{ organization.industry }}</template>
                </p>
                <div class="mt-4 flex items-center justify-between gap-3">
                    <span class="pub-chip">
                        {{ organization.open_jobs_count || 0 }}
                        {{ organization.open_jobs_count === 1 ? 'job' : 'jobs' }}
                    </span>
                    <Link :href="`/organization/${organization.slug}`" class="font-semibold text-primary hover:underline">
                        View jobs
                    </Link>
                </div>
            </article>
        </section>
        <p v-else class="pub-card mt-8 p-8 text-lg text-muted-foreground">
            No organizations yet.
            <Link href="/organization/register" class="text-primary underline">Register yours</Link>
            to start hiring.
        </p>
    </PublicLayout>
</template>
