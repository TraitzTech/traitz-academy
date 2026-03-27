<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, CheckSquare, Clock, Edit2, PlusCircle, Trash2, Users } from 'lucide-vue-next';
import { ref } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Category {
  id: number;
  name: string;
  slug: string;
}

interface Course {
  id: number;
  title: string;
  slug: string;
  cover_image: string | null;
  level: 'beginner' | 'intermediate' | 'advanced';
  status: 'draft' | 'pending_review' | 'published' | 'archived';
  price: string;
  duration: string | null;
  enrollments_count: number;
  category: Category | null;
  created_at: string;
}

interface PaginatedCourses {
  data: Course[];
  total: number;
  current_page: number;
  last_page: number;
  links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
  courses: PaginatedCourses;
  stats: { total: number; active: number; pending: number; students: number };
}>();

const deletingId    = ref<number | null>(null);
const deleteTarget  = ref<Course | null>(null);

const statusConfig: Record<string, { label: string; class: string }> = {
  draft:          { label: 'Draft',          class: 'bg-gray-100 text-gray-600' },
  pending_review: { label: 'Pending Review', class: 'bg-yellow-100 text-yellow-700' },
  published:      { label: 'Published',      class: 'bg-green-100 text-green-700' },
  archived:       { label: 'Archived',       class: 'bg-red-100 text-red-600' },
};

const levelLabels: Record<string, string> = {
  beginner: 'Beginner',
  intermediate: 'Intermediate',
  advanced: 'Advanced',
};

function coverUrl(url: string | null) {
  if (!url) return null;
  if (url.startsWith('http')) return url;
  return `/storage/${url}`;
}

function formatPrice(price: string) {
  const p = parseFloat(price);
  return p === 0 ? 'Free' : `${p.toLocaleString()} XAF`;
}

function confirmDelete(course: Course) {
  if (course.status === 'published') return;
  deleteTarget.value = course;
}

function doDelete() {
  if (!deleteTarget.value) return;
  deletingId.value = deleteTarget.value.id;
  router.delete(`/tutor/courses/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null; },
    onFinish:  () => { deletingId.value = null; },
  });
}
</script>

<template>
  <AppLayout :breadcrumbs="[{ title: 'My Courses', href: '/tutor/courses' }]">
    <Head title="My Courses — Tutor" />

    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-[#000928] dark:text-white">My Courses</h1>
        <p class="mt-1 text-sm text-gray-500">Manage courses you teach</p>
      </div>
      <Link
        href="/tutor/courses/create"
        class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928]"
      >
        <PlusCircle class="h-4 w-4" />
        New Course
      </Link>
    </div>

    <!-- Empty state -->
    <div
      v-if="courses.data.length === 0"
      class="rounded-2xl border-2 border-dashed border-gray-200 bg-white py-24 text-center dark:bg-gray-800 dark:border-gray-700"
    >
      <BookOpen class="mx-auto mb-4 h-14 w-14 text-gray-300" />
      <h3 class="mb-1 text-lg font-bold text-gray-700 dark:text-gray-200">No courses yet</h3>
      <p class="mb-6 text-sm text-gray-500">Create your first course and start sharing your knowledge.</p>
      <Link
        href="/tutor/courses/create"
        class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928]"
      >
        <PlusCircle class="h-4 w-4" /> Create Course
      </Link>
    </div>

    <!-- Course grid -->
    <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="course in courses.data"
        :key="course.id"
        class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-md dark:bg-gray-800 dark:border-gray-700"
      >
        <!-- Thumbnail -->
        <div class="relative h-40 overflow-hidden bg-gradient-to-br from-[#381998] to-[#42b6c5]">
          <img
            v-if="coverUrl(course.cover_image)"
            :src="coverUrl(course.cover_image)!"
            :alt="course.title"
            class="h-full w-full object-cover opacity-80"
          />
          <div v-else class="flex h-full items-center justify-center">
            <BookOpen class="h-12 w-12 text-white/40" />
          </div>

          <!-- Status badge -->
          <div
            :class="['absolute left-3 top-3 rounded-full px-2.5 py-0.5 text-xs font-bold shadow', statusConfig[course.status]?.class]"
          >
            {{ statusConfig[course.status]?.label }}
          </div>
        </div>

        <!-- Content -->
        <div class="flex flex-1 flex-col p-4">
          <div class="mb-1 flex items-center gap-2 text-xs text-gray-400">
            <span v-if="course.category">{{ course.category.name }}</span>
            <span class="text-gray-300">·</span>
            <span>{{ levelLabels[course.level] }}</span>
          </div>

          <h3 class="mb-3 text-sm font-bold leading-snug text-[#000928] line-clamp-2 dark:text-white">
            {{ course.title }}
          </h3>

          <div class="mb-4 flex flex-wrap items-center gap-3 text-xs text-gray-500">
            <span class="flex items-center gap-1">
              <Users class="h-3.5 w-3.5" /> {{ course.enrollments_count }} enrolled
            </span>
            <span v-if="course.duration" class="flex items-center gap-1">
              <Clock class="h-3.5 w-3.5" /> {{ course.duration }}
            </span>
            <span class="font-semibold text-[#381998]">{{ formatPrice(course.price) }}</span>
          </div>

          <div class="mt-auto flex items-center gap-2">
            <Link
              :href="`/tutor/courses/${course.id}/edit`"
              class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-[#381998] py-2 text-xs font-semibold text-[#381998] transition-colors hover:bg-[#381998] hover:text-white"
            >
              <Edit2 class="h-3.5 w-3.5" /> Edit
            </Link>
            <button
              v-if="course.status !== 'published'"
              @click="confirmDelete(course)"
              :disabled="deletingId === course.id"
              class="flex items-center justify-center rounded-xl border border-red-200 p-2 text-red-400 transition-colors hover:border-red-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-50"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </button>
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
              : 'border border-gray-200 bg-white text-gray-600 hover:border-[#42b6c5] hover:text-[#42b6c5] dark:bg-gray-800 dark:border-gray-700'
          ]"
          v-html="link.label"
        />
        <span
          v-else
          class="flex h-9 min-w-[36px] cursor-not-allowed items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-300"
          v-html="link.label"
        />
      </template>
    </div>
  </AppLayout>
</template>
