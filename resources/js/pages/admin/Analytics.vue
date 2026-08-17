<script setup lang="ts">
import BreakdownChart from '@/components/dashboard/BreakdownChart.vue';
import TrendChart from '@/components/dashboard/TrendChart.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { analytics } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BarChart3, CreditCard } from 'lucide-vue-next';

type ChartPoint = { label: string; value: number };
type FunnelItem = { status: string; count: number };
type ChargeItem = {
    id: number;
    amount: number;
    currency: string;
    description: string;
    user?: string | null;
    at?: string | null;
};

const props = defineProps<{
    stats?: {
        total_users?: number;
        job_seekers?: number;
        hr_professionals?: number;
        companies?: number;
        job_postings?: number;
        applications?: number;
        revenue?: number;
        growth?: number;
    };
    activity?: Array<{ title: string; detail?: string; at?: string | null }>;
    charges?: {
        total: number;
        count: number;
        this_month: number;
        average: number;
        change: number;
        recent: ChargeItem[];
    };
    charts?: {
        users?: ChartPoint[];
        applications?: ChartPoint[];
        charges?: ChartPoint[];
    };
    breakdown?: FunnelItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: analytics().url,
    },
];

const display = (value?: number | null) => (value == null ? '—' : value);

const money = (value?: number | null) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: (value ?? 0) % 1 === 0 ? 0 : 2,
    }).format(value ?? 0);

const formatTime = (value?: string | null) => (value ? new Date(value).toLocaleDateString() : '');
</script>

<template>
    <Head title="Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Platform Analytics</h1>
                <p class="text-muted-foreground mt-2">Users, applications, and successful Stripe charges.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                <div class="dash-stat">
                    <p class="dash-stat-label">Total users</p>
                    <p class="dash-stat-value">{{ display(stats?.total_users) }}</p>
                    <p class="dash-stat-hint">{{ stats?.job_seekers || 0 }} seekers · {{ stats?.hr_professionals || 0 }} HR</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Job postings</p>
                    <p class="dash-stat-value">{{ display(stats?.job_postings) }}</p>
                    <p class="dash-stat-hint">{{ stats?.applications || 0 }} applications</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Charge volume</p>
                    <p class="dash-stat-value">{{ money(charges?.total ?? stats?.revenue) }}</p>
                    <p class="dash-stat-hint">{{ charges?.count || 0 }} successful charges</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">This month</p>
                    <p class="dash-stat-value">{{ money(charges?.this_month) }}</p>
                    <p class="dash-stat-hint">{{ display(stats?.growth) }}% user growth</p>
                </div>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <div class="dash-stat">
                    <p class="dash-stat-label">Charge revenue</p>
                    <p class="mt-1 text-sm text-muted-foreground">Successful payments, last 6 months</p>
                    <TrendChart :points="charts?.charges || []" type="line" format="money" />
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">New users</p>
                    <p class="mt-1 text-sm text-muted-foreground">Sign-ups by month</p>
                    <TrendChart :points="charts?.users || []" type="bar" />
                </div>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <div class="dash-stat">
                    <p class="dash-stat-label">Users by role</p>
                    <div class="mt-4">
                        <BreakdownChart :items="breakdown || []" />
                    </div>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Applications</p>
                    <p class="mt-1 text-sm text-muted-foreground">Submitted over the last 6 months</p>
                    <TrendChart :points="charts?.applications || []" type="bar" />
                </div>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <div>
                    <h2 class="hirely-display mb-3 text-xl">Recent charges</h2>
                    <div v-if="charges?.recent?.length" class="space-y-2">
                        <div v-for="charge in charges.recent" :key="charge.id" class="dash-row flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium">{{ charge.description }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ charge.user ? `${charge.user} · ` : '' }}{{ formatTime(charge.at) }}
                                </p>
                            </div>
                            <span class="text-sm font-semibold">{{ money(charge.amount) }}</span>
                        </div>
                    </div>
                    <div v-else class="dash-empty">
                        <CreditCard class="mb-3 h-8 w-8" />
                        <p class="text-sm">No charges yet.</p>
                    </div>
                </div>
                <div>
                    <h2 class="hirely-display mb-3 text-xl">Recent sign-ups</h2>
                    <div v-if="activity?.length" class="space-y-2">
                        <div v-for="(item, index) in activity" :key="index" class="dash-row flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium">{{ item.title }}</p>
                                <p class="text-xs capitalize text-muted-foreground">{{ item.detail }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="dash-empty">
                        <BarChart3 class="mb-3 h-8 w-8" />
                        <p class="text-sm">No recent sign-ups.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
