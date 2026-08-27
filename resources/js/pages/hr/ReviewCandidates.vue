<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { reviewCandidates } from '@/routes';
import reviewCandidatesRoutes from '@/routes/review-candidates';
import interviewResultsRoutes from '@/routes/interview-results';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Users, Mail, Calendar, Search } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    applications?: Array<{
        id: number;
        status: string;
        cover_letter?: string;
        notes?: string;
        applied_at: string;
        user: {
            id: number;
            name: string;
            email: string;
            latest_processed_cv?: {
                review_score?: number | null;
                extraction?: {
                    skills?: string[];
                    technologies?: string[];
                    summary?: string | null;
                    experience_level?: string;
                    experience_years?: number;
                    education?: Array<{ institution?: string; degree?: string }>;
                    relevant_experience?: string[];
                } | null;
            } | null;
        };
        job: {
            id: number;
            title: string;
        };
        interviews?: Array<{
            id: number;
            status: string;
            score?: number | null;
            review_status?: string | null;
        }>;
    }>;
    templates?: Array<{
        id: number;
        name: string;
        job_id?: number | null;
        difficulty: string;
        mode: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Review Candidates',
        href: reviewCandidates().url,
    },
];

const selectedApplication = ref<any>(null);
const isDialogOpen = ref(false);
const page = usePage();
const errors = computed(() => page.props.errors || {});
const form = ref({
    status: '',
    notes: '',
});
const selectedTemplateId = ref('');
const searchQuery = ref('');
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
    const query = searchQuery.value.trim().toLowerCase();

    return (props.applications || []).filter((application) => {
        const matchesStatus = statusFilter.value === 'all' || application.status === statusFilter.value;
        const haystack = `${application.user.name} ${application.user.email} ${application.job.title}`.toLowerCase();

        return matchesStatus && (!query || haystack.includes(query));
    });
});

const openDialog = (application: any) => {
    selectedApplication.value = application;
    form.value = {
        status: application.status,
        notes: application.notes || '',
    };
    selectedTemplateId.value = '';
    router.reload({ only: ['errors'], preserveState: false });
    isDialogOpen.value = true;
};

const assignInterview = () => {
    if (!selectedApplication.value || !selectedTemplateId.value) {
        return;
    }

    router.post(
        `/review-candidates/${selectedApplication.value.id}/interviews`,
        { interview_template_id: selectedTemplateId.value },
        {
            onSuccess: () => {
                selectedTemplateId.value = '';
            },
        }
    );
};

const updateApplication = () => {
    router.put(
        reviewCandidatesRoutes.update(selectedApplication.value.id).url,
        form.value,
        {
            onSuccess: () => {
                isDialogOpen.value = false;
                selectedApplication.value = null;
            },
            onError: () => {
                // Keep dialog open to show errors
            },
        }
    );
};

const statusBadge = (status: string) => `dash-badge dash-badge-${status}`;
</script>

<template>
    <Head title="Review Candidates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Review Candidates</h1>
                <p class="text-muted-foreground mt-2">
                    Browse and review candidate profiles and applications
                </p>
            </div>

            <div class="dash-filter">
                <div class="dash-filter-search">
                    <Search />
                    <input v-model="searchQuery" placeholder="Search candidates or jobs..." />
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
            </div>

            <div v-if="filteredApplications.length > 0" class="space-y-2">
                <div
                    v-for="application in filteredApplications"
                    :key="application.id"
                    class="dash-row flex items-start justify-between gap-4"
                >
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center gap-2">
                            <h3 class="font-medium">{{ application.user.name }}</h3>
                            <span :class="statusBadge(application.status)">
                                {{ application.status.replace('_', ' ') }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground">{{ application.job.title }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                            <span class="flex items-center gap-1">
                                <Mail class="h-3.5 w-3.5" />
                                {{ application.user.email }}
                            </span>
                            <span class="flex items-center gap-1">
                                <Calendar class="h-3.5 w-3.5" />
                                {{ new Date(application.applied_at).toLocaleDateString() }}
                            </span>
                        </p>
                    </div>
                    <Button size="sm" @click="openDialog(application)">Review</Button>
                </div>
            </div>
            <div v-else class="dash-empty">
                <Users class="mb-3 h-8 w-8" />
                <p class="text-sm">No candidates match this filter.</p>
            </div>

            <Dialog :open="isDialogOpen" @update:open="(val) => { isDialogOpen = val; if (!val) { selectedApplication = null; selectedTemplateId = ''; } }">
                <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Review Application</DialogTitle>
                        <DialogDescription>
                            Review and update the status of {{ selectedApplication?.user?.name }}'s application
                        </DialogDescription>
                    </DialogHeader>
                    <div v-if="selectedApplication" class="space-y-6">
                        <div>
                            <h3 class="font-semibold mb-2">Candidate Information</h3>
                            <div class="space-y-1 text-sm">
                                <p><strong>Name:</strong> {{ selectedApplication.user.name }}</p>
                                <p><strong>Email:</strong> {{ selectedApplication.user.email }}</p>
                                <p><strong>Job:</strong> {{ selectedApplication.job.title }}</p>
                                <p><strong>Applied:</strong> {{ new Date(selectedApplication.applied_at).toLocaleDateString() }}</p>
                            </div>
                        </div>
                        <div v-if="selectedApplication.cover_letter">
                            <h3 class="font-semibold mb-2">Cover Letter</h3>
                            <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ selectedApplication.cover_letter }}</p>
                        </div>
                        <div v-if="selectedApplication.user.latest_processed_cv?.extraction">
                            <h3 class="mb-2 font-semibold">Extracted CV</h3>
                            <p class="text-muted-foreground mb-2 text-sm">
                                {{ selectedApplication.user.latest_processed_cv.extraction.summary }}
                            </p>
                            <p class="mb-2 text-sm">
                                Experience:
                                {{ selectedApplication.user.latest_processed_cv.extraction.experience_years || 0 }} years
                                ({{ selectedApplication.user.latest_processed_cv.extraction.experience_level || 'n/a' }})
                                · CV score {{ selectedApplication.user.latest_processed_cv.review_score ?? '—' }}/100
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="skill in selectedApplication.user.latest_processed_cv.extraction.skills || []"
                                    :key="skill"
                                    class="bg-secondary rounded px-2 py-1 text-xs"
                                >
                                    {{ skill }}
                                </span>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm h-9"
                                :class="errors.status ? 'border-destructive' : ''"
                            >
                                <option value="pending">Pending</option>
                                <option value="reviewing">Reviewing</option>
                                <option value="shortlisted">Shortlisted</option>
                                <option value="interviewed">Interviewed</option>
                                <option value="accepted">Accepted</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <InputError :message="errors.status" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="notes">Notes</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                class="w-full min-h-[100px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                                :class="errors.notes ? 'border-destructive' : ''"
                                placeholder="Add notes about this candidate..."
                            />
                            <InputError :message="errors.notes" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="template">Assign interview</Label>
                            <p
                                v-if="selectedApplication.interviews && selectedApplication.interviews.length > 0"
                                class="text-muted-foreground text-sm"
                            >
                                Already assigned:
                            </p>
                            <div
                                v-if="selectedApplication.interviews && selectedApplication.interviews.length > 0"
                                class="flex flex-col gap-1"
                            >
                                <Link
                                    v-for="item in selectedApplication.interviews"
                                    :key="item.id"
                                    :href="item.status === 'completed' ? interviewResultsRoutes.show(item.id).url : '#'"
                                    class="text-sm"
                                    :class="item.status === 'completed' ? 'text-primary underline' : 'text-muted-foreground pointer-events-none'"
                                >
                                    {{ item.status }}{{ item.score != null ? ` · ${item.score}/100` : '' }}
                                </Link>
                            </div>
                            <div class="flex gap-2">
                                <select
                                    id="template"
                                    v-model="selectedTemplateId"
                                    class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                                >
                                    <option value="">Select a template</option>
                                    <option v-for="template in templates" :key="template.id" :value="template.id">
                                        {{ template.name }} ({{ template.difficulty }}/{{ template.mode }})
                                    </option>
                                </select>
                                <Button type="button" variant="outline" :disabled="!selectedTemplateId" @click="assignInterview">
                                    Assign
                                </Button>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
                        <Button @click="updateApplication">Update Status</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
