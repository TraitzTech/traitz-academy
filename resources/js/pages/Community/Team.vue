<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import SeoHead from '@/components/SeoHead.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import LeaderCard from '@/components/community/LeaderCard.vue';
import LeaderRow from '@/components/community/LeaderRow.vue';
import StatStrip from '@/components/community/StatStrip.vue';
import type { TacLeader } from '@/types/community';

interface RoleGroup {
    role: string;
    label: string;
    plural: string;
    leaders: TacLeader[];
}

interface Props {
    groups: RoleGroup[];
    mentorsByTrack: {
        track: string;
        slug: string | null;
        accent_color: string | null;
        leaders: TacLeader[];
    }[];
    schoolLeads: { school: string; leaders: TacLeader[] }[];
    alumniLeaders: TacLeader[];
    roleLabels: Record<string, string>;
    counts: { active: number; mentors: number; schools: number };
}

const props = defineProps<Props>();

/** Mentors and school leads get their own grouped sections below. */
const coreGroups = () =>
    props.groups.filter(
        (group) => !['track_mentor', 'school_lead'].includes(group.role),
    );
</script>

<template>
    <CommunityShell active="team">
        <SeoHead
            title="Team & Leadership"
            description="The people who run TAC: Lead, Co-Lead, Secretary, Technical Leads, track mentors, school leads and partnership leads."
        />

        <section
            class="border-b border-gray-100 bg-white"
        >
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <h1
                    class="text-3xl font-black tracking-tight text-[#000928] sm:text-4xl"
                >
                    Team & Leadership
                </h1>
                <p
                    class="mt-4 max-w-2xl text-base leading-relaxed text-gray-600"
                >
                    TAC is run by people, not a committee on paper. Leadership
                    rotates as members grow into it — today's member is
                    tomorrow's mentor, and tomorrow's lead.
                </p>

                <div class="mt-9 max-w-2xl">
                    <StatStrip
                        tone="light"
                        :stats="[
                            { label: 'Active leaders', value: counts.active },
                            { label: 'Track mentors', value: counts.mentors },
                            { label: 'Campuses', value: counts.schools },
                        ]"
                    />
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <EmptyState
                v-if="!groups.length"
                icon="users"
                title="The leadership roster is being set up"
                description="TAC leadership will be published here shortly."
            />

            <!-- Core leadership -->
            <section
                v-for="group in coreGroups()"
                :key="group.role"
                class="mb-14"
            >
                <h2
                    class="text-xl font-black tracking-tight text-[#000928]"
                >
                    {{
                        group.leaders.length > 1 ? group.plural : group.label
                    }}
                </h2>
                <div
                    class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <LeaderCard
                        v-for="leader in group.leaders"
                        :key="leader.id"
                        :leader="leader"
                    />
                </div>
            </section>

            <!-- Mentors, one compact row per track -->
            <section v-if="mentorsByTrack.length" class="mb-14">
                <h2
                    class="text-xl font-black tracking-tight text-[#000928]"
                >
                    Track Mentors
                </h2>
                <p class="mt-1.5 text-sm text-gray-500">
                    Each track is led by practitioners who work in it.
                </p>

                <ul class="mt-6 space-y-2.5">
                    <li
                        v-for="group in mentorsByTrack"
                        :key="group.track"
                        class="flex flex-col gap-2.5 sm:flex-row sm:items-center"
                    >
                        <Link
                            :href="group.slug ? `/community/tracks/${group.slug}` : '#'"
                            class="flex w-full items-center gap-2 sm:w-52 sm:shrink-0"
                        >
                            <span
                                class="h-2.5 w-2.5 shrink-0 rounded-full"
                                :style="{ backgroundColor: group.accent_color ?? '#42b6c5' }"
                                aria-hidden="true"
                            />
                            <span
                                class="truncate text-sm font-bold text-[#000928] hover:text-[#381998]"
                            >
                                {{ group.track }}
                            </span>
                        </Link>

                        <div class="min-w-0 flex-1 space-y-2">
                            <LeaderRow
                                v-for="leader in group.leaders"
                                :key="leader.id"
                                :leader="leader"
                            />
                            <p
                                v-if="!group.leaders.length"
                                class="rounded-xl border border-dashed border-gray-300 px-3.5 py-2.5 text-sm text-gray-400"
                            >
                                No mentor yet —
                                <Link
                                    href="/community/get-involved"
                                    class="font-semibold text-[#381998] hover:underline"
                                    >volunteer for this track</Link
                                >
                            </p>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- School leads, one compact row per campus -->
            <section v-if="schoolLeads.length" class="mb-14">
                <h2
                    class="text-xl font-black tracking-tight text-[#000928]"
                >
                    School Leads
                </h2>
                <p class="mt-1.5 text-sm text-gray-500">
                    Members who organise TAC's presence on their own campus.
                </p>

                <ul class="mt-6 space-y-2.5">
                    <li
                        v-for="group in schoolLeads"
                        :key="group.school"
                        class="flex flex-col gap-2.5 sm:flex-row sm:items-center"
                    >
                        <span
                            class="w-full truncate text-sm font-bold text-[#000928] sm:w-52 sm:shrink-0"
                        >
                            {{ group.school }}
                        </span>

                        <div class="min-w-0 flex-1 space-y-2">
                            <LeaderRow
                                v-for="leader in group.leaders"
                                :key="leader.id"
                                :leader="leader"
                            />
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Alumni leaders — the community's own history -->
            <section
                v-if="alumniLeaders.length"
                class="rounded-3xl border border-gray-200 bg-gray-50/70 p-6 sm:p-10"
            >
                <h2
                    class="text-xl font-black tracking-tight text-[#000928]"
                >
                    Past leaders
                </h2>
                <p class="mt-1.5 text-sm text-gray-500">
                    People who have held a TAC leadership post. Leadership
                    rotates — this is the record of who carried it.
                </p>

                <div
                    class="mt-7 grid gap-4 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6"
                >
                    <LeaderCard
                        v-for="leader in alumniLeaders"
                        :key="leader.id"
                        :leader="leader"
                        retired
                        :show-bio="false"
                    />
                </div>
            </section>

            <!-- CTA -->
            <div
                class="mt-14 rounded-3xl bg-gradient-to-r from-[#000928] to-[#381998] px-6 py-12 text-center sm:px-12"
            >
                <h2 class="text-2xl font-black text-white sm:text-3xl">
                    Want to lead something?
                </h2>
                <p class="mx-auto mt-3 max-w-xl text-white/70">
                    Mentors, school leads and partnership leads all started as
                    members. If you want to run a track, host TAC on your
                    campus, or bring in a partner — tell us.
                </p>
                <Link
                    href="/community/get-involved"
                    class="mt-7 inline-block rounded-xl bg-[#42b6c5] px-8 py-3.5 font-bold text-white shadow-xl shadow-[#42b6c5]/25 transition-all hover:-translate-y-0.5 hover:bg-[#35919e]"
                >
                    Get involved
                </Link>
            </div>
        </div>
    </CommunityShell>
</template>
