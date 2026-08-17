<script setup lang="ts">
import BreakdownChart from '@/components/dashboard/BreakdownChart.vue';
import TrendChart from '@/components/dashboard/TrendChart.vue';
import HirelyMark from '@/components/HirelyMark.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { analytics, cvReview, dashboard, interviews, jobApplications, postJobs, rankings, reports, reviewCandidates, userManagement } from '@/routes';
import { payments as adminPayments } from '@/routes/admin';
import { type BreadcrumbItem, type UserRole } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Briefcase,
    Building2,
    Clock,
    CreditCard,
    FileCheck,
    FileText,
    FolderKanban,
    ListOrdered,
    Target,
    TrendingDown,
    TrendingUp,
    Users,
    Video,
} from 'lucide-vue-next';
import { computed } from 'vue';

type ActivityItem = {
    type: string;
    title: string;
    detail?: string;
    at?: string | null;
    href?: string;
};

type ChartPoint = {
    label: string;
    value: number;
};

type FunnelItem = {
    status: string;
    count: number;
};

type ChargeItem = {
    id: number;
    amount: number;
    currency: string;
    status: string;
    type: string;
    description: string;
    user?: string | null;
    at?: string | null;
};

type ChargeSummary = {
    total: number;
    count: number;
    this_month: number;
    last_month: number;
    average: number;
    change: number;
    recent: ChargeItem[];
};

const props = defineProps<{
    role?: UserRole;
    stats?: Record<string, number | string | null>;
    funnel?: FunnelItem[];
    breakdown?: FunnelItem[];
    activity?: ActivityItem[];
    charges?: ChargeSummary;
    charts?: Record<string, ChartPoint[]>;
}>();

const page = usePage();
const userRole = computed(() => (props.role || page.props.auth?.user?.role || 'job_seeker') as UserRole);
const firstName = computed(() => page.props.auth?.user?.name?.split(' ')[0] || 'there');

const intro = computed(() => {
    if (userRole.value === 'hr_professional') {
        return 'Pipeline, interviews, and subscription charges for your hiring team.';
    }
    if (userRole.value === 'admin') {
        return 'Live users, applications, and platform charges across Hirely.';
    }
    return 'Applications, interviews, and any charges on your candidate account.';
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const display = (value: number | string | null | undefined) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return value;
};

const money = (value?: number | null, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: (value ?? 0) % 1 === 0 ? 0 : 2,
    }).format(value ?? 0);
};

const funnelTotal = computed(() => (props.funnel || []).reduce((sum, item) => sum + item.count, 0));

const funnelWidth = (count: number) => {
    if (funnelTotal.value === 0) {
        return '0%';
    }

    return `${Math.max(4, Math.round((count / funnelTotal.value) * 100))}%`;
};

const formatTime = (value?: string | null) => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleDateString();
};

const changeLabel = (value?: number | null) => {
    if (value == null) {
        return 'vs last month';
    }

    const prefix = value > 0 ? '+' : '';

    return `${prefix}${value}% vs last month`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <section class="hirely-hero overflow-hidden rounded-[1.75rem] px-6 py-7 sm:px-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="flex items-center gap-2 text-xs font-medium tracking-wide text-white/75">
                            <HirelyMark class="size-5" />
                            Hirely
                            <span class="rounded-full bg-white/15 px-2 py-0.5 capitalize">{{ userRole.replace('_', ' ') }}</span>
                        </p>
                        <h1 class="hirely-display mt-2 text-3xl leading-tight font-medium sm:text-4xl">
                            Hello, {{ firstName }}
                        </h1>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-white/80">
                            {{ intro }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:min-w-[240px]">
                        <div class="rounded-2xl bg-white/12 px-3 py-2.5">
                            <p class="text-[11px] tracking-wide text-white/70 uppercase">Charges this month</p>
                            <p class="mt-1 text-lg font-semibold">{{ money(charges?.this_month) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/12 px-3 py-2.5">
                            <p class="text-[11px] tracking-wide text-white/70 uppercase">Successful charges</p>
                            <p class="mt-1 text-lg font-semibold">{{ display(charges?.count) }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <Link
                        v-if="userRole === 'job_seeker'"
                        href="/jobs"
                        class="inline-flex h-9 items-center rounded-full bg-white px-4 text-sm font-semibold text-indigo-700"
                    >
                        Browse jobs
                    </Link>
                    <Link
                        v-else-if="userRole === 'hr_professional'"
                        :href="postJobs()"
                        class="inline-flex h-9 items-center rounded-full bg-white px-4 text-sm font-semibold text-indigo-700"
                    >
                        Post a job
                    </Link>
                    <Link
                        v-else
                        href="/"
                        class="inline-flex h-9 items-center rounded-full bg-white px-4 text-sm font-semibold text-indigo-700"
                    >
                        Public jobs board
                    </Link>
                    <Link
                        v-if="userRole === 'job_seeker'"
                        :href="jobApplications()"
                        class="inline-flex h-9 items-center rounded-full bg-white/15 px-4 text-sm font-semibold text-white"
                    >
                        My applications
                    </Link>
                    <Link
                        v-else-if="userRole === 'hr_professional'"
                        href="/subscriptions"
                        class="inline-flex h-9 items-center rounded-full bg-white/15 px-4 text-sm font-semibold text-white"
                    >
                        Billing
                    </Link>
                    <Link
                        v-else
                        :href="adminPayments()"
                        class="inline-flex h-9 items-center rounded-full bg-white/15 px-4 text-sm font-semibold text-white"
                    >
                        All charges
                    </Link>
                </div>
            </section>

            <template v-if="userRole === 'job_seeker'">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">CV reviews</p>
                            <FileText class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.cv_reviews) }}</p>
                        <p class="dash-stat-hint">Processed resumes</p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">ATS scores</p>
                            <FileCheck class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.ats_scores) }}</p>
                        <p class="dash-stat-hint">Compatibility checks</p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Interviews</p>
                            <Video class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.interviews_completed) }}</p>
                        <p class="dash-stat-hint">
                            {{ stats?.interviews_open || 0 }} assigned open ·
                            {{ stats?.mock_interviews || 0 }} mock completed
                        </p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Charges</p>
                            <CreditCard class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ money(charges?.total) }}</p>
                        <p class="dash-stat-hint flex items-center gap-1">
                            <TrendingUp v-if="(charges?.change || 0) >= 0" class="h-3 w-3" />
                            <TrendingDown v-else class="h-3 w-3" />
                            {{ changeLabel(charges?.change) }}
                        </p>
                    </div>
                </div>
            </template>

            <template v-else-if="userRole === 'hr_professional'">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Job postings</p>
                            <Briefcase class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.active_jobs) }}</p>
                        <p class="dash-stat-hint">{{ stats?.total_jobs || 0 }} total jobs</p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Candidates</p>
                            <Users class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.total_applicants) }}</p>
                        <p class="dash-stat-hint">{{ stats?.under_review || 0 }} still need review</p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Interviews to review</p>
                            <Clock class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.interviews_pending_review) }}</p>
                        <p class="dash-stat-hint">{{ stats?.interviews_completed || 0 }} completed</p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Plan charges</p>
                            <CreditCard class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ money(charges?.this_month) }}</p>
                        <p class="dash-stat-hint capitalize">
                            {{ stats?.subscription_plan }} · {{ money(charges?.total) }} total
                        </p>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Total users</p>
                            <Users class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.total_users) }}</p>
                        <p class="dash-stat-hint">{{ stats?.job_seekers || 0 }} seekers · {{ stats?.hr_professionals || 0 }} HR</p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Companies</p>
                            <Building2 class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.companies) }}</p>
                        <p class="dash-stat-hint">{{ stats?.job_postings || 0 }} job postings</p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Charge volume</p>
                            <CreditCard class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ money(charges?.total ?? Number(stats?.revenue || 0)) }}</p>
                        <p class="dash-stat-hint">{{ charges?.count || 0 }} successful charges</p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Growth</p>
                            <TrendingUp class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.growth) }}%</p>
                        <p class="dash-stat-hint">Users vs last month</p>
                    </div>
                </div>
            </template>

            <div class="grid gap-3 lg:grid-cols-2">
                <div class="dash-stat">
                    <p class="dash-stat-label">
                        {{ userRole === 'admin' ? 'Charge revenue' : 'Charges' }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">Successful payments over the last 6 months</p>
                    <TrendChart :points="charts?.charges || []" type="line" format="money" />
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">
                        {{ userRole === 'admin' ? 'New users' : 'Applications' }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ userRole === 'admin' ? 'Sign-ups by month' : 'Volume over the last 6 months' }}
                    </p>
                    <TrendChart
                        :points="(userRole === 'admin' ? charts?.users : charts?.applications) || []"
                        type="bar"
                    />
                </div>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <div v-if="userRole === 'hr_professional' && funnel?.length" class="dash-stat">
                    <p class="dash-stat-label">Hiring pipeline</p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ funnelTotal }} applications by current status</p>
                    <div class="mt-4 space-y-2.5">
                        <div v-for="item in funnel" :key="item.status" class="flex items-center gap-3 text-sm">
                            <span class="w-28 capitalize text-muted-foreground">{{ item.status.replace('_', ' ') }}</span>
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                <div class="h-1.5 rounded-full bg-primary" :style="{ width: funnelWidth(item.count) }" />
                            </div>
                            <span class="w-8 text-right text-xs font-medium">{{ item.count }}</span>
                        </div>
                    </div>
                </div>
                <div v-else-if="breakdown?.length" class="dash-stat">
                    <p class="dash-stat-label">
                        {{ userRole === 'admin' ? 'Users by role' : 'Application status' }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">Share of live records</p>
                    <div class="mt-4">
                        <BreakdownChart :items="breakdown" />
                    </div>
                </div>
                <div v-else class="dash-stat">
                    <p class="dash-stat-label">Interviews</p>
                    <p class="mt-1 text-sm text-muted-foreground">Assigned interviews over the last 6 months</p>
                    <TrendChart :points="charts?.interviews || []" type="bar" />
                </div>

                <div class="dash-stat">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="dash-stat-label">Recent charges</p>
                            <p class="mt-1 text-sm text-muted-foreground">Successful Stripe charges</p>
                        </div>
                        <p class="text-sm font-medium">{{ money(charges?.average) }} avg</p>
                    </div>
                    <div v-if="charges?.recent?.length" class="mt-4 space-y-2">
                        <div
                            v-for="charge in charges.recent"
                            :key="charge.id"
                            class="flex items-center justify-between gap-3 rounded-xl bg-muted/40 px-3 py-2"
                        >
                            <div>
                                <p class="text-sm font-medium">{{ charge.description }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ charge.user ? `${charge.user} · ` : '' }}{{ formatTime(charge.at) }}
                                </p>
                            </div>
                            <span class="text-sm font-semibold">{{ money(charge.amount, charge.currency) }}</span>
                        </div>
                    </div>
                    <div v-else class="dash-empty mt-4 py-8">
                        <CreditCard class="mb-2 h-6 w-6" />
                        <p class="text-sm">No charges yet.</p>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="hirely-display mb-3 text-xl">Jump back in</h2>
                <template v-if="userRole === 'job_seeker'">
                    <div class="grid gap-3 md:grid-cols-3">
                        <Link :href="cvReview()" class="dash-row group">
                            <p class="flex items-center gap-2 font-medium group-hover:text-primary">
                                <FileText class="h-4 w-4" />
                                CV Review
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">Upload and get feedback on your resume</p>
                        </Link>
                        <Link :href="jobApplications()" class="dash-row group">
                            <p class="flex items-center gap-2 font-medium group-hover:text-primary">
                                <Target class="h-4 w-4" />
                                Applications
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">Track jobs you have applied to</p>
                        </Link>
                        <Link :href="interviews()" class="dash-row group">
                            <p class="flex items-center gap-2 font-medium group-hover:text-primary">
                                <FolderKanban class="h-4 w-4" />
                                Interviews
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">Complete assigned recruitment interviews</p>
                        </Link>
                    </div>
                </template>

                <template v-else-if="userRole === 'hr_professional'">
                    <div class="grid gap-3 md:grid-cols-3">
                        <Link :href="reviewCandidates()" class="dash-row group">
                            <p class="flex items-center gap-2 font-medium group-hover:text-primary">
                                <Users class="h-4 w-4" />
                                Review Candidates
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">Move applicants through the pipeline</p>
                        </Link>
                        <Link :href="rankings()" class="dash-row group">
                            <p class="flex items-center gap-2 font-medium group-hover:text-primary">
                                <ListOrdered class="h-4 w-4" />
                                Rankings
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">See weighted shortlists per job</p>
                        </Link>
                        <Link :href="reports()" class="dash-row group">
                            <p class="flex items-center gap-2 font-medium group-hover:text-primary">
                                <BarChart3 class="h-4 w-4" />
                                Reports
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">Funnel, interview volume, and score spread</p>
                        </Link>
                    </div>
                </template>

                <template v-else>
                    <div class="grid gap-3 md:grid-cols-3">
                        <Link :href="userManagement()" class="dash-row group">
                            <p class="flex items-center gap-2 font-medium group-hover:text-primary">
                                <Users class="h-4 w-4" />
                                User Management
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">Manage all platform users</p>
                        </Link>
                        <Link :href="analytics()" class="dash-row group">
                            <p class="flex items-center gap-2 font-medium group-hover:text-primary">
                                <BarChart3 class="h-4 w-4" />
                                Analytics
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">View platform analytics and insights</p>
                        </Link>
                        <Link :href="adminPayments()" class="dash-row group">
                            <p class="flex items-center gap-2 font-medium group-hover:text-primary">
                                <CreditCard class="h-4 w-4" />
                                Payments
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">Manage payments and subscriptions</p>
                        </Link>
                    </div>
                </template>
            </div>

            <div>
                <h2 class="hirely-display mb-3 text-xl">Recent activity</h2>
                <div v-if="activity && activity.length > 0" class="space-y-2">
                    <Link
                        v-for="(item, index) in activity"
                        :key="index"
                        :href="item.href || dashboard().url"
                        class="dash-row flex items-start justify-between"
                    >
                        <div>
                            <p class="text-sm font-medium">{{ item.title }}</p>
                            <p class="text-xs capitalize text-muted-foreground">{{ item.detail }}</p>
                        </div>
                        <span class="text-xs text-muted-foreground">{{ formatTime(item.at) }}</span>
                    </Link>
                </div>
                <div v-else class="dash-empty">
                    <Clock class="mb-3 h-8 w-8" />
                    <p class="text-sm">No recent activity yet.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
