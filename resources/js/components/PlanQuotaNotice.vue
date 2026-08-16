<script setup lang="ts">
import type { PlanQuota } from '@/types/plan-quota';

defineProps<{
    quota?: PlanQuota;
}>();
</script>

<template>
    <div
        v-if="quota && !quota.allowed"
        class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950"
    >
        <p>
            {{ quota.message }}
            <a v-if="quota.billing_url" :href="quota.billing_url" class="font-medium underline">Upgrade your plan</a>
        </p>
    </div>
    <p v-else-if="quota && quota.limit != null" class="text-muted-foreground text-sm">
        {{ quota.used ?? 0 }} of {{ quota.limit }} used on {{ quota.plan_name }}.
    </p>
</template>
