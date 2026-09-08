<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CommunityMember, TacActivity } from '@/types/community';

interface Props {
    stats: {
        members: number;
        members_this_month: number;
        members_last_month: number;
        auto_included: number;
        in_directory: number;
        leaders: number;
        tracks: number;
        partners: number;
        upcoming_activities: number;
        draft_activities: number;
        total_rsvps: number;
    };
    growth: { month: string; label: string; total: number }[];
    bySource: Record<string, number>;
    sourceLabels: Record<string, string>;
    byTrack: {
        id: number;
        name: string;
        slug: string;
        accent_color: string | null;
        members_count: number;
        upcoming_count: number;
    }[];
    recentMembers: CommunityMember[];
    upcoming: TacActivity[];
    needsAttention: {
        drafts: { id: number; title: string; slug: string; type: string }[];
        unscored_competitions: { id: number; title: string; slug: string }[];
        vacant_roles: string[];
    };
    can: {
        executive: boolean;
        manageLeaders: boolean;
        managePartners: boolean;
    };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { dateRange, formatDate, initials } = useCommunity();

const growthMax = computed(() =>
    Math.max(1, ...props.growth.map((point) => point.total)),
);

const monthOnMonth = computed(() => {
    const { members_this_month: now, members_last_month: before } = props.stats;
    if (before === 0) return now > 0 ? null : 0;
    return Math.round(((now - before) / before) * 100);
});

const sourceRows = computed(() =>
    Object.entries(props.bySource)
        .map(([source, total]) => ({
            source,
            label: props.sourceLabels[source] ?? source,
            total,
        }))
        .sort((a, b) => b.total - a.total),
);

const sourceMax = computed(() =>
    Math.max(1, ...sourceRows.value.map((row) => row.total)),
);

const attentionCount = computed(
    () =>
        props.needsAttention.drafts.length +
        props.needsAttention.unscored_competitions.length +
        props.needsAttention.vacant_roles.length,
);
</script>

<template>
    <div class="lms-page">
        <Head title="Community — Admin" />

        <!-- Hero -->
        <div class="lms-hero">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight">
                        Traitz Academy Community
                    </h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        Members, leadership, tracks and activities — everything
                        that keeps TAC running.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/admin/community/activities/create"
                        class="rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#35919e]"
                    >
                        New activity
                    </Link>
                    <Link
                        href="/community"
                        class="rounded-xl border border-white/25 bg-white/10 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-white/20"
                    >
                        View public site
                    </Link>
                </div>
            </div>

            <dl class="mt-7 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    v-for="stat in [
                        { label: 'Total members', value: stats.members },
                        { label: 'Joined this month', value: stats.members_this_month },
                        { label: 'Upcoming activities', value: stats.upcoming_activities },
                        { label: 'Total RSVPs', value: stats.total_rsvps },
                    ]"
                    :key="stat.label"
                    class="rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur"
                >
                    <dd class="text-2xl font-black">{{ stat.value }}</dd>
                    <dt
                        class="mt-0.5 text-[11px] font-semibold tracking-wider text-white/60 uppercase"
                    >
                        {{ stat.label }}
                    </dt>
                </div>
            </dl>
        </div>

        <!-- Quick links -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <Link
                v-for="card in [
                    { label: 'Members', value: stats.members, href: '/admin/community/members' },
                    { label: 'Leaders', value: stats.leaders, href: '/admin/community/leaders' },
                    { label: 'Tracks', value: stats.tracks, href: '/admin/community/tracks' },
                    { label: 'Activities', value: stats.upcoming_activities, href: '/admin/community/activities' },
                    { label: 'Partners', value: stats.partners, href: '/admin/community/partners' },
                ]"
                :key="card.label"
                :href="card.href"
                class="lms-panel transition-all hover:-translate-y-0.5 hover:border-[#42b6c5]/40 hover:shadow-md"
            >
                <p class="text-2xl font-black text-[#000928] dark:text-white">
                    {{ card.value }}
                </p>
                <p class="lms-subtitle mt-0.5">{{ card.label }}</p>
            </Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
            <!-- Growth -->
            <section class="lms-panel">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="lms-title text-lg">Membership growth</h2>
                        <p class="lms-subtitle">
                            New members per month over the last year.
                        </p>
                    </div>
                    <span
                        v-if="monthOnMonth !== null"
                        :class="[
                            'rounded-full px-3 py-1 text-xs font-bold',
                            monthOnMonth >= 0
                                ? 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300'
                                : 'bg-red-500/12 text-red-700 dark:text-red-300',
                        ]"
                    >
                        {{ monthOnMonth >= 0 ? '↑' : '↓' }}
                        {{ Math.abs(monthOnMonth) }}% vs last month
                    </span>
                </div>

                <div
                    class="mt-7 flex h-44 items-end gap-1.5"
                    role="img"
                    aria-label="Monthly new members over the last 12 months"
                >
                    <div
                        v-for="point in growth"
                        :key="point.month"
                        class="group relative flex flex-1 flex-col items-center gap-2"
                    >
                        <span
                            class="pointer-events-none absolute -top-7 rounded-md bg-[#000928] px-2 py-1 text-[11px] font-bold text-white opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            {{ point.total }}
                        </span>
                        <div
                            class="w-full rounded-t-md bg-gradient-to-t from-[#381998] to-[#42b6c5] transition-all group-hover:opacity-80"
                            :style="{
                                height: `${Math.max(4, (point.total / growthMax) * 140)}px`,
                            }"
                        />
                        <span
                            class="text-[10px] font-semibold text-gray-400"
                            >{{ point.label }}</span
                        >
                    </div>
                </div>
            </section>

            <!-- Sources -->
            <section class="lms-panel">
                <h2 class="lms-title text-lg">How members arrive</h2>
                <p class="lms-subtitle">
                    {{ stats.auto_included }} of {{ stats.members }} were
                    auto-included from another registration.
                </p>

                <ul class="mt-6 space-y-3">
                    <li v-for="row in sourceRows" :key="row.source">
                        <div class="flex justify-between text-sm">
                            <span
                                class="font-medium text-gray-700 dark:text-gray-300"
                                >{{ row.label }}</span
                            >
                            <span
                                class="font-bold text-[#000928] dark:text-white"
                                >{{ row.total }}</span
                            >
                        </div>
                        <div
                            class="mt-1.5 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
                        >
                            <div
                                class="h-full rounded-full bg-[#42b6c5]"
                                :style="{
                                    width: `${(row.total / sourceMax) * 100}%`,
                                }"
                            />
                        </div>
                    </li>
                    <li v-if="!sourceRows.length" class="lms-subtitle">
                        No members yet.
                    </li>
                </ul>
            </section>
        </div>

        <!-- Needs attention -->
        <section v-if="attentionCount" class="lms-panel">
            <h2 class="lms-title text-lg">Needs your attention</h2>

            <div class="mt-5 grid gap-6 md:grid-cols-3">
                <div v-if="needsAttention.drafts.length">
                    <h3
                        class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Unpublished drafts
                    </h3>
                    <ul class="mt-3 space-y-2">
                        <li
                            v-for="draft in needsAttention.drafts"
                            :key="draft.id"
                        >
                            <Link
                                :href="`/admin/community/activities/${draft.slug}`"
                                class="block truncate text-sm font-semibold text-[#381998] hover:underline dark:text-[#42b6c5]"
                            >
                                {{ draft.title }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div v-if="needsAttention.unscored_competitions.length">
                    <h3
                        class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Competitions awaiting judging
                    </h3>
                    <ul class="mt-3 space-y-2">
                        <li
                            v-for="competition in needsAttention.unscored_competitions"
                            :key="competition.id"
                        >
                            <Link
                                :href="`/admin/community/activities/${competition.slug}/judge`"
                                class="block truncate text-sm font-semibold text-[#381998] hover:underline dark:text-[#42b6c5]"
                            >
                                {{ competition.title }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div v-if="needsAttention.vacant_roles.length">
                    <h3
                        class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Vacant leadership posts
                    </h3>
                    <ul class="mt-3 flex flex-wrap gap-1.5">
                        <li
                            v-for="role in needsAttention.vacant_roles"
                            :key="role"
                            class="rounded-full bg-amber-500/12 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300"
                        >
                            {{ role }}
                        </li>
                    </ul>
                    <Link
                        v-if="can.manageLeaders"
                        href="/admin/community/leaders"
                        class="mt-3 inline-block text-sm font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                    >
                        Appoint leaders →
                    </Link>
                </div>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Recent members -->
            <section class="lms-panel">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="lms-title text-lg">Newest members</h2>
                    <Link
                        href="/admin/community/members"
                        class="text-sm font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                        >View all →</Link
                    >
                </div>

                <ul
                    v-if="recentMembers.length"
                    class="mt-5 divide-y divide-gray-100 dark:divide-white/5"
                >
                    <li
                        v-for="member in recentMembers"
                        :key="member.id"
                        class="flex items-center gap-3 py-3"
                    >
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#381998]/10 text-xs font-black text-[#381998] dark:text-[#b9a5f5]"
                            aria-hidden="true"
                        >
                            {{
                                initials(
                                    `${member.first_name} ${member.last_name ?? ''}`,
                                )
                            }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="`/admin/community/members/${member.id}`"
                                class="block truncate text-sm font-semibold text-[#000928] hover:underline dark:text-white"
                            >
                                {{ member.first_name }} {{ member.last_name }}
                            </Link>
                            <p class="truncate text-xs text-gray-500">
                                {{ member.school || member.email }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 text-xs font-medium text-gray-400"
                            >{{
                                formatDate(member.joined_at, {
                                    day: 'numeric',
                                    month: 'short',
                                })
                            }}</span
                        >
                    </li>
                </ul>
                <p v-else class="lms-subtitle mt-5">No members yet.</p>
            </section>

            <!-- Upcoming -->
            <section class="lms-panel">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="lms-title text-lg">Coming up</h2>
                    <Link
                        href="/admin/community/activities"
                        class="text-sm font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                        >All activities →</Link
                    >
                </div>

                <ul
                    v-if="upcoming.length"
                    class="mt-5 divide-y divide-gray-100 dark:divide-white/5"
                >
                    <li
                        v-for="activity in upcoming"
                        :key="activity.id"
                        class="flex items-center gap-3 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="`/admin/community/activities/${activity.slug}`"
                                class="block truncate text-sm font-semibold text-[#000928] hover:underline dark:text-white"
                            >
                                {{ activity.title }}
                            </Link>
                            <p class="truncate text-xs text-gray-500">
                                {{ dateRange(activity) }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full bg-[#42b6c5]/12 px-2.5 py-1 text-xs font-bold text-[#26808c] dark:text-[#7fd4df]"
                        >
                            {{ activity.rsvp_count
                            }}<template v-if="activity.capacity"
                                >/{{ activity.capacity }}</template
                            >
                        </span>
                    </li>
                </ul>
                <p v-else class="lms-subtitle mt-5">
                    Nothing scheduled.
                    <Link
                        href="/admin/community/activities/create"
                        class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                        >Create one →</Link
                    >
                </p>
            </section>
        </div>

        <!-- Tracks -->
        <section class="lms-panel">
            <div class="flex items-start justify-between gap-3">
                <h2 class="lms-title text-lg">Members by track</h2>
                <Link
                    href="/admin/community/tracks"
                    class="text-sm font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                    >Manage tracks →</Link
                >
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="track in byTrack"
                    :key="track.id"
                    :href="`/admin/community/members?track=${track.slug}`"
                    class="rounded-xl border border-gray-100 p-4 transition-colors hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :style="{
                                backgroundColor: track.accent_color ?? '#42b6c5',
                            }"
                            aria-hidden="true"
                        />
                        <p
                            class="truncate text-sm font-semibold text-[#000928] dark:text-white"
                        >
                            {{ track.name }}
                        </p>
                    </div>
                    <p
                        class="mt-2 text-xl font-black text-[#000928] dark:text-white"
                    >
                        {{ track.members_count }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ track.upcoming_count }} upcoming
                    </p>
                </Link>
            </div>
        </section>
    </div>
</template>
