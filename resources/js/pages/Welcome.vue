<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const steps = [
    {
        n: '01',
        title: 'A CV becomes a record',
        body: 'Upload a PDF or DOCX. Hirely extracts identity, education, skills, experience, projects, and certifications, then scores the file against a job description. That extraction is what HR sees on Review Candidates — not a mystery blob in a folder.',
    },
    {
        n: '02',
        title: 'An interview is designed, then assigned',
        body: 'Recruiters write a template: question count, duration, difficulty, mix of technical / behavioral / scenario / CV questions, evaluation criteria, and criterion weights. When they assign it, Hirely generates questions from the job and the candidate’s own CV.',
    },
    {
        n: '03',
        title: 'Answers are scored, then argued with',
        body: 'On completion, each answer gets a score, evidence snippet, strengths, and weaknesses. Recruiters accept, edit, or reject the AI score with a required note. Ranking never treats a rejected interview as a hiring signal.',
    },
    {
        n: '04',
        title: 'A shortlist, not a vibe',
        body: 'Applicants are ordered by interview (50%), CV/ATS (30%), and application stage (20%). Compare two to four people on the same job. Export funnel, time in stage, and score buckets as CSV.',
    },
];
</script>

<template>
    <Head title="Hirely">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&display=swap"
            rel="stylesheet"
        />
        <meta
            name="description"
            content="Hirely is recruitment software for CV analysis, AI interviews, ranking, and human review."
        />
    </Head>

    <div class="hirely-home min-h-screen">
        <header class="flex items-baseline justify-between gap-6 px-6 py-6 sm:px-10">
            <Link href="/" class="display text-2xl tracking-tight">Hirely</Link>
            <nav class="flex flex-wrap items-baseline justify-end gap-x-6 gap-y-2 text-sm">
                <a href="#how" class="hover:underline">How it works</a>
                <a href="#candidates" class="hover:underline">Candidates</a>
                <a href="#recruiters" class="hover:underline">Recruiters</a>
                <a href="#interviews" class="hover:underline">Interviews</a>
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
            <section class="hero-split grid lg:grid-cols-2">
                <div class="flex flex-col justify-center px-6 py-12 sm:px-10 lg:py-16">
                    <p class="mb-6 max-w-xl text-sm leading-6 tracking-wide uppercase">
                        Recruitment, with the last word left to people
                    </p>
                    <h1 class="display max-w-xl text-[clamp(2.4rem,5.2vw,4.4rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                        Interviews you can prepare for, and scores you can stand behind.
                    </h1>
                    <p class="mt-6 max-w-lg text-lg leading-8">
                        Hirely is one product for both sides of a hire. Job seekers upload a CV, check ATS fit, practice
                        in text or voice, then sit the interview a recruiter actually assigned. HR posts jobs, designs
                        the interview, reviews explainable scores, ranks the field, and keeps the override.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-3 text-lg">
                        <Link v-if="canRegister && !$page.props.auth.user" :href="register()" class="cta">
                            Create an account
                        </Link>
                        <Link v-else-if="$page.props.auth.user" :href="dashboard()" class="cta">Open dashboard</Link>
                        <a href="#how" class="border-b border-current pb-0.5">See how a hire moves</a>
                    </div>
                </div>
                <figure class="rule-t hero-photo">
                    <img
                        src="/images/home/hero.jpg"
                        alt="A candidate in conversation during an interview"
                        class="photo h-[min(56vh,520px)] w-full object-cover lg:h-full lg:min-h-[640px]"
                    />
                    <figcaption class="px-6 py-3 text-sm opacity-70 sm:px-10 lg:absolute lg:bottom-0 lg:left-0 lg:right-0 lg:bg-gradient-to-t lg:from-[#1c1915]/70 lg:to-transparent lg:px-8 lg:py-5 lg:text-[#f4efe4]">
                        Practice and assigned interviews share the same room: a person, a question, and a record.
                    </figcaption>
                </figure>
            </section>

            <section id="how" class="rule-t">
                <div class="grid lg:grid-cols-12">
                    <div class="rule-r px-6 py-14 sm:px-10 lg:col-span-4">
                        <p class="text-sm tracking-wide uppercase">How a hire moves</p>
                        <h2 class="display mt-4 text-4xl leading-tight">Four steps. No black box at the end.</h2>
                        <p class="mt-5 leading-7 opacity-80">
                            Mock interviews stay practice. Ranking only uses recruitment interviews, CV/ATS, and
                            where the application sits in the pipeline.
                        </p>
                    </div>
                    <ol class="lg:col-span-8">
                        <li v-for="step in steps" :key="step.n" class="rule-t px-6 py-10 sm:px-10 lg:first:border-t-0">
                            <p class="display text-4xl">{{ step.n }}</p>
                            <h3 class="mt-3 text-2xl">{{ step.title }}</h3>
                            <p class="mt-3 max-w-2xl leading-7 opacity-80">{{ step.body }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <section id="candidates" class="rule-t grid lg:grid-cols-2">
                <figure class="order-2 lg:order-1">
                    <img
                        src="/images/home/cv.jpg"
                        alt="Hands reviewing a printed CV beside a laptop"
                        class="photo h-full min-h-[320px] w-full object-cover"
                    />
                </figure>
                <div class="order-1 px-6 py-14 sm:px-10 lg:order-2">
                    <p class="text-sm tracking-wide uppercase">For job seekers</p>
                    <h2 class="display mt-4 text-4xl leading-tight">Arrive with a file. Leave with a trail.</h2>
                    <p class="mt-5 leading-8">
                        Public registration is always a job-seeker account. You keep a portfolio and skill goals by
                        hand; Hirely does not pretend those came from the CV. What the parser does extract — name,
                        education, skills, experience, projects, certifications — is stored and reused when you apply
                        and when an interview is generated.
                    </p>
                    <ul class="mt-8 space-y-5 leading-7">
                        <li>
                            <strong>CV review.</strong>
                            Upload up to the plan limit. See a quality score and a structured extraction, not just a
                            “looks good” paragraph.
                        </li>
                        <li>
                            <strong>ATS scoring.</strong>
                            Paste or pick a job description and see matched skills versus gaps before you apply.
                        </li>
                        <li>
                            <strong>Mock interviews.</strong>
                            Technical, behavioral, or mixed. Beginner through advanced. Text with follow-up probes,
                            or a spoken conversation in the browser.
                        </li>
                        <li>
                            <strong>The real interview.</strong>
                            When HR assigns one, it shows under Interviews. Complete it, then open a result page with
                            scores and rationale — without the recruiter’s private notes.
                        </li>
                        <li>
                            <strong>Jobs.</strong>
                            Browse active postings, apply once with a cover letter, withdraw if you need to. Status
                            changes land in the header bell and in email.
                        </li>
                    </ul>
                </div>
            </section>

            <section id="recruiters" class="rule-t grid lg:grid-cols-2">
                <div class="px-6 py-14 sm:px-10">
                    <p class="text-sm tracking-wide uppercase">For recruiters</p>
                    <h2 class="display mt-4 text-4xl leading-tight">A template is a decision, not a prompt.</h2>
                    <p class="mt-5 leading-8">
                        HR accounts are invited by an admin and linked to a company. Jobs, templates, applicants, and
                        reports stay inside that company. You can edit the company profile yourself; verification
                        stays with Hirely admins.
                    </p>
                    <ul class="mt-8 space-y-5 leading-7">
                        <li>
                            <strong>Jobs and pipeline.</strong>
                            Draft, publish, close. Review applications with the extracted CV attached. Move people
                            through pending, reviewing, shortlisted, interviewed, accepted, or rejected.
                        </li>
                        <li>
                            <strong>Interview templates.</strong>
                            1–20 questions, a duration target, difficulty, text or voice, a mix that must total 100%,
                            named criteria, and a weight on each criterion.
                        </li>
                        <li>
                            <strong>Human-in-the-loop.</strong>
                            Accept the AI score, edit it, or reject it. A note is required. The audit log keeps who
                            changed what.
                        </li>
                        <li>
                            <strong>Rank, compare, report.</strong>
                            Order the field. Put two to four applicants on one screen. Read funnel and score
                            distributions, then download CSV on a Professional or Enterprise plan.
                        </li>
                    </ul>
                </div>
                <figure>
                    <img
                        src="/images/home/recruiters.jpg"
                        alt="Recruiters reviewing printed candidate profiles at a table"
                        class="photo h-full min-h-[320px] w-full object-cover"
                    />
                </figure>
            </section>

            <section id="interviews" class="rule-t grid lg:grid-cols-2">
                <figure class="order-2 lg:order-1">
                    <img
                        src="/images/home/voice.jpg"
                        alt="A candidate speaking during a voice interview"
                        class="photo h-full min-h-[320px] w-full object-cover"
                    />
                </figure>
                <div class="order-1 px-6 py-14 sm:px-10 lg:order-2">
                    <p class="text-sm tracking-wide uppercase">Interviews</p>
                    <h2 class="display mt-4 text-4xl leading-tight">Text, voice, then a follow-up that heard you.</h2>
                    <p class="mt-5 leading-8">
                        Assigned interviews inherit the template. If the mode is text, Next can insert up to three
                        follow-up questions after the answer you just typed. If the mode is voice, the session is a
                        spoken conversation in Chrome or Edge — the same browser speech path as mock voice — then
                        scored from the transcript when you end it.
                    </p>
                    <p class="mt-5 leading-8">
                        Criterion weights are not decoration. Technical depth at 80 and communication at 20 means the
                        overall score leans where you said it should. Candidates see the result. Recruiters see the
                        same evaluation plus the review tools.
                    </p>
                    <p class="mt-5 leading-8 opacity-80">
                        There is no server-side audio file for playback. The record is the conversation, the answers,
                        and the scores — which is what ranking uses.
                    </p>
                </div>
            </section>

            <section class="rule-t px-6 py-16 sm:px-10">
                <p class="text-sm tracking-wide uppercase">What is in the product</p>
                <h2 class="display mt-4 max-w-3xl text-4xl leading-tight">
                    The loop you would sketch on a whiteboard, actually wired.
                </h2>
                <div class="product-grid mt-12">
                    <article class="cell py-8 pr-8">
                        <h3 class="display text-2xl">Auth and roles</h3>
                        <p class="mt-3 leading-7 opacity-80">
                            Login, verification, 2FA. Three roles: job seeker, HR, admin. Public sign-up cannot
                            become HR or admin.
                        </p>
                    </article>
                    <article class="cell py-8 sm:pl-8">
                        <h3 class="display text-2xl">Plans</h3>
                        <p class="mt-3 leading-7 opacity-80">
                            Stripe Checkout and a customer portal. Caps on job posts, mock interviews, stored CVs,
                            ATS runs, and reports — the limits on the plan, not a footnote.
                        </p>
                    </article>
                    <article class="cell py-8 pr-8">
                        <h3 class="display text-2xl">Notifications</h3>
                        <p class="mt-3 leading-7 opacity-80">
                            In-app bell and email when someone applies, when an interview is assigned or finished,
                            when a score needs review, and when ranking refreshes.
                        </p>
                    </article>
                    <article class="cell py-8 sm:pl-8">
                        <h3 class="display text-2xl">What this is not</h3>
                        <p class="mt-3 leading-7 opacity-80">
                            Not SSO, not SMS, not a virus scanner, not a multi-role org chart. Those were left out on
                            purpose. The core hire is in.
                        </p>
                    </article>
                </div>
            </section>

            <section class="rule-t flex flex-col gap-8 px-6 py-16 sm:px-10 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-xl">
                    <h2 class="display text-4xl leading-tight">Start as a candidate.</h2>
                    <p class="mt-4 leading-8">
                        Create a job-seeker account and upload a CV today. If you hire for a company, ask an admin
                        to invite you as HR. Same product, two doors.
                    </p>
                </div>
                <div class="flex flex-wrap gap-6 text-lg">
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="register()"
                        class="cta"
                    >
                        Register
                    </Link>
                    <Link v-if="!$page.props.auth.user" :href="login()" class="border-b border-current pb-0.5">
                        Log in
                    </Link>
                    <Link v-else :href="dashboard()" class="cta">Dashboard</Link>
                </div>
            </section>
        </main>

        <footer class="rule-t px-6 py-6 text-sm opacity-70 sm:px-10">
            Hirely · CV analysis, interviews, ranking, human review
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

.photo {
    display: block;
    filter: saturate(0.88) contrast(1.04);
}

.rule-t {
    border-top: 1px solid var(--line);
}

.product-grid {
    display: grid;
}

.cell {
    border-top: 1px solid var(--line);
}

@media (min-width: 640px) {
    .product-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (min-width: 1024px) {
    .rule-r {
        border-right: 1px solid var(--line);
    }

    .hero-split {
        min-height: calc(100vh - 5.5rem);
    }

    .hero-photo {
        position: relative;
        display: flex;
        min-height: 100%;
        border-top: 0;
        border-left: 1px solid var(--line);
    }

    .hero-photo .photo {
        flex: 1;
        min-height: 100%;
        height: auto;
    }
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

.dark .hirely-home .photo {
    filter: saturate(0.8) contrast(1.06) brightness(0.92);
}
</style>
