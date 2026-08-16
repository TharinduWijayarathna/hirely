<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { profileScore } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { TrendingUp, Target, Award, Star } from 'lucide-vue-next';

const props = defineProps<{
    scores?: {
        overall?: number | null;
        cv?: number | null;
        portfolio?: number | null;
        skills?: number | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Profile Score', href: profileScore().url }];
</script>

<template>
    <Head title="Profile Score" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Profile Score</h1>
                <p class="text-muted-foreground mt-2">
                    Built from your latest CV review, portfolio, and skill goals.
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card class="from-primary/5 to-primary/10 shadow-sm bg-gradient-to-br">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5" />
                            Overall Score
                        </CardTitle>
                        <CardDescription>Your current profile rating</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-end gap-4">
                            <div class="text-6xl font-bold">{{ scores?.overall ?? '--' }}</div>
                            <div class="text-muted-foreground mb-2 text-2xl">/100</div>
                        </div>
                        <p class="text-muted-foreground mt-4 text-sm">
                            {{ scores?.overall == null ? 'Complete your profile to get a score' : 'Average of available profile signals' }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Score Breakdown</CardTitle>
                        <CardDescription>Factors affecting your score</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Target class="text-muted-foreground h-4 w-4" />
                                <span class="text-sm">CV Quality</span>
                            </div>
                            <span class="text-sm font-medium">{{ scores?.cv ?? '--' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Award class="text-muted-foreground h-4 w-4" />
                                <span class="text-sm">Portfolio</span>
                            </div>
                            <span class="text-sm font-medium">{{ scores?.portfolio ?? '--' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Star class="text-muted-foreground h-4 w-4" />
                                <span class="text-sm">Skills</span>
                            </div>
                            <span class="text-sm font-medium">{{ scores?.skills ?? '--' }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
