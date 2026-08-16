<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { payments } from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { CreditCard, DollarSign, TrendingUp, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface Payment {
    id: number;
    amount: number;
    currency: string;
    status: string;
    type: string;
    description: string;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    payment_plan: {
        id: number;
        name: string;
        display_name: string;
    } | null;
}

interface Props {
    stats: {
        totalRevenue: number;
        monthlyRevenue: number;
        activeSubscriptions: number;
    };
    recentPayments: {
        data: Payment[];
        links: any;
        meta: any;
    };
    revenueByMonth: Array<{
        month: string;
        revenue: number;
    }>;
    subscriptionsByStatus: Record<string, number>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payment Management',
        href: payments().url,
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const getStatusBadge = (status: string) => {
    const statusColors: Record<string, string> = {
        succeeded: 'bg-green-500',
        pending: 'bg-yellow-500',
        failed: 'bg-red-500',
        canceled: 'bg-gray-500',
        refunded: 'bg-blue-500',
    };
    
    return statusColors[status] || 'bg-gray-500';
};
</script>

<template>
    <Head title="Payment Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Payment Management</h1>
                <p class="text-muted-foreground mt-2">
                    Manage payments, subscriptions, and billing across the platform
                </p>
            </div>

            <!-- Statistics -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card class="shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.totalRevenue) }}</div>
                        <p class="text-xs text-muted-foreground">All time revenue</p>
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Subscriptions</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ props.stats.activeSubscriptions }}</div>
                        <p class="text-xs text-muted-foreground">Current subscriptions</p>
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Monthly Revenue</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.monthlyRevenue) }}</div>
                        <p class="text-xs text-muted-foreground">This month</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Payment Transactions -->
            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>Payment Transactions</CardTitle>
                    <CardDescription>View and manage all payment transactions</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="props.recentPayments.data.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                        <CreditCard class="h-12 w-12 text-muted-foreground mb-4" />
                        <p class="text-sm text-muted-foreground">
                            No payment transactions found
                        </p>
                    </div>
                    
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-4 font-medium">User</th>
                                    <th class="text-left p-4 font-medium">Plan</th>
                                    <th class="text-left p-4 font-medium">Amount</th>
                                    <th class="text-left p-4 font-medium">Status</th>
                                    <th class="text-left p-4 font-medium">Type</th>
                                    <th class="text-left p-4 font-medium">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in props.recentPayments.data" :key="payment.id" class="border-b hover:bg-muted/50">
                                    <td class="p-4">
                                        <div>
                                            <div class="font-medium">{{ payment.user.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ payment.user.email }}</div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        {{ payment.payment_plan?.display_name || 'One-time' }}
                                    </td>
                                    <td class="p-4 font-medium">
                                        {{ formatCurrency(payment.amount) }}
                                    </td>
                                    <td class="p-4">
                                        <Badge :class="getStatusBadge(payment.status)">
                                            {{ payment.status }}
                                        </Badge>
                                    </td>
                                    <td class="p-4">{{ payment.type }}</td>
                                    <td class="p-4">
                                        {{ new Date(payment.created_at).toLocaleDateString() }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
