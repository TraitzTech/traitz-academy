<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Clock, Search, SlidersHorizontal, Star, Users, X } from 'lucide-vue-next';
import { debounce } from 'lodash-es';
import { computed, ref, watch } from 'vue';

import PublicLayout from '@/layouts/PublicLayout.vue';

interface Category {
  id: number;
  name: string;
  slug: string;
  icon: string | null;
  color: string | null;
}

interface Instructor {
  id: number;
  name: string;
}

interface CourseCategory {
  id: number;
  name: string;
  slug: string;
  color: string | null;
}

interface Course {
  id: number;
  title: string;
  slug: string;
  short_description: string;
  cover_image: string | null;
  level: 'beginner' | 'intermediate' | 'advanced';
  price: string;
  sale_price: string | null;
  duration: string | null;
  enrolled_count: number;
  rating: string;
  review_count: number;
  is_featured: boolean;
  instructor: Instructor | null;
  category: CourseCategory | null;
}

interface PaginatedCourses {
  data: Course[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
  links: { url: string | null; label: string; active: boolean }[];
}

interface Filters {
  search?: string;
  category?: string;
  level?: string;
  sort?: string;
  free?: string | boolean;
}

const props = defineProps<{
  courses: PaginatedCourses;
  categories: Category[];
  filters: Filters;
}>();

const search = ref(props.filters.search ?? '');
const selectedCategory = ref(props.filters.category ?? '');
const selectedLevel = ref(props.filters.level ?? '');
const selectedSort = ref(props.filters.sort ?? '');
const freeOnly = ref(props.filters.free === true || props.filters.free === '1' || props.filters.free === 1);
const filtersOpen = ref(false);

const levelLabels: Record<string, string> = {
  beginner: 'Beginner',
  intermediate: 'Intermediate',
  advanced: 'Advanced',
};

const levelColors: Record<string, string> = {
  beginner: 'bg-green-100 text-green-700',
  intermediate: 'bg-yellow-100 text-yellow-700',
  advanced: 'bg-red-100 text-red-700',
};

const sortOptions = [
  { value: '', label: 'Featured' },
  { value: 'popular', label: 'Most Popular' },
  { value: 'rating', label: 'Highest Rated' },
  { value: 'newest', label: 'Newest' },
  { value: 'price_asc', label: 'Price: Low to High' },
  { value: 'price_desc', label: 'Price: High to Low' },
];

const activeFiltersCount = computed(
  () => [selectedCategory.value, selectedLevel.value, selectedSort.value, freeOnly.value].filter(Boolean).length,
);

const applyFilters = debounce(() => {
  router.get(
    '/online-courses',
    {
      search: search.value || undefined,
      category: selectedCategory.value || undefined,
      level: selectedLevel.value || undefined,
      sort: selectedSort.value || undefined,
      free: freeOnly.value ? '1' : undefined,
    },
    { preserveState: true, replace: true },
  );
}, 300);

watch(search, () => applyFilters());
watch([selectedCategory, selectedLevel, selectedSort, freeOnly], () => applyFilters());

function clearFilters() {
  search.value = '';
  selectedCategory.value = '';
  selectedLevel.value = '';
  selectedSort.value = '';
  freeOnly.value = false;
}

function formatPrice(price: string, salePrice: string | null) {
  const p = parseFloat(price);
  if (p === 0) return 'Free';
  const sp = salePrice ? parseFloat(salePrice) : null;
  return sp ? `${sp.toLocaleString()} XAF` : `${p.toLocaleString()} XAF`;
}

function coverUrl(url: string | null) {
  if (!url) return null;
  if (url.startsWith('http')) return url;
  return `/storage/${url}`;
}
</script>

<template>
  <PublicLayout>
    <Head title="Online Courses — Traitz Academy" />

    <!-- Hero -->
    <section class="relative overflow-hidden bg-gradient-to-br from-[#000928] via-[#1a0a52] to-[#381998] py-20 text-white">
      <div class="absolute inset-0 opacity-10">
        <svg class="absolute inset-0 h-full w-full" viewBox="0 0 1200 1200">
          <defs>
            <pattern id="dots" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
              <circle cx="50" cy="50" r="2" fill="currentColor" />
            </pattern>
          </defs>
          <rect width="1200" height="1200" fill="url(#dots)" />
        </svg>
      </div>
      <div class="absolute right-0 top-0 h-96 w-96 rounded-full bg-[#42b6c5] opacity-10 blur-3xl" />
      <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 rounded-full border border-[#42b6c5]/30 bg-[#42b6c5]/10 px-4 py-1.5 mb-6">
          <BookOpen class="h-4 w-4 text-[#42b6c5]" />
          <span class="text-sm font-semibold text-[#42b6c5]">Online Courses</span>
        </div>
        <h1 class="text-5xl md:text-6xl font-bold mb-4 leading-tight">
          Learn at Your
          <span class="bg-gradient-to-r from-[#42b6c5] to-white bg-clip-text text-transparent"> Own Pace</span>
        </h1>
        <p class="mx-auto max-w-2xl text-lg text-gray-300">
          Browse our growing library of courses taught by industry professionals. Earn certificates, track your progress, and level up your skills.
        </p>

        <!-- Stats row -->
        <div class="mt-10 flex flex-wrap justify-center gap-8 text-sm">
          <div class="flex items-center gap-2">
            <BookOpen class="h-4 w-4 text-[#42b6c5]" />
            <span class="text-gray-300"><span class="font-bold text-white">{{ courses.total }}</span> Courses</span>
          </div>
          <div class="flex items-center gap-2">
            <Users class="h-4 w-4 text-[#42b6c5]" />
            <span class="text-gray-300"><span class="font-bold text-white">{{ categories.length }}</span> Categories</span>
          </div>
          <div class="flex items-center gap-2">
            <Star class="h-4 w-4 text-[#42b6c5]" />
            <span class="text-gray-300">Certificate on completion</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Filters + Grid -->
    <section class="bg-gray-50 min-h-screen py-12">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Search + filter bar -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <!-- Search -->
          <div class="relative flex-1 max-w-md">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input
              v-model="search"
              type="text"
              placeholder="Search courses..."
              class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20"
            />
            <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
              <X class="h-4 w-4" />
            </button>
          </div>

          <div class="flex items-center gap-3">
            <!-- Sort -->
            <select
              v-model="selectedSort"
              class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm focus:border-[#42b6c5] focus:outline-none"
            >
              <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>

            <!-- Filter toggle (mobile) -->
            <button
              @click="filtersOpen = !filtersOpen"
              class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:border-[#42b6c5] hover:text-[#42b6c5] transition-colors lg:hidden"
            >
              <SlidersHorizontal class="h-4 w-4" />
              Filters
              <span v-if="activeFiltersCount" class="flex h-5 w-5 items-center justify-center rounded-full bg-[#42b6c5] text-xs font-bold text-white">{{ activeFiltersCount }}</span>
            </button>

            <!-- Clear filters -->
            <button
              v-if="activeFiltersCount > 0 || search"
              type="button"
              @click="clearFilters"
              class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-red-500 transition-colors"
            >
              <X class="h-3.5 w-3.5" /> Clear
            </button>
          </div>
        </div>

        <div class="flex gap-8">
          <!-- Sidebar filters (desktop always visible, mobile collapsible) -->
          <aside
            :class="[
              'shrink-0 lg:block',
              filtersOpen ? 'block w-full lg:w-60' : 'hidden lg:block lg:w-60'
            ]"
          >
            <div class="sticky top-24 space-y-6">
              <!-- Category filter -->
              <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                <h3 class="mb-3 text-sm font-bold text-[#000928] uppercase tracking-wide">Category</h3>
                <div class="space-y-1">
                  <button
                    type="button"
                    @click="selectedCategory = ''"
                    :class="[
                      'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors',
                      !selectedCategory ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]' : 'text-gray-600 hover:bg-gray-50'
                    ]"
                  >
                    All Categories
                  </button>
                  <button
                    type="button"
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategory = cat.slug"
                    :class="[
                      'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors',
                      selectedCategory === cat.slug ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]' : 'text-gray-600 hover:bg-gray-50'
                    ]"
                  >
                    <span v-if="cat.icon" class="text-base">{{ cat.icon }}</span>
                    {{ cat.name }}
                  </button>
                </div>
              </div>

              <!-- Free only -->
              <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <label class="flex cursor-pointer items-center gap-3">
                  <input v-model="freeOnly" type="checkbox" class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]" />
                  <span class="text-sm font-medium text-gray-700">Free courses only</span>
                </label>
              </div>

              <!-- Level filter -->
              <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                <h3 class="mb-3 text-sm font-bold text-[#000928] uppercase tracking-wide">Level</h3>
                <div class="space-y-1">
                  <button
                    type="button"
                    @click="selectedLevel = ''"
                    :class="[
                      'flex w-full items-center rounded-lg px-3 py-2 text-sm transition-colors',
                      !selectedLevel ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]' : 'text-gray-600 hover:bg-gray-50'
                    ]"
                  >
                    All Levels
                  </button>
                  <button
                    type="button"
                    v-for="(label, value) in levelLabels"
                    :key="value"
                    @click="selectedLevel = value"
                    :class="[
                      'flex w-full items-center rounded-lg px-3 py-2 text-sm transition-colors',
                      selectedLevel === value ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]' : 'text-gray-600 hover:bg-gray-50'
                    ]"
                  >
                    {{ label }}
                  </button>
                </div>
              </div>
            </div>
          </aside>

          <!-- Course grid -->
          <div class="flex-1 min-w-0">
            <!-- Results summary -->
            <div class="mb-5 flex items-center justify-between">
              <p class="text-sm text-gray-500">
                Showing <span class="font-semibold text-[#000928]">{{ courses.data.length }}</span> of
                <span class="font-semibold text-[#000928]">{{ courses.total }}</span> courses
              </p>
            </div>

            <!-- Empty state -->
            <div v-if="courses.data.length === 0" class="rounded-2xl border-2 border-dashed border-gray-200 bg-white py-20 text-center">
              <BookOpen class="mx-auto mb-4 h-12 w-12 text-gray-300" />
              <h3 class="text-lg font-bold text-gray-700 mb-1">No courses found</h3>
              <p class="text-sm text-gray-500 mb-6">Try adjusting your search or filters.</p>
              <button @click="clearFilters" class="inline-flex items-center gap-2 rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#35919e] transition-colors">
                <X class="h-4 w-4" /> Clear Filters
              </button>
            </div>

            <!-- Grid -->
            <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="course in courses.data"
                :key="course.id"
                class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
              >
                <!-- Thumbnail -->
                <div class="relative h-44 overflow-hidden bg-gradient-to-br from-[#381998] to-[#42b6c5]">
                  <img
                    v-if="coverUrl(course.cover_image)"
                    :src="coverUrl(course.cover_image) ?? undefined"
                    :alt="course.title"
                    class="h-full w-full object-cover opacity-80 transition-transform duration-300 group-hover:scale-105"
                  />
                  <div v-else class="flex h-full items-center justify-center">
                    <BookOpen class="h-14 w-14 text-white/40" />
                  </div>

                  <!-- Featured badge -->
                  <div v-if="course.is_featured" class="absolute left-3 top-3 rounded-full bg-[#42b6c5] px-2.5 py-0.5 text-xs font-bold text-white shadow">
                    Featured
                  </div>

                  <!-- Category badge -->
                  <div v-if="course.category" class="absolute right-3 top-3 rounded-full bg-white/20 backdrop-blur-sm px-2.5 py-0.5 text-xs font-semibold text-white">
                    {{ course.category.name }}
                  </div>
                </div>

                <!-- Content -->
                <div class="flex flex-1 flex-col p-5">
                  <!-- Level -->
                  <span :class="['mb-2 inline-block self-start rounded-full px-2.5 py-0.5 text-xs font-semibold', levelColors[course.level]]">
                    {{ levelLabels[course.level] }}
                  </span>

                  <h3 class="mb-1.5 text-base font-bold leading-snug text-[#000928] line-clamp-2 group-hover:text-[#381998] transition-colors">
                    {{ course.title }}
                  </h3>
                  <p class="mb-3 text-xs text-gray-500 line-clamp-2">{{ course.short_description }}</p>

                  <!-- Instructor -->
                  <p v-if="course.instructor" class="mb-3 text-xs text-gray-400">
                    by <span class="font-medium text-gray-600">{{ course.instructor.name }}</span>
                  </p>

                  <!-- Meta row -->
                  <div class="mb-4 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                    <span v-if="course.duration" class="flex items-center gap-1">
                      <Clock class="h-3.5 w-3.5" /> {{ course.duration }}
                    </span>
                    <span class="flex items-center gap-1">
                      <Users class="h-3.5 w-3.5" /> {{ course.enrolled_count.toLocaleString() }} enrolled
                    </span>
                    <span v-if="parseFloat(course.rating) > 0" class="flex items-center gap-1">
                      <Star class="h-3.5 w-3.5 fill-yellow-400 text-yellow-400" />
                      {{ parseFloat(course.rating).toFixed(1) }}
                      <span class="text-gray-400">({{ course.review_count }})</span>
                    </span>
                  </div>

                  <!-- Price + CTA -->
                  <div class="mt-auto flex items-center justify-between">
                    <div>
                      <span class="text-lg font-bold text-[#000928]">
                        {{ formatPrice(course.price, course.sale_price) }}
                      </span>
                      <span v-if="course.sale_price && parseFloat(course.price) > 0" class="ml-2 text-sm text-gray-400 line-through">
                        {{ parseFloat(course.price).toLocaleString() }} XAF
                      </span>
                    </div>
                    <Link
                      :href="`/online-courses/${course.id}`"
                      class="rounded-xl bg-[#000928] px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#381998]"
                    >
                      View Course
                    </Link>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="courses.last_page > 1" class="mt-10 flex items-center justify-center gap-2">
              <template v-for="link in courses.links" :key="link.label">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  :class="[
                    'flex h-9 min-w-[36px] items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors',
                    link.active
                      ? 'bg-[#42b6c5] text-white shadow'
                      : 'border border-gray-200 bg-white text-gray-600 hover:border-[#42b6c5] hover:text-[#42b6c5]'
                  ]"
                ><span v-html="link.label" /></Link>
                <span
                  v-else
                  :class="[
                    'flex h-9 min-w-[36px] items-center justify-center rounded-lg px-3 text-sm font-medium',
                    link.active ? 'bg-[#42b6c5] text-white' : 'cursor-not-allowed text-gray-300'
                  ]"
                  v-html="link.label"
                />
              </template>
            </div>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
