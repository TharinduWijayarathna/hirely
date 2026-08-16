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
        <section class="mx-auto max-w-xl px-6 py-12 sm:px-10">
            <h1 class="display text-[clamp(2rem,4vw,3rem)] leading-[0.95] font-medium tracking-[-0.04em]">
                Register your organization
            </h1>
            <p class="mt-4 leading-8 opacity-80">
                This creates the company page at
                <span class="whitespace-nowrap">/organization/your-company</span>
                and an HR account that can post jobs. Job seekers register separately.
            </p>

            <form class="mt-10 space-y-5" @submit.prevent="submit">
                <div>
                    <label class="block text-sm" for="organization_name">Organization name</label>
                    <input
                        id="organization_name"
                        v-model="form.organization_name"
                        required
                        class="mt-2 h-12 w-full border border-current/20 bg-transparent px-4"
                        placeholder="Acme Labs"
                    />
                    <p v-if="form.errors.organization_name" class="mt-1 text-sm">{{ form.errors.organization_name }}</p>
                </div>
                <div>
                    <label class="block text-sm" for="organization_location">Location</label>
                    <input
                        id="organization_location"
                        v-model="form.organization_location"
                        class="mt-2 h-12 w-full border border-current/20 bg-transparent px-4"
                        placeholder="Colombo"
                    />
                    <p v-if="form.errors.organization_location" class="mt-1 text-sm">{{ form.errors.organization_location }}</p>
                </div>
                <div>
                    <label class="block text-sm" for="industry">Industry</label>
                    <input
                        id="industry"
                        v-model="form.industry"
                        class="mt-2 h-12 w-full border border-current/20 bg-transparent px-4"
                        placeholder="Software"
                    />
                    <p v-if="form.errors.industry" class="mt-1 text-sm">{{ form.errors.industry }}</p>
                </div>
                <div>
                    <label class="block text-sm" for="name">Your name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        required
                        class="mt-2 h-12 w-full border border-current/20 bg-transparent px-4"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm" for="email">Work email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="mt-2 h-12 w-full border border-current/20 bg-transparent px-4"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-sm" for="password">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        class="mt-2 h-12 w-full border border-current/20 bg-transparent px-4"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-sm">{{ form.errors.password }}</p>
                </div>
                <div>
                    <label class="block text-sm" for="password_confirmation">Confirm password</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        class="mt-2 h-12 w-full border border-current/20 bg-transparent px-4"
                    />
                </div>
                <button type="submit" class="cta" :disabled="form.processing">Create organization</button>
            </form>

            <p class="mt-8 text-sm opacity-70">
                Looking for a job?
                <Link :href="register()" class="underline">Register as a candidate</Link>
                ·
                <Link :href="login()" class="underline">Log in</Link>
            </p>
        </section>
    </PublicLayout>
</template>

<style scoped>
.display {
    font-family: Fraunces, 'Times New Roman', serif;
}

.cta {
    background: #1c1915;
    color: #f4efe4;
    padding: 0.75rem 1.5rem;
}

.cta:hover {
    background: #3a342c;
}

.dark .cta {
    background: #f4efe4;
    color: #161410;
}
</style>
