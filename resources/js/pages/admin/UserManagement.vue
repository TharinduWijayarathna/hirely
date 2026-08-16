<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { userManagement } from '@/routes';
import userManagementRoutes from '@/routes/user-management';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Users, Search, Edit, Trash2, Mail, Plus, Building2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    users?: {
        data?: Array<{
            id: number;
            name: string;
            email: string;
            role: string;
            company?: {
                id: number;
                name: string;
            };
            created_at: string;
        }>;
    };
    filters?: {
        role?: string;
        search?: string;
    };
    companies?: Array<{
        id: number;
        name: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Management',
        href: userManagement().url,
    },
];

const isDialogOpen = ref(false);
const editingUser = ref<any>(null);
const searchQuery = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');
const page = usePage();
const errors = computed(() => page.props.errors || {});

const form = ref({
    name: '',
    email: '',
    password: '',
    role: 'admin',
    company_id: '',
});

const openDialog = (user?: any) => {
    editingUser.value = user || null;
    if (user) {
        form.value = {
            name: user.name || '',
            email: user.email || '',
            password: '',
            role: user.role || 'admin',
            company_id: user.company?.id || '',
        };
    } else {
        form.value = {
            name: '',
            email: '',
            password: '',
            role: 'admin',
            company_id: '',
        };
    }
    // Clear errors when opening dialog
    router.reload({ only: ['errors'], preserveState: false });
    isDialogOpen.value = true;
};

const submitForm = () => {
    const data = { ...form.value };
    if (editingUser.value && !data.password) {
        delete data.password;
    }

    if (editingUser.value) {
        router.put(
            userManagementRoutes.update(editingUser.value.id).url,
            data,
            {
                onSuccess: () => {
                    isDialogOpen.value = false;
                    editingUser.value = null;
                },
                onError: () => {
                    // Keep dialog open to show errors
                },
            }
        );
    } else {
        router.post(userManagementRoutes.store().url, data, {
            onSuccess: () => {
                isDialogOpen.value = false;
                editingUser.value = null;
            },
            onError: () => {
                // Keep dialog open to show errors
            },
        });
    }
};

const deleteUser = (id: number) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(userManagementRoutes.destroy(id).url);
    }
};

const applyFilters = () => {
    router.get(userManagement().url, {
        search: searchQuery.value,
        role: roleFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getRoleBadge = (role: string) => `dash-badge dash-badge-${role}`;
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">User Management</h1>
                <p class="text-muted-foreground mt-2">
                    Manage admin and HR professional accounts
                </p>
                </div>
                <Dialog :open="isDialogOpen" @update:open="(val) => { isDialogOpen = val; if (!val) editingUser = null; }">
                    <DialogTrigger as-child>
                        <Button @click="openDialog()">
                            <Plus class="mr-2 h-4 w-4" />
                            Add User
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{{ editingUser ? 'Edit User' : 'Add New User' }}</DialogTitle>
                            <DialogDescription>{{ editingUser ? 'Update user information' : 'Create a new user account' }}</DialogDescription>
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
                            <div v-if="!editingUser" class="grid gap-2">
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
                                <Label for="role">Role</Label>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm h-9"
                                    :class="errors.role ? 'border-destructive' : ''"
                                >
                                    <option value="admin">Admin</option>
                                    <option value="hr_professional">HR Professional</option>
                                </select>
                                <InputError :message="errors.role" />
                            </div>
                            <div v-if="form.role === 'hr_professional' && companies" class="grid gap-2">
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
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
                            <Button @click="submitForm">{{ editingUser ? 'Update' : 'Create' }}</Button>
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
                <select v-model="roleFilter" class="dash-select" @change="applyFilters">
                    <option value="">All roles</option>
                    <option value="admin">Admin</option>
                    <option value="hr_professional">HR</option>
                </select>
                <Button size="sm" @click="applyFilters">Search</Button>
            </div>

            <Card class="shadow-sm">
                <CardHeader>
                    <CardTitle>All Users</CardTitle>
                    <CardDescription>View and manage user accounts</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="users?.data && users.data.length > 0" class="space-y-4">
                        <div
                            v-for="user in users.data"
                            :key="user.id"
                            class="dash-row"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <h3 class="font-medium">{{ user.name }}</h3>
                                        <span :class="getRoleBadge(user.role)">
                                            {{ user.role.replace('_', ' ') }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-muted-foreground mb-2">
                                        <span class="flex items-center gap-1">
                                            <Mail class="h-4 w-4" />
                                            {{ user.email }}
                                        </span>
                                        <span v-if="user.company" class="flex items-center gap-1">
                                            <Building2 class="h-4 w-4" />
                                            {{ user.company.name }}
                                        </span>
                                        <span>Joined {{ new Date(user.created_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <Button variant="ghost" size="sm" @click="openDialog(user)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" @click="deleteUser(user.id)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                        <Users class="h-12 w-12 text-muted-foreground mb-4" />
                        <p class="text-sm text-muted-foreground">
                            No users found. Create your first user to get started.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
