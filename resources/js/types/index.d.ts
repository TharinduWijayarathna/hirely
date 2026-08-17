import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    notifications?: {
        unread: number;
        recent: Array<{
            id: string;
            title: string;
            body: string;
            url: string;
            type: string;
            read: boolean;
            created_at?: string | null;
        }>;
    };
    payments?: {
        required: boolean;
    };
    twoFactor?: {
        enabled: boolean;
    };
    emailVerification?: {
        enabled: boolean;
    };
};

export type UserRole = 'job_seeker' | 'hr_professional' | 'admin';

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    role?: UserRole;
    subscription_tier?: 'basic' | 'professional' | 'enterprise' | null;
    stats?: {
        total_users?: number;
        revenue?: string;
    };
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
