<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { hrManagement } from '@/routes';
import hrManagementRoutes from '@/routes/hr-management';
import { Combobox } from '@/components/ui/combobox';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Users, UserCog, Mail, Calendar, Edit, Trash2, Plus, Building2, Search } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    hrProfessionals?: Array<{
        id: number;
        name: string;
        email: string;
        company?: {
            id: number;
            name: string;
        };
        created_at: string;
    }>;
    companies?: Array<{
        id: number;
        name: string;
    }>;
    filters?: {
        company_id?: string;
        search?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'HR Professionals Management',
        href: hrManagement().url,
    },
];

const isDialogOpen = ref(false);
const editingHR = ref<any>(null);
const searchQuery = ref(props.filters?.search || '');
const companyFilter = ref(props.filters?.company_id || '');
const page = usePage();
const errors = computed(() => page.props.errors || {});
const form = ref({
    name: '',
    email: '',
    password: '',
    company_id: '',
});

const companyOptions = computed(() => {
    if (!props.companies) return [];
    return props.companies.map(company => ({
        value: company.id,
        label: company.name,
    }));
});

const selectedCompanyLabel = computed(() => {
    if (!form.value.company_id) return '';
    const company = props.companies?.find(c => c.id === Number(form.value.company_id));
    return company?.name || '';
});

const openDialog = (hr?: any) => {
    editingHR.value = hr || null;
    if (hr) {
        form.value = {
            name: hr.name || '',
            email: hr.email || '',
            password: '',
            company_id: hr.company?.id || '',
        };
    } else {
        form.value = {
            name: '',
            email: '',
            password: '',
            company_id: '',
        };
    }
    router.reload({ only: ['errors'], preserveState: false });
    isDialogOpen.value = true;
};

const submitForm = () => {
    const data = { ...form.value };
    if (editingHR.value && !data.password) {
        delete (data as any).password;
    }

    if (editingHR.value) {
        router.put(
            hrManagementRoutes.update(editingHR.value.id).url,
            data,
            {
                onSuccess: () => {
                    isDialogOpen.value = false;
                    editingHR.value = null;
                },
                onError: () => {
                    // Keep dialog open to show errors
                },
            }
        );
    } else {
        router.post(hrManagementRoutes.store().url, data, {
            onSuccess: () => {
                isDialogOpen.value = false;
                editingHR.value = null;
            },
            onError: () => {
                // Keep dialog open to show errors
            },
        });
    }
};

const deleteHR = (id: number) => {
    if (confirm('Are you sure you want to delete this HR professional?')) {
        router.delete(hrManagementRoutes.destroy(id).url);
    }
};

const applyFilters = () => {
    router.get(hrManagement().url, {
        search: searchQuery.value,
        company_id: companyFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

</script>

<template>
    <Head title="HR Professionals Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">HR Professionals Management</h1>
                    <p class="text-muted-foreground mt-2">
                        Manage HR professionals and their accounts on the platform
                    </p>
                </div>
                <Dialog :open="isDialogOpen" @update:open="(val) => { isDialogOpen = val; if (!val) editingHR = null; }">
                    <DialogTrigger as-child>
                        <Button @click="openDialog()">
                            <Plus class="mr-2 h-4 w-4" />
                            Add HR Professional
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{{ editingHR ? 'Edit HR Professional' : 'Add HR Professional' }}</DialogTitle>
                            <DialogDescription>{{ editingHR ? 'Update HR professional information' : 'Create a new HR professional account' }}</DialogDescription>
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
                            <div v-if="!editingHR" class="grid gap-2">
                                <Label for="password">Password</Label>
                                <Input id="password" v-model="form.password" type="password" required :class="errors.password ? 'border-destructive' : ''" />
                                <InputError :message="errors.password" />
                            </div>
                            <div v-else class="grid gap-2">
                                <Label for="password">Password <span class="text-muted-foreground text-xs">(leave empty to keep current)</span></Label>
                                <Input id="password" v-model="form.password" type="password" :class="errors.password ? 'border-destructive' : ''" />
                                <InputError :message="errors.password" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="company_id">Company *</Label>
                                <Combobox
                                    id="company_id"
                                    v-model="form.company_id"
                                    :options="companyOptions"
                                    placeholder="Select company..."
                                    search-placeholder="Search companies..."
                                    empty-text="No company found."
                                />
                                <InputError :message="errors.company_id" />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
                            <Button @click="submitForm">{{ editingHR ? 'Update' : 'Create' }}</Button>
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
                <select v-model="companyFilter" class="dash-select" @change="applyFilters">
                    <option value="">All companies</option>
                    <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.name }}
                    </option>
                </select>
                <Button size="sm" @click="applyFilters">Search</Button>
            </div>

            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>HR Professionals</CardTitle>
                    <CardDescription>View and manage HR professional accounts</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="hrProfessionals && hrProfessionals.length > 0" class="space-y-4">
                        <div
                            v-for="hr in hrProfessionals"
                            :key="hr.id"
                            class="p-4 border rounded-lg hover:bg-accent transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <UserCog class="h-5 w-5 text-primary" />
                                        <h3 class="font-semibold text-lg">{{ hr.name }}</h3>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <Mail class="h-4 w-4" />
                                            {{ hr.email }}
                                        </span>
                                        <span v-if="hr.company" class="flex items-center gap-1">
                                            <Building2 class="h-4 w-4" />
                                            {{ hr.company.name }}
                                        </span>
                                        <span v-else class="text-yellow-600">No Company Assigned</span>
                                        <span class="flex items-center gap-1">
                                            <Calendar class="h-4 w-4" />
                                            Joined {{ new Date(hr.created_at).toLocaleDateString() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <Button variant="ghost" size="sm" @click="openDialog(hr)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" @click="deleteHR(hr.id)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                        <UserCog class="h-12 w-12 text-muted-foreground mb-4" />
                        <p class="text-sm text-muted-foreground">
                            No HR professionals found. Create your first HR professional to get started.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
