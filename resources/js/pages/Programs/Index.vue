<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';

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
    priceRange: params.get('price_min') && params.get('price_max') 
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
  'all': 'All Programs',
  'professional-training': 'Professional Training',
  'bootcamp': 'Bootcamp',
  'workshop': 'Workshop',
  'academic-internship': 'Academic Internship',
  'professional-internship': 'Professional Internship',
};

const formatCategory = (cat: string) => categoryLabels[cat] || cat.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

// Format price
const formatPrice = (price: number) => {
  if (price === 0) return 'Free'
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(price)
}

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
    result = result.filter(p => p.category === selectedCategory.value);
  }

  // Search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(p => 
      p.title.toLowerCase().includes(query) ||
      p.description.toLowerCase().includes(query) ||
      p.category.toLowerCase().includes(query)
    );
  }

  // Duration filter
  if (selectedDuration.value) {
    result = result.filter(p => p.duration === selectedDuration.value);
  }

  // Has slots filter
  if (hasSlots.value) {
    result = result.filter(p => p.capacity - p.enrolled_count > 0);
  }

  // Featured filter
  if (featured.value) {
    result = result.filter(p => p.is_featured);
  }

  // Starting soon filter (within 30 days)
  if (startingSoon.value) {
    const thirtyDaysFromNow = new Date();
    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
    result = result.filter(p => {
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
const categories = ['all', 'professional-training', 'bootcamp', 'workshop', 'academic-internship', 'professional-internship'];
</script>

<template>
  <PublicLayout>
    <Head title="Programs - Traitz Academy" />

    <!-- Page Header -->
    <section class="bg-gradient-to-r from-[#000928] to-[#381998] text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Our Programs</h1>
        <p class="text-xl text-gray-300">Choose from our comprehensive range of training, internships, and professional development programs</p>
      </div>
    </section>

    <!-- Search & Filters Section -->
    <section class="py-8 bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Search Bar Row -->
        <div class="flex flex-col md:flex-row gap-4 mb-6">
          <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search programs by title, skills, topics..."
              class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 transition-all text-gray-800 placeholder-gray-400"
            />
          </div>
          
          <button
            @click="showFilters = !showFilters"
            :class="[
              'px-6 py-3 rounded-xl font-semibold flex items-center gap-2 transition-all border-2',
              showFilters || activeFiltersCount > 0
                ? 'bg-[#42b6c5] text-white border-[#42b6c5]'
                : 'bg-white text-gray-700 border-gray-200 hover:border-[#42b6c5]'
            ]"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Filters
            <span v-if="activeFiltersCount > 0" class="bg-white text-[#42b6c5] px-2 py-0.5 rounded-full text-xs font-bold">
              {{ activeFiltersCount }}
            </span>
          </button>
        </div>

        <!-- Category Pills -->
        <div class="flex flex-wrap gap-2 mb-4">
          <button
            v-for="category in categories"
            :key="category"
            @click="selectedCategory = category"
            :class="[
              'px-4 py-2 rounded-full font-medium text-sm transition-all',
              selectedCategory === category
                ? 'bg-[#42b6c5] text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ formatCategory(category) }}
          </button>
        </div>

        <!-- Expandable Filters Panel -->
        <div v-if="showFilters" class="pt-4 border-t border-gray-200 mt-4">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Duration Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
              <select
                v-model="selectedDuration"
                class="w-full px-4 py-2 rounded-lg border-2 border-gray-200 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 transition-all text-gray-800"
              >
                <option value="">Any Duration</option>
                <option v-for="dur in searchOptions?.durations" :key="dur" :value="dur">
                  {{ dur }}
                </option>
              </select>
            </div>

            <!-- Price Range Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Price Range</label>
              <select
                v-model="selectedPriceRange"
                class="w-full px-4 py-2 rounded-lg border-2 border-gray-200 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 transition-all text-gray-800"
              >
                <option value="">Any Price</option>
                <option v-for="range in searchOptions?.priceRanges" :key="range.label" :value="range.label">
                  {{ range.label }}
                </option>
              </select>
            </div>

            <!-- Checkbox Filters -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Quick Filters</label>
              <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="hasSlots"
                    class="w-4 h-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                  />
                  <span class="text-gray-700 text-sm">Available Slots</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="featured"
                    class="w-4 h-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                  />
                  <span class="text-gray-700 text-sm">Featured Only</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="startingSoon"
                    class="w-4 h-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                  />
                  <span class="text-gray-700 text-sm">Starting Soon</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Clear Filters -->
          <div class="mt-4 flex justify-end">
            <button
              @click="clearFilters"
              class="text-[#42b6c5] hover:text-[#35919e] font-medium text-sm transition-colors"
            >
              Clear All Filters
            </button>
          </div>
        </div>

        <!-- Active Filters Summary -->
        <div v-if="activeFiltersCount > 0 && !showFilters" class="mt-4 flex items-center gap-2 text-sm text-gray-600">
          <span>{{ filteredPrograms.length }} programs found</span>
          <span class="text-gray-300">|</span>
          <button @click="clearFilters" class="text-[#42b6c5] hover:underline">
            Clear filters
          </button>
        </div>
      </div>
    </section>

    <!-- Programs Grid -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Results Count -->
        <div class="mb-8 flex items-center justify-between">
          <p class="text-gray-600">
            Showing <span class="font-semibold text-gray-900">{{ filteredPrograms.length }}</span> programs
          </p>
        </div>

        <div v-if="filteredPrograms.length === 0" class="text-center py-16">
          <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">No programs found</h3>
          <p class="text-gray-600 mb-6">Try adjusting your search or filter criteria</p>
          <button
            @click="clearFilters"
            class="px-6 py-3 bg-[#42b6c5] text-white rounded-lg font-semibold hover:bg-[#35919e] transition-colors"
          >
            Clear All Filters
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div
            v-for="program in filteredPrograms"
            :key="program.id"
            class="bg-white rounded-lg overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 flex flex-col"
          >
            <!-- Image -->
            <div class="h-48 relative overflow-hidden bg-gradient-to-br from-[#381998] to-[#42b6c5]">
              <img :src="'/storage/' + program.image_url" :alt="program.title" class="w-full h-full object-cover opacity-80" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
              <div v-if="program.is_featured" class="absolute top-4 right-4 bg-yellow-400 text-gray-900 px-3 py-1 rounded-full text-sm font-bold">Featured</div>
              <div v-if="program.capacity - program.enrolled_count <= 5 && program.capacity - program.enrolled_count > 0" class="absolute top-4 left-4 bg-orange-500 text-white px-3 py-1 rounded-full text-xs font-bold">
                {{ program.capacity - program.enrolled_count }} spots left
              </div>
            </div>

            <!-- Content -->
            <div class="p-6 flex-grow flex flex-col">
              <div class="inline-block bg-[#42b6c5]/10 text-[#42b6c5] px-3 py-1 rounded-full text-sm font-semibold mb-2 w-fit">
                {{ formatCategory(program.category) }}
              </div>

              <h3 class="text-xl font-bold text-[#000928] mb-2 line-clamp-2">{{ program.title }}</h3>
              <p class="text-gray-600 mb-4 flex-grow line-clamp-3">{{ program.description }}</p>

              <div class="space-y-2 text-sm text-gray-600 mb-6">
                <div class="flex justify-between">
                  <span class="font-semibold">Duration:</span>
                  <span>{{ program.duration }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-semibold">Enrolled:</span>
                  <span>{{ program.enrolled_count }}/{{ program.capacity }} participants</span>
                </div>
                <div v-if="program.price !== null && program.price !== undefined" class="flex justify-between">
                  <span class="font-semibold">Price:</span>
                  <span class="text-[#42b6c5] font-semibold">{{ formatPrice(program.price) }}</span>
                </div>
              </div>

              <div class="space-y-2">
                <Link
                  :href="`/programs/${program.slug}`"
                  class="block w-full text-center px-4 py-2 bg-[#000928] text-white rounded-lg font-semibold hover:bg-[#381998] transition-colors"
                >
                  View Details
                </Link>
                <Link
                  :href="`/programs/${program.id}/apply`"
                  class="block w-full text-center px-4 py-2 bg-[#42b6c5] text-white rounded-lg font-semibold hover:bg-[#35919e] transition-colors"
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
