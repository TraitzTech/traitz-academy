<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    CommunityMember,
    ResponsibilityStatus,
    TacActivity,
    TacLeaderPerformanceReview,
    TacLeaderResponsibility,
} from '@/types/community';

interface Leadership {
    id: number;
    role_type: string;
    label: string;
    track: { id: number; name: string; slug: string } | null;
    school: string | null;
    responsibilities: TacLeaderResponsibility[];
    performance_reviews: TacLeaderPerformanceReview[];
}

interface TrackStat {
    id: number;
    name: string;
    slug: string;
    accent_color: string | null;
    members_count: number;
    upcoming_count: number;
}

interface SchoolStat {
    name: string;
    member_count: number;
}

interface PartnerStat {
    id: number;
    name: string;
    slug: string;
    tier: string;
    is_active: boolean;
}

interface Props {
    leaderships: Leadership[];
    tracks: TrackStat[];
    schools: SchoolStat[];
    partners: PartnerStat[];
    stats: {
        members: number;
        members_this_month: number;
        upcoming_activities: number;
        draft_activities: number;
    };
    recentMembers: CommunityMember[];
    upcoming: TacActivity[];
    drafts: { id: number; title: string; slug: string; type: string; updated_at: string }[];
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { dateRange, formatDate, initials } = useCommunity();

const hasTracks = computed(() => props.tracks.length > 0);
const hasSchools = computed(() => props.schools.length > 0);
const hasPartners = computed(() => props.partners.length > 0);

const roleSummary = computed(() =>
    props.leaderships
        .map((l) => (l.track ? `${l.track.name} — ${l.label}` : l.label))
        .join(' · '),
);

/** Flattened across every post held, each item still knows which leadership
 * (and therefore which route) it belongs to. */
const allResponsibilities = computed(() =>
    props.leaderships.flatMap((l) =>
        l.responsibilities.map((r) => ({ ...r, leaderId: l.id })),
    ),
);

const allReviews = computed(() =>
    props.leaderships.flatMap((l) => l.performance_reviews),
);

const averageRating = computed(() => {
    if (!allReviews.value.length) return null;
    return (
        allReviews.value.reduce((sum, r) => sum + r.rating, 0) /
        allReviews.value.length
    ).toFixed(1);
});

const STATUS_META: Record<ResponsibilityStatus, { label: string; classes: string }> = {
    pending: { label: 'Pending', classes: 'bg-gray-500/12 text-gray-600 dark:text-gray-300' },
    in_progress: { label: 'In progress', classes: 'bg-amber-500/12 text-amber-700 dark:text-amber-300' },
    completed: { label: 'Completed', classes: 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300' },
};

const updatingResponsibility = ref<number | null>(null);

const setResponsibilityStatus = (
    responsibility: { id: number; leaderId: number },
    status: ResponsibilityStatus,
) => {
    updatingResponsibility.value = responsibility.id;
    router.patch(
        `/admin/community/leaders/${responsibility.leaderId}/responsibilities/${responsibility.id}/status`,
        { status },
        { preserveScroll: true, onFinish: () => (updatingResponsibility.value = null) },
    );
};
</script>

<template>
    <div class="lms-page">
        <Head title="My Community — TAC" />

        <!-- Header -->
        <div class="lms-hero">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight">
                        My Community
                    </h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        {{ roleSummary }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/admin/community/announcements"
                        class="rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#35919e]"
                    >
                        Send announcement
                    </Link>
                    <Link
                        href="/admin/community/activities/create"
                        class="rounded-xl border border-white/25 bg-white/10 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-white/20"
                    >
                        New activity
                    </Link>
                </div>
            </div>

            <dl class="mt-7 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    v-for="stat in [
                        { label: 'Members', value: stats.members },
                        { label: 'Joined this month', value: stats.members_this_month },
                        { label: 'Upcoming activities', value: stats.upcoming_activities },
                        { label: 'Drafts', value: stats.draft_activities },
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

        <!-- My tracks -->
        <section v-if="hasTracks" class="lms-panel">
            <h2 class="lms-title text-lg">
                {{ tracks.length > 1 ? 'My Tracks' : 'My Track' }}
            </h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="track in tracks"
                    :key="track.id"
                    class="rounded-xl border border-gray-100 p-4 dark:border-white/10"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :style="{ backgroundColor: track.accent_color ?? '#42b6c5' }"
                            aria-hidden="true"
                        />
                        <p class="font-bold text-[#000928] dark:text-white">
                            {{ track.name }}
                        </p>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-2 text-center">
                        <div class="rounded-lg bg-gray-50 py-2 dark:bg-white/5">
                            <dd class="text-lg font-black text-[#000928] dark:text-white">
                                {{ track.members_count }}
                            </dd>
                            <dt class="text-[10px] font-semibold tracking-wider text-gray-500 uppercase">
                                Members
                            </dt>
                        </div>
                        <div class="rounded-lg bg-gray-50 py-2 dark:bg-white/5">
                            <dd class="text-lg font-black text-[#000928] dark:text-white">
                                {{ track.upcoming_count }}
                            </dd>
                            <dt class="text-[10px] font-semibold tracking-wider text-gray-500 uppercase">
                                Upcoming
                            </dt>
                        </div>
                    </dl>

                    <div class="mt-3 flex flex-wrap gap-3 text-xs">
                        <Link
                            :href="`/admin/community/members?track=${track.slug}`"
                            class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                        >
                            View members
                        </Link>
                        <Link
                            :href="`/admin/community/activities?track=${track.slug}`"
                            class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                        >
                            View activities
                        </Link>
                        <Link
                            :href="`/community/tracks/${track.slug}`"
                            class="font-bold text-gray-500 hover:underline"
                        >
                            Public page
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- My schools -->
        <section v-if="hasSchools" class="lms-panel">
            <h2 class="lms-title text-lg">
                {{ schools.length > 1 ? 'My Schools' : 'My School' }}
            </h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="school in schools"
                    :key="school.name"
                    class="rounded-xl border border-gray-100 p-4 dark:border-white/10"
                >
                    <p class="font-bold text-[#000928] dark:text-white">
                        {{ school.name }}
                    </p>
                    <p class="mt-2 text-2xl font-black text-[#000928] dark:text-white">
                        {{ school.member_count }}
                    </p>
                    <p class="text-[10px] font-semibold tracking-wider text-gray-500 uppercase">
                        Members
                    </p>

                    <div class="mt-3">
                        <Link
                            :href="`/admin/community/members?school=${encodeURIComponent(school.name)}`"
                            class="text-xs font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                        >
                            View members →
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- My partners -->
        <section v-if="hasPartners" class="lms-panel">
            <div class="flex items-start justify-between gap-3">
                <h2 class="lms-title text-lg">My Partners</h2>
                <Link
                    href="/admin/community/partners"
                    class="text-sm font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                    >Manage partners →</Link
                >
            </div>

            <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
                <li
                    v-for="partner in partners"
                    :key="partner.id"
                    class="flex items-center justify-between gap-3 py-3"
                >
                    <span class="text-sm font-semibold text-[#000928] dark:text-white">
                        {{ partner.name }}
                    </span>
                    <span class="flex items-center gap-2 text-xs">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 font-semibold text-gray-600 capitalize dark:bg-white/10 dark:text-gray-300">
                            {{ partner.tier }}
                        </span>
                        <span
                            v-if="!partner.is_active"
                            class="rounded-full bg-gray-500/12 px-2 py-0.5 font-bold text-gray-500"
                            >Inactive</span
                        >
                    </span>
                </li>
                <li v-if="!partners.length" class="lms-subtitle py-3">
                    No partners yet.
                </li>
            </ul>
        </section>

        <!-- My responsibilities & performance -->
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="lms-panel">
                <h2 class="lms-title text-lg">My responsibilities</h2>
                <p class="lms-subtitle">Assigned by academy staff.</p>

                <ul
                    v-if="allResponsibilities.length"
                    class="mt-5 divide-y divide-gray-100 dark:divide-white/5"
                >
                    <li v-for="r in allResponsibilities" :key="r.id" class="py-3.5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-[#000928] dark:text-white">
                                    {{ r.title }}
                                </p>
                                <p v-if="r.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ r.description }}
                                </p>
                                <p v-if="r.due_date" class="mt-1 text-xs text-gray-400">
                                    Due {{ formatDate(r.due_date, { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </p>
                            </div>
                            <span :class="['shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold', STATUS_META[r.status]?.classes]">
                                {{ STATUS_META[r.status]?.label ?? r.status }}
                            </span>
                        </div>

                        <div class="mt-2.5 flex gap-2">
                            <button
                                v-for="option in (['pending', 'in_progress', 'completed'] as ResponsibilityStatus[])"
                                :key="option"
                                type="button"
                                :disabled="updatingResponsibility === r.id || r.status === option"
                                :class="[
                                    'rounded-lg px-2.5 py-1 text-[11px] font-bold transition-colors disabled:cursor-default',
                                    r.status === option
                                        ? STATUS_META[option].classes
                                        : 'bg-gray-50 text-gray-400 hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10',
                                ]"
                                @click="setResponsibilityStatus(r, option)"
                            >
                                Mark {{ STATUS_META[option].label.toLowerCase() }}
                            </button>
                        </div>
                    </li>
                </ul>
                <p v-else class="lms-subtitle mt-5">Nothing assigned yet.</p>
            </section>

            <section class="lms-panel">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="lms-title text-lg">My performance</h2>
                    <span
                        v-if="averageRating"
                        class="rounded-lg bg-amber-500/12 px-3 py-1.5 text-sm font-black text-amber-700 dark:text-amber-300"
                    >
                        {{ averageRating }} / 5
                    </span>
                </div>
                <p class="lms-subtitle">Reviews written by academy staff.</p>

                <ul
                    v-if="allReviews.length"
                    class="mt-5 divide-y divide-gray-100 dark:divide-white/5"
                >
                    <li v-for="review in allReviews" :key="review.id" class="py-3.5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="font-bold">
                                <span class="text-amber-500">{{ '★'.repeat(review.rating) }}</span
                                ><span class="text-gray-300">{{ '★'.repeat(5 - review.rating) }}</span>
                            </p>
                            <span class="text-xs text-gray-400">
                                {{ formatDate(review.created_at, { day: 'numeric', month: 'short', year: 'numeric' }) }}
                            </span>
                        </div>
                        <p v-if="review.period_label" class="text-xs font-semibold text-gray-500">
                            {{ review.period_label }}
                        </p>
                        <p v-if="review.notes" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ review.notes }}
                        </p>
                    </li>
                </ul>
                <p v-else class="lms-subtitle mt-5">No reviews yet.</p>
            </section>
        </div>

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
                            {{ initials(`${member.first_name} ${member.last_name ?? ''}`) }}
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
                        <span class="shrink-0 text-xs font-medium text-gray-400">{{
                            formatDate(member.joined_at, { day: 'numeric', month: 'short' })
                        }}</span>
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
                        <span class="shrink-0 rounded-full bg-[#42b6c5]/12 px-2.5 py-1 text-xs font-bold text-[#26808c] dark:text-[#7fd4df]">
                            {{ activity.rsvp_count
                            }}<template v-if="activity.capacity">/{{ activity.capacity }}</template>
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

        <!-- Drafts -->
        <section v-if="drafts.length" class="lms-panel">
            <h2 class="lms-title text-lg">Unpublished drafts</h2>
            <ul class="mt-4 space-y-2">
                <li v-for="draft in drafts" :key="draft.id">
                    <Link
                        :href="`/admin/community/activities/${draft.slug}`"
                        class="text-sm font-semibold text-[#381998] hover:underline dark:text-[#42b6c5]"
                    >
                        {{ draft.title }}
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
