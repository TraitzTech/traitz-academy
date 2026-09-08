<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Program {
    id: number;
    title: string;
    slug: string;
    category: string;
    description: string;
    duration: string;
    capacity: number;
    enrolled_count: number;
    image_url: string;
    is_featured: boolean;
    price: number | null;
    start_date: string | null;
}

interface SearchOptions {
    programCategories: string[];
    eventCategories: string[];
    durations: string[];
    priceRanges: { label: string; min: number; max: number | null }[];
}

interface Props {
    programs: Program[];
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
        duration: params.get('duration') || '',
        priceRange:
            params.get('price_min') && params.get('price_max')
                ? `${params.get('price_min')}-${params.get('price_max')}`
                : '',
        hasSlots: params.get('has_slots') === '1',
        featured: params.get('featured') === '1',
        startingSoon: params.get('starting_soon') === '1',
    };
};

// State initialized from URL
const urlParams = getUrlParams();
const searchQuery = ref(urlParams.search);
const selectedCategory = ref(urlParams.category);
const selectedDuration = ref(urlParams.duration);
const selectedPriceRange = ref(urlParams.priceRange);
const hasSlots = ref(urlParams.hasSlots);
const featured = ref(urlParams.featured);
const startingSoon = ref(urlParams.startingSoon);
const showFilters = ref(false);

// Category labels
const categoryLabels: Record<string, string> = {
    all: 'All Programs',
    'professional-training': 'Professional Training',
    bootcamp: 'Bootcamp',
    workshop: 'Workshop',
    'academic-internship': 'Academic Internship',
    'professional-internship': 'Professional Internship',
    'job-opportunity': 'Job Opportunity',
};

const formatCategory = (cat: string) =>
    categoryLabels[cat] ||
    cat.replace(/-/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());

// Format price
const formatPrice = (price: number) => {
    if (price === 0) return 'Free';
    return new Intl.NumberFormat('en-CM', {
        style: 'currency',
        currency: 'XAF',
    }).format(price);
};

// Fetch search options
onMounted(async () => {
    try {
        const response = await fetch('/api/search/options');
        searchOptions.value = await response.json();
    } catch (error) {
        console.error('Failed to fetch search options:', error);
    }
});

// Filter programs client-side
const filteredPrograms = computed(() => {
    let result = props.programs;

    // Category filter
    if (selectedCategory.value && selectedCategory.value !== 'all') {
        result = result.filter((p) => p.category === selectedCategory.value);
    }

    // Search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (p) =>
                p.title.toLowerCase().includes(query) ||
                p.description.toLowerCase().includes(query) ||
                p.category.toLowerCase().includes(query),
        );
    }

    // Duration filter
    if (selectedDuration.value) {
        result = result.filter((p) => p.duration === selectedDuration.value);
    }

    // Has slots filter
    if (hasSlots.value) {
        result = result.filter((p) => p.capacity - p.enrolled_count > 0);
    }

    // Featured filter
    if (featured.value) {
        result = result.filter((p) => p.is_featured);
    }

    // Starting soon filter (within 30 days)
    if (startingSoon.value) {
        const thirtyDaysFromNow = new Date();
        thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
        result = result.filter((p) => {
            if (!p.start_date) return false;
            const startDate = new Date(p.start_date);
            return startDate >= new Date() && startDate <= thirtyDaysFromNow;
        });
    }

    return result;
});

// Active filters count
const activeFiltersCount = computed(() => {
    let count = 0;
    if (selectedCategory.value && selectedCategory.value !== 'all') count++;
    if (searchQuery.value) count++;
    if (selectedDuration.value) count++;
    if (selectedPriceRange.value) count++;
    if (hasSlots.value) count++;
    if (featured.value) count++;
    if (startingSoon.value) count++;
    return count;
});

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = 'all';
    selectedDuration.value = '';
    selectedPriceRange.value = '';
    hasSlots.value = false;
    featured.value = false;
    startingSoon.value = false;
};

// Categories for filter
const categories = [
    'all',
    'professional-training',
    'bootcamp',
    'workshop',
    'academic-internship',
    'professional-internship',
    'job-opportunity',
];
</script>

<template>
    <PublicLayout>
        <SeoHead
            title="Programs & Training"
            description="Browse hands-on training programs, bootcamps and professional internships at Traitz Academy — Web Development, Mobile Apps, UI/UX, AI & Machine Learning, Cybersecurity, Data Science and more."
        />

        <!-- Page Header -->
        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] py-16 text-white"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">
                    Our Programs
                </h1>
                <p class="text-xl text-gray-300">
                    Choose from our comprehensive range of training,
                    internships, and professional development programs
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
                            placeholder="Search programs by title, skills, topics..."
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
                        <!-- Duration Filter -->
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Duration</label
                            >
                            <select
                                v-model="selectedDuration"
                                class="w-full rounded-lg border-2 border-gray-200 px-4 py-2 text-gray-800 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                            >
                                <option value="">Any Duration</option>
                                <option
                                    v-for="dur in searchOptions?.durations"
                                    :key="dur"
                                    :value="dur"
                                >
                                    {{ dur }}
                                </option>
                            </select>
                        </div>

                        <!-- Price Range Filter -->
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Price Range</label
                            >
                            <select
                                v-model="selectedPriceRange"
                                class="w-full rounded-lg border-2 border-gray-200 px-4 py-2 text-gray-800 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                            >
                                <option value="">Any Price</option>
                                <option
                                    v-for="range in searchOptions?.priceRanges"
                                    :key="range.label"
                                    :value="range.label"
                                >
                                    {{ range.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Checkbox Filters -->
                        <div class="md:col-span-2">
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
                                        v-model="hasSlots"
                                        class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    />
                                    <span class="text-sm text-gray-700"
                                        >Available Slots</span
                                    >
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="featured"
                                        class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    />
                                    <span class="text-sm text-gray-700"
                                        >Featured Only</span
                                    >
                                </label>
                                <label
                                    class="flex cursor-pointer items-center gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="startingSoon"
                                        class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    />
                                    <span class="text-sm text-gray-700"
                                        >Starting Soon</span
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
                    <span>{{ filteredPrograms.length }} programs found</span>
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

        <!-- Programs Grid -->
        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Results Count -->
                <div class="mb-8 flex items-center justify-between">
                    <p class="text-gray-600">
                        Showing
                        <span class="font-semibold text-gray-900">{{
                            filteredPrograms.length
                        }}</span>
                        programs
                    </p>
                </div>

                <div
                    v-if="filteredPrograms.length === 0"
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
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-900">
                        No programs found
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
                        v-for="program in filteredPrograms"
                        :key="program.id"
                        class="flex transform flex-col overflow-hidden rounded-lg bg-white shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl"
                    >
                        <!-- Image -->
                        <div
                            class="relative h-48 overflow-hidden bg-gradient-to-br from-[#381998] to-[#42b6c5]"
                        >
                            <img
                                :src="'/storage/' + program.image_url"
                                :alt="program.title"
                                class="h-full w-full object-cover opacity-80"
                            />
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"
                            ></div>
                            <div
                                v-if="program.is_featured"
                                class="absolute top-4 right-4 rounded-full bg-yellow-400 px-3 py-1 text-sm font-bold text-gray-900"
                            >
                                Featured
                            </div>
                            <div
                                v-if="
                                    program.capacity - program.enrolled_count <=
                                        5 &&
                                    program.capacity - program.enrolled_count >
                                        0
                                "
                                class="absolute top-4 left-4 rounded-full bg-orange-500 px-3 py-1 text-xs font-bold text-white"
                            >
                                {{
                                    program.capacity - program.enrolled_count
                                }}
                                spots left
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex flex-grow flex-col p-6">
                            <div
                                class="mb-2 inline-block w-fit rounded-full bg-[#42b6c5]/10 px-3 py-1 text-sm font-semibold text-[#42b6c5]"
                            >
                                {{ formatCategory(program.category) }}
                            </div>

                            <h3
                                class="mb-2 line-clamp-2 text-xl font-bold text-[#000928]"
                            >
                                {{ program.title }}
                            </h3>
                            <p
                                class="mb-4 line-clamp-3 flex-grow text-gray-600"
                            >
                                {{ program.description }}
                            </p>

                            <div class="mb-6 space-y-2 text-sm text-gray-600">
                                <div class="flex justify-between">
                                    <span class="font-semibold">Duration:</span>
                                    <span>{{ program.duration }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-semibold">Enrolled:</span>
                                    <span
                                        >{{ program.enrolled_count }}/{{
                                            program.capacity
                                        }}
                                        participants</span
                                    >
                                </div>
                                <div
                                    v-if="
                                        program.price !== null &&
                                        program.price !== undefined
                                    "
                                    class="flex justify-between"
                                >
                                    <span class="font-semibold">Price:</span>
                                    <span
                                        class="font-semibold text-[#42b6c5]"
                                        >{{ formatPrice(program.price) }}</span
                                    >
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Link
                                    :href="`/programs/${program.slug}`"
                                    class="block w-full rounded-lg bg-[#000928] px-4 py-2 text-center font-semibold text-white transition-colors hover:bg-[#381998]"
                                >
                                    View Details
                                </Link>
                                <Link
                                    :href="`/programs/${program.id}/apply`"
                                    class="block w-full rounded-lg bg-[#42b6c5] px-4 py-2 text-center font-semibold text-white transition-colors hover:bg-[#35919e]"
                                >
                                    Apply Now
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
