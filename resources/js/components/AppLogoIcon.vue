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
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M7 4h5.4v9.2h7.2V4H25v24h-5.4v-9.2h-7.2V28H7V4z"
        />
    </svg>
</template>
