<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Clock, Search, SlidersHorizontal, Star, Users, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { categoryIconFor } from '@/utils/categoryIcons';

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
];

const activeFiltersCount = computed(() =>
  [selectedCategory.value, selectedLevel.value, selectedSort.value].filter(Boolean).length
);

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (val) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => applyFilters(), val ? 400 : 0);
});

watch([selectedCategory, selectedLevel, selectedSort], () => applyFilters());

function applyFilters() {
  router.get(
    '/dashboard/courses',
    {
      search: search.value || undefined,
      category: selectedCategory.value || undefined,
      level: selectedLevel.value || undefined,
      sort: selectedSort.value || undefined,
    },
    { preserveState: true, replace: true },
  );
}

function clearFilters() {
  search.value = '';
  selectedCategory.value = '';
  selectedLevel.value = '';
  selectedSort.value = '';
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
  <AppLayout :breadcrumbs="[{ title: 'Courses', href: '/dashboard/courses' }]">
    <Head title="All Courses" />

    <div class="lms-page">
    <!-- Page header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="lms-title">All Courses</h1>
        <p class="lms-subtitle">
          Browse <span class="font-semibold text-[#381998]">{{ courses.total }}</span> available courses across
          <span class="font-semibold text-[#381998]">{{ categories.length }}</span> categories
        </p>
      </div>

      <!-- Search bar -->
      <div class="relative w-full sm:w-72">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input
          v-model="search"
          type="text"
          placeholder="Search courses..."
          class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
        />
        <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
          <X class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Toolbar: sort + filter toggle + clear -->
    <div class="mb-5 flex flex-wrap items-center gap-3">
      <select
        v-model="selectedSort"
        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
      >
        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>

      <button
        @click="filtersOpen = !filtersOpen"
        :class="[
          'flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium shadow-sm transition-colors',
          filtersOpen
            ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#42b6c5]'
            : 'border-gray-200 bg-white text-gray-700 hover:border-[#42b6c5] hover:text-[#42b6c5] dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200'
        ]"
      >
        <SlidersHorizontal class="h-4 w-4" />
        Filters
        <span v-if="activeFiltersCount" class="flex h-5 w-5 items-center justify-center rounded-full bg-[#42b6c5] text-xs font-bold text-white">
          {{ activeFiltersCount }}
        </span>
      </button>

      <button
        v-if="activeFiltersCount > 0 || search"
        @click="clearFilters"
        class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-red-500 transition-colors"
      >
        <X class="h-3.5 w-3.5" /> Clear all
      </button>
    </div>

    <div class="flex gap-6">
      <!-- Filter panel -->
      <aside
        v-if="filtersOpen"
        class="w-56 shrink-0"
      >
        <div class="sticky top-6 space-y-4">
          <!-- Category -->
          <div class="lms-panel p-4">
            <h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-[#000928] dark:text-white">Category</h3>
            <div class="space-y-0.5">
              <button
                @click="selectedCategory = ''"
                :class="[
                  'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors',
                  !selectedCategory ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700'
                ]"
              >
                All Categories
              </button>
              <button
                v-for="cat in categories"
                :key="cat.id"
                @click="selectedCategory = cat.slug"
                :class="[
                  'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors',
                  selectedCategory === cat.slug ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700'
                ]"
              >
                <component :is="categoryIconFor(cat.icon)" v-if="categoryIconFor(cat.icon)" class="h-4 w-4 shrink-0" />
                {{ cat.name }}
              </button>
            </div>
          </div>

          <!-- Level -->
          <div class="lms-panel p-4">
            <h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-[#000928] dark:text-white">Level</h3>
            <div class="space-y-0.5">
              <button
                @click="selectedLevel = ''"
                :class="[
                  'flex w-full items-center rounded-lg px-3 py-2 text-sm transition-colors',
                  !selectedLevel ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700'
                ]"
              >
                All Levels
              </button>
              <button
                v-for="(label, value) in levelLabels"
                :key="value"
                @click="selectedLevel = value"
                :class="[
                  'flex w-full items-center rounded-lg px-3 py-2 text-sm transition-colors',
                  selectedLevel === value ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700'
                ]"
              >
                {{ label }}
              </button>
            </div>
          </div>
        </div>
      </aside>

      <!-- Course grid -->
      <div class="min-w-0 flex-1">
        <!-- Empty state -->
        <div
          v-if="courses.data.length === 0"
          class="rounded-2xl border-2 border-dashed border-gray-200 bg-white py-20 text-center dark:bg-gray-800 dark:border-gray-700"
        >
          <BookOpen class="mx-auto mb-4 h-12 w-12 text-gray-300" />
          <h3 class="mb-1 text-lg font-bold text-gray-700 dark:text-gray-200">No courses found</h3>
          <p class="mb-6 text-sm text-gray-500">Try adjusting your search or filters.</p>
          <button
            @click="clearFilters"
            class="inline-flex items-center gap-2 rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35919e]"
          >
            <X class="h-4 w-4" /> Clear Filters
          </button>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="course in courses.data"
            :key="course.id"
            class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 dark:border-gray-700"
          >
            <!-- Thumbnail -->
            <div class="relative h-40 overflow-hidden bg-gradient-to-br from-[#381998] to-[#42b6c5]">
              <img
                v-if="coverUrl(course.cover_image)"
                :src="coverUrl(course.cover_image)!"
                :alt="course.title"
                class="h-full w-full object-cover opacity-80 transition-transform duration-300 group-hover:scale-105"
              />
              <div v-else class="flex h-full items-center justify-center">
                <BookOpen class="h-12 w-12 text-white/40" />
              </div>

              <div
                v-if="course.is_featured"
                class="absolute left-3 top-3 rounded-full bg-[#42b6c5] px-2.5 py-0.5 text-xs font-bold text-white shadow"
              >
                Featured
              </div>
              <div
                v-if="course.category"
                class="absolute right-3 top-3 rounded-full bg-white/20 backdrop-blur-sm px-2.5 py-0.5 text-xs font-semibold text-white"
              >
                {{ course.category.name }}
              </div>
            </div>

            <!-- Content -->
            <div class="flex flex-1 flex-col p-4">
              <span :class="['mb-2 inline-block self-start rounded-full px-2.5 py-0.5 text-xs font-semibold', levelColors[course.level]]">
                {{ levelLabels[course.level] }}
              </span>

              <h3 class="mb-1 text-sm font-bold leading-snug text-[#000928] line-clamp-2 transition-colors group-hover:text-[#381998] dark:text-white dark:group-hover:text-[#42b6c5]">
                {{ course.title }}
              </h3>
              <p class="mb-3 text-xs text-gray-500 line-clamp-2">{{ course.short_description }}</p>

              <p v-if="course.instructor" class="mb-3 text-xs text-gray-400">
                by <span class="font-medium text-gray-600 dark:text-gray-300">{{ course.instructor.name }}</span>
              </p>

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

              <div class="mt-auto flex items-center justify-between">
                <div>
                  <span class="text-base font-bold text-[#000928] dark:text-white">
                    {{ formatPrice(course.price, course.sale_price) }}
                  </span>
                  <span v-if="parseFloat(course.sale_price) > 0 && parseFloat(course.sale_price) < parseFloat(course.price)" class="ml-2 text-xs text-gray-400 line-through">
                    {{ parseFloat(course.price).toLocaleString() }} XAF
                  </span>
                </div>
                <Link
                  :href="`/dashboard/courses/${course.id}`"
                  class="rounded-xl bg-[#000928] px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#381998]"
                >
                  View
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="courses.last_page > 1" class="mt-8 flex items-center justify-center gap-2">
          <template v-for="link in courses.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="[
                'flex h-9 min-w-[36px] items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors',
                link.active
                  ? 'bg-[#42b6c5] text-white shadow'
                  : 'border border-gray-200 bg-white text-gray-600 hover:border-[#42b6c5] hover:text-[#42b6c5] dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300'
              ]"
            ><span v-html="link.label" /></Link>
            <span
              v-else
              :class="[
                'flex h-9 min-w-[36px] items-center justify-center rounded-lg px-3 text-sm font-medium',
                link.active ? 'bg-[#42b6c5] text-white' : 'cursor-not-allowed text-gray-300'
              ]"
            ><span v-html="link.label" /></span>
          </template>
        </div>
      </div>
    </div>
    </div>
  </AppLayout>
</template>
