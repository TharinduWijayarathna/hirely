<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { analytics } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BarChart3 } from 'lucide-vue-next';

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
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Analytics',
        href: analytics().url,
    },
];

const display = (value?: number | null) => (value == null ? '—' : value);
</script>

<template>
    <Head title="Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Platform Analytics</h1>
                <p class="text-muted-foreground mt-2">Live counts for users, companies, jobs, and revenue.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                <div class="dash-stat">
                    <p class="dash-stat-label">Total users</p>
                    <p class="dash-stat-value">{{ display(stats?.total_users) }}</p>
                    <p class="dash-stat-hint">Registered users</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Job seekers</p>
                    <p class="dash-stat-value">{{ display(stats?.job_seekers) }}</p>
                    <p class="dash-stat-hint">{{ stats?.hr_professionals || 0 }} HR professionals</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Job postings</p>
                    <p class="dash-stat-value">{{ display(stats?.job_postings) }}</p>
                    <p class="dash-stat-hint">{{ stats?.applications || 0 }} applications</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Growth</p>
                    <p class="dash-stat-value">{{ display(stats?.growth) }}%</p>
                    <p class="dash-stat-hint">Month over month users</p>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="dash-stat">
                    <p class="dash-stat-label">Companies</p>
                    <p class="dash-stat-value">{{ display(stats?.companies) }}</p>
                    <p class="dash-stat-hint">Organizations on the platform</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Revenue</p>
                    <p class="dash-stat-value">${{ display(stats?.revenue) }}</p>
                    <p class="dash-stat-hint">Successful payments</p>
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
    </AppLayout>
</template>
