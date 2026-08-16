<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { filterCandidates } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Search, User, Mail } from 'lucide-vue-next';
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

            <div class="dash-filter">
                <div class="dash-filter-search">
                    <Search />
                    <input
                        v-model="filters.skills"
                        placeholder="Search by skills..."
                        @keyup.enter="applyFilters"
                    />
                </div>
                <select v-model="filters.experience" class="dash-select" @change="applyFilters">
                    <option value="any">Any experience</option>
                    <option value="entry">Entry</option>
                    <option value="mid">Mid</option>
                    <option value="senior">Senior</option>
                </select>
                <Button size="sm" @click="applyFilters">Search</Button>
            </div>

            <div v-if="candidates?.data && candidates.data.length > 0" class="space-y-2">
                <div
                    v-for="candidate in candidates.data"
                    :key="candidate.id"
                    class="dash-row"
                >
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/10">
                            <User class="h-5 w-5 text-primary" />
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium">{{ candidate.name }}</h3>
                            <p class="flex items-center gap-1 text-sm text-muted-foreground">
                                <Mail class="h-3.5 w-3.5" />
                                {{ candidate.email }}
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ candidate.latest_processed_cv?.extraction?.experience_level || 'No CV yet' }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    v-for="skill in candidate.latest_processed_cv?.extraction?.skills?.slice(0, 8) || []"
                                    :key="skill"
                                    class="dash-badge"
                                >
                                    {{ skill }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="dash-empty">
                <Search class="mb-3 h-8 w-8" />
                <p class="text-sm">Search by skill to see matching candidates.</p>
            </div>
        </div>
    </AppLayout>
</template>
