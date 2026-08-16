<script setup lang="ts">
import HirelyMark from '@/components/HirelyMark.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, cvReview, interviews, jobApplications } from '@/routes';
import { reviewCandidates, rankings, reports, postJobs } from '@/routes';
import { userManagement, analytics } from '@/routes';
import { payments as adminPayments } from '@/routes/admin';
import { type BreadcrumbItem, type UserRole } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    FileText,
    FileCheck,
    Video,
    TrendingUp,
    Clock,
    Target,
    Briefcase,
    Users,
    BarChart3,
    CreditCard,
    Building2,
    FolderKanban,
    ListOrdered,
} from 'lucide-vue-next';
import { computed } from 'vue';

type ActivityItem = {
    type: string;
    title: string;
    detail?: string;
    at?: string | null;
    href?: string;
};

type FunnelItem = {
    status: string;
    count: number;
};

const props = defineProps<{
    role?: UserRole;
    stats?: Record<string, number | string | null>;
    funnel?: FunnelItem[];
    activity?: ActivityItem[];
}>();

const page = usePage();
const userRole = computed(() => (props.role || page.props.auth?.user?.role || 'job_seeker') as UserRole);
const firstName = computed(() => page.props.auth?.user?.name?.split(' ')[0] || 'there');

const intro = computed(() => {
    if (userRole.value === 'hr_professional') {
        return 'Post roles, review candidates, and keep the last word on every hire.';
    }
    if (userRole.value === 'admin') {
        return 'Companies, users, and the public jobs board — all in Hirely.';
    }
    return 'Find jobs, apply from a link, and sit the interview the company assigned.';
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
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-6">
            <section class="hirely-hero overflow-hidden rounded-[1.75rem] px-6 py-7 sm:px-8">
                <p class="flex items-center gap-2 text-xs font-medium tracking-wide text-white/75">
                    <HirelyMark class="size-5" />
                    Hirely
                </p>
                <h1 class="hirely-display mt-2 text-3xl leading-tight font-medium sm:text-4xl">
                    Hello, {{ firstName }}
                </h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/80">
                    {{ intro }}
                </p>
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
                </div>
            </section>

            <template v-if="userRole === 'job_seeker'">
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
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
                            <p class="dash-stat-label">Profile score</p>
                            <TrendingUp class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.profile_score) }}</p>
                        <p class="dash-stat-hint">{{ stats?.applications || 0 }} applications</p>
                    </div>
                </div>
            </template>

            <template v-else-if="userRole === 'hr_professional'">
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
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
                        <p class="dash-stat-hint">Total applicants</p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Needs attention</p>
                            <Clock class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.under_review) }}</p>
                        <p class="dash-stat-hint">
                            {{ stats?.interviews_pending_review || 0 }} interviews to review
                        </p>
                    </div>
                    <div class="dash-stat">
                        <div class="flex items-center justify-between">
                            <p class="dash-stat-label">Subscription</p>
                            <CreditCard class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">{{ display(stats?.subscription_plan) }}</p>
                        <p class="dash-stat-hint capitalize">
                            {{ stats?.subscription_status === 'none' ? 'No paid plan' : stats?.subscription_status }}
                        </p>
                    </div>
                </div>

                <div v-if="funnel?.length" class="dash-stat">
                    <p class="dash-stat-label">Pipeline</p>
                    <p class="mt-1 text-sm text-muted-foreground">Applications by current status</p>
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
            </template>

            <template v-else>
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
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
                            <p class="dash-stat-label">Revenue</p>
                            <CreditCard class="h-4 w-4 text-primary" />
                        </div>
                        <p class="dash-stat-value">${{ display(stats?.revenue) }}</p>
                        <p class="dash-stat-hint">Successful payments</p>
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
