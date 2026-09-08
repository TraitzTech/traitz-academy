<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useCommunity } from '@/composables/useCommunity';
import type { TacTrack } from '@/types/community';

interface Props {
    track: TacTrack;
}

const props = defineProps<Props>();
const { asset, initials } = useCommunity();

const accent = computed(() => props.track.accent_color ?? '#42b6c5');
const mentors = computed(() => props.track.mentors ?? []);
</script>

<template>
    <Link
        :href="`/community/tracks/${track.slug}`"
        class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-[#000928]/5 focus-visible:ring-2 focus-visible:ring-[#42b6c5] focus-visible:outline-none"
    >
        <!-- Accent wash that identifies the track at a glance -->
        <span
            class="absolute inset-x-0 top-0 h-1 transition-all group-hover:h-1.5"
            :style="{ backgroundColor: accent }"
            aria-hidden="true"
        />

        <div class="flex items-start justify-between gap-4">
            <span
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-lg font-black"
                :style="{ backgroundColor: `${accent}1f`, color: accent }"
                aria-hidden="true"
            >
                {{ initials(track.name) }}
            </span>

            <span
                v-if="track.upcoming_count"
                class="rounded-full bg-[#42b6c5]/12 px-2.5 py-1 text-[11px] font-bold text-[#26808c]"
            >
                {{ track.upcoming_count }} upcoming
            </span>
        </div>

        <h3
            class="mt-4 text-lg font-bold text-[#000928] transition-colors group-hover:text-[#381998]"
        >
            {{ track.name }}
        </h3>

        <p
            v-if="track.tagline"
            class="mt-1.5 text-sm text-gray-600"
        >
            {{ track.tagline }}
        </p>

        <div class="mt-auto pt-5">
            <!-- Mentors: the reason to pick a track is who runs it -->
            <div v-if="mentors.length" class="flex items-center gap-2">
                <div class="flex -space-x-2">
                    <span
                        v-for="mentor in mentors.slice(0, 4)"
                        :key="mentor.id"
                        class="relative inline-block h-7 w-7 overflow-hidden rounded-full ring-2 ring-white"
                        :title="mentor.name"
                    >
                        <img
                            v-if="asset(mentor.photo_path)"
                            :src="asset(mentor.photo_path)!"
                            :alt="mentor.name"
                            loading="lazy"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-else
                            class="flex h-full w-full items-center justify-center bg-[#381998] text-[10px] font-bold text-white"
                        >
                            {{ initials(mentor.name) }}
                        </span>
                    </span>
                </div>
                <span class="text-xs font-medium text-gray-500">
                    {{ mentors.length }}
                    {{ mentors.length === 1 ? 'mentor' : 'mentors' }}
                </span>
            </div>
            <p v-else class="text-xs font-medium text-gray-400">
                Mentor to be announced
            </p>

            <div
                class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-xs"
            >
                <span class="font-medium text-gray-500">
                    {{ track.members_count ?? 0 }}
                    {{ track.members_count === 1 ? 'member' : 'members' }}
                </span>
                <span
                    class="font-bold text-[#381998] transition-transform group-hover:translate-x-0.5"
                >
                    Explore →
                </span>
            </div>
        </div>
    </Link>
</template>
