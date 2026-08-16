<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { filterCandidates } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Filter, Search, SlidersHorizontal, User, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    candidates?: {
        data?: Array<{
            id: number;
            name: string;
            email: string;
            latest_processed_cv?: {
                extraction?: {
                    skills?: string[];
                    experience_level?: string;
                    summary?: string | null;
                } | null;
            } | null;
        }>;
    };
    filters?: {
        skills?: string;
        experience?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Filter Candidates',
        href: filterCandidates().url,
    },
];

const filters = ref({
    skills: props.filters?.skills || '',
    experience: props.filters?.experience || 'any',
});

const applyFilters = () => {
    router.get(filterCandidates().url, filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Filter Candidates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Filter Candidates</h1>
                <p class="text-muted-foreground mt-2">
                    Search and filter job seekers by skills, experience, and qualifications
                </p>
            </div>

            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <SlidersHorizontal class="h-5 w-5" />
                        Filter Options
                    </CardTitle>
                    <CardDescription>Use filters to find the perfect candidates</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div>
                            <Label for="skills">Skills</Label>
                            <Input
                                id="skills"
                                v-model="filters.skills"
                                placeholder="Search by skills..."
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div>
                            <Label for="experience">Experience Level</Label>
                            <select
                                id="experience"
                                v-model="filters.experience"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="any">Any</option>
                                <option value="entry">Entry Level</option>
                                <option value="mid">Mid Level</option>
                                <option value="senior">Senior Level</option>
                            </select>
                        </div>
                        <Button @click="applyFilters" class="w-full">
                            <Search class="mr-2 h-4 w-4" />
                            Search Candidates
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>Search Results</CardTitle>
                    <CardDescription>Matching candidates will appear here</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="candidates?.data && candidates.data.length > 0" class="space-y-4">
                        <div
                            v-for="candidate in candidates.data"
                            :key="candidate.id"
                            class="p-4 border rounded-lg hover:bg-accent transition-colors"
                        >
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-primary/10">
                                    <User class="h-6 w-6 text-primary" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-lg">{{ candidate.name }}</h3>
                                    <p class="text-sm text-muted-foreground flex items-center gap-1">
                                        <Mail class="h-4 w-4" />
                                        {{ candidate.email }}
                                    </p>
                                    <p class="text-muted-foreground mt-1 text-sm">
                                        {{ candidate.latest_processed_cv?.extraction?.experience_level || 'No CV yet' }}
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span
                                            v-for="skill in candidate.latest_processed_cv?.extraction?.skills?.slice(0, 8) || []"
                                            :key="skill"
                                            class="bg-secondary rounded px-2 py-1 text-xs"
                                        >
                                            {{ skill }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                        <Search class="h-12 w-12 text-muted-foreground mb-4" />
                        <p class="text-sm text-muted-foreground">
                            Apply filters above to search for candidates
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
