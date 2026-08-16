<script setup lang="ts">
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

    <div class="hirely-home min-h-screen">
        <header class="flex items-baseline justify-between gap-6 px-6 py-6 sm:px-10">
            <Link href="/" class="display text-2xl tracking-tight">Hirely</Link>
            <nav class="flex flex-wrap items-baseline justify-end gap-x-6 gap-y-2 text-sm">
                <Link href="/jobs" class="hover:underline">Jobs</Link>
                <Link href="/organization" class="hover:underline">Organizations</Link>
                <Link v-if="$page.props.auth.user" :href="dashboard()" class="hover:underline">Dashboard</Link>
                <template v-else>
                    <Link :href="login()" class="hover:underline">Log in</Link>
                    <Link :href="register()" class="border-b border-current pb-0.5 hover:opacity-70">
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <main>
            <slot />
        </main>

        <footer class="rule-t flex flex-wrap items-baseline justify-between gap-4 px-6 py-6 text-sm opacity-70 sm:px-10">
            <span>Hirely</span>
            <Link href="/organization/register" class="hover:underline">Hiring? Register your organization</Link>
        </footer>
    </div>
</template>

<style scoped>
.hirely-home {
    --line: rgb(28 25 21 / 0.15);
    background: #f4efe4;
    color: #1c1915;
    font-family:
        'Instrument Sans',
        ui-sans-serif,
        system-ui,
        sans-serif;
}

.display {
    font-family: Fraunces, 'Times New Roman', serif;
}

.rule-t {
    border-top: 1px solid var(--line);
}

.dark .hirely-home {
    --line: rgb(244 239 228 / 0.18);
    background: #161410;
    color: #f4efe4;
}
</style>
