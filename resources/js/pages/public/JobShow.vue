<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login, register } from '@/routes';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    job: {
        id: number;
        slug: string;
        title: string;
        description: string;
        requirements?: string | null;
        location?: string | null;
        type: string;
        remote: string;
        skills?: string[];
        salary_min?: number | null;
        salary_max?: number | null;
        salary_currency?: string | null;
        company?: {
            name: string;
            slug?: string;
            location?: string | null;
        } | null;
    };
    share_url: string;
    has_applied: boolean;
    interview_id?: number | null;
    can_apply: boolean;
    success?: string | null;
}>();

const page = usePage();
const copied = ref(false);
const guest = computed(() => !page.props.auth.user);

const form = useForm({
    cover_letter: '',
});

const typeLabel = (type: string) =>
    type.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const copyLink = async () => {
    await navigator.clipboard.writeText(props.share_url);
    copied.value = true;
    window.setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const submit = () => {
    form.post(`/jobs/${props.job.slug}/apply`);
};
</script>

<template>
    <PublicLayout :title="job.title" :description="`${job.title} at ${job.company?.name ?? 'Hirely'}`">
        <section class="grid gap-6 lg:grid-cols-12">
            <div class="pub-hero rounded-3xl px-6 py-10 sm:px-10 lg:col-span-7">
                <Link href="/jobs" class="text-sm font-semibold text-white/80 hover:text-white">Jobs</Link>
                <p v-if="job.company" class="mt-6 text-sm font-semibold tracking-wide text-white/80 uppercase">
                    <Link
                        v-if="job.company.slug"
                        :href="`/organization/${job.company.slug}`"
                        class="hover:underline"
                    >
                        {{ job.company.name }}
                    </Link>
                    <template v-else>{{ job.company.name }}</template>
                </p>
                <h1 class="display mt-3 text-[clamp(2.2rem,4.5vw,3.4rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                    {{ job.title }}
                </h1>
                <p class="mt-5 text-lg text-white/85">
                    {{ job.location || 'Location open' }} · {{ typeLabel(job.type) }} ·
                    {{ job.remote.replaceAll('_', ' ') }}
                </p>
                <p v-if="job.salary_min || job.salary_max" class="mt-2 text-sm text-white/75">
                    {{ job.salary_currency }}
                    {{ job.salary_min ?? '' }}
                    <template v-if="job.salary_min && job.salary_max"> – </template>
                    {{ job.salary_max ?? '' }}
                </p>
                <p v-if="success" class="mt-6 rounded-xl bg-white/15 px-4 py-3 text-sm">{{ success }}</p>
            </div>
            <aside class="pub-card px-6 py-8 sm:px-8 lg:col-span-5">
                <p class="text-sm font-semibold tracking-wide text-primary uppercase">Share this role</p>
                <p class="mt-3 leading-7 text-muted-foreground">
                    Send this URL to a candidate. They apply as a job seeker from this page.
                </p>
                <div class="mt-4 flex gap-2">
                    <input :value="share_url" readonly class="pub-field flex-1 text-sm" />
                    <button type="button" class="pub-cta shrink-0" @click="copyLink">
                        {{ copied ? 'Copied' : 'Copy' }}
                    </button>
                </div>
            </aside>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-12">
            <div class="pub-card px-6 py-8 sm:px-8 lg:col-span-7">
                <h2 class="display text-3xl">The work</h2>
                <p class="mt-5 whitespace-pre-wrap leading-8">{{ job.description }}</p>
                <template v-if="job.requirements">
                    <h3 class="mt-10 text-xl font-semibold">Requirements</h3>
                    <p class="mt-4 whitespace-pre-wrap leading-8 text-muted-foreground">{{ job.requirements }}</p>
                </template>
                <div v-if="job.skills?.length" class="mt-8 flex flex-wrap gap-2">
                    <span v-for="skill in job.skills" :key="skill" class="pub-chip">{{ skill }}</span>
                </div>
            </div>

            <div class="pub-card px-6 py-8 sm:px-8 lg:col-span-5">
                <h2 class="display text-3xl">Apply</h2>

                <div v-if="has_applied" class="mt-5 leading-7">
                    <p>You have already applied for this role.</p>
                    <Link
                        v-if="interview_id"
                        :href="`/interviews/${interview_id}`"
                        class="pub-cta mt-6"
                    >
                        Continue interview
                    </Link>
                </div>

                <form v-else-if="can_apply" class="mt-5 space-y-4" @submit.prevent="submit">
                    <label class="block text-sm font-medium" for="cover_letter">Cover letter</label>
                    <textarea
                        id="cover_letter"
                        v-model="form.cover_letter"
                        rows="8"
                        class="pub-field h-auto py-3"
                        placeholder="Why this role, in your own words."
                    />
                    <p v-if="form.errors.cover_letter || form.errors.job_id" class="text-sm text-destructive">
                        {{ form.errors.cover_letter || form.errors.job_id }}
                    </p>
                    <button type="submit" class="pub-cta" :disabled="form.processing">
                        Submit application
                    </button>
                </form>

                <div v-else-if="guest" class="mt-5 leading-7 text-muted-foreground">
                    <p>Create a job seeker account, then apply from this page.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link :href="`/jobs/${job.slug}/apply`" class="pub-cta">Log in to apply</Link>
                        <Link :href="register()" class="font-semibold text-primary hover:underline">Register</Link>
                    </div>
                    <p class="mt-4 text-sm">
                        Already registered?
                        <Link :href="login()" class="text-primary underline">Log in</Link>
                    </p>
                </div>

                <p v-else class="mt-5 leading-7 text-muted-foreground">
                    Applications are open to job seekers.
                </p>
            </div>
        </section>
    </PublicLayout>
</template>
