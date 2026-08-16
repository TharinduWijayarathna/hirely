<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="rounded-lg border border-sidebar-border/70 bg-sidebar-accent/40 p-3.5">
        <p class="hirely-display text-sm font-semibold">Hirely Admin</p>
        <p class="mt-1 text-[10px] leading-tight text-muted-foreground">
            Companies, users, and the public jobs board.
        </p>
        <Link href="/" class="mt-2 inline-block text-[11px] font-medium text-primary hover:underline">
            Open public site
        </Link>
    </div>
</template>
