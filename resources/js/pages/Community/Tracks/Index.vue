<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import CommunityShell from '@/components/community/CommunityShell.vue';
import TrackCard from '@/components/community/TrackCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import type { TacTrack } from '@/types/community';

interface Props {
    tracks: TacTrack[];
    isMember: boolean;
}

defineProps<Props>();
</script>

<template>
    <CommunityShell active="tracks">
        <SeoHead
            title="Tracks"
            description="The eight technology tracks TAC organises around, each with its own mentors, activities and members."
        />

        <section
            class="border-b border-gray-100 bg-white"
        >
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <h1
                    class="text-3xl font-black tracking-tight text-[#000928] sm:text-4xl"
                >
                    Tracks
                </h1>
                <p
                    class="mt-4 max-w-2xl text-base leading-relaxed text-gray-600"
                >
                    TAC organises around eight technology tracks. Each has its
                    own mentors, its own stream of activities, and its own
                    members. Belong to as many as you like — your interests are
                    allowed to change.
                </p>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <TrackCard
                    v-for="track in tracks"
                    :key="track.id"
                    :track="track"
                />
            </div>

            <div
                v-if="!isMember"
                class="mt-14 rounded-3xl bg-gradient-to-r from-[#000928] to-[#381998] px-6 py-12 text-center sm:px-12"
            >
                <h2 class="text-2xl font-black text-white sm:text-3xl">
                    Found your track?
                </h2>
                <p class="mx-auto mt-3 max-w-xl text-white/70">
                    Join TAC, pick your tracks, and we'll point the right
                    workshops, mentors and competitions your way.
                </p>
                <Link
                    href="/community/join"
                    class="mt-7 inline-block rounded-xl bg-[#42b6c5] px-8 py-3.5 font-bold text-white shadow-xl shadow-[#42b6c5]/25 transition-all hover:-translate-y-0.5 hover:bg-[#35919e]"
                >
                    Join the community
                </Link>
            </div>
        </section>
    </CommunityShell>
</template>
