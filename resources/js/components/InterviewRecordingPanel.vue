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
    <div v-if="recordingUrl || screenshots?.length" class="space-y-4 rounded-2xl border border-border bg-card p-5">
        <div>
            <h2 class="hirely-display text-xl">Session recording</h2>
            <p class="mt-1 text-sm text-muted-foreground">Camera stills and the WebRTC recording from this interview.</p>
        </div>
        <video v-if="recordingUrl" class="w-full rounded-xl bg-black" controls :src="recordingUrl" />
        <div v-if="screenshots?.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <figure v-for="shot in screenshots" :key="shot.url" class="overflow-hidden rounded-xl border border-border">
                <img :src="shot.url" :alt="shot.label || 'Candidate screenshot'" class="aspect-video w-full object-cover" />
                <figcaption class="truncate px-2 py-1 text-[11px] text-muted-foreground">
                    {{ shot.label }}
                </figcaption>
            </figure>
        </div>
    </div>
</template>
