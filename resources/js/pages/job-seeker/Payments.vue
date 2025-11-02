<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { payments } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CreditCard, CheckCircle2, Sparkles, Zap, ArrowUp } from 'lucide-vue-next';
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
    } | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payments',
        href: payments().url,
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
    return canUpgrade.value || !props.activeSubscription;
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
    <Head title="Premium Features" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Premium Features</h1>
                <p class="text-muted-foreground mt-2">
                    Unlock premium features to enhance your job search experience
                </p>
            </div>

            <!-- Current Subscription Card -->
            <Card v-if="activeSubscription" :class="['shadow-sm', isFreePlan ? 'border-yellow-500' : 'border-primary']">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Sparkles class="h-5 w-5" :class="isFreePlan ? 'text-yellow-500' : 'text-primary'" />
                        Active Plan: {{ activeSubscription.payment_plan.display_name }}
                        <Badge v-if="isFreePlan" class="ml-2 bg-yellow-500 text-white">
                            Free Plan
                        </Badge>
                    </CardTitle>
                    <CardDescription>
                        <span v-if="isFreePlan" class="text-primary font-medium">
                            Upgrade to unlock premium features!
                        </span>
                        <span v-else>
                            You have access to premium features
                        </span>
                    </CardDescription>
                </CardHeader>
                <CardContent v-if="isFreePlan">
                    <Button 
                        v-if="getFirstPremiumPlan()"
                        @click="handleCheckout(getFirstPremiumPlan()!.id)"
                        class="w-full"
                    >
                        <ArrowUp class="h-4 w-4 mr-2" />
                        Upgrade to Premium
                    </Button>
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
                                <component
                                    :is="plan.name === 'basic' ? Sparkles : 
                                         plan.name === 'professional' ? CreditCard : Zap"
                                    :class="[
                                        'h-5 w-5',
                                        plan.name === 'basic' ? 'text-blue-500' : 
                                        plan.name === 'professional' ? 'text-primary' : 
                                        'text-purple-500'
                                    ]"
                                />
                                {{ plan.display_name }}
                                <Badge v-if="isCurrentPlan(plan.id)" class="ml-auto bg-primary text-white">
                                    Current
                                </Badge>
                                <Badge v-else-if="canUpgrade && plan.amount > 0" class="ml-auto bg-yellow-500 text-white">
                                    Upgrade Available
                                </Badge>
                            </CardTitle>
                            <CardDescription>{{ plan.description || '' }}</CardDescription>
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
                                <ArrowUp v-if="canUpgrade && plan.amount > 0" class="h-4 w-4 mr-2" />
                                {{ getButtonText(plan) }}
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
