<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login, register } from '@/routes';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    organization_name: '',
    organization_location: '',
    industry: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/organization/register');
};
</script>

<template>
    <PublicLayout title="Register your organization" description="Create an organization and an HR account to post jobs.">
        <section class="mx-auto max-w-xl">
            <div class="pub-hero rounded-3xl px-6 py-8 sm:px-8">
                <h1 class="display text-[clamp(2rem,4vw,2.8rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                    Register your organization
                </h1>
                <p class="mt-4 leading-8 text-white/85">
                    Creates your company page at /organization/your-company and an HR account that can post jobs.
                </p>
            </div>

            <form class="pub-card mt-6 space-y-5 p-6 sm:p-8" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium" for="organization_name">Organization name</label>
                    <input
                        id="organization_name"
                        v-model="form.organization_name"
                        required
                        class="pub-field mt-2"
                        placeholder="Acme Labs"
                    />
                    <p v-if="form.errors.organization_name" class="mt-1 text-sm text-destructive">{{ form.errors.organization_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="organization_location">Location</label>
                    <input
                        id="organization_location"
                        v-model="form.organization_location"
                        class="pub-field mt-2"
                        placeholder="Colombo"
                    />
                    <p v-if="form.errors.organization_location" class="mt-1 text-sm text-destructive">{{ form.errors.organization_location }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="industry">Industry</label>
                    <input
                        id="industry"
                        v-model="form.industry"
                        class="pub-field mt-2"
                        placeholder="Software"
                    />
                    <p v-if="form.errors.industry" class="mt-1 text-sm text-destructive">{{ form.errors.industry }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="name">Your name</label>
                    <input id="name" v-model="form.name" required class="pub-field mt-2" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-destructive">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="email">Work email</label>
                    <input id="email" v-model="form.email" type="email" required class="pub-field mt-2" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-destructive">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="password">Password</label>
                    <input id="password" v-model="form.password" type="password" required class="pub-field mt-2" />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-destructive">{{ form.errors.password }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="password_confirmation">Confirm password</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        class="pub-field mt-2"
                    />
                </div>
                <button type="submit" class="pub-cta" :disabled="form.processing">Create organization</button>
            </form>

            <p class="mt-8 text-sm text-muted-foreground">
                Looking for a job?
                <Link :href="register()" class="font-medium text-primary underline">Register as a candidate</Link>
                ·
                <Link :href="login()" class="font-medium text-primary underline">Log in</Link>
            </p>
        </section>
    </PublicLayout>
</template>
