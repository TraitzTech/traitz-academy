<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Award, BookMarked, BookOpen, CheckCircle2, Clock, PlayCircle, Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';

interface Instructor {
  id: number;
  name: string;
}

interface CourseCategory {
  id: number;
  name: string;
  slug: string;
}

interface Course {
  id: number;
  title: string;
  slug: string;
  short_description: string;
  cover_image: string | null;
  level: 'beginner' | 'intermediate' | 'advanced';
  duration: string | null;
  instructor: Instructor | null;
  category: CourseCategory | null;
  first_quiz_id?: number | null;
}

interface Enrollment {
  id: number;
  access_status: 'active' | 'suspended' | 'revoked' | 'completed';
  progress: number;
  payment_type: string | null;
  enrolled_at: string;
  completed_at: string | null;
  course: Course | null;
}

interface PaginatedEnrollments {
  data: Enrollment[];
  total: number;
  current_page: number;
  last_page: number;
  links: { url: string | null; label: string; active: boolean }[];
}

interface Stats {
  total: number;
  active: number;
  completed: number;
}

interface Filters {
  search?: string;
  status?: string;
}

const props = defineProps<{
  enrollments: PaginatedEnrollments;
  stats: Stats;
  filters: Filters;
}>();

const search = ref(props.filters.search ?? '');
const selectedStatus = ref(props.filters.status ?? '');

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

const statusConfig: Record<string, { label: string; class: string }> = {
  active: { label: 'In Progress', class: 'bg-blue-100 text-blue-700' },
  completed: { label: 'Completed', class: 'bg-green-100 text-green-700' },
  suspended: { label: 'Suspended', class: 'bg-yellow-100 text-yellow-700' },
  revoked: { label: 'Revoked', class: 'bg-red-100 text-red-700' },
};

const statusTabs = [
  { value: '', label: 'All' },
  { value: 'active', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
];

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (val) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => applyFilters(), val ? 400 : 0);
});

watch(selectedStatus, () => applyFilters());

function applyFilters() {
  router.get(
    '/dashboard/my-courses',
    {
      search: search.value || undefined,
      status: selectedStatus.value || undefined,
    },
    { preserveState: true, replace: true },
  );
}

function coverUrl(url: string | null) {
  if (!url) return null;
  if (url.startsWith('http')) return url;
  return `/storage/${url}`;
}

function enrolledDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
  <AppLayout :breadcrumbs="[{ title: 'My Courses', href: '/dashboard/my-courses' }]">
    <Head title="My Courses" />

    <div class="lms-page">
    <!-- Page header -->
    <div class="mb-6">
      <h1 class="lms-title">My Courses</h1>
      <p class="lms-subtitle">Track and continue your enrolled courses</p>
    </div>

    <!-- Stats row -->
    <div class="mb-6 grid grid-cols-3 gap-4">
      <div class="lms-panel p-4">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#381998]/10">
            <BookMarked class="h-5 w-5 text-[#381998]" />
          </div>
          <div>
            <p class="lms-title">{{ stats.total }}</p>
            <p class="text-xs text-gray-500">Total Enrolled</p>
          </div>
        </div>
      </div>
      <div class="lms-panel p-4">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#42b6c5]/10">
            <PlayCircle class="h-5 w-5 text-[#42b6c5]" />
          </div>
          <div>
            <p class="lms-title">{{ stats.active }}</p>
            <p class="text-xs text-gray-500">In Progress</p>
          </div>
        </div>
      </div>
      <div class="lms-panel p-4">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100">
            <CheckCircle2 class="h-5 w-5 text-green-600" />
          </div>
          <div>
            <p class="lms-title">{{ stats.completed }}</p>
            <p class="text-xs text-gray-500">Completed</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <!-- Status tabs -->
      <div class="flex items-center gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <button
          v-for="tab in statusTabs"
          :key="tab.value"
          @click="selectedStatus = tab.value"
          :class="[
            'rounded-lg px-4 py-1.5 text-sm font-medium transition-colors',
            selectedStatus === tab.value
              ? 'bg-[#000928] text-white shadow-sm'
              : 'text-gray-600 hover:text-[#000928] dark:text-gray-300 dark:hover:text-white'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Search -->
      <div class="relative w-full sm:w-64">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input
          v-model="search"
          type="text"
          placeholder="Search my courses..."
          class="w-full rounded-xl border border-gray-200 bg-white py-2 pl-10 pr-10 text-sm placeholder:text-gray-400 focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
        />
        <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
          <X class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-if="enrollments.data.length === 0"
      class="rounded-2xl border-2 border-dashed border-gray-200 bg-white py-24 text-center dark:bg-gray-800 dark:border-gray-700"
    >
      <BookOpen class="mx-auto mb-4 h-14 w-14 text-gray-300" />
      <h3 class="mb-1 text-lg font-bold text-gray-700 dark:text-gray-200">
        {{ search || selectedStatus ? 'No courses match your filters' : "You haven't enrolled in any courses yet" }}
      </h3>
      <p class="mb-6 text-sm text-gray-500">
        {{ search || selectedStatus ? 'Try clearing your filters.' : 'Browse available courses and start learning today.' }}
      </p>
      <Link
        v-if="!search && !selectedStatus"
        href="/dashboard/courses"
        class="inline-flex items-center gap-2 rounded-xl bg-[#000928] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#381998]"
      >
        <BookOpen class="h-4 w-4" /> Browse Courses
      </Link>
      <button
        v-else
        @click="search = ''; selectedStatus = ''"
        class="inline-flex items-center gap-2 rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35919e]"
      >
        <X class="h-4 w-4" /> Clear Filters
      </button>
    </div>

    <!-- Course cards -->
    <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="enrollment in enrollments.data"
        :key="enrollment.id"
        class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800 dark:border-gray-700"
      >
        <!-- Thumbnail -->
        <div class="relative h-40 overflow-hidden bg-linear-to-br from-[#381998] to-[#42b6c5]">
          <img
            v-if="enrollment.course && coverUrl(enrollment.course.cover_image)"
            :src="coverUrl(enrollment.course.cover_image)!"
            :alt="enrollment.course.title"
            class="h-full w-full object-cover opacity-80 transition-transform duration-300 group-hover:scale-105"
          />
          <div v-else class="flex h-full items-center justify-center">
            <BookOpen class="h-12 w-12 text-white/40" />
          </div>

          <!-- Status badge -->
          <div
            v-if="enrollment.access_status"
            :class="['absolute left-3 top-3 rounded-full px-2.5 py-0.5 text-xs font-bold shadow', statusConfig[enrollment.access_status]?.class ?? 'bg-gray-100 text-gray-700']"
          >
            {{ statusConfig[enrollment.access_status]?.label ?? enrollment.access_status }}
          </div>

          <!-- Completed icon -->
          <div v-if="enrollment.access_status === 'completed'" class="absolute right-3 top-3">
            <Award class="h-6 w-6 text-yellow-300 drop-shadow" />
          </div>
        </div>

        <!-- Content -->
        <div class="flex flex-1 flex-col p-4">
          <template v-if="enrollment.course">
            <div class="mb-2 flex items-center gap-2">
              <span
                v-if="enrollment.course.level"
                :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', levelColors[enrollment.course.level]]"
              >
                {{ levelLabels[enrollment.course.level] }}
              </span>
              <span v-if="enrollment.course.category" class="text-xs text-gray-400">
                {{ enrollment.course.category.name }}
              </span>
            </div>

            <h3 class="mb-1 text-sm font-bold leading-snug text-[#000928] line-clamp-2 transition-colors group-hover:text-[#381998] dark:text-white dark:group-hover:text-[#42b6c5]">
              {{ enrollment.course.title }}
            </h3>

            <p v-if="enrollment.course.instructor" class="mb-3 text-xs text-gray-400">
              by <span class="font-medium text-gray-600 dark:text-gray-300">{{ enrollment.course.instructor.name }}</span>
            </p>

            <!-- Progress bar -->
            <div class="mb-3">
              <div class="mb-1 flex items-center justify-between text-xs">
                <span class="text-gray-500">Progress</span>
                <span class="font-semibold text-[#42b6c5]">{{ enrollment.progress }}%</span>
              </div>
              <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                <div
                  class="h-full rounded-full bg-gradient-to-r from-[#381998] to-[#42b6c5] transition-all duration-500"
                  :style="{ width: `${enrollment.progress}%` }"
                />
              </div>
            </div>

            <!-- Meta -->
            <div class="mb-4 flex flex-wrap items-center gap-3 text-xs text-gray-500">
              <span v-if="enrollment.course.duration" class="flex items-center gap-1">
                <Clock class="h-3.5 w-3.5" /> {{ enrollment.course.duration }}
              </span>
              <span class="flex items-center gap-1">
                Enrolled {{ enrolledDate(enrollment.enrolled_at) }}
              </span>
            </div>
          </template>

          <!-- CTA -->
          <div class="mt-auto">
            <div class="grid grid-cols-1 gap-2" :class="{ 'sm:grid-cols-2': enrollment.course?.first_quiz_id }">
              <Link
                :href="enrollment.course ? `/dashboard/courses/${enrollment.course.id}` : '/dashboard/my-courses'"
                :class="[
                  'flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition-colors',
                  enrollment.access_status === 'completed'
                    ? 'bg-green-600 text-white hover:bg-green-700'
                    : 'bg-[#000928] text-white hover:bg-[#381998]'
                ]"
              >
                <PlayCircle v-if="enrollment.access_status !== 'completed'" class="h-4 w-4" />
                <Award v-else class="h-4 w-4" />
                {{ enrollment.access_status === 'completed' ? 'View Certificate' : 'Continue Learning' }}
              </Link>
              <Link
                v-if="enrollment.course?.first_quiz_id"
                :href="`/dashboard/quizzes/${enrollment.course.first_quiz_id}`"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#35919e]"
              >
                Take Quiz
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="enrollments.last_page > 1" class="mt-8 flex items-center justify-center gap-2">
      <template v-for="link in enrollments.links" :key="link.label">
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
  </AppLayout>
</template>
