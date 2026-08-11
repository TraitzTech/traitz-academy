<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    BookOpen,
    BookMarked,
    Award,
    BarChart3,
    Briefcase,
    Calendar,
    Clock,
    ClipboardList,
    Upload,
    Folder,
    Image,
    Layers,
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
    Video,
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
const unreadNotificationsCount = computed(() => Number((page.props as Record<string, unknown>).unreadNotificationsCount ?? 0))

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
    icon: Lightbulb,
    collapsible: true,
    defaultOpen: false,
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

// Standalone programs/events (each its own collapsible dropdown) live here.
// To add another one later (e.g. a future hackathon or bootcamp), build it
// as its own NavGroup like aiForgeGroup and push it into this list.
const eventsGroup: NavGroup = {
    label: 'Events',
    groups: [aiForgeGroup],
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
        {
            title: 'Enrollments',
            href: '/admin/enrollments',
            icon: Users,
        },
        {
            title: 'Platform Summary',
            href: '/admin/lms/platform-summary',
            icon: LayoutGrid,
        },
        {
            title: 'Per-course Reports',
            href: '/admin/lms/course-reports',
            icon: BookMarked,
        },
        {
            title: 'Per-user Reports',
            href: '/admin/lms/user-reports',
            icon: UserCheck,
        },
        {
            title: 'Discussions',
            href: '/admin/lms/discussions',
            icon: MessageCircle,
        },
    ],
}));

// Live Classes/Tasks/Schedule now span Courses, Internship Cohorts, and
// Programs (see RosterResolver-backed generalization) — kept out of the
// course-only LMS group so it doesn't read as course-management only.
const learningOpsGroup: NavGroup = {
    label: 'Learning Ops',
    items: [
        {
            title: 'Live Classes',
            href: '/admin/lms/live-classes',
            icon: Video,
        },
        {
            title: 'Tasks',
            href: '/admin/lms/assignments',
            icon: ClipboardList,
        },
        {
            title: 'Schedule',
            href: '/admin/lms/schedules',
            icon: Calendar,
        },
        {
            title: 'Notifications',
            href: '/admin/lms/notifications',
            icon: Mail,
        },
    ],
};

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
        // Applies regardless of whether you're a course student or an
        // intern — Tasks/Schedule/Live Classes now span both (see
        // RosterResolver-backed generalization), so this group is not
        // course-specific.
        label: 'Learning',
        items: [
            {
                title: 'Tasks',
                href: '/dashboard/assignments',
                icon: ClipboardList,
            },
            {
                title: 'Schedule',
                href: '/dashboard/schedules',
                icon: Calendar,
            },
            {
                title: 'Live Classes',
                href: '/dashboard/live-classes',
                icon: Video,
            },
            {
                title: 'Recorded Live Classes',
                href: '/dashboard/live-classes/recordings',
                icon: Library,
            },
            {
                title: 'Notifications',
                href: '/dashboard/notifications',
                icon: Mail,
                badge: unreadNotificationsCount.value > 0 ? unreadNotificationsCount.value : undefined,
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
                title: 'Discussions',
                href: '/dashboard/discussions',
                icon: MessageCircle,
            },
            {
                // Lesson notes are tied to course lessons, so they live with Courses.
                title: 'My Notes',
                href: '/dashboard/notes',
                icon: NotebookPen,
            },
            {
                title: 'My Certificates',
                href: '/dashboard/certificates',
                icon: Award,
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
        activeMatch: 'prefix',
    },
];

const tutorGroups: NavGroup[] = [
    {
        label: 'Course Management',
        items: [
            {
                title: 'My Courses',
                href: '/tutor/courses',
                icon: BookOpen,
                activeMatch: 'prefix',
            },
            {
                title: 'Create Course',
                href: '/tutor/courses/create',
                icon: PlusCircle,
                activeMatch: 'prefix',
            },
            {
                title: 'Upload Lesson',
                href: '/tutor/lessons/upload',
                icon: Upload,
                activeMatch: 'prefix',
            },
            {
                title: 'Discussions',
                href: '/tutor/discussions',
                icon: MessageCircle,
                activeMatch: 'prefix',
            },
        ],
    },
    {
        label: 'Learners',
        items: [
            {
                title: 'My Students',
                href: '/tutor/students',
                icon: Users,
                activeMatch: 'prefix',
            },
        ],
    },
    {
        // Live Classes/Tasks/Schedule now span Courses, Internship Cohorts,
        // and Programs the tutor supervises — not course-only.
        label: 'Learning Ops',
        items: [
            {
                title: 'Live Classes',
                href: '/tutor/live-classes',
                icon: Video,
                activeMatch: 'prefix',
            },
            {
                title: 'Tasks',
                href: '/tutor/assignments',
                icon: ClipboardList,
                activeMatch: 'prefix',
            },
            {
                title: 'Schedule',
                href: '/tutor/schedules',
                icon: Calendar,
                activeMatch: 'prefix',
            },
            {
                title: 'Resources',
                href: '/tutor/resources',
                icon: Library,
                activeMatch: 'prefix',
            },
            {
                title: 'Notifications',
                href: '/tutor/notifications',
                icon: Mail,
                activeMatch: 'prefix',
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
                activeMatch: 'prefix',
            },
        ],
    },
];

// Computed navigation based on role
const supervisorStandaloneItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/supervisor/dashboard',
        icon: LayoutGrid,
    },
];

const standaloneItems = computed<NavItem[]>(() => {
    if (isAdmin.value) return adminStandaloneItems;
    if (isTutor.value) return tutorStandaloneItems;
    // Pure supervisors (staff, not learners) get their supervisor home.
    if (internshipAccess.value?.supervises) return supervisorStandaloneItems;
    return userStandaloneItems;
});

const internshipAccess = computed(() => (page.props as Record<string, unknown>).internshipAccess as { is_intern?: boolean; supervises?: boolean } | undefined);

const internshipGroup = computed<NavGroup | null>(() => {
    const items: NavItem[] = [];
    if (isAdmin.value) {
        items.push({ title: 'Cohorts', href: '/admin/internships/cohorts', icon: Users });
        items.push({ title: 'Intern Activity', href: '/supervisor/interns', icon: ClipboardList });
    } else if (internshipAccess.value?.supervises) {
        // Tutors who also supervise have their own (teaching) home, so give them
        // a link into the supervisor overview. Pure supervisors already land
        // there as their main Dashboard, so no duplicate link for them.
        if (isTutor.value) {
            items.push({ title: 'Supervisor Overview', href: '/supervisor/dashboard', icon: LayoutGrid });
        } else {
            // Pure supervisors get the learning-ops tools here (tutors already
            // have them in their own Learning Ops group).
            items.push({ title: 'Live Classes', href: '/tutor/live-classes', icon: Video, activeMatch: 'prefix' });
            items.push({ title: 'Tasks', href: '/tutor/assignments', icon: ClipboardList, activeMatch: 'prefix' });
            items.push({ title: 'Schedule', href: '/tutor/schedules', icon: Calendar, activeMatch: 'prefix' });
            items.push({ title: 'Resources', href: '/tutor/resources', icon: Library, activeMatch: 'prefix' });
            items.push({ title: 'Notifications', href: '/tutor/notifications', icon: Mail, activeMatch: 'prefix' });
        }
        items.push({ title: 'My Interns', href: '/supervisor/interns', icon: ClipboardList });
        items.push({ title: 'Cohorts', href: '/supervisor/cohorts', icon: Layers, activeMatch: 'prefix' });
    }
    if (internshipAccess.value?.is_intern) {
        items.push({ title: 'My Internship', href: '/dashboard/internship', icon: Briefcase });
        items.push({ title: 'My Logbook', href: '/dashboard/internship/logbook', icon: NotebookPen });
        items.push({ title: 'Program Resources', href: '/dashboard/program-resources', icon: Library });
    }

    return items.length ? { label: 'Internships', items } : null;
});

const navGroups = computed<NavGroup[]>(() => {
    if (isAdmin.value) {
        const groups: NavGroup[] = [academyGroup, admissionsGroup, isExecutive.value ? financeGroupWithWithdrawals : financeGroup, contentGroup, eventsGroup];
        // Internships sits before Learning Ops/LMS.
        if (internshipGroup.value) groups.push(internshipGroup.value);
        groups.push(learningOpsGroup);
        groups.push(lmsGroup.value);
        if (isExecutive.value) groups.push(systemGroup);

        return groups;
    }

    // Pure supervisors (staff who supervise interns but aren't themselves
    // enrolled as a student) get their Tasks/Schedule/Live Classes/Notifications
    // from internshipGroup already, and never went through an application/
    // payment flow — the generic learner-facing "My Account", "Learning", and
    // "Courses" groups would just show them empty/irrelevant pages.
    const isPureSupervisor = !isTutor.value && internshipAccess.value?.supervises && !internshipAccess.value?.is_intern;

    const groups: NavGroup[] = isTutor.value
        ? [...tutorGroups]
        : isPureSupervisor
          ? userGroups.filter((g) => g.label !== 'My Account' && g.label !== 'Learning' && g.label !== 'Courses')
          : [...userGroups];
    if (internshipGroup.value) {
        // Place Internships before the general Learning section if present,
        // else before the course-management section.
        let lmsIndex = groups.findIndex((g) => g.label === 'Learning' || g.label === 'Learning Ops');
        if (lmsIndex < 0) {
            lmsIndex = groups.findIndex((g) => g.label === 'Courses' || g.label === 'Course Management');
        }
        if (lmsIndex >= 0) {
            groups.splice(lmsIndex, 0, internshipGroup.value);
        } else {
            groups.push(internshipGroup.value);
        }
    }

    return groups;
});

// Home link based on role
const homeLink = computed(() => {
    if (isAdmin.value) return '/admin/dashboard';
    if (!isTutor.value && internshipAccess.value?.supervises) return '/supervisor/dashboard';
    return '/dashboard';
});

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
