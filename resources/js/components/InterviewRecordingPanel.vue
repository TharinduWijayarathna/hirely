<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Camera, Images } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    recordingUrl?: string | null;
    screenshots?: Array<{
        url: string;
        label?: string;
        captured_at?: string | null;
    }>;
}>();

const galleryOpen = ref(false);
const activeIndex = ref(0);

const screenshotCount = computed(() => props.screenshots?.length ?? 0);
const activeShot = computed(() => props.screenshots?.[activeIndex.value] ?? null);

const openGallery = (index = 0) => {
    activeIndex.value = index;
    galleryOpen.value = true;
};

const showPrevious = () => {
    if (!props.screenshots?.length) {
        return;
    }

    activeIndex.value = (activeIndex.value - 1 + props.screenshots.length) % props.screenshots.length;
};

const showNext = () => {
    if (!props.screenshots?.length) {
        return;
    }

    activeIndex.value = (activeIndex.value + 1) % props.screenshots.length;
};

const formatCapturedAt = (value?: string | null) => {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleTimeString();
};
</script>

<template>
    <div v-if="recordingUrl || screenshotCount > 0" class="rounded-xl border border-border bg-card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-medium">Interview media</p>
                <p class="text-xs text-muted-foreground">
                    Session recording and camera stills from the voice interview.
                </p>
            </div>

            <Button
                v-if="screenshotCount > 0"
                variant="outline"
                size="sm"
                class="shrink-0 gap-2"
                @click="openGallery(0)"
            >
                <Images class="h-4 w-4" />
                View {{ screenshotCount }} screenshot{{ screenshotCount === 1 ? '' : 's' }}
            </Button>
        </div>

        <video
            v-if="recordingUrl"
            class="mt-3 w-full rounded-lg bg-black"
            controls
            :src="recordingUrl"
        />
    </div>

    <p v-else class="text-sm text-muted-foreground">
        No interview media yet. Recording and screenshots appear after a voice interview with camera access.
    </p>

    <Dialog v-model:open="galleryOpen">
        <DialogContent class="max-w-3xl gap-0 overflow-hidden p-0">
            <DialogHeader class="border-b border-border px-5 py-4">
                <DialogTitle class="flex items-center gap-2 text-base">
                    <Camera class="h-4 w-4" />
                    Candidate screenshots
                </DialogTitle>
                <DialogDescription>
                    {{ screenshotCount }} capture{{ screenshotCount === 1 ? '' : 's' }} taken during the interview.
                </DialogDescription>
            </DialogHeader>

            <div v-if="activeShot" class="space-y-4 p-5">
                <div class="overflow-hidden rounded-lg border border-border bg-black">
                    <img
                        :src="activeShot.url"
                        :alt="activeShot.label || 'Candidate screenshot'"
                        class="aspect-video w-full object-contain"
                    />
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 text-sm">
                        <p class="truncate font-medium capitalize">{{ activeShot.label || 'capture' }}</p>
                        <p v-if="formatCapturedAt(activeShot.captured_at)" class="text-xs text-muted-foreground">
                            {{ formatCapturedAt(activeShot.captured_at) }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <Button variant="outline" size="sm" @click="showPrevious">Previous</Button>
                        <span class="text-xs text-muted-foreground">
                            {{ activeIndex + 1 }} / {{ screenshotCount }}
                        </span>
                        <Button variant="outline" size="sm" @click="showNext">Next</Button>
                    </div>
                </div>

                <div class="grid grid-cols-6 gap-2 sm:grid-cols-8">
                    <button
                        v-for="(shot, index) in screenshots"
                        :key="shot.url"
                        type="button"
                        class="overflow-hidden rounded-md border transition-colors"
                        :class="index === activeIndex ? 'border-primary ring-2 ring-primary/30' : 'border-border hover:border-primary/50'"
                        @click="activeIndex = index"
                    >
                        <img
                            :src="shot.url"
                            :alt="shot.label || 'Candidate screenshot'"
                            class="aspect-video w-full object-cover"
                        />
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
