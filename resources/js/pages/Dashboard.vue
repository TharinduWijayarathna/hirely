<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, cvReview, interviews, jobApplications } from '@/routes';
import { reviewCandidates, rankings, reports } from '@/routes';
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
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Welcome Back</h1>
                <p class="text-muted-foreground mt-2">
                    <template v-if="userRole === 'job_seeker'">Your career preparation dashboard</template>
                    <template v-else-if="userRole === 'hr_professional'">Your HR management dashboard</template>
                    <template v-else>Your platform administration dashboard</template>
                </p>
            </div>

            <template v-if="userRole === 'job_seeker'">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">CV Reviews</CardTitle>
                            <FileText class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.cv_reviews) }}</div>
                            <p class="text-muted-foreground text-xs">Processed resumes</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">ATS Scores</CardTitle>
                            <FileCheck class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.ats_scores) }}</div>
                            <p class="text-muted-foreground text-xs">Compatibility checks</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Interviews</CardTitle>
                            <Video class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.interviews_completed) }}</div>
                            <p class="text-muted-foreground text-xs">
                                {{ stats?.interviews_open || 0 }} assigned open ·
                                {{ stats?.mock_interviews || 0 }} mock completed
                            </p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Profile Score</CardTitle>
                            <TrendingUp class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.profile_score) }}</div>
                            <p class="text-muted-foreground text-xs">
                                {{ stats?.applications || 0 }} applications
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <template v-else-if="userRole === 'hr_professional'">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Job Postings</CardTitle>
                            <Briefcase class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.active_jobs) }}</div>
                            <p class="text-muted-foreground text-xs">{{ stats?.total_jobs || 0 }} total jobs</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Candidates</CardTitle>
                            <Users class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.total_applicants) }}</div>
                            <p class="text-muted-foreground text-xs">Total applicants</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Needs attention</CardTitle>
                            <Clock class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.under_review) }}</div>
                            <p class="text-muted-foreground text-xs">
                                {{ stats?.interviews_pending_review || 0 }} interviews to review
                            </p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Subscription</CardTitle>
                            <CreditCard class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.subscription_plan) }}</div>
                            <p class="text-muted-foreground text-xs capitalize">
                                {{ stats?.subscription_status === 'none' ? 'No paid plan' : stats?.subscription_status }}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Card v-if="funnel?.length" class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Pipeline</CardTitle>
                        <CardDescription>Applications by current status</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-for="item in funnel" :key="item.status" class="flex items-center gap-3 text-sm">
                            <span class="w-28 capitalize">{{ item.status.replace('_', ' ') }}</span>
                            <div class="bg-muted h-2 flex-1 overflow-hidden rounded">
                                <div class="bg-primary h-2" :style="{ width: funnelWidth(item.count) }" />
                            </div>
                            <span class="w-8 text-right">{{ item.count }}</span>
                        </div>
                    </CardContent>
                </Card>
            </template>

            <template v-else>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                            <Users class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.total_users) }}</div>
                            <p class="text-muted-foreground text-xs">{{ stats?.job_seekers || 0 }} seekers · {{ stats?.hr_professionals || 0 }} HR</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Companies</CardTitle>
                            <Building2 class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.companies) }}</div>
                            <p class="text-muted-foreground text-xs">{{ stats?.job_postings || 0 }} job postings</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Revenue</CardTitle>
                            <CreditCard class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">${{ display(stats?.revenue) }}</div>
                            <p class="text-muted-foreground text-xs">Successful payments</p>
                        </CardContent>
                    </Card>
                    <Card class="shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Growth</CardTitle>
                            <TrendingUp class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ display(stats?.growth) }}%</div>
                            <p class="text-muted-foreground text-xs">Users vs last month</p>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <div>
                <h2 class="mb-4 text-xl font-semibold">Quick Actions</h2>
                <template v-if="userRole === 'job_seeker'">
                    <div class="grid gap-4 md:grid-cols-3">
                        <Card class="group shadow-sm">
                            <Link :href="cvReview()">
                                <CardHeader>
                                    <CardTitle class="group-hover:text-primary flex items-center gap-2 transition-colors">
                                        <FileText class="h-5 w-5" />
                                        CV Review
                                    </CardTitle>
                                    <CardDescription>Upload and get feedback on your resume</CardDescription>
                                </CardHeader>
                            </Link>
                        </Card>
                        <Card class="group shadow-sm">
                            <Link :href="jobApplications()">
                                <CardHeader>
                                    <CardTitle class="group-hover:text-primary flex items-center gap-2 transition-colors">
                                        <Target class="h-5 w-5" />
                                        Applications
                                    </CardTitle>
                                    <CardDescription>Track jobs you have applied to</CardDescription>
                                </CardHeader>
                            </Link>
                        </Card>
                        <Card class="group shadow-sm">
                            <Link :href="interviews()">
                                <CardHeader>
                                    <CardTitle class="group-hover:text-primary flex items-center gap-2 transition-colors">
                                        <FolderKanban class="h-5 w-5" />
                                        Interviews
                                    </CardTitle>
                                    <CardDescription>Complete assigned recruitment interviews</CardDescription>
                                </CardHeader>
                            </Link>
                        </Card>
                    </div>
                </template>

                <template v-else-if="userRole === 'hr_professional'">
                    <div class="grid gap-4 md:grid-cols-3">
                        <Card class="group shadow-sm">
                            <Link :href="reviewCandidates()">
                                <CardHeader>
                                    <CardTitle class="group-hover:text-primary flex items-center gap-2 transition-colors">
                                        <Users class="h-5 w-5" />
                                        Review Candidates
                                    </CardTitle>
                                    <CardDescription>Move applicants through the pipeline</CardDescription>
                                </CardHeader>
                            </Link>
                        </Card>
                        <Card class="group shadow-sm">
                            <Link :href="rankings()">
                                <CardHeader>
                                    <CardTitle class="group-hover:text-primary flex items-center gap-2 transition-colors">
                                        <ListOrdered class="h-5 w-5" />
                                        Rankings
                                    </CardTitle>
                                    <CardDescription>See weighted shortlists per job</CardDescription>
                                </CardHeader>
                            </Link>
                        </Card>
                        <Card class="group shadow-sm">
                            <Link :href="reports()">
                                <CardHeader>
                                    <CardTitle class="group-hover:text-primary flex items-center gap-2 transition-colors">
                                        <BarChart3 class="h-5 w-5" />
                                        Reports
                                    </CardTitle>
                                    <CardDescription>Funnel, interview volume, and score spread</CardDescription>
                                </CardHeader>
                            </Link>
                        </Card>
                    </div>
                </template>

                <template v-else>
                    <div class="grid gap-4 md:grid-cols-3">
                        <Card class="group shadow-sm">
                            <Link :href="userManagement()">
                                <CardHeader>
                                    <CardTitle class="group-hover:text-primary flex items-center gap-2 transition-colors">
                                        <Users class="h-5 w-5" />
                                        User Management
                                    </CardTitle>
                                    <CardDescription>Manage all platform users</CardDescription>
                                </CardHeader>
                            </Link>
                        </Card>
                        <Card class="group shadow-sm">
                            <Link :href="analytics()">
                                <CardHeader>
                                    <CardTitle class="group-hover:text-primary flex items-center gap-2 transition-colors">
                                        <BarChart3 class="h-5 w-5" />
                                        Analytics
                                    </CardTitle>
                                    <CardDescription>View platform analytics and insights</CardDescription>
                                </CardHeader>
                            </Link>
                        </Card>
                        <Card class="group shadow-sm">
                            <Link :href="adminPayments()">
                                <CardHeader>
                                    <CardTitle class="group-hover:text-primary flex items-center gap-2 transition-colors">
                                        <CreditCard class="h-5 w-5" />
                                        Payments
                                    </CardTitle>
                                    <CardDescription>Manage payments and subscriptions</CardDescription>
                                </CardHeader>
                            </Link>
                        </Card>
                    </div>
                </template>
            </div>

            <div>
                <h2 class="mb-4 text-xl font-semibold">Recent Activity</h2>
                <Card class="shadow-sm">
                    <CardContent class="pt-6">
                        <div v-if="activity && activity.length > 0" class="space-y-3">
                            <Link
                                v-for="(item, index) in activity"
                                :key="index"
                                :href="item.href || dashboard().url"
                                class="hover:bg-muted/50 flex items-start justify-between rounded-md p-3"
                            >
                                <div>
                                    <p class="text-sm font-medium">{{ item.title }}</p>
                                    <p class="text-muted-foreground text-xs capitalize">{{ item.detail }}</p>
                                </div>
                                <span class="text-muted-foreground text-xs">{{ formatTime(item.at) }}</span>
                            </Link>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                            <Clock class="text-muted-foreground mb-4 h-12 w-12" />
                            <p class="text-muted-foreground text-sm">
                                No recent activity yet.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
