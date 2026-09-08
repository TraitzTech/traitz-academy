<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Award, Calendar, GraduationCap, Handshake, Rocket, Users } from 'lucide-vue-next';
import { computed } from 'vue';

import ActivityCard from '@/components/community/ActivityCard.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import LeaderCard from '@/components/community/LeaderCard.vue';
import StatStrip from '@/components/community/StatStrip.vue';
import TrackCard from '@/components/community/TrackCard.vue';
import { useCommunity } from '@/composables/useCommunity';
import type {
    TacActivity,
    TacLeader,
    TacPartner,
    TacTrack,
} from '@/types/community';

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
    upcoming: TacActivity[];
    recentHighlights: TacActivity[];
    featuredLeaders: TacLeader[];
    partners: TacPartner[];
    isMember: boolean;
}

const props = defineProps<Props>();

const { asset, formatDate } = useCommunity();

const heroStats = computed(() => [
    { label: 'Members', value: props.stats.members, suffix: '+' },
    { label: 'Tracks', value: props.stats.tracks },
    { label: 'Leaders & mentors', value: props.stats.leaders },
    { label: 'Activities run', value: props.stats.activities },
    { label: 'Schools reached', value: props.stats.schools },
]);

const pillars = [
    {
        title: 'Stay connected, year-round',
        body: 'TAC keeps students, interns and tech enthusiasts in the same room between and beyond internship cohorts — not just while a program is running.',
    },
    {
        title: 'Real leadership, real growth',
        body: 'Interns and past interns take on genuine roles: track mentor, school lead, partnership lead. Today’s member is tomorrow’s lead.',
    },
    {
        title: 'Always something happening',
        body: 'A continuous calendar of workshops, trainings, bootcamps, handouts and competitions across all eight tracks.',
    },
];

const benefits = [
    {
        icon: GraduationCap,
        title: 'Mentorship that continues after class ends',
        body: 'Track mentors and school leads are on hand for guidance long after a cohort finishes — not a one-off Q&A.',
    },
    {
        icon: Calendar,
        title: 'A steady stream of hands-on activities',
        body: 'Workshops, bootcamps, handouts and competitions across all eight tracks, with RSVP and reminders in your member area.',
    },
    {
        icon: Rocket,
        title: 'A path into leadership',
        body: 'Members grow into Track Mentor, School Lead or Partnership Lead roles — with real responsibility and a dashboard to run it.',
    },
    {
        icon: Users,
        title: 'A network across schools and cohorts',
        body: 'Meet students and alumni from other schools and past intakes, not just the people in your own class.',
    },
    {
        icon: Handshake,
        title: 'Direct line to partners and opportunities',
        body: 'Partnership leads surface internships, gigs and collaborations from organisations working with the academy.',
    },
    {
        icon: Award,
        title: 'Recognition for what you actually do',
        body: 'Leadership responsibilities and contributions are tracked, reviewed and visible — not informal favours.',
    },
];
</script>

<template>
    <CommunityShell active="index">
        <Head title="Traitz Academy Community (TAC)">
            <meta
                name="description"
                content="TAC is the year-round home base for Traitz Academy students, interns, mentors and tech enthusiasts across eight technology tracks."
            />
        </Head>

        <!-- ================================================== Hero -->
        <section class="relative overflow-hidden py-16 text-white lg:py-20">
            <div class="absolute inset-0">
                <img
                    src="/images/academy-community/sports/warmup-stretching.jpg"
                    alt="TAC members warming up together before a community sports session"
                    class="h-full w-full object-cover"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-r from-[#000928]/95 to-[#381998]/85"
                ></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <h1 class="text-4xl font-bold md:text-5xl">
                        Traitz Academy Community
                    </h1>

                    <p class="mt-4 max-w-2xl text-xl text-gray-300">
                        A standing home for anyone who has passed through, is
                        currently in, or wants to be part of the Traitz Academy
                        world. Eight tracks, real mentors, and a calendar that
                        never stops.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            v-if="!isMember"
                            href="/community/join"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#42b6c5] px-7 py-3.5 text-base font-bold text-white transition-colors hover:bg-[#35919e]"
                        >
                            Join the community
                        </Link>
                        <Link
                            v-else
                            href="/community/me"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#42b6c5] px-7 py-3.5 text-base font-bold text-white transition-colors hover:bg-[#35919e]"
                        >
                            Go to my member area
                        </Link>

                        <Link
                            href="/community/activities"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-7 py-3.5 text-base font-bold text-white transition-colors hover:bg-white/10"
                        >
                            See what's on
                            <span
                                v-if="stats.upcoming"
                                class="rounded-full bg-[#42b6c5] px-2 py-0.5 text-xs"
                            >
                                {{ stats.upcoming }}
                            </span>
                        </Link>
                    </div>
                </div>

                <div class="mt-12">
                    <StatStrip :stats="heroStats" tone="dark" />
                </div>
            </div>
        </section>

        <!-- ================================================== What TAC is -->
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <div class="mx-auto mb-10 max-w-3xl text-center">
                <h2 class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl">
                    What TAC actually is
                </h2>
                <p class="mt-3 text-base leading-relaxed text-gray-600">
                    The Traitz Academy Community is the standing, year-round
                    network behind every program, internship and event we
                    run. The moment you register for anything at Traitz
                    Academy, you're already a member — no separate
                    application, no expiry date when a cohort ends.
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-3">
                <div
                    v-for="pillar in pillars"
                    :key="pillar.title"
                    class="rounded-2xl border border-gray-200 bg-white p-7"
                >
                    <h3
                        class="text-lg font-bold text-[#000928]"
                    >
                        {{ pillar.title }}
                    </h3>
                    <p
                        class="mt-3 text-sm leading-relaxed text-gray-600"
                    >
                        {{ pillar.body }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ================================================== Benefits -->
        <section class="border-t border-gray-100 py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                    <div class="overflow-hidden rounded-2xl">
                        <img
                            src="/images/academy-community/classroom/mentor-teaching-session.jpg"
                            alt="A TAC mentor guiding students through a session at Traitz Academy"
                            class="h-full max-h-[420px] w-full object-cover"
                            loading="lazy"
                        />
                    </div>

                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl">
                            What you actually get as a member
                        </h2>
                        <p class="mt-3 text-sm text-gray-600">
                            Membership isn't a mailing list. Here's what it
                            translates to day to day.
                        </p>

                        <div class="mt-8 grid gap-6 sm:grid-cols-2">
                            <div
                                v-for="benefit in benefits"
                                :key="benefit.title"
                                class="flex gap-3"
                            >
                                <component
                                    :is="benefit.icon"
                                    class="mt-0.5 h-5 w-5 shrink-0 text-[#42b6c5]"
                                />
                                <div>
                                    <h3 class="text-sm font-bold text-[#000928]">
                                        {{ benefit.title }}
                                    </h3>
                                    <p class="mt-1 text-sm leading-relaxed text-gray-600">
                                        {{ benefit.body }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================== Upcoming -->
        <section
            class="border-y border-gray-100 bg-gray-50/70 py-16 lg:py-20"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="flex flex-wrap items-end justify-between gap-4 pb-8"
                >
                    <div>
                        <h2
                            class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
                        >
                            What's coming up
                        </h2>
                        <p
                            class="mt-2 text-sm text-gray-600"
                        >
                            Workshops, trainings, bootcamps and competitions —
                            open to every member.
                        </p>
                    </div>
                    <Link
                        href="/community/activities"
                        class="text-sm font-bold text-[#381998] hover:underline"
                    >
                        Full calendar →
                    </Link>
                </div>

                <div
                    v-if="upcoming.length"
                    class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <ActivityCard
                        v-for="activity in upcoming"
                        :key="activity.id"
                        :activity="activity"
                    />
                </div>

                <EmptyState
                    v-else
                    title="Nothing scheduled just yet"
                    description="The next round of activities is being planned. Join TAC and we'll email you as soon as something lands."
                >
                    <Link
                        href="/community/join"
                        class="lms-btn-accent"
                        v-if="!isMember"
                    >
                        Join the community
                    </Link>
                </EmptyState>
            </div>
        </section>

        <!-- ================================================== Tracks -->
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <div class="pb-8 text-center">
                <h2
                    class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
                >
                    Eight tracks. Pick your lane.
                </h2>
                <p
                    class="mx-auto mt-3 max-w-2xl text-sm text-gray-600"
                >
                    Every track has its own mentors, its own activities, and its
                    own members. You can belong to more than one.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <TrackCard
                    v-for="track in tracks"
                    :key="track.id"
                    :track="track"
                />
            </div>
        </section>

        <!-- ================================================== Highlights -->
        <section
            v-if="recentHighlights.length"
            class="border-y border-gray-100 bg-gray-50/70 py-16 lg:py-20"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4 pb-8">
                    <div>
                        <h2
                            class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
                        >
                            Recently in the community
                        </h2>
                        <p class="mt-2 text-sm text-gray-600">
                            What we have already run, and how it went.
                        </p>
                    </div>
                    <Link
                        href="/community/activities?window=past"
                        class="text-sm font-bold text-[#381998] hover:underline"
                    >
                        Browse the archive →
                    </Link>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <Link
                        v-for="item in recentHighlights"
                        :key="item.id"
                        :href="`/community/activities/${item.slug}`"
                        class="group overflow-hidden rounded-2xl border border-gray-200 bg-white transition-all hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div
                            class="aspect-[16/10] overflow-hidden bg-gradient-to-br from-[#000928] to-[#381998]"
                        >
                            <img
                                v-if="asset(item.media?.[0]?.path ?? item.cover_image)"
                                :src="asset(item.media?.[0]?.path ?? item.cover_image)!"
                                :alt="item.title"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                        </div>
                        <div class="p-5">
                            <p
                                class="text-[11px] font-bold tracking-wide text-[#42b6c5] uppercase"
                            >
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
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ================================================== Leaders -->
        <section
            v-if="featuredLeaders.length"
            class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20"
        >
            <div class="flex flex-wrap items-end justify-between gap-4 pb-8">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-[#000928] sm:text-3xl"
                    >
                        The people running TAC
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Real leaders with real responsibility — many of them
                        started here as members.
                    </p>
                </div>
                <Link
                    href="/community/team"
                    class="text-sm font-bold text-[#381998] hover:underline"
                >
                    Meet the full team →
                </Link>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <LeaderCard
                    v-for="leader in featuredLeaders"
                    :key="leader.id"
                    :leader="leader"
                    :show-bio="false"
                />
            </div>
        </section>

        <!-- ================================================== Partners -->
        <section
            v-if="partners.length"
            class="border-t border-gray-100 py-14"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p
                    class="text-center text-[11px] font-bold tracking-widest text-gray-400 uppercase"
                >
                    Working with
                </p>
                <div
                    class="mt-7 flex flex-wrap items-center justify-center gap-x-10 gap-y-6"
                >
                    <component
                        :is="partner.website_url ? 'a' : 'div'"
                        v-for="partner in partners"
                        :key="partner.id"
                        :href="partner.website_url ?? undefined"
                        :target="partner.website_url ? '_blank' : undefined"
                        rel="noopener noreferrer"
                        class="grayscale transition-all hover:grayscale-0"
                    >
                        <img
                            v-if="asset(partner.logo_path)"
                            :src="asset(partner.logo_path)!"
                            :alt="partner.name"
                            loading="lazy"
                            class="h-9 w-auto object-contain"
                        />
                        <span
                            v-else
                            class="text-sm font-bold text-gray-400"
                        >
                            {{ partner.name }}
                        </span>
                    </component>
                </div>
                <div class="mt-8 text-center">
                    <Link
                        href="/community/partners"
                        class="text-sm font-bold text-[#381998] hover:underline"
                    >
                        All partners & sponsors →
                    </Link>
                </div>
            </div>
        </section>

        <!-- ================================================== Join CTA -->
        <section class="px-4 pb-20 sm:px-6 lg:px-8">
            <div
                class="mx-auto max-w-7xl rounded-2xl bg-gradient-to-r from-[#000928] to-[#381998] px-6 py-14 text-center sm:px-12 lg:py-20"
            >
                <div>
                    <h2
                        class="text-3xl font-black tracking-tight text-white sm:text-4xl"
                    >
                        {{
                            isMember
                                ? 'You’re already one of us'
                                : 'Join a community that keeps going'
                        }}
                    </h2>
                    <p
                        class="mx-auto mt-4 max-w-2xl text-base text-white/70 sm:text-lg"
                    >
                        {{
                            isMember
                                ? 'Head to your member area to pick your tracks, RSVP to what’s next and find other members.'
                                : 'Free to join, open to students, past interns and anyone curious about tech. No cohort deadline — TAC runs all year, every year.'
                        }}
                    </p>
                    <div
                        class="mt-9 flex flex-wrap items-center justify-center gap-3"
                    >
                        <Link
                            :href="isMember ? '/community/me' : '/community/join'"
                            class="rounded-xl bg-[#42b6c5] px-8 py-3.5 text-base font-bold text-white shadow-xl shadow-[#42b6c5]/25 transition-all hover:-translate-y-0.5 hover:bg-[#35919e]"
                        >
                            {{ isMember ? 'My member area' : 'Join TAC — it’s free' }}
                        </Link>
                        <Link
                            href="/community/get-involved"
                            class="rounded-xl border border-white/25 bg-white/5 px-8 py-3.5 text-base font-bold text-white backdrop-blur transition-all hover:bg-white/15"
                        >
                            Mentor, host or partner
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </CommunityShell>
</template>
