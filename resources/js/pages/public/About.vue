<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login, register } from '@/routes';
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BarChart3,
    Bell,
    Brain,
    Briefcase,
    Building2,
    CheckCircle2,
    ClipboardList,
    CreditCard,
    FileSearch,
    FileText,
    Globe,
    GraduationCap,
    Layers,
    LineChart,
    Lock,
    Mail,
    MessageSquare,
    Mic,
    Search,
    Shield,
    ShieldCheck,
    Sparkles,
    Target,
    TrendingUp,
    UserCheck,
    Users,
    Zap,
} from 'lucide-vue-next';

const highlights = [
    { icon: Briefcase, label: 'Public jobs board', detail: 'Search, filter & apply' },
    { icon: Brain, label: 'AI-powered', detail: 'Gemini-driven intelligence' },
    { icon: Mic, label: 'Voice & text interviews', detail: 'Mock & recruitment modes' },
    { icon: BarChart3, label: 'Smart rankings', detail: 'Data-driven hiring' },
];

const values = [
    {
        icon: Target,
        title: 'Transparency',
        description: 'Explainable AI scores, evidence quotes, and ranking rationale so every decision is understandable.',
    },
    {
        icon: UserCheck,
        title: 'Human-in-the-loop',
        description: 'AI assists — humans decide. HR can accept, edit, or reject AI evaluations with full audit trails.',
    },
    {
        icon: Zap,
        title: 'Speed without shortcuts',
        description: 'Automate screening and interviews while keeping quality high through structured evaluation criteria.',
    },
    {
        icon: GraduationCap,
        title: 'Career growth',
        description: 'Job seekers get real preparation tools — not just listings — to improve and land better roles.',
    },
];

const audiences = [
    {
        icon: Users,
        title: 'Job seekers',
        tagline: 'Prepare smarter. Apply with confidence.',
        points: [
            'Browse and search active job listings by title, company, or location',
            'Upload PDF/DOCX CVs and receive AI quality reviews with structured extraction',
            'Score your CV against job descriptions to beat applicant tracking systems',
            'Practice text and voice mock interviews with follow-up questions and scoring',
            'Build a portfolio showcasing projects, skills, and experience',
            'Set and track skill expectations and career goals',
            'Apply to jobs with optional cover letters and track every application',
            'Complete assigned recruitment interviews in text or voice mode',
            'View profile readiness scores based on CV, portfolio, and interview history',
            'Receive real-time notifications when application status changes',
        ],
    },
    {
        icon: Building2,
        title: 'HR & organizations',
        tagline: 'Post jobs. Evaluate talent. Hire faster.',
        points: [
            'Register your organization and manage a public company profile',
            'Create job postings with type, remote mode, salary range, and skill tags',
            'Manage listings through draft, active, closed, and expired statuses',
            'Review applications with status updates, notes, and candidate details',
            'Configure AI interview templates with question mix, difficulty, and criteria',
            'Assign structured interviews to candidates and review AI-generated results',
            'Rank applicants automatically using interview, CV/ATS, and pipeline signals',
            'Compare 2–4 candidates side by side on scores, skills, and strengths',
            'Accept, edit, or reject AI scores with required notes and audit logs',
            'Export recruitment reports, hiring funnels, and CSV data',
        ],
    },
    {
        icon: Shield,
        title: 'Platform administrators',
        tagline: 'Oversee the entire hiring ecosystem.',
        points: [
            'Create and manage companies with verification status',
            'Onboard HR professionals and link them to organizations',
            'Manage admin accounts and job seeker accounts separately',
            'View platform-wide analytics: users, jobs, applications, and interviews',
            'Monitor Stripe payments, subscriptions, and billing activity',
            'Ensure role-based access control across all platform areas',
        ],
    },
];

const aiFeatures = [
    {
        icon: FileText,
        title: 'CV analysis',
        description: 'Upload documents and get structured field extraction, quality feedback, and improvement tips powered by Gemini.',
        points: ['PDF & DOCX support', 'Structured skill extraction', 'Quality scoring & suggestions'],
    },
    {
        icon: Target,
        title: 'ATS scoring',
        description: 'Compare your CV against a pasted job description or a live posting to see keyword match and compatibility scores.',
        points: ['Job-specific matching', 'Keyword gap analysis', 'Premium plan feature'],
    },
    {
        icon: MessageSquare,
        title: 'Text interviews',
        description: 'Sequential Q&A with optional AI follow-up probes. Complete a session and receive detailed feedback and an overall score.',
        points: ['Up to 3 follow-up probes', 'Technical & behavioral modes', 'Instant AI feedback'],
    },
    {
        icon: Mic,
        title: 'Voice interviews',
        description: 'Conversational AI interviews using speech recognition and synthesis for a natural, real-world practice experience.',
        points: ['Google Cloud TTS powered', 'Dynamic follow-ups', 'Recruitment & mock modes'],
    },
    {
        icon: ClipboardList,
        title: 'Interview templates',
        description: 'HR configures question count, duration, difficulty, mode, mix percentages, and weighted evaluation criteria.',
        points: ['Technical / behavioral / scenario mix', 'Custom criterion weights', 'Reusable templates'],
    },
    {
        icon: Sparkles,
        title: 'Explainable evaluation',
        description: 'Every score comes with criterion breakdowns, evidence quotes, strengths, weaknesses, and confidence levels.',
        points: ['0–100 scoring scale', 'Per-dimension feedback', 'Evidence-backed rationale'],
    },
    {
        icon: TrendingUp,
        title: 'Automatic ranking',
        description: 'Candidates ranked by composite score: 50% interview, 30% CV/ATS match, 20% application stage — with transparent rationale.',
        points: ['Per-job rankings', 'Human-readable rationale', 'Persistent rank positions'],
    },
    {
        icon: LineChart,
        title: 'Recruitment reports',
        description: 'Hiring funnels, time-in-stage metrics, interview volume, score distributions, and CSV export for HR teams.',
        points: ['Funnel visualization', 'Score bucket analysis', 'CSV export'],
    },
];

const seekerSteps = [
    { icon: Mail, title: 'Register & verify', detail: 'Create a free account and confirm your email to unlock career tools and job applications.' },
    { icon: FileText, title: 'Upload your CV', detail: 'Add your resume for AI extraction, quality review, and use when applying to roles.' },
    { icon: Layers, title: 'Build your profile', detail: 'Create a portfolio, set skill expectations, and track your career readiness score.' },
    { icon: Brain, title: 'Prepare with AI', detail: 'Run mock interviews, optimize for ATS, and review detailed feedback before real interviews.' },
    { icon: Briefcase, title: 'Apply to jobs', detail: 'Browse listings, submit applications with cover letters, and track status in one place.' },
    { icon: Mic, title: 'Complete interviews', detail: 'Take assigned recruitment interviews and view explainable AI evaluation results.' },
];

const orgSteps = [
    { icon: Building2, title: 'Register organization', detail: 'Sign up your company on Hirely and set up your public profile and team.' },
    { icon: Briefcase, title: 'Post job openings', detail: 'Create listings with employment type, remote options, salary range, and required skills.' },
    { icon: FileSearch, title: 'Review applications', detail: 'Manage incoming applications, update statuses, add notes, and view candidate CVs.' },
    { icon: ClipboardList, title: 'Assign interviews', detail: 'Use AI interview templates to assign structured text or voice interviews to candidates.' },
    { icon: BarChart3, title: 'Rank & compare', detail: 'View AI-generated rankings and compare top candidates side by side on multiple dimensions.' },
    { icon: CheckCircle2, title: 'Review & decide', detail: 'Accept, edit, or reject AI scores, export reports, and move candidates through your pipeline.' },
];

const capabilities = [
    { icon: Search, label: 'Public jobs board with search and filters' },
    { icon: Building2, label: 'Organization directory and company profiles' },
    { icon: Briefcase, label: 'One-click job applications with cover letters' },
    { icon: ClipboardList, label: 'Application tracking, status updates & withdrawal' },
    { icon: FileText, label: 'CV upload with AI extraction and review' },
    { icon: Target, label: 'ATS scoring against job descriptions' },
    { icon: MessageSquare, label: 'Text mock interviews with follow-ups' },
    { icon: Mic, label: 'Voice mock and recruitment interviews' },
    { icon: Layers, label: 'Portfolio and skill expectation tracking' },
    { icon: TrendingUp, label: 'Profile readiness scoring' },
    { icon: Brain, label: 'AI interview template configuration' },
    { icon: Sparkles, label: 'Explainable AI interview evaluation' },
    { icon: BarChart3, label: 'Automatic candidate ranking per job' },
    { icon: Users, label: 'Side-by-side candidate comparison (2–4)' },
    { icon: LineChart, label: 'Recruitment reports and CSV export' },
    { icon: Bell, label: 'Real-time notifications (in-app & email)' },
    { icon: CreditCard, label: 'Stripe subscriptions and billing portal' },
    { icon: Shield, label: 'Admin analytics and user management' },
];

const seekerPlans = [
    {
        name: 'Free',
        price: '$0',
        highlighted: false,
        features: [
            'Browse and apply to all public jobs',
            '3 mock interviews per calendar month',
            '1 stored CV with AI review',
            'Portfolio and skill tracking',
            'Application status notifications',
        ],
    },
    {
        name: 'Premium',
        price: '$19.99/mo',
        highlighted: true,
        features: [
            'Everything in Free',
            'Unlimited mock interviews',
            'Unlimited stored CVs',
            'ATS scoring against job descriptions',
            'Priority access to new features',
        ],
    },
];

const orgPlans = [
    {
        name: 'Basic',
        price: '$0',
        highlighted: false,
        features: ['Up to 5 active job listings', 'Application review & notes', 'AI interview assignment', 'Candidate ranking'],
    },
    {
        name: 'Professional',
        price: '$49/mo',
        highlighted: false,
        features: ['Unlimited job listings', 'Recruitment report exports', 'Full interview templates', 'Side-by-side comparison'],
    },
    {
        name: 'Enterprise',
        price: '$99/mo',
        highlighted: true,
        features: ['Everything in Professional', 'Advanced reporting & analytics', 'Priority support', 'Full platform access'],
    },
];

const securityFeatures = [
    { icon: Lock, title: 'Hashed passwords', detail: 'All credentials stored securely with industry-standard hashing.' },
    { icon: ShieldCheck, title: 'Two-factor auth', detail: 'Optional TOTP 2FA with recovery codes for every account.' },
    { icon: Mail, title: 'Email verification', detail: 'Verified email required before accessing authenticated features.' },
    { icon: Users, title: 'Role-based access', detail: 'Job seeker, HR, and admin roles with server-side middleware enforcement.' },
    { icon: Building2, title: 'Company scoping', detail: 'HR users can only manage jobs and candidates within their organization.' },
    { icon: CreditCard, title: 'Secure billing', detail: 'Stripe Checkout and webhooks with signature verification.' },
];

const notifications = [
    'Application submitted — candidate and company HR notified',
    'Application status changed — candidate notified',
    'Interview assigned — candidate notified',
    'Interview completed — HR notified for review',
    'AI score reviewed — candidate notified of outcome',
    'Ranking refreshed — HR notified of updated candidate order',
];
</script>

<template>
    <PublicLayout
        title="About Hirely"
        description="Hirely is an AI-assisted recruitment and career-preparation platform. Browse jobs, prepare with mock interviews, and hire smarter."
    >
        <!-- Hero -->
        <section class="pub-hero overflow-hidden rounded-3xl px-6 py-12 sm:px-10 sm:py-16">
            <p class="text-sm font-semibold tracking-wide text-white/80 uppercase">About Hirely</p>
            <h1 class="display mt-3 max-w-3xl text-[clamp(2.4rem,5vw,4rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                Find jobs. Apply. Interview. Hire smarter.
            </h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-white/85">
                Hirely brings together a public jobs board, AI-powered career preparation for candidates, and intelligent
                recruitment tools for organizations — all in one platform built to make hiring faster, fairer, and more
                transparent.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <Link href="/jobs" class="pub-cta inline-flex h-12 items-center gap-2 shadow-lg">
                    Browse jobs
                    <ArrowRight class="h-4 w-4" />
                </Link>
                <Link
                    :href="register()"
                    class="pub-cta inline-flex h-12 items-center gap-2 border border-white/30 bg-white/10 shadow-lg hover:bg-white/20"
                >
                    Create account
                </Link>
            </div>

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="item in highlights"
                    :key="item.label"
                    class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur-sm"
                >
                    <component :is="item.icon" class="h-5 w-5 text-white/90" />
                    <p class="mt-2 text-sm font-semibold text-white">{{ item.label }}</p>
                    <p class="mt-0.5 text-xs text-white/70">{{ item.detail }}</p>
                </div>
            </div>
        </section>

        <!-- What is Hirely -->
        <section class="mt-16 sm:mt-24">
            <div class="grid gap-10 lg:grid-cols-2 lg:items-start">
                <div>
                    <h2 class="display text-3xl font-medium tracking-tight">What is Hirely?</h2>
                    <p class="mt-4 text-lg leading-8 text-muted-foreground">
                        Hirely is a full-stack recruitment platform that connects job seekers with organizations actively
                        hiring. Unlike a traditional job board, Hirely embeds artificial intelligence throughout the hiring
                        journey — from CV optimization and mock interviews for candidates, to AI-assisted candidate ranking,
                        structured interview evaluation, and recruitment analytics for HR teams.
                    </p>
                    <p class="mt-4 text-lg leading-8 text-muted-foreground">
                        Anyone can browse open roles and explore organizations without an account. Job seekers register to
                        apply, build a portfolio, practice interviews, and track applications. Organizations register to post
                        jobs, review candidates, run AI-assisted interviews, and export hiring reports.
                    </p>
                </div>
                <div class="pub-card p-8">
                    <h3 class="flex items-center gap-2 text-lg font-semibold">
                        <Globe class="h-5 w-5 text-primary" />
                        The Hirely ecosystem
                    </h3>
                    <ul class="mt-5 space-y-3">
                        <li v-for="point in [
                            'Public jobs board visible to everyone — no login required',
                            'Organization directory with open job counts per company',
                            'Authenticated dashboards tailored to your role',
                            'AI services powered by Google Gemini (gemini-2.5-flash)',
                            'Secure payments and subscriptions via Stripe',
                            'Database notifications plus email for key hiring events',
                        ]" :key="point" class="flex gap-3 text-sm text-muted-foreground">
                            <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                            <span>{{ point }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Values -->
        <section class="mt-16 sm:mt-24">
            <div class="mb-10 text-center">
                <h2 class="display text-3xl font-medium tracking-tight">What we believe</h2>
                <p class="mt-4 text-lg text-muted-foreground">The principles that guide every feature we build.</p>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <article v-for="value in values" :key="value.title" class="pub-card p-6">
                    <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <component :is="value.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-4 font-semibold">{{ value.title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ value.description }}</p>
                </article>
            </div>
        </section>

        <!-- Audiences -->
        <section class="mt-16 sm:mt-24">
            <div class="mb-10 text-center">
                <h2 class="display text-3xl font-medium tracking-tight">Built for everyone in hiring</h2>
                <p class="mt-4 text-lg text-muted-foreground">
                    Whether you are looking for your next role or building your team, Hirely has dedicated tools for you.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <article v-for="audience in audiences" :key="audience.title" class="pub-card flex flex-col p-8">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <component :is="audience.icon" class="h-6 w-6" />
                    </div>
                    <h3 class="mt-4 text-xl font-semibold">{{ audience.title }}</h3>
                    <p class="mt-1 text-sm font-medium text-primary">{{ audience.tagline }}</p>
                    <ul class="mt-5 space-y-2.5">
                        <li v-for="point in audience.points" :key="point" class="flex gap-2.5 text-sm text-muted-foreground">
                            <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-primary/70" />
                            <span>{{ point }}</span>
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <!-- AI Features -->
        <section class="mt-16 sm:mt-24">
            <div class="mb-10 text-center">
                <div class="mx-auto inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-sm font-medium text-primary">
                    <Brain class="h-4 w-4" />
                    Powered by Google Gemini
                </div>
                <h2 class="display mt-4 text-3xl font-medium tracking-tight">AI at the core</h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-muted-foreground">
                    Intelligent features across the entire recruitment lifecycle, with human-in-the-loop review so hiring
                    decisions always stay in your hands.
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <article v-for="feature in aiFeatures" :key="feature.title" class="pub-card flex flex-col p-6">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <component :is="feature.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-4 font-semibold">{{ feature.title }}</h3>
                    <p class="mt-2 flex-1 text-sm leading-6 text-muted-foreground">{{ feature.description }}</p>
                    <ul class="mt-4 space-y-1.5 border-t border-border pt-4">
                        <li v-for="point in feature.points" :key="point" class="flex items-center gap-2 text-xs text-muted-foreground">
                            <span class="h-1 w-1 rounded-full bg-primary" />
                            {{ point }}
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <!-- How it works -->
        <section class="mt-16 sm:mt-24">
            <div class="mb-10">
                <h2 class="display text-3xl font-medium tracking-tight">How it works</h2>
                <p class="mt-4 max-w-2xl text-lg text-muted-foreground">
                    Two connected workflows — candidates prepare and apply, organizations evaluate and hire — linked through
                    a shared jobs board and application pipeline.
                </p>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <div class="pub-card p-8">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Users class="h-5 w-5" />
                        </div>
                        <h3 class="text-xl font-semibold">For job seekers</h3>
                    </div>
                    <ol class="mt-6 space-y-5">
                        <li v-for="(step, index) in seekerSteps" :key="step.title" class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                                >
                                    {{ index + 1 }}
                                </span>
                                <span v-if="index < seekerSteps.length - 1" class="mt-1 w-px flex-1 bg-border" />
                            </div>
                            <div class="pb-2">
                                <div class="flex items-center gap-2">
                                    <component :is="step.icon" class="h-4 w-4 text-primary" />
                                    <p class="font-medium">{{ step.title }}</p>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">{{ step.detail }}</p>
                            </div>
                        </li>
                    </ol>
                </div>

                <div class="pub-card p-8">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <h3 class="text-xl font-semibold">For organizations</h3>
                    </div>
                    <ol class="mt-6 space-y-5">
                        <li v-for="(step, index) in orgSteps" :key="step.title" class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                                >
                                    {{ index + 1 }}
                                </span>
                                <span v-if="index < orgSteps.length - 1" class="mt-1 w-px flex-1 bg-border" />
                            </div>
                            <div class="pb-2">
                                <div class="flex items-center gap-2">
                                    <component :is="step.icon" class="h-4 w-4 text-primary" />
                                    <p class="font-medium">{{ step.title }}</p>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">{{ step.detail }}</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>
        </section>

        <!-- Capabilities -->
        <section class="mt-16 sm:mt-24">
            <div class="mb-10 text-center">
                <h2 class="display text-3xl font-medium tracking-tight">Platform capabilities</h2>
                <p class="mt-4 text-lg text-muted-foreground">Everything Hirely offers, at a glance.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="capability in capabilities"
                    :key="capability.label"
                    class="pub-card flex items-center gap-3 px-4 py-3.5"
                >
                    <div class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <component :is="capability.icon" class="h-4 w-4" />
                    </div>
                    <span class="text-sm font-medium">{{ capability.label }}</span>
                </div>
            </div>
        </section>

        <!-- Notifications -->
        <section class="mt-16 sm:mt-24">
            <div class="pub-card p-8 sm:p-10">
                <div class="flex flex-col gap-8 lg:flex-row lg:items-start">
                    <div class="lg:w-1/3">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Bell class="h-5 w-5" />
                        </div>
                        <h2 class="display mt-4 text-2xl font-medium tracking-tight">Stay in the loop</h2>
                        <p class="mt-3 text-muted-foreground">
                            Hirely keeps everyone informed with in-app notifications and email alerts at every key stage of
                            the hiring process.
                        </p>
                    </div>
                    <ul class="grid flex-1 gap-3 sm:grid-cols-2">
                        <li
                            v-for="item in notifications"
                            :key="item"
                            class="flex items-start gap-3 rounded-xl border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground"
                        >
                            <Bell class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                            {{ item }}
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Plans -->
        <section class="mt-16 sm:mt-24">
            <div class="mb-10 text-center">
                <h2 class="display text-3xl font-medium tracking-tight">Flexible plans</h2>
                <p class="mt-4 text-lg text-muted-foreground">
                    Start free and upgrade when you need more. Billing is handled securely through Stripe.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div>
                    <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <Users class="h-5 w-5 text-primary" />
                        Job seeker plans
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="plan in seekerPlans"
                            :key="plan.name"
                            class="pub-card p-6"
                            :class="plan.highlighted ? 'ring-2 ring-primary/30' : ''"
                        >
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="text-lg font-semibold">{{ plan.name }}</span>
                                <span class="text-muted-foreground">{{ plan.price }}</span>
                            </div>
                            <ul class="mt-4 space-y-2">
                                <li v-for="feature in plan.features" :key="feature" class="flex gap-2 text-sm text-muted-foreground">
                                    <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <Building2 class="h-5 w-5 text-primary" />
                        Organization plans
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="plan in orgPlans"
                            :key="plan.name"
                            class="pub-card p-6"
                            :class="plan.highlighted ? 'ring-2 ring-primary/30' : ''"
                        >
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="text-lg font-semibold">{{ plan.name }}</span>
                                <span class="text-muted-foreground">{{ plan.price }}</span>
                            </div>
                            <ul class="mt-4 space-y-2">
                                <li v-for="feature in plan.features" :key="feature" class="flex gap-2 text-sm text-muted-foreground">
                                    <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Security -->
        <section class="mt-16 sm:mt-24">
            <div class="mb-10 text-center">
                <h2 class="display text-3xl font-medium tracking-tight">Security &amp; trust</h2>
                <p class="mt-4 text-lg text-muted-foreground">Enterprise-grade protections built into every layer.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="item in securityFeatures" :key="item.title" class="pub-card p-6">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <component :is="item.icon" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-4 font-semibold">{{ item.title }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">{{ item.detail }}</p>
                </article>
            </div>

            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <span v-for="tag in ['CSRF protection', 'Session auth', 'Rate limiting', 'Owner checks', 'Webhook signatures', 'HTTPS in production']" :key="tag" class="pub-chip">
                    {{ tag }}
                </span>
            </div>
        </section>

        <!-- CTA -->
        <section class="mt-16 sm:mt-24 rounded-3xl border border-border bg-muted/30 px-6 py-12 text-center sm:px-10">
            <Sparkles class="mx-auto h-8 w-8 text-primary" />
            <h2 class="display mt-4 text-3xl font-medium tracking-tight">Ready to get started?</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-muted-foreground">
                Browse open roles, create your candidate profile, or register your organization to start hiring on Hirely
                today.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <Link href="/jobs" class="pub-cta inline-flex items-center gap-2">
                    Browse jobs
                    <ArrowRight class="h-4 w-4" />
                </Link>
                <Link href="/organization" class="pub-cta-primary pub-cta">View organizations</Link>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm">
                <Link :href="register()" class="font-semibold text-primary hover:underline">Register as job seeker</Link>
                <span class="text-muted-foreground">·</span>
                <Link href="/organization/register" class="font-semibold text-primary hover:underline">Register organization</Link>
                <span class="text-muted-foreground">·</span>
                <Link :href="login()" class="font-semibold text-primary hover:underline">Log in</Link>
            </div>
        </section>
    </PublicLayout>
</template>
