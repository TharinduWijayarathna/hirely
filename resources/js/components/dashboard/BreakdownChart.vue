<script setup lang="ts">
import { computed } from 'vue';

type Slice = {
    status: string;
    count: number;
};

const props = defineProps<{
    items: Slice[];
}>();

const colors = ['#4f46e5', '#7c3aed', '#c2410c', '#0f766e', '#ca8a04', '#64748b'];

const total = computed(() => props.items.reduce((sum, item) => sum + item.count, 0));

const slices = computed(() => {
    let offset = 0;

    return props.items.map((item, index) => {
        const value = total.value === 0 ? 0 : (item.count / total.value) * 100;
        const slice = {
            ...item,
            color: colors[index % colors.length],
            value,
            offset,
        };
        offset += value;

        return slice;
    });
});

const gradient = computed(() => {
    if (total.value === 0) {
        return 'conic-gradient(var(--muted) 0 100%)';
    }

    return `conic-gradient(${slices.value
        .map((slice) => `${slice.color} ${slice.offset}% ${slice.offset + slice.value}%`)
        .join(', ')})`;
});

const label = (status: string) => status.replaceAll('_', ' ');
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="dash-donut" :style="{ background: gradient }" />
        <ul class="grid flex-1 gap-2 text-sm">
            <li v-for="slice in slices" :key="slice.status" class="flex items-center justify-between gap-3">
                <span class="flex items-center gap-2 capitalize text-muted-foreground">
                    <span class="size-2.5 rounded-full" :style="{ background: slice.color }" />
                    {{ label(slice.status) }}
                </span>
                <span class="font-medium">{{ slice.count }}</span>
            </li>
        </ul>
    </div>
</template>
