<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

defineProps<{
    className?: HTMLAttributes['class'];
}>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        fill="none"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    >
        <rect width="32" height="32" rx="8" fill="#4f46e5" />
        <path
            fill="#fff"
            d="M7.6 6c0-.9.7-1.6 1.6-1.6h2.2c.9 0 1.6.7 1.6 1.6v6.2h6v-6.2c0-.9.7-1.6 1.6-1.6H23c.9 0 1.6.7 1.6 1.6v20c0 .9-.7 1.6-1.6 1.6h-2.4c-.9 0-1.6-.7-1.6-1.6v-6.2h-6v6.2c0 .9-.7 1.6-1.6 1.6H9.2c-.9 0-1.6-.7-1.6-1.6V6z"
        />
        <path
            fill="#f97316"
            d="M25.9 3.4c.16 1.7.48 2.28 2.2 2.72-1.72.44-2.04 1.02-2.2 2.72-.16-1.7-.48-2.28-2.2-2.72 1.72-.44 2.04-1.02 2.2-2.72z"
        />
    </svg>
</template>
