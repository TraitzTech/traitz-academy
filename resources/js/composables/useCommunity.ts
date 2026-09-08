import {
    Dribbble,
    Facebook,
    Github,
    Globe,
    Instagram,
    Linkedin,
    Link2,
    Mail,
    Twitch,
    Twitter,
    Youtube,
    type LucideIcon,
} from 'lucide-vue-next';

import type {
    MembershipStatus,
    RsvpStatus,
    TacActivity,
    TacActivityType,
} from '@/types/community';

/**
 * Presentation helpers shared across every TAC page — labels, colours and date
 * formatting live here so a workshop badge looks identical on the home page,
 * the calendar, the track page and the member area.
 */

const TYPE_META: Record<
    TacActivityType,
    { label: string; classes: string; icon: string }
> = {
    event: {
        label: 'Event',
        classes: 'bg-[#42b6c5]/12 text-[#26808c] dark:text-[#7fd4df]',
        icon: 'Calendar',
    },
    workshop: {
        label: 'Workshop',
        classes: 'bg-[#381998]/12 text-[#381998] dark:text-[#b9a5f5]',
        icon: 'Wrench',
    },
    training: {
        label: 'Training',
        classes: 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300',
        icon: 'GraduationCap',
    },
    bootcamp: {
        label: 'Bootcamp',
        classes: 'bg-orange-500/12 text-orange-700 dark:text-orange-300',
        icon: 'Flame',
    },
    internship: {
        label: 'Internship',
        classes: 'bg-blue-500/12 text-blue-700 dark:text-blue-300',
        icon: 'Briefcase',
    },
    handout: {
        label: 'Handout',
        classes: 'bg-slate-500/12 text-slate-700 dark:text-slate-300',
        icon: 'FileText',
    },
    competition: {
        label: 'Competition',
        classes: 'bg-pink-500/12 text-pink-700 dark:text-pink-300',
        icon: 'Trophy',
    },
};

const RSVP_META: Record<RsvpStatus, { label: string; classes: string }> = {
    registered: {
        label: 'Registered',
        classes: 'bg-[#42b6c5]/12 text-[#26808c] dark:text-[#7fd4df]',
    },
    confirmed: {
        label: 'Confirmed',
        classes: 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300',
    },
    waitlisted: {
        label: 'Waitlisted',
        classes: 'bg-amber-500/12 text-amber-700 dark:text-amber-300',
    },
    attended: {
        label: 'Attended',
        classes: 'bg-[#381998]/12 text-[#381998] dark:text-[#b9a5f5]',
    },
    cancelled: {
        label: 'Cancelled',
        classes: 'bg-gray-500/12 text-gray-600 dark:text-gray-300',
    },
    no_show: {
        label: 'No show',
        classes: 'bg-red-500/12 text-red-700 dark:text-red-300',
    },
};

const MEMBERSHIP_META: Record<
    MembershipStatus,
    { label: string; classes: string }
> = {
    member: {
        label: 'Member',
        classes: 'bg-gray-500/12 text-gray-700 dark:text-gray-300',
    },
    contributor: {
        label: 'Contributor',
        classes: 'bg-[#42b6c5]/12 text-[#26808c] dark:text-[#7fd4df]',
    },
    mentor: {
        label: 'Mentor',
        classes: 'bg-[#381998]/12 text-[#381998] dark:text-[#b9a5f5]',
    },
    lead: {
        label: 'Lead',
        classes: 'bg-amber-500/15 text-amber-800 dark:text-amber-300',
    },
    alumni: {
        label: 'Alumni',
        classes: 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300',
    },
};

export function useCommunity() {
    const activityType = (type: TacActivityType) =>
        TYPE_META[type] ?? TYPE_META.event;

    const rsvpStatus = (status: RsvpStatus) =>
        RSVP_META[status] ?? RSVP_META.registered;

    const membershipStatus = (status: MembershipStatus) =>
        MEMBERSHIP_META[status] ?? MEMBERSHIP_META.member;

    /** Storage-relative paths become public URLs; absolute URLs pass through. */
    const asset = (path: string | null | undefined): string | null => {
        if (!path) return null;
        if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
        return `/storage/${path}`;
    };

    const initials = (name: string | null | undefined): string =>
        (name ?? '')
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0]?.toUpperCase() ?? '')
            .join('') || '?';

    const formatDate = (
        value: string | null | undefined,
        options: Intl.DateTimeFormatOptions = {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        },
    ): string => {
        if (!value) return 'Date to be announced';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Date to be announced';
        return date.toLocaleDateString('en-GB', options);
    };

    const formatTime = (value: string | null | undefined): string | null => {
        if (!value) return null;
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return null;
        return date.toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    /** "in 3 days" / "2 weeks ago" — keeps the calendar feeling alive. */
    const relative = (value: string | null | undefined): string => {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';

        const diffMs = date.getTime() - Date.now();
        const absDays = Math.round(Math.abs(diffMs) / 86_400_000);
        const formatter = new Intl.RelativeTimeFormat('en', {
            numeric: 'auto',
        });

        if (absDays === 0) {
            const hours = Math.round(diffMs / 3_600_000);
            return formatter.format(hours, 'hour');
        }
        if (absDays < 31) {
            return formatter.format(Math.round(diffMs / 86_400_000), 'day');
        }
        if (absDays < 365) {
            return formatter.format(Math.round(absDays / 30) * Math.sign(diffMs), 'month');
        }
        return formatter.format(Math.round(absDays / 365) * Math.sign(diffMs), 'year');
    };

    const dateRange = (activity: Pick<TacActivity, 'starts_at' | 'ends_at'>) => {
        if (!activity.starts_at) return 'Date to be announced';

        const start = new Date(activity.starts_at);
        const end = activity.ends_at ? new Date(activity.ends_at) : null;

        const sameDay =
            end && start.toDateString() === end.toDateString();

        if (!end || sameDay) {
            const time = formatTime(activity.starts_at);
            const endTime = end ? formatTime(activity.ends_at) : null;
            return `${formatDate(activity.starts_at)}${time ? `, ${time}` : ''}${endTime ? `–${endTime}` : ''}`;
        }

        return `${formatDate(activity.starts_at)} – ${formatDate(activity.ends_at)}`;
    };

    const money = (amount: number, currency = 'XAF'): string =>
        `${new Intl.NumberFormat('en-US').format(amount)} ${currency}`;

    const locationLabel = (
        activity: Pick<TacActivity, 'location_type' | 'location'>,
    ): string => {
        if (activity.location_type === 'virtual') return 'Online';
        if (activity.location_type === 'hybrid')
            return `${activity.location ?? 'On site'} + Online`;
        return activity.location ?? 'Location to be announced';
    };

    /**
     * The real brand icon for a social link — matched against the URL's own
     * domain first (reliable, since the label a leader types is free-form),
     * then the label text as a fallback, then a generic link icon.
     */
    const socialIcon = (label: string, url?: string | null): LucideIcon => {
        let host = '';
        if (url) {
            try {
                host = new URL(url).hostname.replace(/^www\./, '');
            } catch {
                host = '';
            }
        }

        const haystack = `${host} ${label}`.toLowerCase();

        if (/linkedin/.test(haystack)) return Linkedin;
        if (/github/.test(haystack)) return Github;
        if (/(twitter\.com|\bx\.com|\btwitter\b|\bx\b)/.test(haystack))
            return Twitter;
        if (/instagram/.test(haystack)) return Instagram;
        if (/(facebook|\bfb\.com)/.test(haystack)) return Facebook;
        if (/(youtube|youtu\.be)/.test(haystack)) return Youtube;
        if (/dribbble/.test(haystack)) return Dribbble;
        if (/twitch/.test(haystack)) return Twitch;
        if (/^mailto:/.test(url ?? '') || /\bmail\b|\bemail\b/.test(haystack))
            return Mail;
        if (/website|site|portfolio|blog|\.dev\b|\.io\b/.test(haystack))
            return Globe;

        return Link2;
    };

    return {
        socialIcon,
        activityType,
        rsvpStatus,
        membershipStatus,
        asset,
        initials,
        formatDate,
        formatTime,
        relative,
        dateRange,
        money,
        locationLabel,
    };
}
