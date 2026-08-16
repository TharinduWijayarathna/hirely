<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { companySettings, postJobs } from '@/routes';
import postJobsRoutes from '@/routes/post-jobs';
import InputError from '@/components/InputError.vue';
import PlanQuotaNotice from '@/components/PlanQuotaNotice.vue';
import { type PlanQuota } from '@/types/plan-quota';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Briefcase, Plus, MapPin, DollarSign, Edit, Trash2, Link2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    jobs?: Array<{
        id: number;
        title: string;
        description: string;
        location?: string;
        type: string;
        remote: string;
        salary_min?: number;
        salary_max?: number;
        salary_currency?: string;
        status: string;
        public_url?: string;
        slug?: string;
        company_id?: number;
        company?: {
            name: string;
        };
    }>;
    companies?: Array<{
        id: number;
        name: string;
    }>;
    quota?: PlanQuota;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Post Jobs',
        href: postJobs().url,
    },
];

const isDialogOpen = ref(false);
const editingJob = ref<any>(null);
const page = usePage();
const errors = computed(() => page.props.errors || {});

const form = ref({
    company_id: '',
    title: '',
    description: '',
    requirements: '',
    location: '',
    type: 'full_time',
    remote: 'on_site',
    salary_min: '',
    salary_max: '',
    salary_currency: 'USD',
    skills: [] as string[],
    status: 'draft',
    expires_at: '',
});

const skillInput = ref('');

const hasOrganization = computed(() => (props.companies?.length ?? 0) > 0);
const canCreate = computed(() => props.quota?.allowed !== false && hasOrganization.value);
const copiedJobId = ref<number | null>(null);
const defaultCompanyId = () => props.companies?.[0]?.id?.toString() || '';

const openDialog = (job?: any) => {
    if (!job && !canCreate.value) {
        return;
    }

    editingJob.value = job || null;
    if (job) {
        form.value = {
            company_id: job.company_id || defaultCompanyId(),
            title: job.title || '',
            description: job.description || '',
            requirements: job.requirements || '',
            location: job.location || '',
            type: job.type || 'full_time',
            remote: job.remote || 'on_site',
            salary_min: job.salary_min || '',
            salary_max: job.salary_max || '',
            salary_currency: job.salary_currency || 'USD',
            skills: job.skills || [],
            status: job.status || 'draft',
            expires_at: job.expires_at || '',
        };
    } else {
        form.value = {
            company_id: defaultCompanyId(),
            title: '',
            description: '',
            requirements: '',
            location: '',
            type: 'full_time',
            remote: 'on_site',
            salary_min: '',
            salary_max: '',
            salary_currency: 'USD',
            skills: [],
            status: 'draft',
            expires_at: '',
        };
    }
    router.reload({ only: ['errors'], preserveState: false });
    isDialogOpen.value = true;
};

const addSkill = () => {
    if (skillInput.value.trim() && !form.value.skills.includes(skillInput.value.trim())) {
        form.value.skills.push(skillInput.value.trim());
        skillInput.value = '';
    }
};

const removeSkill = (skill: string) => {
    form.value.skills = form.value.skills.filter(s => s !== skill);
};

const submitForm = () => {
    if (editingJob.value) {
        router.put(postJobsRoutes.update(editingJob.value.id).url, form.value, {
            onSuccess: () => {
                isDialogOpen.value = false;
                editingJob.value = null;
            },
            onError: () => {
                // Keep dialog open to show errors
            },
        });
    } else {
        router.post(postJobsRoutes.store().url, form.value, {
            onSuccess: () => {
                isDialogOpen.value = false;
                editingJob.value = null;
            },
            onError: () => {
                // Keep dialog open to show errors
            },
        });
    }
};

const copyShareLink = async (job: { id: number; public_url?: string }) => {
    if (!job.public_url) {
        return;
    }

    await navigator.clipboard.writeText(job.public_url);
    copiedJobId.value = job.id;
    window.setTimeout(() => {
        if (copiedJobId.value === job.id) {
            copiedJobId.value = null;
        }
    }, 2000);
};

const deleteJob = (id: number) => {
    if (confirm('Are you sure you want to delete this job posting?')) {
        router.delete(postJobsRoutes.destroy(id).url);
    }
};

const statusFilter = ref('all');
const statusOptions = [
    { value: 'all', label: 'All' },
    { value: 'draft', label: 'Draft' },
    { value: 'active', label: 'Active' },
    { value: 'closed', label: 'Closed' },
    { value: 'expired', label: 'Expired' },
];

const filteredJobs = computed(() => {
    if (statusFilter.value === 'all') {
        return props.jobs || [];
    }

    return (props.jobs || []).filter((job) => job.status === statusFilter.value);
});

const statusBadge = (status: string) => `dash-badge dash-badge-${status}`;
</script>

<template>
    <Head title="Post Jobs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Post Job Vacancies</h1>
                    <p class="text-muted-foreground mt-2">
                        Create and manage job postings for your organization
                    </p>
                    <PlanQuotaNotice class="mt-3" :quota="quota" />
                    <InputError class="mt-2" :message="errors.plan || errors.company" />
                    <p v-if="!hasOrganization" class="text-muted-foreground mt-2 max-w-xl text-sm">
                        Your account must belong to an organization before you can post. Ask an admin to
                        attach you to a company, then confirm it on
                        <Link :href="companySettings()" class="underline">Company settings</Link>.
                    </p>
                </div>
                <Dialog :open="isDialogOpen" @update:open="(val) => { isDialogOpen = val; if (!val) editingJob = null; }">
                    <DialogTrigger as-child>
                        <Button :disabled="!canCreate" @click="openDialog()">
                            <Plus class="mr-2 h-4 w-4" />
                            Post New Job
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>{{ editingJob ? 'Edit Job Posting' : 'Post New Job' }}</DialogTitle>
                            <DialogDescription>Create a new job posting to attract candidates</DialogDescription>
                        </DialogHeader>
                        <div class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="title">Job Title *</Label>
                                <Input id="title" v-model="form.title" placeholder="e.g., Senior Developer" required :class="errors.title ? 'border-destructive' : ''" />
                                <InputError :message="errors.title" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="company_id">Company</Label>
                                <select
                                    id="company_id"
                                    v-model="form.company_id"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm h-9"
                                    :class="errors.company_id ? 'border-destructive' : ''"
                                >
                                    <option value="">Select Company</option>
                                    <option v-for="company in companies" :key="company.id" :value="company.id">
                                        {{ company.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.company_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="description">Description *</Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    class="w-full min-h-[120px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    :class="errors.description ? 'border-destructive' : ''"
                                    placeholder="Job description"
                                    required
                                />
                                <InputError :message="errors.description" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="requirements">Requirements</Label>
                                <textarea
                                    id="requirements"
                                    v-model="form.requirements"
                                    class="w-full min-h-[100px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    :class="errors.requirements ? 'border-destructive' : ''"
                                    placeholder="Job requirements"
                                />
                                <InputError :message="errors.requirements" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="location">Location</Label>
                                    <Input id="location" v-model="form.location" placeholder="City, Country" :class="errors.location ? 'border-destructive' : ''" />
                                    <InputError :message="errors.location" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="remote">Remote Type</Label>
                                    <select
                                        id="remote"
                                        v-model="form.remote"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm h-9"
                                        :class="errors.remote ? 'border-destructive' : ''"
                                    >
                                        <option value="on_site">On Site</option>
                                        <option value="remote">Remote</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                    <InputError :message="errors.remote" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="type">Job Type</Label>
                                    <select
                                        id="type"
                                        v-model="form.type"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm h-9"
                                        :class="errors.type ? 'border-destructive' : ''"
                                    >
                                        <option value="full_time">Full Time</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="contract">Contract</option>
                                        <option value="freelance">Freelance</option>
                                        <option value="internship">Internship</option>
                                    </select>
                                    <InputError :message="errors.type" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="status">Status</Label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm h-9"
                                        :class="errors.status ? 'border-destructive' : ''"
                                    >
                                        <option value="draft">Draft</option>
                                        <option value="active">Active</option>
                                        <option value="closed">Closed</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                    <InputError :message="errors.status" />
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="grid gap-2">
                                    <Label for="salary_min">Min Salary</Label>
                                    <Input id="salary_min" v-model.number="form.salary_min" type="number" min="0" :class="errors.salary_min ? 'border-destructive' : ''" />
                                    <InputError :message="errors.salary_min" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="salary_max">Max Salary</Label>
                                    <Input id="salary_max" v-model.number="form.salary_max" type="number" min="0" :class="errors.salary_max ? 'border-destructive' : ''" />
                                    <InputError :message="errors.salary_max" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="salary_currency">Currency</Label>
                                    <Input id="salary_currency" v-model="form.salary_currency" maxlength="3" :class="errors.salary_currency ? 'border-destructive' : ''" />
                                    <InputError :message="errors.salary_currency" />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label>Required Skills</Label>
                                <div class="flex gap-2">
                                    <Input v-model="skillInput" placeholder="Add skill" @keyup.enter="addSkill" :class="errors.skills ? 'border-destructive' : ''" />
                                    <Button type="button" @click="addSkill">Add</Button>
                                </div>
                                <InputError :message="errors.skills" />
                                <div v-if="form.skills.length > 0" class="flex flex-wrap gap-2 mt-2">
                                    <span
                                        v-for="skill in form.skills"
                                        :key="skill"
                                        class="inline-flex items-center gap-1 px-2 py-1 bg-primary/10 text-primary rounded-md text-sm"
                                    >
                                        {{ skill }}
                                        <button type="button" @click="removeSkill(skill)" class="hover:text-destructive">×</button>
                                    </span>
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="expires_at">Expiry Date</Label>
                                <Input id="expires_at" v-model="form.expires_at" type="date" :class="errors.expires_at ? 'border-destructive' : ''" />
                                <InputError :message="errors.expires_at" />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
                            <Button @click="submitForm">{{ editingJob ? 'Update' : 'Create' }}</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <div v-if="filteredJobs.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
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

            <div v-if="filteredJobs.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="job in filteredJobs" :key="job.id">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="text-lg">{{ job.title }}</CardTitle>
                                <CardDescription v-if="job.company">{{ job.company.name }}</CardDescription>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    v-if="job.status === 'active' && job.public_url"
                                    variant="ghost"
                                    size="sm"
                                    :title="copiedJobId === job.id ? 'Copied' : 'Copy apply link'"
                                    @click="copyShareLink(job)"
                                >
                                    <Link2 class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" @click="openDialog(job)">
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="sm" @click="deleteJob(job.id)">
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <MapPin class="h-4 w-4" />
                            <span>{{ job.location || 'Not specified' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Briefcase class="h-4 w-4" />
                            <span>{{ job.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}</span>
                        </div>
                        <div v-if="job.salary_min || job.salary_max" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <DollarSign class="h-4 w-4" />
                            <span>
                                {{ job.salary_min ? `${job.salary_currency} ${job.salary_min}` : '' }}
                                {{ job.salary_min && job.salary_max ? ' - ' : '' }}
                                {{ job.salary_max ? `${job.salary_currency} ${job.salary_max}` : '' }}
                            </span>
                        </div>
                        <div>
                            <span :class="statusBadge(job.status)">
                                {{ job.status }}
                            </span>
                        </div>
                        <a
                            v-if="job.status === 'active' && job.public_url"
                            :href="job.public_url"
                            target="_blank"
                            class="block text-sm underline"
                        >
                            {{ copiedJobId === job.id ? 'Apply link copied' : 'Open public apply page' }}
                        </a>
                    </CardContent>
                </Card>
            </div>
            <div v-else class="dash-empty">
                <Briefcase class="mb-3 h-8 w-8" />
                <p class="mb-4 text-sm">{{ jobs?.length ? 'No jobs match this filter.' : 'No jobs posted yet.' }}</p>
                <Button v-if="!jobs?.length" variant="outline" :disabled="!canCreate" @click="openDialog()">
                    <Plus class="h-4 w-4" />
                    Create first job
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
