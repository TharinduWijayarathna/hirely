<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { jobApplications } from '@/routes';
import jobApplicationsRoutes from '@/routes/job-applications';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { FileSearch, Trash2, Building2, MapPin } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    applications?: Array<{
        id: number;
        status: string;
        applied_at: string;
        cover_letter?: string;
        job: {
            id: number;
            title: string;
            location?: string;
            company?: {
                name: string;
            };
        };
    }>;
    stats?: {
        total: number;
        pending: number;
        accepted: number;
        rejected: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Job Applications',
        href: jobApplications().url,
    },
];

const deleteApplication = (id: number) => {
    if (confirm('Are you sure you want to withdraw this application?')) {
        router.delete(jobApplicationsRoutes.destroy(id).url);
    }
};

const statusFilter = ref('all');
const statusOptions = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'reviewing', label: 'Reviewing' },
    { value: 'shortlisted', label: 'Shortlisted' },
    { value: 'interviewed', label: 'Interviewed' },
    { value: 'accepted', label: 'Accepted' },
    { value: 'rejected', label: 'Rejected' },
];

const filteredApplications = computed(() => {
    if (statusFilter.value === 'all') {
        return props.applications || [];
    }

    return (props.applications || []).filter((application) => application.status === statusFilter.value);
});

const statusBadge = (status: string) => `dash-badge dash-badge-${status}`;
</script>

<template>
    <Head title="Job Applications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Job Applications</h1>
                <p class="text-muted-foreground mt-2">
                    Track your job applications and suggested positions
                </p>
            </div>

            <div class="grid gap-3 md:grid-cols-4">
                <div class="dash-stat">
                    <p class="dash-stat-label">Total</p>
                    <p class="dash-stat-value">{{ stats?.total || 0 }}</p>
                    <p class="dash-stat-hint">Applications sent</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Pending</p>
                    <p class="dash-stat-value">{{ stats?.pending || 0 }}</p>
                    <p class="dash-stat-hint">Under review</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Accepted</p>
                    <p class="dash-stat-value">{{ stats?.accepted || 0 }}</p>
                    <p class="dash-stat-hint">Successful</p>
                </div>
                <div class="dash-stat">
                    <p class="dash-stat-label">Rejected</p>
                    <p class="dash-stat-value">{{ stats?.rejected || 0 }}</p>
                    <p class="dash-stat-hint">Not selected</p>
                </div>
            </div>

            <div class="dash-chips">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    type="button"
                    class="dash-chip"
                    :class="{ 'dash-chip-active': statusFilter === option.value }"
                    @click="statusFilter = option.value"
                >
                    {{ option.label }}
                </button>
            </div>

            <div v-if="filteredApplications.length > 0" class="space-y-2">
                <div
                    v-for="application in filteredApplications"
                    :key="application.id"
                    class="dash-row flex items-start justify-between gap-4"
                >
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center gap-2">
                            <h3 class="font-medium">{{ application.job.title }}</h3>
                            <span :class="statusBadge(application.status)">
                                {{ application.status.replace('_', ' ') }}
                            </span>
                        </div>
                        <p class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span v-if="application.job.company" class="flex items-center gap-1">
                                <Building2 class="h-3.5 w-3.5" />
                                {{ application.job.company.name }}
                            </span>
                            <span v-if="application.job.location" class="flex items-center gap-1">
                                <MapPin class="h-3.5 w-3.5" />
                                {{ application.job.location }}
                            </span>
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Applied {{ new Date(application.applied_at).toLocaleDateString() }}
                        </p>
                    </div>
                    <Button variant="ghost" size="icon" @click="deleteApplication(application.id)">
                        <Trash2 class="h-4 w-4 text-destructive" />
                    </Button>
                </div>
            </div>
            <div v-else class="dash-empty">
                <FileSearch class="mb-3 h-8 w-8" />
                <p class="text-sm">No applications in this view yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
