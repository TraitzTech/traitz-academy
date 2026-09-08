<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ActivityCard from '@/components/community/ActivityCard.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import { useCommunity } from '@/composables/useCommunity';
import type { TacActivity, TacTrack } from '@/types/community';

interface Props {
    member: {
        id: number;
        first_name: string;
        full_name: string;
        email: string;
        joined_at: string | null;
    };
    tracks: TacTrack[];
    upcoming: TacActivity[];
    whatsappLink: string | null;
}

defineProps<Props>();

const { initials } = useCommunity();
</script>

<template>
    <CommunityShell active="" bare>
        <Head title="Welcome to the Traitz Academy Community" />

        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] px-4 py-20 sm:px-6 lg:px-8"
        >
            <div class="mx-auto max-w-2xl text-center">
                <span
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#42b6c5] text-white shadow-xl shadow-[#42b6c5]/30"
                    aria-hidden="true"
                >
                    <svg
                        class="h-8 w-8"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </span>

                <h1
                    class="mt-7 text-3xl font-black tracking-tight text-white sm:text-4xl"
                >
                    Welcome to TAC, {{ member.first_name }}
                </h1>
                <p class="mt-4 text-lg text-white/70">
                    Your membership is confirmed. We've sent a welcome email to
                    <strong class="text-white">{{ member.email }}</strong> with
                    everything you need.
                </p>

                <div
                    v-if="tracks.length"
                    class="mt-8 flex flex-wrap justify-center gap-2"
                >
                    <span
                        v-for="track in tracks"
                        :key="track.id"
                        class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-sm font-semibold text-white backdrop-blur"
                    >
                        <span
                            class="flex h-5 w-5 items-center justify-center rounded text-[9px] font-black"
                            :style="{
                                backgroundColor: track.accent_color ?? '#42b6c5',
                            }"
                            aria-hidden="true"
                        >
                            {{ initials(track.name) }}
                        </span>
                        {{ track.name }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Next steps -->
        <section class="mx-auto max-w-5xl px-4 py-14 sm:px-6 lg:px-8">
            <h2
                class="text-center text-xl font-black tracking-tight text-[#000928]"
            >
                What to do next
            </h2>

            <div class="mt-8 grid gap-5 sm:grid-cols-3">
                <Link
                    href="/community/activities"
                    class="group rounded-2xl border border-gray-200 bg-white p-6 transition-all hover:-translate-y-1 hover:border-[#42b6c5]/40 hover:shadow-lg"
                >
                    <span class="text-2xl" aria-hidden="true">📅</span>
                    <h3 class="mt-3 font-bold text-[#000928]">
                        Browse activities
                    </h3>
                    <p
                        class="mt-1.5 text-sm text-gray-600"
                    >
                        RSVP to workshops, trainings and competitions in your
                        tracks.
                    </p>
                </Link>

                <Link
                    href="/community/team"
                    class="group rounded-2xl border border-gray-200 bg-white p-6 transition-all hover:-translate-y-1 hover:border-[#42b6c5]/40 hover:shadow-lg"
                >
                    <span class="text-2xl" aria-hidden="true">👥</span>
                    <h3 class="mt-3 font-bold text-[#000928]">
                        Meet your mentors
                    </h3>
                    <p
                        class="mt-1.5 text-sm text-gray-600"
                    >
                        See who leads each track and how to reach them.
                    </p>
                </Link>

                <a
                    v-if="whatsappLink"
                    :href="whatsappLink"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group rounded-2xl border border-gray-200 bg-white p-6 transition-all hover:-translate-y-1 hover:border-[#42b6c5]/40 hover:shadow-lg"
                >
                    <span class="text-2xl" aria-hidden="true">💬</span>
                    <h3 class="mt-3 font-bold text-[#000928]">
                        Say hello
                    </h3>
                    <p
                        class="mt-1.5 text-sm text-gray-600"
                    >
                        Join the conversation with other members on WhatsApp.
                    </p>
                </a>
                <Link
                    v-else
                    href="/community/get-involved"
                    class="group rounded-2xl border border-gray-200 bg-white p-6 transition-all hover:-translate-y-1 hover:border-[#42b6c5]/40 hover:shadow-lg"
                >
                    <span class="text-2xl" aria-hidden="true">🚀</span>
                    <h3 class="mt-3 font-bold text-[#000928]">
                        Get involved
                    </h3>
                    <p
                        class="mt-1.5 text-sm text-gray-600"
                    >
                        Mentor, host TAC at your school, or bring a partner in.
                    </p>
                </Link>
            </div>

            <div v-if="upcoming.length" class="mt-14">
                <h2
                    class="text-lg font-black tracking-tight text-[#000928]"
                >
                    Happening soon
                </h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-3">
                    <ActivityCard
                        v-for="activity in upcoming"
                        :key="activity.id"
                        :activity="activity"
                        compact
                    />
                </div>
            </div>

            <div class="mt-12 text-center">
                <Link
                    href="/community"
                    class="text-sm font-bold text-[#381998] hover:underline"
                >
                    ← Back to the community home
                </Link>
            </div>
        </section>
    </CommunityShell>
</template>
