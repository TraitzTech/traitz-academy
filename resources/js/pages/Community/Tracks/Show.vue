<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import ActivityCard from '@/components/community/ActivityCard.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import LeaderCard from '@/components/community/LeaderCard.vue';
import { useCommunity } from '@/composables/useCommunity';
import type { CommunityMember, TacActivity, TacTrack } from '@/types/community';

interface Props {
    track: TacTrack;
    upcoming: TacActivity[];
    past: TacActivity[];
    members: CommunityMember[];
    directoryCount: number;
    isMember: boolean;
    inThisTrack: boolean;
}

const props = defineProps<Props>();
const { asset, initials, formatDate } = useCommunity();

const accent = computed(() => props.track.accent_color ?? '#42b6c5');
const mentors = computed(() => props.track.mentors ?? []);
</script>

<template>
    <CommunityShell active="tracks">
        <Head :title="`${track.name} — TAC track`">
            <meta
                name="description"
                :content="track.tagline ?? `The ${track.name} track in the Traitz Academy Community.`"
            />
        </Head>

        <!-- Hero -->
        <section class="relative overflow-hidden bg-[#000928]">
            <div class="absolute inset-0" aria-hidden="true">
                <img
                    v-if="asset(track.cover_image)"
                    :src="asset(track.cover_image)!"
                    alt=""
                    class="h-full w-full object-cover opacity-25"
                />
                <div
                    class="absolute inset-0"
                    :style="{
                        background: `linear-gradient(135deg, #000928 0%, ${accent}55 100%)`,
                    }"
                />
            </div>

            <div
                class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20"
            >
                <nav class="mb-6 text-sm" aria-label="Breadcrumb">
                    <Link
                        href="/community/tracks"
                        class="font-semibold text-white/60 transition-colors hover:text-white"
                    >
                        ← All tracks
                    </Link>
                </nav>

                <div class="flex flex-wrap items-center gap-4">
                    <span
                        class="flex h-16 w-16 items-center justify-center rounded-2xl text-xl font-black text-white backdrop-blur"
                        :style="{ backgroundColor: `${accent}33` }"
                        aria-hidden="true"
                    >
                        {{ initials(track.name) }}
                    </span>
                    <div>
                        <h1
                            class="text-3xl font-black tracking-tight text-white sm:text-4xl"
                        >
                            {{ track.name }}
                        </h1>
                        <p
                            v-if="track.tagline"
                            class="mt-1.5 text-lg text-white/70"
                        >
                            {{ track.tagline }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="track.description"
                    class="mt-7 max-w-3xl leading-relaxed text-white/70"
                >
                    {{ track.description }}
                </p>

                <div
                    class="mt-8 flex flex-wrap gap-x-8 gap-y-2 text-sm text-white/80"
                >
                    <span
                        ><strong class="text-white">{{
                            track.members_count ?? 0
                        }}</strong>
                        members</span
                    >
                    <span
                        ><strong class="text-white">{{ mentors.length }}</strong>
                        {{ mentors.length === 1 ? 'mentor' : 'mentors' }}</span
                    >
                    <span
                        ><strong class="text-white">{{
                            track.activities_count ?? 0
                        }}</strong>
                        activities</span
                    >
                </div>

                <div class="mt-8">
                    <Link
                        v-if="!isMember"
                        href="/community/join"
                        class="inline-block rounded-xl bg-[#42b6c5] px-7 py-3 font-bold text-white shadow-xl shadow-[#42b6c5]/25 transition-all hover:-translate-y-0.5 hover:bg-[#35919e]"
                    >
                        Join TAC and follow this track
                    </Link>
                    <Link
                        v-else-if="!inThisTrack"
                        href="/community/me/profile"
                        class="inline-block rounded-xl bg-[#42b6c5] px-7 py-3 font-bold text-white shadow-xl shadow-[#42b6c5]/25 transition-all hover:-translate-y-0.5 hover:bg-[#35919e]"
                    >
                        Add this track to my profile
                    </Link>
                    <span
                        v-else
                        class="inline-flex items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-6 py-3 font-bold text-white backdrop-blur"
                    >
                        <span aria-hidden="true">✓</span> This is one of your
                        tracks
                    </span>
                </div>
            </div>
        </section>

        <!-- Mentors -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2
                class="text-2xl font-black tracking-tight text-[#000928]"
            >
                Mentors for this track
            </h2>

            <div
                v-if="mentors.length"
                class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
            >
                <LeaderCard
                    v-for="mentor in mentors"
                    :key="mentor.id"
                    :leader="mentor"
                />
            </div>

            <EmptyState
                v-else
                class="mt-6"
                icon="users"
                title="No mentor assigned yet"
                description="We're recruiting a mentor for this track. If this is your area, we'd like to hear from you."
            >
                <Link href="/community/get-involved" class="lms-btn-accent">
                    Volunteer as a mentor
                </Link>
            </EmptyState>
        </section>

        <!-- Upcoming -->
        <section
            class="border-y border-gray-100 bg-gray-50/70 py-14"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2
                        class="text-2xl font-black tracking-tight text-[#000928]"
                    >
                        Coming up in {{ track.name }}
                    </h2>
                    <Link
                        :href="`/community/activities?track=${track.slug}`"
                        class="text-sm font-bold text-[#381998] hover:underline"
                    >
                        All {{ track.name }} activities →
                    </Link>
                </div>

                <div
                    v-if="upcoming.length"
                    class="mt-7 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <ActivityCard
                        v-for="activity in upcoming"
                        :key="activity.id"
                        :activity="activity"
                    />
                </div>

                <EmptyState
                    v-else
                    class="mt-7"
                    title="Nothing scheduled in this track yet"
                    description="Join TAC and pick this track — we'll email you the moment something is announced."
                />
            </div>
        </section>

        <!-- Past -->
        <section
            v-if="past.length"
            class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8"
        >
            <h2
                class="text-2xl font-black tracking-tight text-[#000928]"
            >
                Previously in this track
            </h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="item in past"
                    :key="item.id"
                    :href="`/community/activities/${item.slug}`"
                    class="group rounded-2xl border border-gray-200 p-5 transition-all hover:-translate-y-1 hover:border-[#42b6c5]/40 hover:shadow-lg"
                >
                    <p class="text-[11px] font-bold text-gray-400 uppercase">
                        {{ formatDate(item.starts_at) }}
                    </p>
                    <h3
                        class="mt-2 line-clamp-2 font-bold text-[#000928]"
                    >
                        {{ item.title }}
                    </h3>
                    <p
                        v-if="item.outcome_summary"
                        class="mt-2 line-clamp-3 text-sm text-gray-600"
                    >
                        {{ item.outcome_summary }}
                    </p>
                </Link>
            </div>
        </section>

        <!-- Members (opt-in only) -->
        <section
            v-if="members.length"
            class="border-t border-gray-100 py-14"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2
                            class="text-2xl font-black tracking-tight text-[#000928]"
                        >
                            Members in this track
                        </h2>
                        <p
                            class="mt-1.5 text-sm text-gray-500"
                        >
                            {{ directoryCount }} members have chosen to be
                            listed. Sign in to see the full directory.
                        </p>
                    </div>
                    <Link
                        v-if="isMember"
                        :href="`/community/me/directory?track=${track.slug}`"
                        class="text-sm font-bold text-[#381998] hover:underline"
                    >
                        Open the directory →
                    </Link>
                </div>

                <ul
                    class="mt-6 flex flex-wrap gap-3"
                    aria-label="Members in this track"
                >
                    <li
                        v-for="member in members"
                        :key="member.id"
                        class="flex items-center gap-2.5 rounded-full border border-gray-200 py-1.5 pr-4 pl-1.5"
                    >
                        <span class="h-8 w-8 overflow-hidden rounded-full">
                            <img
                                v-if="asset(member.avatar_path)"
                                :src="asset(member.avatar_path)!"
                                :alt="''"
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="flex h-full w-full items-center justify-center bg-[#381998] text-[10px] font-bold text-white"
                                aria-hidden="true"
                            >
                                {{ initials(`${member.first_name} ${member.last_name ?? ''}`) }}
                            </span>
                        </span>
                        <span class="text-sm">
                            <span
                                class="font-semibold text-[#000928]"
                            >
                                {{ member.first_name }}
                                {{ member.last_name }}
                            </span>
                            <span
                                v-if="member.school"
                                class="ml-1.5 text-xs text-gray-400"
                                >{{ member.school }}</span
                            >
                        </span>
                    </li>
                </ul>
            </div>
        </section>
    </CommunityShell>
</template>
