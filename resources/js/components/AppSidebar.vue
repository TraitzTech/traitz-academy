<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { 
    BookOpen, 
    Calendar,
    ClipboardList,
    Folder, 
    LayoutGrid,
    Mail,
    MessageSquare,
    Receipt,
    Settings,
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
import { type NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const adminRoles = ['cto', 'ceo', 'program_coordinator', 'admin']
const executiveRoles = ['cto', 'ceo', 'admin']
const isAdmin = computed(() => adminRoles.includes(String(user.value?.role ?? '')))
const isExecutive = computed(() => executiveRoles.includes(String(user.value?.role ?? '')))

// Admin navigation items
const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: LayoutGrid,
    },
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
        title: 'Applications',
        href: '/admin/applications',
        icon: ClipboardList,
    },
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
        title: 'Success Stories',
        href: '/admin/success-stories',
        icon: Star,
    },
    {
        title: 'Interviews',
        href: '/admin/interviews',
        icon: MessageSquare,
    },
];

const executiveNavItems: NavItem[] = [
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
]

// User navigation items
const userNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'My Applications',
        href: '/dashboard#applications',
        icon: FileText,
    },
    {
        title: 'My Registrations',
        href: '/dashboard#registrations',
        icon: Calendar,
    },
    {
        title: 'Payments',
        href: '/dashboard#payments',
        icon: Wallet,
    },
];

// Main navigation based on role
const mainNavItems = computed(() => {
    if (!isAdmin.value) {
        return userNavItems
    }

    if (isExecutive.value) {
        return [...adminNavItems, ...executiveNavItems]
    }

    return adminNavItems
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
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
