<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import type { BreadcrumbItemType } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const auth = computed(() => page.props.auth);
const notifications = computed(() => page.props.notifications || { unread: 0, recent: [] });

const openNotification = (item: { id: string; url: string; read: boolean }) => {
    if (!item.read) {
        router.post(`/notifications/${item.id}/read`, {}, { preserveScroll: true });
    }

    if (item.url) {
        router.visit(item.url);
    }
};

const markAllRead = () => {
    router.post('/notifications/read-all', {}, { preserveScroll: true });
};

</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="relative h-9 w-9">
                        <Bell class="h-5 w-5" />
                        <span
                            v-if="notifications.unread > 0"
                            class="absolute right-1 top-1 h-2 w-2 rounded-full bg-red-500"
                        />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-80">
                    <DropdownMenuLabel class="flex items-center justify-between">
                        <span>Notifications</span>
                        <button
                            v-if="notifications.unread > 0"
                            class="text-muted-foreground text-xs font-normal"
                            @click="markAllRead"
                        >
                            Mark all read
                        </button>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        v-for="item in notifications.recent"
                        :key="item.id"
                        class="flex cursor-pointer flex-col items-start gap-1 py-2"
                        @click="openNotification(item)"
                    >
                        <span class="text-sm font-medium" :class="item.read ? 'text-muted-foreground' : ''">
                            {{ item.title }}
                        </span>
                        <span class="text-muted-foreground text-xs whitespace-normal">{{ item.body }}</span>
                    </DropdownMenuItem>
                    <div v-if="!notifications.recent?.length" class="text-muted-foreground px-2 py-6 text-center text-sm">
                        No notifications yet.
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- User Menu -->
            <DropdownMenu>
                <DropdownMenuTrigger :as-child="true">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                    >
                        <Avatar class="size-8 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="auth.user.avatar"
                                :src="auth.user.avatar"
                                :alt="auth.user.name"
                            />
                            <AvatarFallback
                                class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                            >
                                {{ getInitials(auth.user?.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="auth.user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
