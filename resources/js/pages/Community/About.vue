<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import SeoHead from '@/components/SeoHead.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import LeaderCard from '@/components/community/LeaderCard.vue';
import StatStrip from '@/components/community/StatStrip.vue';
import { useCommunity } from '@/composables/useCommunity';
import type { TacLeader, TacTrack } from '@/types/community';

interface Props {
    stats: {
        members: number;
        tracks: number;
        leaders: number;
        activities: number;
        upcoming: number;
        schools: number;
    };
    tracks: TacTrack[];
    leadership: TacLeader[];
    isMember: boolean;
}

defineProps<Props>();

const { initials } = useCommunity();

const goals = [
    {
        title: 'Stay connected',
        body: 'Keep Traitz Academy close to students and tech enthusiasts on an ongoing basis — not only while a cohort is running.',
    },
    {
        title: 'Give real leadership',
        body: 'Interns and past interns take on genuine roles with genuine responsibility, and a path to grow within the community.',
    },
    {
        title: 'Stay active year-round',
        body: 'Keep the academy alive between internship cohorts, with a calendar that runs continuously rather than in bursts.',
    },
    {
        title: 'Sustain the work',
        body: 'Generate steady, recurring support for Traitz Academy so the programs it runs can keep getting better.',
    },
];
</script>

<template>
    <CommunityShell active="about">
        <SeoHead
            title="About TAC"
            description="What the Traitz Academy Community is, why it exists, and how it relates to the Traitz Academy internship programs."
        />

        <!-- Hero -->
        <section
            class="relative overflow-hidden bg-gradient-to-r from-[#000928] to-[#381998] px-4 py-16 sm:px-6 lg:px-8 lg:py-24"
        >
            <div class="relative mx-auto max-w-4xl">
                <span
                    class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-bold tracking-wide text-white uppercase backdrop-blur"
                >
                    What is TAC?
                </span>
                <h1
                    class="mt-6 text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl"
                >
                    A standing community, not a campaign
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-white/70">
                    The Traitz Academy Community is a persistent home base for
                    anyone who has passed through, is currently in, or wants to
                    be part of the Traitz Academy world. It launched in
                    September 2026 and it is built to run indefinitely — with a
                    continuous stream of members, activities and leadership
                    cycles.
                </p>
            </div>
        </section>

        <!-- Stats -->
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <StatStrip
                tone="light"
                :stats="[
                    { label: 'Members', value: stats.members },
                    { label: 'Tracks', value: stats.tracks },
                    { label: 'Leaders', value: stats.leaders },
                    { label: 'Activities', value: stats.activities },
                    { label: 'Schools', value: stats.schools },
                ]"
            />
        </section>

        <!-- Why it exists -->
        <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
            <h2
                class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
            >
                Why TAC exists
            </h2>
            <div class="mt-7 grid gap-5 sm:grid-cols-2">
                <div
                    v-for="(goal, index) in goals"
                    :key="goal.title"
                    class="rounded-2xl border border-gray-200 bg-white p-6"
                >
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#42b6c5]/12 text-sm font-black text-[#26808c]"
                        aria-hidden="true"
                    >
                        {{ index + 1 }}
                    </span>
                    <h3
                        class="mt-4 font-bold text-[#000928]"
                    >
                        {{ goal.title }}
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-gray-600"
                    >
                        {{ goal.body }}
                    </p>
                </div>
            </div>
        </section>

        <!-- In action -->
        <section class="border-t border-gray-100 py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl">
                    What that looks like in practice
                </h2>
                <div class="mt-7 grid gap-4 sm:grid-cols-3">
                    <div class="overflow-hidden rounded-2xl">
                        <img
                            src="/images/academy-community/workshops/iot-hands-on-workshop.jpg"
                            alt="Members getting hands-on with IoT hardware during a TAC workshop"
                            class="aspect-[4/3] w-full object-cover"
                            loading="lazy"
                        />
                    </div>
                    <div class="overflow-hidden rounded-2xl">
                        <img
                            src="/images/academy-community/sports/team-circle-session.jpg"
                            alt="TAC members building team spirit off-screen, away from the classroom"
                            class="aspect-[4/3] w-full object-cover"
                            loading="lazy"
                        />
                    </div>
                    <div class="overflow-hidden rounded-2xl">
                        <img
                            src="/images/academy-community/classroom/students-group-session.jpg"
                            alt="Members working through a problem together in a TAC session"
                            class="aspect-[4/3] w-full object-cover"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- TAC vs internships -->
        <section
            class="border-y border-gray-100 bg-gray-50/70 py-14"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2
                    class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
                >
                    TAC and the internship programs
                </h2>
                <p class="mt-3 max-w-3xl text-gray-600">
                    They are related, but they are not the same thing. TAC is
                    where you stay; the internship is something you do.
                </p>

                <div class="mt-8 grid gap-6 md:grid-cols-2">
                    <div
                        class="rounded-2xl border border-[#42b6c5]/30 bg-[#42b6c5]/8 p-7"
                    >
                        <h3
                            class="text-lg font-bold text-[#000928]"
                        >
                            The Community (TAC)
                        </h3>
                        <ul
                            class="mt-4 space-y-2.5 text-sm text-gray-700"
                        >
                            <li v-for="point in [
                                'Free to join, open to anyone interested in tech',
                                'No start or end date — it runs continuously',
                                'Workshops, trainings, handouts and competitions',
                                'Mentorship and peer connection across eight tracks',
                                'A path into leadership roles within the community',
                            ]" :key="point" class="flex gap-2.5">
                                <span class="text-[#42b6c5]" aria-hidden="true">▸</span>
                                <span>{{ point }}</span>
                            </li>
                        </ul>
                        <Link
                            href="/community/join"
                            class="mt-6 inline-block text-sm font-bold text-[#381998] hover:underline"
                        >
                            Join TAC →
                        </Link>
                    </div>

                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-7"
                    >
                        <h3
                            class="text-lg font-bold text-[#000928]"
                        >
                            Traitz Academy internships
                        </h3>
                        <ul
                            class="mt-4 space-y-2.5 text-sm text-gray-700"
                        >
                            <li v-for="point in [
                                'Structured, time-boxed cohorts with an application process',
                                'Supervised, hands-on professional experience',
                                'Formal assessment, logbooks and certification',
                                'A defined start and end date per cohort',
                                'Every intern is automatically part of TAC too',
                            ]" :key="point" class="flex gap-2.5">
                                <span class="text-[#381998]" aria-hidden="true">▸</span>
                                <span>{{ point }}</span>
                            </li>
                        </ul>
                        <Link
                            href="/programs"
                            class="mt-6 inline-block text-sm font-bold text-[#381998] hover:underline"
                        >
                            Browse programs →
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- How membership works -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2
                class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
            >
                How membership works
            </h2>

            <ol class="mt-8 grid gap-6 md:grid-cols-3">
                <li
                    v-for="(step, index) in [
                        {
                            title: 'You come in',
                            body: 'Join directly from the site, or arrive automatically when you register for any Traitz Academy program, event or course.',
                        },
                        {
                            title: 'You pick your tracks',
                            body: 'Choose the technology areas you care about. We use them to point the right activities, mentors and opportunities your way.',
                        },
                        {
                            title: 'You grow into it',
                            body: 'Attend, contribute, compete. Members become contributors, contributors become mentors, mentors become leads.',
                        },
                    ]"
                    :key="step.title"
                    class="relative rounded-2xl border border-gray-200 bg-white p-7"
                >
                    <span
                        class="absolute -top-3.5 left-7 rounded-lg bg-[#000928] px-3 py-1 text-xs font-black text-white"
                        aria-hidden="true"
                    >
                        Step {{ index + 1 }}
                    </span>
                    <h3
                        class="mt-2 font-bold text-[#000928]"
                    >
                        {{ step.title }}
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-gray-600"
                    >
                        {{ step.body }}
                    </p>
                </li>
            </ol>

            <p
                class="mt-6 rounded-2xl border border-gray-200 bg-gray-50/70 p-5 text-sm text-gray-600"
            >
                <strong class="text-[#000928]"
                    >Automatic inclusion:</strong
                >
                anyone who registers for a Traitz Academy program, event, AI
                Forge, online course or internship becomes a TAC member
                automatically. You'll get a welcome email explaining why, and
                you can update your preferences or unsubscribe at any time from
                your member area.
            </p>
        </section>

        <!-- Tracks -->
        <section
            class="border-t border-gray-100 py-14"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2
                    class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
                >
                    The eight tracks
                </h2>
                <div class="mt-6 flex flex-wrap gap-3">
                    <Link
                        v-for="track in tracks"
                        :key="track.id"
                        :href="`/community/tracks/${track.slug}`"
                        class="group flex items-center gap-2.5 rounded-full border border-gray-200 py-2 pr-5 pl-2 transition-all hover:-translate-y-0.5 hover:border-[#42b6c5]/50 hover:shadow-md"
                    >
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-black"
                            :style="{
                                backgroundColor: `${track.accent_color ?? '#42b6c5'}22`,
                                color: track.accent_color ?? '#42b6c5',
                            }"
                            aria-hidden="true"
                        >
                            {{ initials(track.name) }}
                        </span>
                        <span
                            class="text-sm font-semibold text-[#000928]"
                        >
                            {{ track.name }}
                        </span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Leadership -->
        <section
            v-if="leadership.length"
            class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8"
        >
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2
                    class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
                >
                    Who runs TAC
                </h2>
                <Link
                    href="/community/team"
                    class="text-sm font-bold text-[#381998] hover:underline"
                >
                    Full leadership roster →
                </Link>
            </div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <LeaderCard
                    v-for="leader in leadership"
                    :key="leader.id"
                    :leader="leader"
                />
            </div>
        </section>

        <!-- CTA -->
        <section v-if="!isMember" class="px-4 pb-20 sm:px-6 lg:px-8">
            <div
                class="mx-auto max-w-7xl rounded-3xl bg-gradient-to-r from-[#000928] to-[#381998] px-6 py-14 text-center sm:px-12"
            >
                <h2 class="text-3xl font-black text-white sm:text-4xl">
                    Ready to join?
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-white/70">
                    Free, permanent, and open to students, past interns and
                    anyone curious about technology.
                </p>
                <Link
                    href="/community/join"
                    class="mt-8 inline-block rounded-xl bg-[#42b6c5] px-8 py-3.5 text-base font-bold text-white shadow-xl shadow-[#42b6c5]/25 transition-all hover:-translate-y-0.5 hover:bg-[#35919e]"
                >
                    Join the community
                </Link>
            </div>
        </section>
    </CommunityShell>
</template>
