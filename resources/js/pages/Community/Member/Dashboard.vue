<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ActivityCard from '@/components/community/ActivityCard.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import { useCommunity } from '@/composables/useCommunity';
import type {
    CommunityMember,
    TacActivity,
    TacActivityRsvp,
    TacCompetitionEntry,
} from '@/types/community';

interface Props {
    member: CommunityMember & { leadership?: unknown };
    upcomingRsvps: TacActivityRsvp[];
    pastRsvps: TacActivityRsvp[];
    entries: TacCompetitionEntry[];
    recommended: TacActivity[];
    stats: {
        attended: number;
        upcoming: number;
        entries: number;
        wins: number;
        member_since: string | null;
        engagement_score: number;
    };
}

const props = defineProps<Props>();
const {
    asset,
    initials,
    formatDate,
    dateRange,
    rsvpStatus,
    membershipStatus,
} = useCommunity();
</script>

<template>
    <CommunityShell active="member">
        <Head title="My TAC — member area" />

        <!-- Header -->
        <section
            class="border-b border-gray-100 bg-white"
        >
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center gap-5">
                    <span class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl">
                        <img
                            v-if="asset(member.avatar_path)"
                            :src="asset(member.avatar_path)!"
                            :alt="member.full_name"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-else
                            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#000928] to-[#381998] text-xl font-black text-white"
                            aria-hidden="true"
                            >{{ initials(member.full_name) }}</span
                        >
                    </span>

                    <div class="min-w-0 flex-1">
                        <h1
                            class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
                        >
                            Hello, {{ member.first_name }}
                        </h1>
                        <p
                            class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500"
                        >
                            <span
                                :class="[
                                    'rounded-full px-2.5 py-0.5 text-[11px] font-bold',
                                    membershipStatus(member.membership_status)
                                        .classes,
                                ]"
                            >
                                {{
                                    membershipStatus(member.membership_status)
                                        .label
                                }}
                            </span>
                            <span v-if="stats.member_since"
                                >Member since
                                {{
                                    formatDate(stats.member_since, {
                                        month: 'long',
                                        year: 'numeric',
                                    })
                                }}</span
                            >
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <Link
                            href="/community/me/directory"
                            class="lms-btn-outline"
                            >Directory</Link
                        >
                        <Link
                            href="/community/me/profile"
                            class="lms-btn-primary"
                            >Edit profile</Link
                        >
                    </div>
                </div>

                <!-- Tracks -->
                <div
                    v-if="member.tracks?.length"
                    class="mt-6 flex flex-wrap gap-2"
                >
                    <Link
                        v-for="track in member.tracks"
                        :key="track.id"
                        :href="`/community/tracks/${track.slug}`"
                        class="rounded-full px-3.5 py-1.5 text-sm font-semibold transition-transform hover:-translate-y-0.5"
                        :style="{
                            backgroundColor: `${track.accent_color ?? '#42b6c5'}1f`,
                            color: track.accent_color ?? '#42b6c5',
                        }"
                    >
                        {{ track.name }}
                    </Link>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <!-- Stats -->
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    v-for="stat in [
                        { label: 'Coming up', value: stats.upcoming },
                        { label: 'Attended', value: stats.attended },
                        { label: 'Competition entries', value: stats.entries },
                        { label: 'Wins', value: stats.wins },
                    ]"
                    :key="stat.label"
                    class="rounded-2xl border border-gray-200 bg-white p-5"
                >
                    <dd
                        class="text-2xl font-black text-[#000928]"
                    >
                        {{ stat.value }}
                    </dd>
                    <dt
                        class="mt-1 text-[11px] font-semibold tracking-wider text-gray-500 uppercase"
                    >
                        {{ stat.label }}
                    </dt>
                </div>
            </dl>

            <div class="mt-10 grid gap-10 lg:grid-cols-[1.4fr_1fr]">
                <!-- Upcoming RSVPs -->
                <section>
                    <h2
                        class="text-xl font-black tracking-tight text-[#000928]"
                    >
                        Your upcoming activities
                    </h2>

                    <ul v-if="upcomingRsvps.length" class="mt-5 space-y-3">
                        <li
                            v-for="rsvp in upcomingRsvps"
                            :key="rsvp.id"
                            class="rounded-2xl border border-gray-200 bg-white p-5"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <Link
                                        :href="`/community/activities/${rsvp.activity?.slug}`"
                                        class="font-bold text-[#000928] hover:text-[#381998]"
                                    >
                                        {{ rsvp.activity?.title }}
                                    </Link>
                                    <p
                                        class="mt-1 text-sm text-gray-500"
                                    >
                                        {{
                                            rsvp.activity
                                                ? dateRange(rsvp.activity)
                                                : ''
                                        }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span
                                        :class="[
                                            'rounded-full px-2.5 py-0.5 text-[11px] font-bold',
                                            rsvpStatus(rsvp.status).classes,
                                        ]"
                                    >
                                        {{ rsvpStatus(rsvp.status).label }}
                                    </span>
                                    <span
                                        v-if="rsvp.payment_status === 'pending'"
                                        class="rounded-full bg-amber-500/15 px-2.5 py-0.5 text-[11px] font-bold text-amber-700"
                                    >
                                        Payment due
                                    </span>
                                </div>
                            </div>

                            <Link
                                v-if="rsvp.payment_status === 'pending'"
                                :href="`/community/activities/${rsvp.activity?.slug}/checkout`"
                                class="lms-btn-accent mt-4 inline-flex"
                            >
                                Complete payment
                            </Link>
                        </li>
                    </ul>

                    <EmptyState
                        v-else
                        class="mt-5"
                        title="Nothing on your calendar yet"
                        description="Browse what's coming up and reserve a place — most TAC activities are free."
                    >
                        <Link
                            href="/community/activities"
                            class="lms-btn-accent"
                            >Browse activities</Link
                        >
                    </EmptyState>

                    <!-- Competition entries -->
                    <section v-if="entries.length" class="mt-10">
                        <h2
                            class="text-xl font-black tracking-tight text-[#000928]"
                        >
                            Your competition entries
                        </h2>
                        <ul class="mt-5 space-y-3">
                            <li
                                v-for="entry in entries"
                                :key="entry.id"
                                :class="[
                                    'rounded-2xl border p-5',
                                    entry.is_winner
                                        ? 'border-amber-400/50 bg-amber-50'
                                        : 'border-gray-200 bg-white',
                                ]"
                            >
                                <div
                                    class="flex flex-wrap items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="font-bold text-[#000928]"
                                        >
                                            {{ entry.title }}
                                            <span
                                                v-if="entry.award"
                                                class="ml-2 rounded-full bg-amber-400/25 px-2 py-0.5 text-[11px] font-bold text-amber-700"
                                                >{{ entry.award }}</span
                                            >
                                        </p>
                                        <Link
                                            :href="`/community/activities/${entry.activity?.slug}`"
                                            class="mt-0.5 text-sm text-gray-500 hover:underline"
                                        >
                                            {{ entry.activity?.title }}
                                        </Link>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p
                                            v-if="entry.rank"
                                            class="text-sm font-black text-[#000928]"
                                        >
                                            #{{ entry.rank }}
                                        </p>
                                        <p
                                            class="text-xs text-gray-500 capitalize"
                                        >
                                            {{ entry.status.replace('_', ' ') }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <!-- History -->
                    <section v-if="pastRsvps.length" class="mt-10">
                        <h2
                            class="text-xl font-black tracking-tight text-[#000928]"
                        >
                            Your history
                        </h2>
                        <ul
                            class="mt-5 divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200"
                        >
                            <li
                                v-for="rsvp in pastRsvps"
                                :key="rsvp.id"
                                class="flex items-center justify-between gap-4 px-5 py-3.5"
                            >
                                <Link
                                    :href="`/community/activities/${rsvp.activity?.slug}`"
                                    class="min-w-0 truncate text-sm font-semibold text-[#000928] hover:underline"
                                >
                                    {{ rsvp.activity?.title }}
                                </Link>
                                <span class="flex shrink-0 items-center gap-3">
                                    <span
                                        class="text-xs text-gray-400"
                                        >{{
                                            formatDate(rsvp.activity?.starts_at, {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                            })
                                        }}</span
                                    >
                                    <span
                                        :class="[
                                            'rounded-full px-2.5 py-0.5 text-[11px] font-bold',
                                            rsvpStatus(rsvp.status).classes,
                                        ]"
                                        >{{ rsvpStatus(rsvp.status).label }}</span
                                    >
                                </span>
                            </li>
                        </ul>
                    </section>
                </section>

                <!-- Recommended -->
                <aside>
                    <h2
                        class="text-xl font-black tracking-tight text-[#000928]"
                    >
                        Picked for your tracks
                    </h2>
                    <p class="mt-1.5 text-sm text-gray-500">
                        Upcoming activities in the areas you follow.
                    </p>

                    <div v-if="recommended.length" class="mt-5 space-y-5">
                        <ActivityCard
                            v-for="activity in recommended"
                            :key="activity.id"
                            :activity="activity"
                            compact
                        />
                    </div>

                    <EmptyState
                        v-else
                        class="mt-5"
                        title="No new suggestions"
                        description="You're already signed up for everything in your tracks, or nothing new has been announced yet."
                    >
                        <Link
                            href="/community/me/profile"
                            class="lms-btn-outline"
                            >Add more tracks</Link
                        >
                    </EmptyState>
                </aside>
            </div>
        </div>
    </CommunityShell>
</template>
