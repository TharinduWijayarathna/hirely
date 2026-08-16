<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div
        class="inline-flex rounded-full border border-border bg-card p-1 shadow-sm"
        role="group"
        aria-label="Theme"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            :title="label"
            :aria-pressed="appearance === value"
            class="flex h-8 w-8 items-center justify-center rounded-full text-muted-foreground transition-colors"
            :class="
                appearance === value
                    ? 'bg-primary text-primary-foreground'
                    : 'hover:bg-muted hover:text-foreground'
            "
            @click="updateAppearance(value)"
        >
            <component :is="Icon" class="h-4 w-4" />
            <span class="sr-only">{{ label }}</span>
        </button>
    </div>
</template>
