<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface SearchOptions {
    programCategories: string[];
    eventCategories: string[];
    durations: string[];
    priceRanges: { label: string; min: number; max: number | null }[];
}

const props = defineProps<{
    compact?: boolean;
}>();

const emit = defineEmits<{
    (e: 'search', filters: Record<string, any>): void;
}>();

// State
const searchType = ref<'programs' | 'events'>('programs');
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedDuration = ref('');
const selectedPriceRange = ref('');
const hasSlots = ref(false);
const isFeatured = ref(false);
const startingSoon = ref(false);
const eventType = ref('');
const isOnline = ref<boolean | null>(null);
const isLoading = ref(false);
const showAdvanced = ref(false);
const options = ref<SearchOptions | null>(null);

// Category labels
const categoryLabels: Record<string, string> = {
    'professional-training': 'Professional Training',
    bootcamp: 'Bootcamp',
    workshop: 'Workshop',
    'academic-internship': 'Academic Internship',
    'professional-internship': 'Professional Internship',
    'job-opportunity': 'Job Opportunity',
    webinar: 'Webinar',
    networking: 'Networking',
    conference: 'Conference',
};

const formatCategory = (cat: string) =>
    categoryLabels[cat] ||
    cat.replace(/-/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());

// Fetch search options on mount
onMounted(async () => {
    try {
        const response = await fetch('/api/search/options');
        options.value = await response.json();
    } catch (error) {
        console.error('Failed to fetch search options:', error);
    }
});

// Computed categories based on search type
const categories = computed(() => {
    if (!options.value) return [];
    return searchType.value === 'programs'
        ? options.value.programCategories
        : options.value.eventCategories;
});

// Build search params
const buildSearchParams = () => {
    const params: Record<string, any> = {};

    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedCategory.value) params.category = selectedCategory.value;

    if (searchType.value === 'programs') {
        if (selectedDuration.value) params.duration = selectedDuration.value;
        if (selectedPriceRange.value && options.value) {
            const range = options.value.priceRanges.find(
                (r) => r.label === selectedPriceRange.value,
            );
            if (range) {
                if (range.min !== undefined) params.price_min = range.min;
                if (range.max !== null) params.price_max = range.max;
            }
        }
        if (hasSlots.value) params.has_slots = '1';
        if (isFeatured.value) params.featured = '1';
        if (startingSoon.value) params.starting_soon = '1';
    } else {
        if (eventType.value) params.event_type = eventType.value;
        if (hasSlots.value) params.has_slots = '1';
        params.upcoming = '1'; // Default to upcoming events
    }

    return params;
};

// Handle search
const handleSearch = () => {
    const params = buildSearchParams();

    // Navigate to the appropriate page with filters
    const route = searchType.value === 'programs' ? '/programs' : '/events';

    // Build query string
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${route}?${queryString}` : route;

    router.visit(url);
};

// Reset filters
const resetFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = '';
    selectedDuration.value = '';
    selectedPriceRange.value = '';
    hasSlots.value = false;
    isFeatured.value = false;
    startingSoon.value = false;
    eventType.value = '';
};
</script>

<template>
    <div class="w-full">
        <!-- Search Type Toggle -->
        <div class="mb-6 flex justify-center">
            <div class="inline-flex rounded-lg bg-white/10 p-1">
                <button
                    @click="
                        searchType = 'programs';
                        resetFilters();
                    "
                    :class="[
                        'rounded-lg px-6 py-2 text-sm font-semibold transition-all',
                        searchType === 'programs'
                            ? 'bg-[#42b6c5] text-white shadow-lg'
                            : 'text-white/80 hover:bg-white/10 hover:text-white',
                    ]"
                >
                    <span class="flex items-center gap-2">
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
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                        Programs
                    </span>
                </button>
                <button
                    @click="
                        searchType = 'events';
                        resetFilters();
                    "
                    :class="[
                        'rounded-lg px-6 py-2 text-sm font-semibold transition-all',
                        searchType === 'events'
                            ? 'bg-[#42b6c5] text-white shadow-lg'
                            : 'text-white/80 hover:bg-white/10 hover:text-white',
                    ]"
                >
                    <span class="flex items-center gap-2">
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
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        Events
                    </span>
                </button>
            </div>
        </div>

        <!-- Main Search Box -->
        <div
            class="rounded-2xl bg-white/95 p-6 shadow-2xl backdrop-blur-sm md:p-8"
        >
            <!-- Search Input Row -->
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
                        :placeholder="
                            searchType === 'programs'
                                ? 'Search by title, skills, topics...'
                                : 'Search events by name, location...'
                        "
                        class="w-full rounded-xl border-2 border-gray-200 py-4 pr-4 pl-12 text-gray-800 placeholder-gray-400 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                        @keyup.enter="handleSearch"
                    />
                </div>

                <button
                    @click="handleSearch"
                    class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#42b6c5] to-[#35919e] px-8 py-4 font-bold text-white transition-all hover:scale-[1.02] hover:shadow-lg"
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
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                    Search
                </button>
            </div>

            <!-- Quick Filters -->
            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- Category -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Category</label
                    >
                    <select
                        v-model="selectedCategory"
                        class="w-full rounded-lg border-2 border-gray-200 px-4 py-3 text-gray-800 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                    >
                        <option value="">All Categories</option>
                        <option
                            v-for="cat in categories"
                            :key="cat"
                            :value="cat"
                        >
                            {{ formatCategory(cat) }}
                        </option>
                    </select>
                </div>

                <!-- Duration (Programs) / Event Type (Events) -->
                <div v-if="searchType === 'programs'">
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Duration</label
                    >
                    <select
                        v-model="selectedDuration"
                        class="w-full rounded-lg border-2 border-gray-200 px-4 py-3 text-gray-800 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                    >
                        <option value="">Any Duration</option>
                        <option
                            v-for="dur in options?.durations"
                            :key="dur"
                            :value="dur"
                        >
                            {{ dur }}
                        </option>
                    </select>
                </div>
                <div v-else>
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Event Type</label
                    >
                    <select
                        v-model="eventType"
                        class="w-full rounded-lg border-2 border-gray-200 px-4 py-3 text-gray-800 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                    >
                        <option value="">All Types</option>
                        <option value="online">Online Events</option>
                        <option value="in-person">In-Person Events</option>
                    </select>
                </div>

                <!-- Price Range (Programs only) -->
                <div v-if="searchType === 'programs'">
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Price Range</label
                    >
                    <select
                        v-model="selectedPriceRange"
                        class="w-full rounded-lg border-2 border-gray-200 px-4 py-3 text-gray-800 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                    >
                        <option value="">Any Price</option>
                        <option
                            v-for="range in options?.priceRanges"
                            :key="range.label"
                            :value="range.label"
                        >
                            {{ range.label }}
                        </option>
                    </select>
                </div>
                <div v-else>
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >&nbsp;</label
                    >
                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-lg border-2 border-gray-200 px-4 py-3 transition-all hover:border-[#42b6c5]"
                    >
                        <input
                            type="checkbox"
                            v-model="hasSlots"
                            class="h-5 w-5 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                        />
                        <span class="text-gray-700">Available Spots Only</span>
                    </label>
                </div>
            </div>

            <!-- Advanced Filters Toggle -->
            <button
                @click="showAdvanced = !showAdvanced"
                class="flex items-center gap-2 font-medium text-[#42b6c5] transition-colors hover:text-[#35919e]"
            >
                <svg
                    class="h-4 w-4 transition-transform"
                    :class="{ 'rotate-180': showAdvanced }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
                {{ showAdvanced ? 'Hide' : 'Show' }} Advanced Filters
            </button>

            <!-- Advanced Filters -->
            <div v-if="showAdvanced" class="mt-4 border-t border-gray-200 pt-4">
                <div class="flex flex-wrap gap-4">
                    <template v-if="searchType === 'programs'">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                v-model="hasSlots"
                                class="h-5 w-5 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span class="text-gray-700">Available Slots</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                v-model="isFeatured"
                                class="h-5 w-5 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span class="text-gray-700">Featured Programs</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                v-model="startingSoon"
                                class="h-5 w-5 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span class="text-gray-700"
                                >Starting Soon (30 days)</span
                            >
                        </label>
                    </template>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="mt-6 border-t border-gray-200 pt-4">
                <p class="mb-3 text-sm text-gray-500">Popular searches:</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="searchType === 'programs'"
                        @click="
                            selectedCategory = 'bootcamp';
                            handleSearch();
                        "
                        class="rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-[#42b6c5]/10 hover:text-[#42b6c5]"
                    >
                        🚀 Bootcamps
                    </button>
                    <button
                        v-if="searchType === 'programs'"
                        @click="
                            selectedCategory = 'professional-training';
                            handleSearch();
                        "
                        class="rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-[#42b6c5]/10 hover:text-[#42b6c5]"
                    >
                        💼 Professional Training
                    </button>
                    <button
                        v-if="searchType === 'programs'"
                        @click="
                            isFeatured = true;
                            handleSearch();
                        "
                        class="rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-[#42b6c5]/10 hover:text-[#42b6c5]"
                    >
                        ⭐ Featured
                    </button>
                    <button
                        v-if="searchType === 'events'"
                        @click="
                            eventType = 'online';
                            handleSearch();
                        "
                        class="rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-[#42b6c5]/10 hover:text-[#42b6c5]"
                    >
                        🌐 Online Events
                    </button>
                    <button
                        v-if="searchType === 'events'"
                        @click="
                            eventType = 'in-person';
                            handleSearch();
                        "
                        class="rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-[#42b6c5]/10 hover:text-[#42b6c5]"
                    >
                        📍 In-Person Events
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
