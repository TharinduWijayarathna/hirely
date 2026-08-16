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
        <section class="flex flex-col gap-6 px-6 py-12 sm:flex-row sm:items-end sm:justify-between sm:px-10">
            <div>
                <h1 class="display text-[clamp(2.2rem,4.5vw,3.6rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                    Organizations
                </h1>
                <p class="mt-4 max-w-xl text-lg leading-8 opacity-80">
                    Browse companies and their open jobs. Hiring? Register your organization and post roles.
                </p>
            </div>
            <Link href="/organization/register" class="cta shrink-0">Register organization</Link>
        </section>

        <section v-if="organizations.length">
            <article
                v-for="organization in organizations"
                :key="organization.id"
                class="rule-t grid gap-4 px-6 py-8 sm:px-10 lg:grid-cols-12 lg:items-center"
            >
                <div class="lg:col-span-8">
                    <h2 class="display text-2xl leading-tight">
                        <Link :href="`/organization/${organization.slug}`" class="hover:underline">
                            {{ organization.name }}
                        </Link>
                    </h2>
                    <p class="mt-2 text-sm opacity-70">
                        {{ organization.location || 'Location open' }}
                        <template v-if="organization.industry"> · {{ organization.industry }}</template>
                        · {{ organization.open_jobs_count || 0 }}
                        {{ organization.open_jobs_count === 1 ? 'job' : 'jobs' }}
                    </p>
                </div>
                <div class="lg:col-span-4 lg:text-right">
                    <Link :href="`/organization/${organization.slug}`" class="border-b border-current pb-0.5">
                        View jobs
                    </Link>
                </div>
            </article>
        </section>
        <p v-else class="rule-t px-6 py-12 text-lg leading-8 opacity-80 sm:px-10">
            No organizations yet.
            <Link href="/organization/register" class="underline">Register yours</Link>
            to start hiring.
        </p>
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
