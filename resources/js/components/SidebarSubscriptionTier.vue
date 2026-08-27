<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { subscriptions } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { Crown, Sparkles, Zap, ArrowRight, Check } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    tier?: 'basic' | 'professional' | 'enterprise' | null;
    href?: string;
}

const props = withDefaults(defineProps<Props>(), {
    tier: null,
});

const subscriptionTier = computed(() => props.tier || 'basic');
const linkHref = computed(() => props.href || subscriptions());

const tierConfig = {
    basic: {
        name: 'Basic',
        icon: Sparkles,
        iconBg: 'bg-blue-500/20',
        iconColor: 'text-blue-500',
        gradient: 'from-blue-500/10 via-blue-500/5 to-transparent',
        borderColor: 'border-blue-500/30',
        badgeColor: 'bg-blue-500/20 text-blue-600 dark:text-blue-400 border-blue-500/30',
        description: 'Free tier',
        features: ['Essential features'],
        glow: 'shadow-blue-500/10',
    },
    professional: {
        name: 'Professional',
        icon: Crown,
        iconBg: 'bg-primary/20',
        iconColor: 'text-primary',
        gradient: 'from-primary/20 via-primary/10 to-transparent',
        borderColor: 'border-primary/40',
        badgeColor: 'bg-primary/20 text-primary border-primary/40',
        description: 'Premium features',
        features: ['Advanced tools', 'Priority support'],
        glow: 'shadow-primary/20',
    },
    enterprise: {
        name: 'Enterprise',
        icon: Zap,
        iconBg: 'bg-purple-500/20',
        iconColor: 'text-purple-500',
        gradient: 'from-purple-500/20 via-purple-500/10 to-transparent',
        borderColor: 'border-purple-500/40',
        badgeColor: 'bg-purple-500/20 text-purple-600 dark:text-purple-400 border-purple-500/40',
        description: 'Full access',
        features: ['All features', '24/7 support', 'Custom solutions'],
        glow: 'shadow-purple-500/20',
    },
};

const currentTier = computed(() => tierConfig[subscriptionTier.value]);
</script>

<template>
    <Link
        :href="linkHref"
        :class="[
            'group relative block overflow-hidden rounded-lg border p-3.5 transition-all duration-300',
            `bg-gradient-to-br ${currentTier.gradient}`,
            `border ${currentTier.borderColor}`,
            'hover:scale-[1.01] hover:shadow-md',
            subscriptionTier === 'basic' && 'hover:border-blue-500/50 hover:shadow-blue-500/10',
            subscriptionTier === 'professional' && 'hover:border-primary/50 hover:shadow-primary/10',
            subscriptionTier === 'enterprise' && 'hover:border-purple-500/50 hover:shadow-purple-500/10',
            'cursor-pointer',
        ]"
    >
        <!-- Decorative background elements -->
        <div
            :class="[
                'absolute -right-3 -top-3 h-12 w-12 rounded-full opacity-15 blur-xl transition-opacity group-hover:opacity-20',
                currentTier.iconBg.replace('/20', '/40'),
            ]"
        ></div>

        <div class="relative flex items-center justify-between gap-3">
            <!-- Left side: Icon and tier info -->
            <div class="flex items-center gap-2.5">
                <div
                    :class="[
                        'flex h-9 w-9 items-center justify-center rounded-lg transition-transform group-hover:scale-105',
                        currentTier.iconBg,
                    ]"
                >
                    <component
                        :is="currentTier.icon"
                        :class="['h-4 w-4', currentTier.iconColor]"
                    />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-semibold leading-tight">{{ currentTier.name }}</span>
                    <span class="text-[10px] text-muted-foreground leading-tight">
                        {{ currentTier.description }}
                    </span>
                </div>
            </div>

            <!-- Right side: Badge and arrow -->
            <div class="flex items-center gap-2">
                <Badge
                    :class="[
                        'border px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide',
                        currentTier.badgeColor,
                    ]"
                >
                    {{ currentTier.name }}
                </Badge>
                <ArrowRight
                    :class="[
                        'h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5',
                        currentTier.iconColor,
                    ]"
                />
            </div>
        </div>
    </Link>
</template>

