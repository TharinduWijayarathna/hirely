<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const twoFactorEnabled = computed(() => page.props.twoFactor?.enabled !== false);
const emailVerificationEnabled = computed(() => page.props.emailVerification?.enabled !== false);

const registerDescription = computed(() => {
    if (emailVerificationEnabled.value && twoFactorEnabled.value) {
        return "We'll email a verification link, then you'll set up two-factor authentication before you can use Hirely.";
    }

    if (emailVerificationEnabled.value) {
        return "Enter your details below to create your account. We'll email a verification link.";
    }

    if (twoFactorEnabled.value) {
        return "Enter your details below to create your account. Then you'll set up two-factor authentication.";
    }

    return 'Enter your details below to create your account.';
});
</script>

<template>
    <AuthBase
        title="Create an account"
        :description="registerDescription"
    >
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
                <p v-if="emailVerificationEnabled && twoFactorEnabled" class="text-center text-xs text-muted-foreground">
                    After you register, confirm your email, then enable an authenticator app for login.
                </p>
                <p v-else-if="emailVerificationEnabled" class="text-center text-xs text-muted-foreground">
                    After you register, confirm your email to continue.
                </p>
                <p v-else-if="twoFactorEnabled" class="text-center text-xs text-muted-foreground">
                    After you register, you'll enable an authenticator app for login.
                </p>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="6"
                    >Log in</TextLink
                >
            </div>
            <p class="text-center text-sm text-muted-foreground">
                Hiring for a company?
                <TextLink href="/organization/register" class="underline underline-offset-4">
                    Register your organization
                </TextLink>
            </p>
        </Form>
    </AuthBase>
</template>
