<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { analytics } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BarChart3, TrendingUp, Users, Briefcase, Building2, CreditCard } from 'lucide-vue-next';

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

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card class="shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                        <Users class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ display(stats?.total_users) }}</div>
                        <p class="text-muted-foreground text-xs">Registered users</p>
                    </CardContent>
                </Card>
                <Card class="shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Job Seekers</CardTitle>
                        <Users class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ display(stats?.job_seekers) }}</div>
                        <p class="text-muted-foreground text-xs">{{ stats?.hr_professionals || 0 }} HR professionals</p>
                    </CardContent>
                </Card>
                <Card class="shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Job Postings</CardTitle>
                        <Briefcase class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ display(stats?.job_postings) }}</div>
                        <p class="text-muted-foreground text-xs">{{ stats?.applications || 0 }} applications</p>
                    </CardContent>
                </Card>
                <Card class="shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Growth Rate</CardTitle>
                        <TrendingUp class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ display(stats?.growth) }}%</div>
                        <p class="text-muted-foreground text-xs">Month over month users</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Building2 class="h-5 w-5" />
                            Companies
                        </CardTitle>
                        <CardDescription>Organizations on the platform</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">{{ display(stats?.companies) }}</p>
                    </CardContent>
                </Card>
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <CreditCard class="h-5 w-5" />
                            Revenue
                        </CardTitle>
                        <CardDescription>Successful payments</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">${{ display(stats?.revenue) }}</p>
                    </CardContent>
                </Card>
            </div>

            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>Recent sign-ups</CardTitle>
                    <CardDescription>Latest users on the platform</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="activity?.length" class="space-y-3">
                        <div v-for="(item, index) in activity" :key="index" class="flex items-center justify-between text-sm">
                            <div>
                                <p class="font-medium">{{ item.title }}</p>
                                <p class="text-muted-foreground capitalize">{{ item.detail }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                        <BarChart3 class="text-muted-foreground mb-4 h-12 w-12" />
                        <p class="text-muted-foreground text-sm">No recent sign-ups.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
