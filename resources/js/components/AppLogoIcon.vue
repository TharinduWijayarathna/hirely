<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
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
        <path
            fill="currentColor"
            d="M7 5.2c0-1.1.9-2 2-2h2.4c1.1 0 2 .9 2 2v6.3h5.2V5.2c0-1.1.9-2 2-2H23c1.1 0 2 .9 2 2V26.8c0 1.1-.9 2-2 2h-2.4c-1.1 0-2-.9-2-2v-6.3h-5.2v6.3c0 1.1-.9 2-2 2H9c-1.1 0-2-.9-2-2V5.2z"
        />
        <path
            class="fill-[#f97316]"
            d="M26.4 3.2c.18 1.9.55 2.55 2.5 3.05-1.95.5-2.32 1.15-2.5 3.05-.18-1.9-.55-2.55-2.5-3.05 1.95-.5 2.32-1.15 2.5-3.05z"
        />
    </svg>
</template>
