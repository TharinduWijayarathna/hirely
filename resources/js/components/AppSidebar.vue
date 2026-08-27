<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import SidebarAdminPanel from '@/components/SidebarAdminPanel.vue';
import SidebarSubscriptionTier from '@/components/SidebarSubscriptionTier.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { cvReview } from '@/routes';
import { atsScoring } from '@/routes';
import { mockInterview } from '@/routes';
import { portfolio, profileScore, skillExpectations, jobApplications, browseJobs, interviews } from '@/routes';
import { postJobs, reviewCandidates, subscriptions, interviewTemplates, interviewResults, rankings, reports, companySettings } from '@/routes';
import { userManagement, analytics, companyManagement, hrManagement, jobSeekerManagement } from '@/routes';
import { payments as adminPayments } from '@/routes/admin';
import { type NavItem, type UserRole } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    FileText,
    FileCheck,
    Video,
    Settings,
    Briefcase,
    TrendingUp,
    Target,
    FileSearch,
    FolderKanban,
    Users,
    BarChart3,
    CreditCard,
    Building2,
    UserCog,
    ClipboardList,
    ClipboardCheck,
    ListOrdered,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const userRole = computed(() => (page.props.auth?.user?.role || 'job_seeker') as UserRole);
const paymentsRequired = computed(() => page.props.payments?.required ?? true);
const subscriptionTier = computed(
    () => (page.props.auth?.user?.tier || page.props.auth?.user?.subscription_tier || null) as
        | 'basic'
        | 'professional'
        | 'enterprise'
        | null,
);

// Job Seeker Navigation Items
const jobSeekerNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'CV Review',
        href: cvReview(),
        icon: FileText,
    },
    {
        title: 'ATS Scoring',
        href: atsScoring(),
        icon: FileCheck,
    },
    {
        title: 'Mock Interview',
        href: mockInterview(),
        icon: Video,
    },
    {
        title: 'Interviews',
        href: interviews(),
        icon: ClipboardList,
    },
    {
        title: 'Portfolio',
        href: portfolio(),
        icon: FolderKanban,
    },
    {
        title: 'Profile Score',
        href: profileScore(),
        icon: TrendingUp,
    },
    {
        title: 'Skill Expectations',
        href: skillExpectations(),
        icon: Target,
    },
    {
        title: 'Browse Jobs',
        href: browseJobs(),
        icon: Briefcase,
    },
    {
        title: 'Job Applications',
        href: jobApplications(),
        icon: FileSearch,
    }
];

// HR Professional Navigation Items
const hrProfessionalNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Post Jobs',
        href: postJobs(),
        icon: Briefcase,
    },
    {
        title: 'Review Candidates',
        href: reviewCandidates(),
        icon: Users,
    },
    {
        title: 'Interview Templates',
        href: interviewTemplates(),
        icon: ClipboardList,
    },
    {
        title: 'Interview Results',
        href: interviewResults(),
        icon: ClipboardCheck,
    },
    {
        title: 'Rankings',
        href: rankings(),
        icon: ListOrdered,
    },
    {
        title: 'Reports',
        href: reports(),
        icon: BarChart3,
    },
    {
        title: 'Company',
        href: companySettings(),
        icon: Building2,
    }
];

// Admin Navigation Items
const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'User Management',
        href: userManagement(),
        icon: Users,
    },
    {
        title: 'Job Seekers',
        href: jobSeekerManagement(),
        icon: UserCog,
    },
    {
        title: 'Companies',
        href: companyManagement(),
        icon: Building2,
    },
    {
        title: 'HR Professionals',
        href: hrManagement(),
        icon: UserCog,
    },
    {
        title: 'Analytics',
        href: analytics(),
        icon: BarChart3,
    },
    {
        title: 'Payments',
        href: adminPayments(),
        icon: CreditCard,
    }
];

const navLabel = computed(() => {
    switch (userRole.value) {
        case 'hr_professional':
            return 'Hiring';
        case 'admin':
            return 'Admin';
        default:
            return 'For you';
    }
});

const mainNavItems = computed(() => {
    let items: NavItem[];

    switch (userRole.value) {
        case 'hr_professional':
            items = hrProfessionalNavItems;
            break;
        case 'admin':
            items = adminNavItems;
            break;
        case 'job_seeker':
        default:
            items = jobSeekerNavItems;
            break;
    }

    if (paymentsRequired.value) {
        return items;
    }

    return items.filter((item) => ! ['Premium Features', 'Subscriptions'].includes(item.title));
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" :label="navLabel" />
        </SidebarContent>

        <SidebarFooter class="p-4">
            <!-- Subscription Tier for Job Seekers and HR Professionals -->
            <template v-if="paymentsRequired && (userRole === 'job_seeker' || userRole === 'hr_professional')">
                <SidebarSubscriptionTier 
                    :tier="subscriptionTier" 
                    :href="userRole === 'job_seeker' ? '/payments' : '/subscriptions'"
                />
            </template>

            <!-- Admin Panel Info for Admins -->
            <template v-else-if="userRole === 'admin'">
                <SidebarAdminPanel />
            </template>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
