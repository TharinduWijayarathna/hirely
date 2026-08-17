<script setup lang="ts">
import { computed } from 'vue';

type Point = {
    label: string;
    value: number;
};

const props = withDefaults(
    defineProps<{
        points: Point[];
        type?: 'line' | 'bar';
        format?: 'number' | 'money';
    }>(),
    {
        type: 'line',
        format: 'number',
    },
);

const width = 320;
const height = 140;
const padX = 12;
const padY = 18;

const maxValue = computed(() => Math.max(1, ...props.points.map((point) => Number(point.value) || 0)));

const coordinates = computed(() => {
    const inner = width - padX * 2;

    return props.points.map((point, index) => {
        const x =
            props.type === 'bar'
                ? padX + (inner / Math.max(props.points.length, 1)) * (index + 0.5)
                : padX + (index / Math.max(props.points.length - 1, 1)) * inner;
        const y = height - padY - ((Number(point.value) || 0) / maxValue.value) * (height - padY * 2);

        return { ...point, x, y };
    });
});

const linePath = computed(() => {
    if (coordinates.value.length === 0) {
        return '';
    }

    return coordinates.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
});

const areaPath = computed(() => {
    if (coordinates.value.length === 0) {
        return '';
    }

    const baseline = height - padY;
    const first = coordinates.value[0];
    const last = coordinates.value[coordinates.value.length - 1];

    return `${linePath.value} L ${last.x} ${baseline} L ${first.x} ${baseline} Z`;
});

const barWidth = computed(() => {
    if (props.points.length === 0) {
        return 0;
    }

    return Math.max(8, (width - padX * 2) / props.points.length - 8);
});

const formatValue = (value: number) => {
    if (props.format === 'money') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: value % 1 === 0 ? 0 : 2,
        }).format(value);
    }

    return new Intl.NumberFormat('en-US').format(value);
};
</script>

<template>
    <div class="dash-chart">
        <svg :viewBox="`0 0 ${width} ${height}`" class="h-36 w-full" role="img">
            <template v-if="type === 'line'">
                <path :d="areaPath" class="fill-primary/15" />
                <path :d="linePath" class="stroke-primary fill-none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                <circle
                    v-for="(point, index) in coordinates"
                    :key="index"
                    :cx="point.x"
                    :cy="point.y"
                    r="3.5"
                    class="fill-primary"
                />
            </template>
            <template v-else>
                <rect
                    v-for="(point, index) in coordinates"
                    :key="index"
                    :x="point.x - barWidth / 2"
                    :y="point.y"
                    :width="barWidth"
                    :height="height - padY - point.y"
                    rx="5"
                    class="fill-primary/80"
                />
            </template>
        </svg>
        <div class="mt-1 flex justify-between px-1 text-[11px] text-muted-foreground">
            <span v-for="point in points" :key="point.label">{{ point.label }}</span>
        </div>
        <p class="sr-only">
            {{ points.map((point) => `${point.label}: ${formatValue(Number(point.value) || 0)}`).join(', ') }}
        </p>
    </div>
</template>
