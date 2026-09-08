<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useCommunity } from '@/composables/useCommunity';
import type { TacActivity } from '@/types/community';

interface Props {
    activity: TacActivity;
    /** Compact cards drop the summary and cover image for dense lists. */
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), { compact: false });

const { activityType, asset, dateRange, relative, money, locationLabel } =
    useCommunity();

const meta = computed(() => activityType(props.activity.type));
const cover = computed(() => asset(props.activity.cover_image));

const isPast = computed(() => {
    const end = props.activity.ends_at ?? props.activity.starts_at;
    return end ? new Date(end).getTime() < Date.now() : false;
});

const seatsLeft = computed(() => {
    if (props.activity.capacity === null) return null;
    return Math.max(0, props.activity.capacity - props.activity.rsvp_count);
});

const accent = computed(
    () => props.activity.track?.accent_color ?? '#42b6c5',
);
</script>

<template>
    <Link
        :href="`/community/activities/${activity.slug}`"
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white transition-all hover:-translate-y-1 hover:border-[#42b6c5]/40 hover:shadow-xl hover:shadow-[#000928]/5 focus-visible:ring-2 focus-visible:ring-[#42b6c5] focus-visible:outline-none"
    >
        <!-- Cover -->
        <div
            v-if="!compact"
            class="relative aspect-[16/9] w-full overflow-hidden bg-gradient-to-br from-[#000928] via-[#1a0a52] to-[#381998]"
        >
            <img
                v-if="cover"
                :src="cover"
                :alt="activity.title"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center"
                aria-hidden="true"
            >
                <span class="text-4xl font-black text-white/15">TAC</span>
            </div>

            <span
                :class="[
                    'absolute top-3 left-3 rounded-full px-2.5 py-1 text-[11px] font-bold tracking-wide uppercase backdrop-blur',
                    'bg-white/90 text-[#000928]',
                ]"
            >
                {{ meta.label }}
            </span>

            <span
                v-if="activity.is_paid"
                class="absolute top-3 right-3 rounded-full bg-[#42b6c5] px-2.5 py-1 text-[11px] font-bold text-white"
            >
                {{ money(activity.price, activity.currency) }}
            </span>
            <span
                v-else-if="!isPast"
                class="absolute top-3 right-3 rounded-full bg-emerald-500 px-2.5 py-1 text-[11px] font-bold text-white"
            >
                Free
            </span>
        </div>

        <div class="flex flex-1 flex-col p-5">
            <div class="flex flex-wrap items-center gap-2">
                <span
                    v-if="compact"
                    :class="['rounded-full px-2.5 py-0.5 text-[11px] font-bold', meta.classes]"
                >
                    {{ meta.label }}
                </span>
                <span
                    v-if="activity.track"
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                    :style="{
                        backgroundColor: `${accent}1f`,
                        color: accent,
                    }"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full"
                        :style="{ backgroundColor: accent }"
                    />
                    {{ activity.track.name }}
                </span>
                <span
                    v-if="activity.status === 'cancelled'"
                    class="rounded-full bg-red-500/12 px-2.5 py-0.5 text-[11px] font-bold text-red-700"
                >
                    Cancelled
                </span>
            </div>

            <h3
                class="mt-3 line-clamp-2 text-lg leading-snug font-bold text-[#000928] transition-colors group-hover:text-[#381998]"
            >
                {{ activity.title }}
            </h3>

            <p
                v-if="!compact && activity.summary"
                class="mt-2 line-clamp-2 text-sm text-gray-600"
            >
                {{ activity.summary }}
            </p>

            <dl class="mt-4 space-y-1.5 text-sm">
                <div class="flex items-start gap-2 text-gray-600">
                    <dt class="sr-only">When</dt>
                    <svg
                        class="mt-0.5 h-4 w-4 shrink-0 text-[#42b6c5]"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    <dd>{{ dateRange(activity) }}</dd>
                </div>
                <div class="flex items-start gap-2 text-gray-600">
                    <dt class="sr-only">Where</dt>
                    <svg
                        class="mt-0.5 h-4 w-4 shrink-0 text-[#42b6c5]"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                    <dd class="line-clamp-1">{{ locationLabel(activity) }}</dd>
                </div>
            </dl>

            <div
                class="mt-auto flex items-center justify-between gap-3 pt-4 text-xs"
            >
                <span
                    v-if="!isPast && activity.starts_at"
                    class="font-semibold text-[#381998]"
                >
                    {{ relative(activity.starts_at) }}
                </span>
                <span v-else class="font-medium text-gray-400">
                    {{ activity.rsvp_count }}
                    {{ activity.rsvp_count === 1 ? 'attendee' : 'attendees' }}
                </span>

                <span
                    v-if="!isPast && seatsLeft !== null && seatsLeft <= 10"
                    :class="[
                        'rounded-full px-2 py-0.5 font-bold',
                        seatsLeft === 0
                            ? 'bg-amber-500/15 text-amber-700'
                            : 'bg-red-500/12 text-red-700',
                    ]"
                >
                    {{ seatsLeft === 0 ? 'Waitlist only' : `${seatsLeft} left` }}
                </span>
            </div>
        </div>
    </Link>
</template>
