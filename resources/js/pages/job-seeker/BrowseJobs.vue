<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { browseJobs } from '@/routes';
import jobApplicationsRoutes from '@/routes/job-applications';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Briefcase, Search, MapPin, DollarSign, Building2, Send } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    jobs?: {
        data?: Array<{
            id: number;
            title: string;
            description: string;
            location?: string;
            type: string;
            remote: string;
            salary_min?: number;
            salary_max?: number;
            salary_currency?: string;
            skills?: string[];
            company?: {
                name: string;
            };
            slug?: string;
            public_url?: string;
        }>;
        links?: any;
        current_page?: number;
        last_page?: number;
    };
    appliedJobIds?: number[];
    filters?: {
        search?: string;
        type?: string;
        remote?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Browse Jobs',
        href: browseJobs().url,
    },
];

const page = usePage();
const errors = computed(() => page.props.errors || {});

const isDialogOpen = ref(false);
const selectedJob = ref<any>(null);
const searchQuery = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const remoteFilter = ref(props.filters?.remote || '');

const form = ref({
    job_id: '',
    cover_letter: '',
    resume_path: '',
});

const openDialog = (job: any) => {
    selectedJob.value = job;
    form.value = {
        job_id: job.id.toString(),
        cover_letter: '',
        resume_path: '',
    };
    router.reload({ only: ['errors'], preserveState: false });
    isDialogOpen.value = true;
};

const submitApplication = () => {
    router.post(jobApplicationsRoutes.store().url, form.value, {
        onSuccess: () => {
            isDialogOpen.value = false;
            selectedJob.value = null;
            router.reload();
        },
        onError: () => {
            // Keep dialog open to show errors
        },
    });
};

const applyFilters = () => {
    router.get(browseJobs().url, {
        search: searchQuery.value || undefined,
        type: typeFilter.value || undefined,
        remote: remoteFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const isApplied = (jobId: number) => {
    return props.appliedJobIds?.includes(jobId) || false;
};

const getJobTypeLabel = (type: string) => {
    return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getRemoteLabel = (remote: string) => {
    const labels: Record<string, string> = {
        on_site: 'On Site',
        remote: 'Remote',
        hybrid: 'Hybrid',
    };
    return labels[remote] || remote;
};
</script>

<template>
    <Head title="Browse Jobs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Browse Jobs</h1>
                <p class="text-muted-foreground mt-2">
                    Discover and apply to job opportunities that match your skills
                </p>
            </div>

            <!-- Filters -->
            <div class="dash-filter">
                <div class="dash-filter-search">
                    <Search />
                    <input
                        v-model="searchQuery"
                        placeholder="Search jobs..."
                        @keyup.enter="applyFilters"
                    />
                </div>
                <select v-model="typeFilter" class="dash-select" @change="applyFilters">
                    <option value="">All types</option>
                    <option value="full_time">Full time</option>
                    <option value="part_time">Part time</option>
                    <option value="contract">Contract</option>
                    <option value="freelance">Freelance</option>
                    <option value="internship">Internship</option>
                </select>
                <select v-model="remoteFilter" class="dash-select" @change="applyFilters">
                    <option value="">All locations</option>
                    <option value="on_site">On site</option>
                    <option value="remote">Remote</option>
                    <option value="hybrid">Hybrid</option>
                </select>
                <Button size="sm" @click="applyFilters">Search</Button>
            </div>

            <!-- Jobs List -->
            <div v-if="jobs?.data && jobs.data.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="job in jobs.data"
                    :key="job.id"
                    class="shadow-sm hover:shadow-md transition-shadow"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="text-lg">{{ job.title }}</CardTitle>
                                <CardDescription v-if="job.company">{{ job.company.name }}</CardDescription>
                            </div>
                            <span
                                v-if="isApplied(job.id)"
                                class="dash-badge dash-badge-accepted"
                            >
                                Applied
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p class="text-sm text-muted-foreground line-clamp-2">{{ job.description }}</p>
                        
                        <div class="flex flex-wrap gap-2 text-sm text-muted-foreground">
                            <span v-if="job.location" class="flex items-center gap-1">
                                <MapPin class="h-4 w-4" />
                                {{ job.location }}
                            </span>
                            <span class="flex items-center gap-1">
                                <Briefcase class="h-4 w-4" />
                                {{ getJobTypeLabel(job.type) }}
                            </span>
                            <span class="flex items-center gap-1">
                                {{ getRemoteLabel(job.remote) }}
                            </span>
                        </div>

                        <div v-if="job.salary_min || job.salary_max" class="flex items-center gap-1 text-sm text-muted-foreground">
                            <DollarSign class="h-4 w-4" />
                            <span>
                                {{ job.salary_min ? `${job.salary_currency} ${job.salary_min}` : '' }}
                                {{ job.salary_min && job.salary_max ? ' - ' : '' }}
                                {{ job.salary_max ? `${job.salary_currency} ${job.salary_max}` : '' }}
                            </span>
                        </div>

                        <div v-if="job.skills && job.skills.length > 0" class="flex flex-wrap gap-1">
                            <span
                                v-for="(skill, index) in job.skills.slice(0, 3)"
                                :key="index"
                                class="px-2 py-1 bg-secondary text-secondary-foreground rounded text-xs"
                            >
                                {{ skill }}
                            </span>
                            <span v-if="job.skills.length > 3" class="px-2 py-1 text-xs text-muted-foreground">
                                +{{ job.skills.length - 3 }} more
                            </span>
                        </div>

                        <Button
                            :variant="isApplied(job.id) ? 'outline' : 'default'"
                            :disabled="isApplied(job.id)"
                            class="w-full"
                            @click="openDialog(job)"
                        >
                            <Send class="mr-2 h-4 w-4" />
                            {{ isApplied(job.id) ? 'Already Applied' : 'Apply Now' }}
                        </Button>
                        <Link
                            v-if="job.slug"
                            :href="`/jobs/${job.slug}`"
                            class="block text-center text-sm underline"
                        >
                            Open public posting
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="dash-empty">
                <Briefcase class="mb-3 h-8 w-8" />
                <p class="text-sm">No jobs found. Try adjusting your filters.</p>
            </div>

            <!-- Pagination -->
            <div v-if="jobs?.links && jobs.links.length > 3" class="flex justify-center gap-2">
                <Button
                    v-for="(link, index) in jobs.links"
                    :key="index"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    @click="link.url && router.get(link.url)"
                    v-html="link.label"
                />
            </div>

            <!-- Application Dialog -->
            <Dialog :open="isDialogOpen" @update:open="(val) => { isDialogOpen = val; if (!val) selectedJob = null; }">
                <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Apply for {{ selectedJob?.title }}</DialogTitle>
                        <DialogDescription v-if="selectedJob?.company">
                            {{ selectedJob.company.name }}
                        </DialogDescription>
                    </DialogHeader>
                    <div v-if="selectedJob" class="space-y-6">
                        <div>
                            <h3 class="font-semibold mb-2">Job Details</h3>
                            <div class="space-y-1 text-sm text-muted-foreground">
                                <p><strong>Location:</strong> {{ selectedJob.location || 'Not specified' }}</p>
                                <p><strong>Type:</strong> {{ getJobTypeLabel(selectedJob.type) }}</p>
                                <p><strong>Remote:</strong> {{ getRemoteLabel(selectedJob.remote) }}</p>
                                <p v-if="selectedJob.salary_min || selectedJob.salary_max">
                                    <strong>Salary:</strong>
                                    {{ selectedJob.salary_min ? `${selectedJob.salary_currency} ${selectedJob.salary_min}` : '' }}
                                    {{ selectedJob.salary_min && selectedJob.salary_max ? ' - ' : '' }}
                                    {{ selectedJob.salary_max ? `${selectedJob.salary_currency} ${selectedJob.salary_max}` : '' }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2">Description</h3>
                            <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ selectedJob.description }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="cover_letter">Cover Letter</Label>
                            <textarea
                                id="cover_letter"
                                v-model="form.cover_letter"
                                class="w-full min-h-[150px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                                :class="errors.cover_letter ? 'border-destructive' : ''"
                                placeholder="Write a cover letter explaining why you're a good fit for this position..."
                            />
                            <InputError :message="errors.cover_letter" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
                        <Button @click="submitApplication">
                            <Send class="mr-2 h-4 w-4" />
                            Submit Application
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

