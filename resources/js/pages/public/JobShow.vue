<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login, register } from '@/routes';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
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
        <section class="grid lg:grid-cols-12">
            <div class="px-6 py-12 sm:px-10 lg:col-span-7">
                <Link href="/jobs" class="text-sm hover:underline">Jobs</Link>
                <p v-if="job.company" class="mt-6 text-sm tracking-wide uppercase opacity-70">
                    <Link
                        v-if="job.company.slug"
                        :href="`/organization/${job.company.slug}`"
                        class="hover:underline"
                    >
                        {{ job.company.name }}
                    </Link>
                    <template v-else>{{ job.company.name }}</template>
                </p>
                <h1 class="display mt-3 text-[clamp(2.2rem,4.5vw,3.6rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                    {{ job.title }}
                </h1>
                <p class="mt-5 text-lg opacity-80">
                    {{ job.location || 'Location open' }} · {{ typeLabel(job.type) }} ·
                    {{ job.remote.replaceAll('_', ' ') }}
                </p>
                <p
                    v-if="job.salary_min || job.salary_max"
                    class="mt-2 text-sm opacity-70"
                >
                    {{ job.salary_currency }}
                    {{ job.salary_min ?? '' }}
                    <template v-if="job.salary_min && job.salary_max"> – </template>
                    {{ job.salary_max ?? '' }}
                </p>
                <p v-if="success" class="mt-6 text-sm">{{ success }}</p>
            </div>
            <aside class="rule-t px-6 py-12 sm:px-10 lg:col-span-5 lg:border-t-0 lg:border-l lg:border-[rgb(28_25_21_/_0.15)]">
                <p class="text-sm tracking-wide uppercase">Share this role</p>
                <p class="mt-3 leading-7 opacity-80">
                    Send this URL to a candidate. They log in as a job seeker, apply, and sit the
                    interview this organization assigned to the posting.
                </p>
                <div class="mt-4 flex gap-2">
                    <input
                        :value="share_url"
                        readonly
                        class="h-12 flex-1 border border-current/20 bg-transparent px-3 text-sm"
                    />
                    <button type="button" class="cta shrink-0" @click="copyLink">
                        {{ copied ? 'Copied' : 'Copy' }}
                    </button>
                </div>
            </aside>
        </section>

        <section class="rule-t grid gap-10 px-6 py-12 sm:px-10 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <h2 class="display text-3xl">The work</h2>
                <p class="mt-5 whitespace-pre-wrap leading-8">{{ job.description }}</p>
                <template v-if="job.requirements">
                    <h3 class="mt-10 text-xl">Requirements</h3>
                    <p class="mt-4 whitespace-pre-wrap leading-8 opacity-80">{{ job.requirements }}</p>
                </template>
                <div v-if="job.skills?.length" class="mt-8 flex flex-wrap gap-2">
                    <span
                        v-for="skill in job.skills"
                        :key="skill"
                        class="border border-current/20 px-3 py-1 text-sm"
                    >
                        {{ skill }}
                    </span>
                </div>
            </div>

            <div class="lg:col-span-5">
                <h2 class="display text-3xl">Apply</h2>

                <div v-if="has_applied" class="mt-5 leading-7">
                    <p>You have already applied for this role.</p>
                    <Link
                        v-if="interview_id"
                        :href="`/interviews/${interview_id}`"
                        class="cta mt-6 inline-block"
                    >
                        Continue interview
                    </Link>
                </div>

                <form v-else-if="can_apply" class="mt-5 space-y-4" @submit.prevent="submit">
                    <label class="block text-sm" for="cover_letter">Cover letter</label>
                    <textarea
                        id="cover_letter"
                        v-model="form.cover_letter"
                        rows="8"
                        class="w-full border border-current/20 bg-transparent px-3 py-3 text-sm"
                        placeholder="Why this role, in your own words."
                    />
                    <p v-if="form.errors.cover_letter || form.errors.job_id" class="text-sm">
                        {{ form.errors.cover_letter || form.errors.job_id }}
                    </p>
                    <button type="submit" class="cta" :disabled="form.processing">
                        Submit application
                    </button>
                </form>

                <div v-else-if="guest" class="mt-5 leading-7">
                    <p>
                        Create a job seeker account, then apply from this page. If the company has an
                        interview template on the role, the interview starts after you apply.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3">
                        <Link :href="`/jobs/${job.slug}/apply`" class="cta">Log in to apply</Link>
                        <Link :href="register()" class="border-b border-current pb-0.5">Register</Link>
                    </div>
                    <p class="mt-4 text-sm opacity-70">
                        Already registered?
                        <Link :href="login()" class="underline">Log in</Link>
                    </p>
                </div>

                <p v-else class="mt-5 leading-7 opacity-80">
                    Applications are open to job seekers. Recruiters share this link; they review
                    applications from Review Candidates.
                </p>
            </div>
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
