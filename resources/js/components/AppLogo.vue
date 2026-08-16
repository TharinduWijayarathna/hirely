<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm group-data-[collapsible=icon]:hidden">
        <span class="hirely-display truncate leading-tight font-semibold">Hirely</span>
        <span class="truncate text-[10px] text-muted-foreground">Jobs & interviews</span>
    </div>
</template>
