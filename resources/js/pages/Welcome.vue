<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link, router } from '@inertiajs/vue3';
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

const typeLabel = (type: string) =>
    type.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const remoteLabel = (remote: string) => remote.replaceAll('_', ' ');
</script>

<template>
    <Head title="Hirely">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&display=swap"
            rel="stylesheet"
        />
        <meta name="description" content="Find jobs. Apply. Interview. Hirely is a public jobs board." />
    </Head>

    <div class="hirely-home min-h-screen">
        <header class="flex items-baseline justify-between gap-6 px-6 py-6 sm:px-10">
            <Link href="/" class="display text-2xl tracking-tight">Hirely</Link>
            <nav class="flex flex-wrap items-baseline justify-end gap-x-6 gap-y-2 text-sm">
                <Link href="/jobs" class="hover:underline">Jobs</Link>
                <Link href="/organization" class="hover:underline">Organizations</Link>
                <Link v-if="$page.props.auth.user" :href="dashboard()" class="hover:underline">Dashboard</Link>
                <template v-else>
                    <Link :href="login()" class="hover:underline">Log in</Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="border-b border-current pb-0.5 hover:opacity-70"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <main>
            <section class="px-6 py-12 sm:px-10">
                <h1 class="display max-w-3xl text-[clamp(2.4rem,5vw,4rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                    Find jobs
                </h1>
                <p class="mt-4 max-w-xl text-lg leading-8 opacity-80">
                    {{ jobCount }} {{ jobCount === 1 ? 'job' : 'jobs' }} from organizations hiring on Hirely.
                </p>
                <form class="mt-8 flex max-w-2xl flex-col gap-3 sm:flex-row" @submit.prevent="searchJobs">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Job title, company, or city"
                        class="h-12 flex-1 border border-current/20 bg-transparent px-4"
                    />
                    <button type="submit" class="cta h-12 shrink-0">Search</button>
                </form>
            </section>

            <section>
                <article
                    v-for="job in jobs"
                    :key="job.id"
                    class="rule-t grid gap-4 px-6 py-8 sm:px-10 lg:grid-cols-12 lg:items-center"
                >
                    <div class="lg:col-span-8">
                        <Link
                            v-if="job.company?.slug"
                            :href="`/organization/${job.company.slug}`"
                            class="text-sm tracking-wide uppercase opacity-70 hover:underline"
                        >
                            {{ job.company.name }}
                        </Link>
                        <p v-else-if="job.company" class="text-sm tracking-wide uppercase opacity-70">
                            {{ job.company.name }}
                        </p>
                        <h2 class="display mt-1 text-2xl leading-tight">
                            <Link :href="`/jobs/${job.slug}`" class="hover:underline">{{ job.title }}</Link>
                        </h2>
                        <p class="mt-2 text-sm opacity-70">
                            {{ job.location || 'Location open' }} · {{ typeLabel(job.type) }} ·
                            {{ remoteLabel(job.remote) }}
                        </p>
                    </div>
                    <div class="lg:col-span-4 lg:text-right">
                        <Link :href="`/jobs/${job.slug}`" class="cta inline-block">Apply</Link>
                    </div>
                </article>
                <p v-if="!jobs.length" class="rule-t px-6 py-12 text-lg leading-8 opacity-80 sm:px-10">
                    No jobs posted yet.
                </p>
                <div v-if="jobCount > jobs.length" class="rule-t px-6 py-8 sm:px-10">
                    <Link href="/jobs" class="border-b border-current pb-0.5">See all jobs</Link>
                </div>
            </section>
        </main>

        <footer class="rule-t flex flex-wrap items-baseline justify-between gap-4 px-6 py-6 text-sm opacity-70 sm:px-10">
            <span>Hirely</span>
            <Link href="/organization/register" class="hover:underline">Hiring? Register your organization</Link>
        </footer>
    </div>
</template>

<style scoped>
.hirely-home {
    --line: rgb(28 25 21 / 0.15);
    background: #f4efe4;
    color: #1c1915;
    font-family:
        'Instrument Sans',
        ui-sans-serif,
        system-ui,
        sans-serif;
}

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
    border-top: 1px solid var(--line);
}

.dark .hirely-home {
    --line: rgb(244 239 228 / 0.18);
    background: #161410;
    color: #f4efe4;
}

.dark .hirely-home .cta {
    background: #f4efe4;
    color: #161410;
}

.dark .hirely-home .cta:hover {
    background: #e4dccd;
}
</style>
