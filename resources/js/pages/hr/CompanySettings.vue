<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { companySettings } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    company?: {
        id: number;
        name: string;
        description?: string | null;
        website?: string | null;
        industry?: string | null;
        size?: string | null;
        location?: string | null;
        address?: string | null;
        phone?: string | null;
        email?: string | null;
        is_verified?: boolean;
    } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Company', href: companySettings().url },
];

const page = usePage();
const errors = computed(() => page.props.errors || {});

const form = useForm({
    name: props.company?.name || '',
    description: props.company?.description || '',
    website: props.company?.website || '',
    industry: props.company?.industry || '',
    size: props.company?.size || '',
    location: props.company?.location || '',
    address: props.company?.address || '',
    phone: props.company?.phone || '',
    email: props.company?.email || '',
});

const submit = () => {
    form.put('/company-settings');
};
</script>

<template>
    <Head title="Company Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Company</h1>
                <p class="text-muted-foreground mt-2">
                    Update the profile candidates see on your job postings. Verification stays with Hirely admins.
                </p>
            </div>

            <Card v-if="!company" class="shadow-sm">
                <CardContent class="flex flex-col items-center justify-center py-12 text-center">
                    <Building2 class="text-muted-foreground mb-4 h-12 w-12" />
                    <p class="text-muted-foreground text-sm">
                        Your account is not linked to a company. Ask an admin to assign one.
                    </p>
                    <InputError class="mt-2" :message="errors.company" />
                </CardContent>
            </Card>

            <Card v-else class="shadow-sm">
                <CardHeader>
                    <CardTitle>{{ company.name }}</CardTitle>
                    <CardDescription>
                        {{ company.is_verified ? 'Verified company' : 'Not yet verified' }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" required />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="border-input bg-background min-h-[120px] w-full rounded-md border px-3 py-2 text-sm"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="website">Website</Label>
                            <Input id="website" v-model="form.website" type="url" placeholder="https://" />
                            <InputError :message="errors.website" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="industry">Industry</Label>
                            <Input id="industry" v-model="form.industry" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="size">Company size</Label>
                            <select id="size" v-model="form.size" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                <option value="">Select size</option>
                                <option value="1-10">1-10</option>
                                <option value="11-50">11-50</option>
                                <option value="51-200">51-200</option>
                                <option value="201-500">201-500</option>
                                <option value="500+">500+</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="location">Location</Label>
                            <Input id="location" v-model="form.location" />
                        </div>
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="address">Address</Label>
                            <Input id="address" v-model="form.address" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="phone">Phone</Label>
                            <Input id="phone" v-model="form.phone" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="email">Public email</Label>
                            <Input id="email" v-model="form.email" type="email" />
                            <InputError :message="errors.email" />
                        </div>
                        <div class="md:col-span-2">
                            <Button type="submit" :disabled="form.processing">Save company profile</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
