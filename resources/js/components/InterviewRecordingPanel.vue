<script setup lang="ts">
defineProps<{
    recordingUrl?: string | null;
    screenshots?: Array<{
        url: string;
        label?: string;
        captured_at?: string | null;
    }>;
}>();
</script>

<template>
    <div class="space-y-4 rounded-2xl border border-border bg-card p-5">
        <div>
            <h2 class="hirely-display text-xl">Candidate screenshots</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Random camera stills and the session recording captured during the voice interview.
            </p>
        </div>
        <video v-if="recordingUrl" class="w-full rounded-xl bg-black" controls :src="recordingUrl" />
        <div v-if="screenshots?.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <figure v-for="shot in screenshots" :key="shot.url" class="overflow-hidden rounded-xl border border-border">
                <img :src="shot.url" :alt="shot.label || 'Candidate screenshot'" class="aspect-video w-full object-cover" />
                <figcaption class="truncate px-2 py-1 text-[11px] text-muted-foreground">
                    {{ shot.label }}
                    <span v-if="shot.captured_at"> · {{ new Date(shot.captured_at).toLocaleTimeString() }}</span>
                </figcaption>
            </figure>
        </div>
        <p v-else-if="!recordingUrl" class="text-sm text-muted-foreground">
            No screenshots yet. They appear here after the candidate completes a voice interview with camera access.
        </p>
    </div>
</template>
