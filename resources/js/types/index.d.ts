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
    badge?: number | string;
    activeMatch?: 'exact' | 'prefix';
}

export interface NavGroup {
    label: string;
    icon?: LucideIcon;
    items?: NavItem[];
    /** Opt this group into a collapsible dropdown (all other groups stay flat/always-expanded). */
    collapsible?: boolean;
    /** Collapsed by default unless the user has toggled it before or one of its items is active. */
    defaultOpen?: boolean;
    /** Nested collapsible groups rendered under this group's label (e.g. "Events" containing "AI Forge"). */
    groups?: NavGroup[];
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
};

export interface User {
    id: number;
    name: string;
    email: string;
    role?: 'user' | 'tutor' | 'cto' | 'ceo' | 'program_coordinator' | 'admin';
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export type BreadcrumbItemType = BreadcrumbItem;
