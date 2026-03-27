<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    BookOpen,
    BookMarked,
    Award,
    BarChart3,
    Calendar,
    Clock,
    ClipboardList,
    Upload,
    Folder,
    Image,
    MessageCircle,
    LayoutGrid,
    Library,
    Lightbulb,
    Mail,
    MessageSquare,
    NotebookPen,
    Package,
    PlusCircle,
    Receipt,
    Search,
    Settings,
    ShoppingBag,
    UserCheck,
    Users,
    GraduationCap,
    FileText,
    Home,
    Star,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';

import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavGroup, type NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const adminRoles = ['cto', 'ceo', 'program_coordinator', 'admin']
const executiveRoles = ['cto', 'ceo', 'admin']
const isAdmin = computed(() => adminRoles.includes(String(user.value?.role ?? '')))
const isExecutive = computed(() => executiveRoles.includes(String(user.value?.role ?? '')))
const isTutor = computed(() => user.value?.role === 'tutor')

// Admin standalone items (always visible at top)
const adminStandaloneItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: LayoutGrid,
    },
];

// Admin grouped navigation
const academyGroup: NavGroup = {
    label: 'Academy',
    items: [
        {
            title: 'Programs',
            href: '/admin/programs',
            icon: GraduationCap,
        },
        {
            title: 'Events',
            href: '/admin/events',
            icon: Calendar,
        },
        {
            title: 'Gallery',
            href: '/admin/gallery',
            icon: Image,
        },
        {
            title: 'Resources',
            href: '/admin/learning-resources',
            icon: Library,
        },
    ],
};

const admissionsGroup: NavGroup = {
    label: 'Admissions',
    items: [
        {
            title: 'Applications',
            href: '/admin/applications',
            icon: ClipboardList,
        },
        {
            title: 'Interviews',
            href: '/admin/interviews',
            icon: MessageSquare,
        },
        {
            title: 'Feedback',
            href: '/admin/feedback',
            icon: MessageCircle,
        },
    ],
};

const financeGroup: NavGroup = {
    label: 'Finance',
    items: [
        {
            title: 'Payments',
            href: '/admin/payments',
            icon: Wallet,
        },
        {
            title: 'Expenses',
            href: '/admin/expenses',
            icon: Receipt,
        },
    ],
};

const financeGroupWithWithdrawals: NavGroup = {
    label: 'Finance',
    items: [
        {
            title: 'Payments',
            href: '/admin/payments',
            icon: Wallet,
        },
        {
            title: 'Expenses',
            href: '/admin/expenses',
            icon: Receipt,
        },
        {
            title: 'Withdrawals',
            href: '/admin/withdrawals',
            icon: ArrowDownToLine,
        },
    ],
};

const contentGroup: NavGroup = {
    label: 'Content',
    items: [
        {
            title: 'Success Stories',
            href: '/admin/success-stories',
            icon: Star,
        },
    ],
};

const aiForgeGroup: NavGroup = {
    label: 'AI Forge',
    items: [
        {
            title: 'Settings',
            href: '/admin/ai-forge',
            icon: Lightbulb,
        },
        {
            title: 'Swags',
            href: '/admin/ai-forge/swags',
            icon: ShoppingBag,
        },
        {
            title: 'Registrations',
            href: '/admin/ai-forge/registrations',
            icon: UserCheck,
        },
        {
            title: 'Orders',
            href: '/admin/ai-forge/orders',
            icon: Package,
        },
    ],
};

const pendingCoursesCount = computed(() => (page.props as Record<string, unknown>).pendingCoursesCount as number | null)

const lmsGroup = computed<NavGroup>(() => ({
    label: 'LMS',
    items: [
        {
            title: 'Courses',
            href: '/admin/courses',
            icon: BookOpen,
        },
        {
            title: 'Pending Courses',
            href: '/admin/courses?status=pending_review',
            icon: Clock,
            badge: pendingCoursesCount.value || undefined,
        },
        {
            title: 'Course Categories',
            href: '/admin/course-categories',
            icon: Folder,
        },
    ],
}));

const systemGroup: NavGroup = {
    label: 'System',
    items: [
        {
            title: 'Users',
            href: '/admin/users',
            icon: Users,
        },
        {
            title: 'Emails',
            href: '/admin/emails',
            icon: Mail,
        },
        {
            title: 'Settings',
            href: '/admin/settings',
            icon: Settings,
        },
    ],
};

// User navigation items (flat, no grouping needed)
const userStandaloneItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const userGroups: NavGroup[] = [
    {
        label: 'My Account',
        items: [
            {
                title: 'Applications',
                href: '/dashboard#applications',
                icon: FileText,
            },
            {
                title: 'Registrations',
                href: '/dashboard#registrations',
                icon: Calendar,
            },
            {
                title: 'Payments',
                href: '/dashboard#payments',
                icon: Wallet,
            },
        ],
    },
    {
        label: 'Courses',
        items: [
            {
                title: 'All Courses',
                href: '/dashboard/courses',
                icon: Search,
            },
            {
                title: 'My Courses',
                href: '/dashboard/my-courses',
                icon: BookMarked,
            },
            {
                title: 'My Certificates',
                href: '/dashboard/certificates',
                icon: Award,
            },
            {
                title: 'My Notes',
                href: '/dashboard/notes',
                icon: NotebookPen,
            },
        ],
    },
];

// Tutor navigation
const tutorStandaloneItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/tutor/dashboard',
        icon: LayoutGrid,
    },
];

const tutorGroups: NavGroup[] = [
    {
        label: 'Teaching',
        items: [
            {
                title: 'My Courses',
                href: '/tutor/courses',
                icon: BookOpen,
            },
            {
                title: 'Create Course',
                href: '/tutor/courses/create',
                icon: PlusCircle,
            },
            {
                title: 'Upload Lesson',
                href: '/tutor/lessons/upload',
                icon: Upload,
            },
            {
                title: 'Upload Lesson',
                href: '/tutor/lessons/upload',
                icon: Upload,
            },
            {
                title: 'My Students',
                href: '/tutor/students',
                icon: Users,
            },
            {
                title: 'Discussions',
                href: '/tutor/discussions',
                icon: MessageCircle,
            },
        ],
    },
    {
        label: 'Finance',
        items: [
            {
                title: 'Earnings',
                href: '/tutor/earnings',
                icon: BarChart3,
            },
        ],
    },
];

// Computed navigation based on role
const standaloneItems = computed<NavItem[]>(() => {
    if (isAdmin.value) return adminStandaloneItems;
    if (isTutor.value) return tutorStandaloneItems;
    return userStandaloneItems;
});

const navGroups = computed<NavGroup[]>(() => {
    if (isAdmin.value) {
        const groups = [academyGroup, admissionsGroup, isExecutive.value ? financeGroupWithWithdrawals : financeGroup, contentGroup, aiForgeGroup, lmsGroup.value];
        if (isExecutive.value) groups.push(systemGroup);
        return groups;
    }
    if (isTutor.value) return tutorGroups;
    return userGroups;
});

// Home link based on role
const homeLink = computed(() => isAdmin.value ? '/admin/dashboard' : '/dashboard');

const footerNavItems: NavItem[] = [
    {
        title: 'Visit Website',
        href: '/',
        icon: Home,
    },
    {
        title: 'Programs',
        href: '/programs',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="homeLink">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="navGroups" :standalone="standaloneItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
