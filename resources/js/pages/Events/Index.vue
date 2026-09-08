<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Event {
    id: number;
    title: string;
    slug: string;
    description: string;
    event_date: string;
    location: string;
    is_online: boolean;
    event_url: string;
    category: string;
    image_url: string;
    capacity: number;
    registered_count: number;
}

interface SearchOptions {
    programCategories: string[];
    eventCategories: string[];
    durations: string[];
    priceRanges: { label: string; min: number; max: number | null }[];
}

interface Props {
    events: Event[];
    userRegisteredEventIds: number[];
}

const props = defineProps<Props>();

// Search options from API
const searchOptions = ref<SearchOptions | null>(null);

// Get URL params on mount
const getUrlParams = () => {
    const params = new URLSearchParams(window.location.search);
    return {
        search: params.get('search') || '',
        category: params.get('category') || 'all',
        eventType: params.get('event_type') || '',
        upcoming: params.get('upcoming') === '1',
        hasSlots: params.get('has_slots') === '1',
    };
};

// State initialized from URL
const urlParams = getUrlParams();
const searchQuery = ref(urlParams.search);
const selectedCategory = ref(urlParams.category);
const eventType = ref(urlParams.eventType);
const upcomingOnly = ref(urlParams.upcoming);
const hasSlots = ref(urlParams.hasSlots);
const showFilters = ref(false);

// Category labels
const categoryLabels: Record<string, string> = {
    all: 'All Events',
    webinar: 'Webinar',
    workshop: 'Workshop',
    networking: 'Networking',
    conference: 'Conference',
    training: 'Training',
    meetup: 'Meetup',
};

const formatCategory = (cat: string) =>
    categoryLabels[cat] ||
    cat.replace(/-/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());

// Fetch search options
onMounted(async () => {
    try {
        const response = await fetch('/api/search/options');
        searchOptions.value = await response.json();
    } catch (error) {
        console.error('Failed to fetch search options:', error);
    }
});

// Filter events client-side
const filteredEvents = computed(() => {
    let result = props.events;

    // Category filter
    if (selectedCategory.value && selectedCategory.value !== 'all') {
        result = result.filter((e) => e.category === selectedCategory.value);
    }

    // Search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (e) =>
                e.title.toLowerCase().includes(query) ||
                e.description.toLowerCase().includes(query) ||
                (e.location && e.location.toLowerCase().includes(query)) ||
                (e.category && e.category.toLowerCase().includes(query)),
        );
    }

    // Event type filter (online/in-person)
    if (eventType.value) {
        if (eventType.value === 'online') {
            result = result.filter((e) => e.is_online);
        } else if (eventType.value === 'in-person') {
            result = result.filter((e) => !e.is_online);
        }
    }

    // Upcoming only
    if (upcomingOnly.value) {
        result = result.filter((e) => new Date(e.event_date) > new Date());
    }

    // Has slots
    if (hasSlots.value) {
        result = result.filter(
            (e) => !e.capacity || e.capacity - (e.registered_count || 0) > 0,
        );
    }

    return result;
});

// Active filters count
const activeFiltersCount = computed(() => {
    let count = 0;
    if (selectedCategory.value && selectedCategory.value !== 'all') count++;
    if (searchQuery.value) count++;
    if (eventType.value) count++;
    if (upcomingOnly.value) count++;
    if (hasSlots.value) count++;
    return count;
});

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = 'all';
    eventType.value = '';
    upcomingOnly.value = false;
    hasSlots.value = false;
};

// Categories for filter
const categories = [
    'all',
    'webinar',
    'workshop',
    'networking',
    'conference',
    'training',
    'meetup',
];

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
const isUpcoming = (date: string) => new Date(date) > new Date();
</script>

<template>
    <PublicLayout>
        <SeoHead
            title="Events"
            description="Workshops, webinars and networking events from Traitz Academy — open to students, interns, alumni and anyone curious about tech."
        />

        <!-- Page Header -->
        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] py-16 text-white"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">Our Events</h1>
                <p class="text-xl text-gray-300">
                    Join us for webinars, workshops, networking events, and more
                </p>
            </div>
        </section>

        <!-- Search & Filters Section -->
        <section
            class="sticky top-0 z-30 border-b border-gray-200 bg-white py-8 shadow-sm"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Search Bar Row -->
                <div class="mb-6 flex flex-col gap-4 md:flex-row">
                    <div class="relative flex-1">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"
                        >
                            <svg
                                class="h-5 w-5 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search events by name, location, topics..."
                            class="w-full rounded-xl border-2 border-gray-200 py-3 pr-4 pl-12 text-gray-800 placeholder-gray-400 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                        />
                    </div>

                    <button
                        @click="showFilters = !showFilters"
                        :class="[
                            'flex items-center gap-2 rounded-xl border-2 px-6 py-3 font-semibold transition-all',
                            showFilters || activeFiltersCount > 0
                                ? 'border-[#42b6c5] bg-[#42b6c5] text-white'
                                : 'border-gray-200 bg-white text-gray-700 hover:border-[#42b6c5]',
                        ]"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                            />
                        </svg>
                        Filters
                        <span
                            v-if="activeFiltersCount > 0"
                            class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-[#42b6c5]"
                        >
                            {{ activeFiltersCount }}
                        </span>
                    </button>
                </div>

                <!-- Category Pills -->
                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        v-for="category in categories"
                        :key="category"
                        @click="selectedCategory = category"
                        :class="[
                            'rounded-full px-4 py-2 text-sm font-medium transition-all',
                            selectedCategory === category
                                ? 'bg-[#42b6c5] text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                        ]"
                    >
                        {{ formatCategory(category) }}
                    </button>
                </div>

                <!-- Expandable Filters Panel -->
                <div
                    v-if="showFilters"
                    class="mt-4 border-t border-gray-200 pt-4"
                >
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <!-- Event Type Filter -->
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Event Type</label
                            >
                            <select
                                v-model="eventType"
                                class="w-full rounded-lg border-2 border-gray-200 px-4 py-2 text-gray-800 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                            >
                                <option value="">All Types</option>
                                <option value="online">Online Events</option>
                                <option value="in-person">
                                    In-Person Events
                                </option>
                            </select>
                        </div>

                        <!-- Checkbox Filters -->
                        <div class="md:col-span-3">
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700"
                                >Quick Filters</label
                            >
                            <div class="flex flex-wrap gap-4">
                                <label
                                    class="flex cursor-pointer items-center gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="upcomingOnly"
                                        class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    />
                                    <span class="text-sm text-gray-700"
                                        >Upcoming Only</span
                                    >
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="hasSlots"
                                        class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    />
                                    <span class="text-sm text-gray-700"
                                        >Available Spots</span
                                    >
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    <div class="mt-4 flex justify-end">
                        <button
                            @click="clearFilters"
                            class="text-sm font-medium text-[#42b6c5] transition-colors hover:text-[#35919e]"
                        >
                            Clear All Filters
                        </button>
                    </div>
                </div>

                <!-- Active Filters Summary -->
                <div
                    v-if="activeFiltersCount > 0 && !showFilters"
                    class="mt-4 flex items-center gap-2 text-sm text-gray-600"
                >
                    <span>{{ filteredEvents.length }} events found</span>
                    <span class="text-gray-300">|</span>
                    <button
                        @click="clearFilters"
                        class="text-[#42b6c5] hover:underline"
                    >
                        Clear filters
                    </button>
                </div>
            </div>
        </section>

        <!-- Events Grid -->
        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Results Count -->
                <div class="mb-8 flex items-center justify-between">
                    <p class="text-gray-600">
                        Showing
                        <span class="font-semibold text-gray-900">{{
                            filteredEvents.length
                        }}</span>
                        events
                    </p>
                </div>

                <div
                    v-if="filteredEvents.length === 0"
                    class="py-16 text-center"
                >
                    <div
                        class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100"
                    >
                        <svg
                            class="h-12 w-12 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-900">
                        No events found
                    </h3>
                    <p class="mb-6 text-gray-600">
                        Try adjusting your search or filter criteria
                    </p>
                    <button
                        @click="clearFilters"
                        class="rounded-lg bg-[#42b6c5] px-6 py-3 font-semibold text-white transition-colors hover:bg-[#35919e]"
                    >
                        Clear All Filters
                    </button>
                </div>

                <div
                    class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="event in filteredEvents"
                        :key="event.id"
                        class="flex transform flex-col overflow-hidden rounded-lg bg-white shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl"
                    >
                        <!-- Date Header -->
                        <div
                            class="relative bg-gradient-to-r from-[#381998] to-[#42b6c5] p-6 text-white"
                        >
                            <div class="text-5xl font-bold">
                                {{ new Date(event.event_date).getDate() }}
                            </div>
                            <p class="text-white/90">
                                {{
                                    new Date(
                                        event.event_date,
                                    ).toLocaleDateString('en-US', {
                                        month: 'short',
                                        year: 'numeric',
                                    })
                                }}
                            </p>
                            <p class="mt-2 text-sm text-white/80">
                                {{
                                    new Date(
                                        event.event_date,
                                    ).toLocaleTimeString('en-US', {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })
                                }}
                            </p>

                            <!-- Status Badge -->
                            <div
                                v-if="!isUpcoming(event.event_date)"
                                class="absolute top-4 right-4 rounded-full bg-gray-800 px-3 py-1 text-xs font-bold text-white"
                            >
                                Past Event
                            </div>
                            <div
                                v-else-if="
                                    props.userRegisteredEventIds.includes(
                                        event.id,
                                    )
                                "
                                class="absolute top-4 right-4 flex items-center gap-1 rounded-full bg-emerald-500 px-3 py-1 text-xs font-bold text-white"
                            >
                                <svg
                                    class="h-3 w-3"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Registered
                            </div>
                            <div
                                v-else-if="event.is_online"
                                class="absolute top-4 right-4 rounded-full bg-green-500 px-3 py-1 text-xs font-bold text-white"
                            >
                                🌐 Online
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex flex-grow flex-col p-6">
                            <div
                                class="mb-2 inline-block w-fit rounded-full bg-[#42b6c5]/10 px-3 py-1 text-sm font-semibold text-[#42b6c5]"
                            >
                                {{ event.category || 'Event' }}
                            </div>

                            <h3
                                class="mb-2 line-clamp-2 text-xl font-bold text-[#000928]"
                            >
                                {{ event.title }}
                            </h3>
                            <p
                                class="mb-4 line-clamp-3 flex-grow text-gray-600"
                            >
                                {{ event.description }}
                            </p>

                            <!-- Location/Online Info -->
                            <div
                                v-if="event.location || event.is_online"
                                class="mb-6 flex flex-wrap gap-2"
                            >
                                <span
                                    v-if="event.is_online"
                                    class="inline-flex items-center rounded-full bg-[#42b6c5]/10 px-3 py-1 text-sm font-semibold text-[#42b6c5]"
                                >
                                    <svg
                                        class="mr-1 h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    Online
                                </span>
                                <span
                                    v-if="event.location"
                                    class="inline-flex items-center text-sm font-semibold text-gray-600"
                                >
                                    <svg
                                        class="mr-1 h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    {{ event.location }}
                                </span>
                            </div>

                            <!-- Capacity Info -->
                            <div
                                v-if="event.capacity"
                                class="mb-4 text-sm text-gray-600"
                            >
                                <div class="mb-1 flex justify-between">
                                    <span
                                        >{{ event.registered_count || 0 }} /
                                        {{ event.capacity }} registered</span
                                    >
                                    <span
                                        v-if="
                                            event.capacity -
                                                (event.registered_count || 0) <=
                                                5 &&
                                            event.capacity -
                                                (event.registered_count || 0) >
                                                0
                                        "
                                        class="font-semibold text-orange-500"
                                    >
                                        {{
                                            event.capacity -
                                            (event.registered_count || 0)
                                        }}
                                        spots left!
                                    </span>
                                </div>
                                <div
                                    class="h-2 w-full rounded-full bg-gray-200"
                                >
                                    <div
                                        class="h-2 rounded-full bg-[#42b6c5] transition-all"
                                        :style="{
                                            width: `${Math.min(((event.registered_count || 0) / event.capacity) * 100, 100)}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Link
                                    :href="`/events/${event.slug}`"
                                    class="block w-full rounded-lg bg-[#000928] px-4 py-2 text-center font-semibold text-white transition-colors hover:bg-[#381998]"
                                >
                                    View Details
                                </Link>
                                <div
                                    v-if="
                                        props.userRegisteredEventIds.includes(
                                            event.id,
                                        ) && isUpcoming(event.event_date)
                                    "
                                    class="block w-full rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-center font-semibold text-emerald-700"
                                >
                                    <span
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Already Registered
                                    </span>
                                </div>
                                <Link
                                    v-else-if="isUpcoming(event.event_date)"
                                    :href="`/events/${event.slug}`"
                                    class="block w-full rounded-lg bg-[#42b6c5] px-4 py-2 text-center font-semibold text-white transition-colors hover:bg-[#35919e]"
                                >
                                    Register Now
                                </Link>
                                <button
                                    v-else
                                    disabled
                                    class="block w-full cursor-not-allowed rounded-lg bg-gray-300 px-4 py-2 text-center font-semibold text-gray-600"
                                >
                                    Event Ended
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
