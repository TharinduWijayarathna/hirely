<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { jobSeekerManagement } from '@/routes';
import jobSeekerManagementRoutes from '@/routes/job-seeker-management';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Users, Search, Edit, Trash2, Mail, Plus, UserCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    jobSeekers?: {
        data?: Array<{
            id: number;
            name: string;
            email: string;
            created_at: string;
        }>;
    };
    filters?: {
        search?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Job Seeker Management',
        href: jobSeekerManagement().url,
    },
];

const isDialogOpen = ref(false);
const editingJobSeeker = ref<any>(null);
const searchQuery = ref(props.filters?.search || '');
const page = usePage();
const errors = computed(() => page.props.errors || {});

const form = ref({
    name: '',
    email: '',
    password: '',
});

const openDialog = (jobSeeker?: any) => {
    editingJobSeeker.value = jobSeeker || null;
    if (jobSeeker) {
        form.value = {
            name: jobSeeker.name || '',
            email: jobSeeker.email || '',
            password: '',
        };
    } else {
        form.value = {
            name: '',
            email: '',
            password: '',
        };
    }
    router.reload({ only: ['errors'], preserveState: false });
    isDialogOpen.value = true;
};

const submitForm = () => {
    const data = { ...form.value };
    if (editingJobSeeker.value && !data.password) {
        delete (data as any).password;
    }

    if (editingJobSeeker.value) {
        router.put(
            jobSeekerManagementRoutes.update(editingJobSeeker.value.id).url,
            data,
            {
                onSuccess: () => {
                    isDialogOpen.value = false;
                    editingJobSeeker.value = null;
                },
                onError: () => {
                    // Keep dialog open to show errors
                },
            }
        );
    } else {
        router.post(jobSeekerManagementRoutes.store().url, data, {
            onSuccess: () => {
                isDialogOpen.value = false;
                editingJobSeeker.value = null;
            },
            onError: () => {
                // Keep dialog open to show errors
            },
        });
    }
};

const deleteJobSeeker = (id: number) => {
    if (confirm('Are you sure you want to delete this job seeker?')) {
        router.delete(jobSeekerManagementRoutes.destroy(id).url);
    }
};

const applyFilters = () => {
    router.get(jobSeekerManagement().url, {
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Job Seeker Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Job Seeker Management</h1>
                    <p class="text-muted-foreground mt-2">
                        Manage job seeker accounts and their profiles
                    </p>
                </div>
                <Dialog :open="isDialogOpen" @update:open="(val) => { isDialogOpen = val; if (!val) editingJobSeeker = null; }">
                    <DialogTrigger as-child>
                        <Button @click="openDialog()">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Job Seeker
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{{ editingJobSeeker ? 'Edit Job Seeker' : 'Add Job Seeker' }}</DialogTitle>
                            <DialogDescription>{{ editingJobSeeker ? 'Update job seeker information' : 'Create a new job seeker account' }}</DialogDescription>
                        </DialogHeader>
                        <div class="space-y-6">
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="form.name" required :class="errors.name ? 'border-destructive' : ''" />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="form.email" type="email" required :class="errors.email ? 'border-destructive' : ''" />
                                <InputError :message="errors.email" />
                            </div>
                            <div v-if="!editingJobSeeker" class="grid gap-2">
                                <Label for="password">Password</Label>
                                <Input id="password" v-model="form.password" type="password" required :class="errors.password ? 'border-destructive' : ''" />
                                <InputError :message="errors.password" />
                            </div>
                            <div v-else class="grid gap-2">
                                <Label for="password">Password <span class="text-muted-foreground text-xs">(leave empty to keep current)</span></Label>
                                <Input id="password" v-model="form.password" type="password" :class="errors.password ? 'border-destructive' : ''" />
                                <InputError :message="errors.password" />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
                            <Button @click="submitForm">{{ editingJobSeeker ? 'Update' : 'Create' }}</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="dash-filter">
                <div class="dash-filter-search">
                    <Search />
                    <input
                        v-model="searchQuery"
                        placeholder="Search by name or email..."
                        @keyup.enter="applyFilters"
                    />
                </div>
                <Button size="sm" @click="applyFilters">Search</Button>
            </div>

            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>Job Seekers</CardTitle>
                    <CardDescription>View and manage job seeker accounts</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="jobSeekers?.data && jobSeekers.data.length > 0" class="space-y-4">
                        <div
                            v-for="jobSeeker in jobSeekers.data"
                            :key="jobSeeker.id"
                            class="p-4 border rounded-lg hover:bg-accent transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <UserCircle class="h-5 w-5 text-primary" />
                                        <h3 class="font-semibold text-lg">{{ jobSeeker.name }}</h3>
                                        <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">
                                            Job Seeker
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-muted-foreground mb-2">
                                        <span class="flex items-center gap-1">
                                            <Mail class="h-4 w-4" />
                                            {{ jobSeeker.email }}
                                        </span>
                                        <span>Joined {{ new Date(jobSeeker.created_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <Button variant="ghost" size="sm" @click="openDialog(jobSeeker)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" @click="deleteJobSeeker(jobSeeker.id)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                        <UserCircle class="h-12 w-12 text-muted-foreground mb-4" />
                        <p class="text-sm text-muted-foreground">
                            No job seekers found. Create your first job seeker to get started.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

