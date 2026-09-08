<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { ref, watch } from 'vue';

import SeoHead from '@/components/SeoHead.vue';
import ActivityCard from '@/components/community/ActivityCard.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import { useCommunity } from '@/composables/useCommunity';
import type {
    Paginated,
    SelectOption,
    TacActivity,
    TacTrack,
} from '@/types/community';

interface Props {
    activities: Paginated<TacActivity>;
    filters: {
        type?: string;
        track?: string;
        search?: string;
        window: 'upcoming' | 'past';
    };
    tracks: TacTrack[];
    types: SelectOption[];
    counts: { upcoming: number; past: number };
    featured: TacActivity | null;
}

const props = defineProps<Props>();
const { dateRange, locationLabel, asset, activityType } = useCommunity();

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');
const track = ref(props.filters.track ?? '');
const window_ = ref<'upcoming' | 'past'>(props.filters.window);

const go = () =>
    router.get(
        '/community/activities',
        {
            search: search.value || undefined,
            type: type.value || undefined,
            track: track.value || undefined,
            window: window_.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );

// Typing debounces; picking from a dropdown should feel instant.
const goDebounced = debounce(go, 350);

watch(search, goDebounced);
watch([type, track, window_], () => {
    goDebounced.cancel();
    go();
});

const reset = () => {
    search.value = '';
    type.value = '';
    track.value = '';
};

const hasFilters = () => Boolean(search.value || type.value || track.value);
</script>

<template>
    <CommunityShell active="activities">
        <SeoHead
            title="Community Activities"
            description="The ongoing TAC calendar: workshops, trainings, bootcamps, handouts and competitions across all eight tracks."
        />

        <!-- Header -->
        <section class="border-b border-gray-100 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                <h1
                    class="text-3xl font-black tracking-tight text-[#000928] sm:text-4xl"
                >
                    Activities
                </h1>
                <p
                    class="mt-3 max-w-2xl text-base text-gray-600"
                >
                    A continuous calendar — not a one-off season. Workshops,
                    trainings, bootcamps, internships, handouts and
                    competitions, all year round.
                </p>

                <!-- Window toggle -->
                <div
                    class="mt-7 inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1"
                    role="tablist"
                >
                    <button
                        v-for="tab in [
                            { key: 'upcoming', label: 'Upcoming', count: counts.upcoming },
                            { key: 'past', label: 'Archive', count: counts.past },
                        ]"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="window_ === tab.key"
                        :class="[
                            'rounded-lg px-5 py-2 text-sm font-bold transition-colors',
                            window_ === tab.key
                                ? 'bg-[#000928] text-white shadow-sm'
                                : 'text-gray-600 hover:text-[#000928]',
                        ]"
                        @click="window_ = tab.key as 'upcoming' | 'past'"
                    >
                        {{ tab.label }}
                        <span class="ml-1 opacity-60">{{ tab.count }}</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Featured -->
        <section
            v-if="featured && window_ === 'upcoming' && !hasFilters()"
            class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8"
        >
            <Link
                :href="`/community/activities/${featured.slug}`"
                class="group grid overflow-hidden rounded-3xl border border-gray-200 bg-white transition-all hover:shadow-2xl md:grid-cols-2"
            >
                <div
                    class="relative min-h-[240px] bg-gradient-to-r from-[#000928] to-[#381998]"
                >
                    <img
                        v-if="asset(featured.cover_image)"
                        :src="asset(featured.cover_image)!"
                        :alt="featured.title"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <span
                        class="absolute top-4 left-4 rounded-full bg-[#42b6c5] px-3 py-1 text-[11px] font-black tracking-wide text-white uppercase"
                    >
                        Featured
                    </span>
                </div>

                <div class="flex flex-col justify-center p-7 sm:p-10">
                    <span
                        :class="[
                            'w-fit rounded-full px-3 py-1 text-[11px] font-bold',
                            activityType(featured.type).classes,
                        ]"
                    >
                        {{ activityType(featured.type).label }}
                    </span>
                    <h2
                        class="mt-4 text-2xl font-black tracking-tight text-[#000928] transition-colors group-hover:text-[#381998] sm:text-3xl"
                    >
                        {{ featured.title }}
                    </h2>
                    <p
                        v-if="featured.summary"
                        class="mt-3 text-sm leading-relaxed text-gray-600"
                    >
                        {{ featured.summary }}
                    </p>
                    <dl class="mt-5 space-y-1 text-sm">
                        <div class="flex gap-2">
                            <dt class="font-semibold text-[#000928]">When:</dt>
                            <dd class="text-gray-600">{{ dateRange(featured) }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="font-semibold text-[#000928]">Where:</dt>
                            <dd class="text-gray-600">{{ locationLabel(featured) }}</dd>
                        </div>
                    </dl>
                    <span
                        class="mt-6 w-fit rounded-xl bg-[#000928] px-6 py-3 text-sm font-bold text-white transition-colors group-hover:bg-[#381998]"
                    >
                        View details →
                    </span>
                </div>
            </Link>
        </section>

        <!-- Filters + grid -->
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <div
                class="mb-8 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 sm:flex-row sm:items-center"
            >
                <div class="relative flex-1">
                    <label for="activity-search" class="sr-only">Search activities</label>
                    <svg
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                    <input
                        id="activity-search"
                        v-model="search"
                        type="search"
                        placeholder="Search activities…"
                        class="lms-input pl-9"
                    />
                </div>

                <div class="flex flex-wrap gap-3">
                    <label for="type-filter" class="sr-only">Filter by type</label>
                    <select id="type-filter" v-model="type" class="lms-input sm:w-44">
                        <option value="">All types</option>
                        <option v-for="t in types" :key="t.value" :value="t.value">
                            {{ t.label }}
                        </option>
                    </select>

                    <label for="track-filter" class="sr-only">Filter by track</label>
                    <select id="track-filter" v-model="track" class="lms-input sm:w-52">
                        <option value="">All tracks</option>
                        <option v-for="t in tracks" :key="t.id" :value="t.slug">
                            {{ t.name }}
                        </option>
                    </select>

                    <button
                        v-if="hasFilters()"
                        type="button"
                        class="lms-btn-outline"
                        @click="reset"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <div
                v-if="activities.data.length"
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <ActivityCard
                    v-for="activity in activities.data"
                    :key="activity.id"
                    :activity="activity"
                />
            </div>

            <EmptyState
                v-else
                :title="
                    hasFilters()
                        ? 'No activities match those filters'
                        : window_ === 'upcoming'
                          ? 'Nothing scheduled right now'
                          : 'No past activities yet'
                "
                :description="
                    hasFilters()
                        ? 'Try clearing a filter or searching for something broader.'
                        : window_ === 'upcoming'
                          ? 'The next round is being planned. Join TAC and we’ll email you when something is announced.'
                          : 'Once activities have run, they’ll be archived here with outcomes and highlights.'
                "
                :icon="hasFilters() ? 'search' : 'calendar'"
            >
                <button v-if="hasFilters()" class="lms-btn-outline" @click="reset">
                    Clear filters
                </button>
                <Link v-else href="/community/join" class="lms-btn-accent">
                    Join the community
                </Link>
            </EmptyState>

            <!-- Pagination -->
            <nav
                v-if="activities.last_page > 1"
                class="mt-10 flex flex-wrap items-center justify-center gap-1"
                aria-label="Pagination"
            >
                <component
                    :is="link.url ? Link : 'span'"
                    v-for="(link, index) in activities.links"
                    :key="index"
                    :href="link.url ?? undefined"
                    preserve-scroll
                    :aria-current="link.active ? 'page' : undefined"
                    :class="[
                        'min-w-9 rounded-lg px-3 py-2 text-center text-sm font-semibold transition-colors',
                        link.active
                            ? 'bg-[#000928] text-white'
                            : link.url
                              ? 'text-gray-600 hover:bg-gray-100'
                              : 'cursor-not-allowed text-gray-300',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </section>
    </CommunityShell>
</template>
