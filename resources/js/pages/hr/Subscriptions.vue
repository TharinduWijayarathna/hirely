<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { subscriptions } from '@/routes';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CreditCard, CheckCircle2, Crown, X, Play, ArrowUp } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    plans: Array<{
        id: number;
        name: string;
        display_name: string;
        description: string;
        amount: number;
        currency: string;
        interval: string;
        features: string[] | null;
    }>;
    activeSubscription: {
        id: number;
        status: string;
        payment_plan: {
            id: number;
            name: string;
            display_name: string;
            amount: number;
        };
        ends_at: string | null;
        cancel_at_period_end: boolean;
    } | null;
    subscriptions: Array<{
        id: number;
        status: string;
        payment_plan: {
            id: number;
            name: string;
            display_name: string;
            amount: number;
        };
    }>;
}

const props = defineProps<Props>();
const page = usePage();
const errors = computed(() => page.props.errors || {});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Subscriptions',
        href: subscriptions().url,
    },
];

// Check if user is on free plan
const isFreePlan = computed(() => {
    return props.activeSubscription?.payment_plan?.amount === 0 && props.activeSubscription?.status === 'active';
});

// Check if user can upgrade (on free plan)
const canUpgrade = computed(() => {
    return isFreePlan.value;
});

// Check if plan is the current active plan
const isCurrentPlan = (planId: number) => {
    return props.activeSubscription?.payment_plan?.id === planId && props.activeSubscription?.status === 'active';
};

// Determine if button should be enabled for a plan
const isButtonEnabled = (plan: { id: number; amount: number }) => {
    // If it's the current plan, disable
    if (isCurrentPlan(plan.id)) {
        return false;
    }
    
    // Free plan: only enabled if no subscription
    if (plan.amount === 0) {
        return !props.activeSubscription;
    }
    
    // Premium plan: enabled if on free plan (can upgrade) or no subscription
    // Also allow if subscription is set to cancel at period end
    const canSwitch = canUpgrade.value || 
                      !props.activeSubscription || 
                      (props.activeSubscription?.cancel_at_period_end === true);
    
    return canSwitch;
};

// Get button text
const getButtonText = (plan: { id: number; amount: number; name: string }) => {
    if (isCurrentPlan(plan.id)) {
        return 'Current Plan';
    }
    if (plan.amount === 0) {
        return 'Free';
    }
    if (canUpgrade.value) {
        return 'Upgrade';
    }
    return 'Subscribe';
};

// Get button variant
const getButtonVariant = (plan: { name: string; amount: number }) => {
    if (isCurrentPlan(plan.id)) {
        return 'outline';
    }
    // Primary button for premium plans when upgrading or for professional plan
    if (plan.amount > 0 && (canUpgrade.value || plan.name === 'professional')) {
        return 'default';
    }
    return 'outline';
};

const handleCheckout = async (planId: number) => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.');
        }

        const response = await fetch(`/payment/checkout/${planId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: 'Failed to create checkout session' }));
            throw new Error(error.message || 'Failed to create checkout session');
        }

        const data = await response.json();
        
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        } else if (data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            throw new Error('No redirect URL received from server');
        }
    } catch (error: any) {
        console.error('Checkout error:', error);
        alert(error.message || 'Failed to create checkout session. Please try again.');
    }
};

const handleCancel = (subscriptionId: number) => {
    if (confirm('Are you sure you want to cancel your subscription? It will remain active until the end of the billing period.')) {
        router.post(`/subscription/${subscriptionId}/cancel`);
    }
};

const handleResume = (subscriptionId: number) => {
    router.post(`/subscription/${subscriptionId}/resume`);
};

const handleBillingPortal = () => {
    router.get('/payment/billing-portal');
};

const formatPrice = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency.toUpperCase(),
    }).format(amount);
};

const getFirstPremiumPlan = () => {
    return props.plans.find(p => p.amount > 0);
};
</script>

<template>
    <Head title="Subscriptions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Manage Subscriptions</h1>
                    <p class="text-muted-foreground mt-2">
                        View and manage your subscription plans and billing
                    </p>
                    <InputError class="mt-2" :message="errors.plan" />
                </div>
                <Button v-if="activeSubscription && !isFreePlan" @click="handleBillingPortal" variant="outline">
                    <CreditCard class="h-4 w-4 mr-2" />
                    Billing Portal
                </Button>
            </div>

            <!-- Current Subscription Card -->
            <Card v-if="activeSubscription" :class="['shadow-sm', isFreePlan ? 'border-yellow-500' : 'border-primary']">
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <Crown class="h-5 w-5" :class="isFreePlan ? 'text-yellow-500' : 'text-primary'" />
                            Current Subscription: {{ activeSubscription.payment_plan.display_name }}
                            <Badge v-if="isFreePlan" class="ml-2 bg-yellow-500 text-white">
                                Free Plan
                            </Badge>
                        </span>
                        <Badge :class="activeSubscription.status === 'active' ? 'bg-green-500' : 'bg-yellow-500'">
                            {{ activeSubscription.status }}
                        </Badge>
                    </CardTitle>
                    <CardDescription>
                        {{ formatPrice(activeSubscription.payment_plan.amount, 'USD') }} per {{ activeSubscription.payment_plan.amount === 0 ? 'month (Free)' : 'month' }}
                        <span v-if="activeSubscription.ends_at && !isFreePlan">
                            • Renews on {{ new Date(activeSubscription.ends_at).toLocaleDateString() }}
                        </span>
                        <span v-if="isFreePlan" class="block mt-2 text-primary font-medium">
                            Upgrade to unlock premium features!
                        </span>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-2">
                        <Button 
                            v-if="isFreePlan && getFirstPremiumPlan()"
                            @click="handleCheckout(getFirstPremiumPlan()!.id)"
                            class="flex-1"
                        >
                            <ArrowUp class="h-4 w-4 mr-2" />
                            Upgrade to Premium
                        </Button>
                        <template v-else-if="!isFreePlan">
                            <Button 
                                v-if="activeSubscription.cancel_at_period_end"
                                @click="handleResume(activeSubscription.id)"
                                variant="outline"
                            >
                                <Play class="h-4 w-4 mr-2" />
                                Resume Subscription
                            </Button>
                            <Button 
                                v-else
                                @click="handleCancel(activeSubscription.id)"
                                variant="destructive"
                            >
                                <X class="h-4 w-4 mr-2" />
                                Cancel Subscription
                            </Button>
                        </template>
                    </div>
                </CardContent>
            </Card>

            <!-- Available Plans -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Available Plans</h2>
                <div class="grid gap-6 md:grid-cols-3">
                    <Card 
                        v-for="plan in plans" 
                        :key="plan.id"
                        :class="[
                            'shadow-sm transition-all hover:shadow-md',
                            isCurrentPlan(plan.id) ? 'border-primary border-2' : '',
                            canUpgrade && plan.amount > 0 ? 'border-yellow-400 border-2' : ''
                        ]"
                    >
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Crown :class="[
                                    'h-5 w-5',
                                    plan.name === 'basic' ? 'text-yellow-500' : 
                                    plan.name === 'professional' ? 'text-primary' : 
                                    'text-purple-500'
                                ]" />
                                {{ plan.display_name }}
                                <Badge v-if="isCurrentPlan(plan.id)" class="ml-auto bg-primary text-white">
                                    Current
                                </Badge>
                                <Badge v-else-if="canUpgrade && plan.amount > 0" class="ml-auto bg-yellow-500 text-white">
                                    Upgrade Available
                                </Badge>
                            </CardTitle>
                            <CardDescription>
                                <span v-if="isCurrentPlan(plan.id)" class="text-primary font-semibold">
                                    Current Plan
                                </span>
                                <span v-else>{{ plan.description || '' }}</span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold mb-2">
                                {{ formatPrice(plan.amount, plan.currency) }}
                            </div>
                            <p class="text-sm text-muted-foreground mb-4">
                                per {{ plan.interval }}
                            </p>
                            
                            <div v-if="plan.features && plan.features.length > 0" class="mb-4 space-y-2">
                                <div 
                                    v-for="feature in plan.features" 
                                    :key="feature"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <CheckCircle2 class="h-4 w-4 text-green-500 flex-shrink-0" />
                                    <span>{{ feature }}</span>
                                </div>
                            </div>

                            <Button 
                                @click="handleCheckout(plan.id)"
                                :variant="getButtonVariant(plan)"
                                :class="'w-full'"
                                :disabled="!isButtonEnabled(plan)"
                            >
                                <ArrowUp v-if="canUpgrade && plan.amount > 0 && !isCurrentPlan(plan.id)" class="h-4 w-4 mr-2" />
                                {{ getButtonText(plan) }}
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
