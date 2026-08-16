<script setup lang="ts">
import PublicThemeToggle from '@/components/PublicThemeToggle.vue';
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <Head :title="title">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700&display=swap"
            rel="stylesheet"
        />
        <meta v-if="description" name="description" :content="description" />
    </Head>

    <div class="public-site">
        <header class="sticky top-0 z-20 border-b border-border/80 bg-background/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <Link href="/" class="display text-2xl tracking-tight text-primary">Hirely</Link>
                <nav class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2 text-sm font-medium">
                    <Link href="/jobs" class="text-foreground/80 hover:text-primary">Jobs</Link>
                    <Link href="/organization" class="text-foreground/80 hover:text-primary">Organizations</Link>
                    <PublicThemeToggle />
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="text-foreground/80 hover:text-primary">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="text-foreground/80 hover:text-primary">Log in</Link>
                        <Link :href="register()" class="pub-cta-primary pub-cta !py-2">Register</Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
            <slot />
        </main>

        <footer class="border-t border-border">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-6 text-sm text-muted-foreground sm:px-6">
                <span>Hirely</span>
                <Link href="/organization/register" class="font-medium text-primary hover:underline">
                    Hiring? Register your organization
                </Link>
            </div>
        </footer>
    </div>
</template>
