<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { BookOpen, CheckSquare, Clock, Edit2, PlusCircle, Search, Trash2, Users } from 'lucide-vue-next'
import { debounce } from 'lodash-es'
import { ref, watch } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import AppLayout from '@/layouts/AppLayout.vue'

interface Category { id: number; name: string; slug: string }

interface Course {
  id: number
  title: string
  slug: string
  cover_image: string | null
  level: 'beginner' | 'intermediate' | 'advanced'
  status: 'draft' | 'pending_review' | 'published' | 'archived'
  price: string
  duration: string | null
  enrollments_count: number
  category: Category | null
  created_at: string
}

interface PaginatedCourses {
  data: Course[]
  total: number
  current_page: number
  last_page: number
  links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
  courses: PaginatedCourses
  filters: { search?: string; status?: string }
  stats: { total: number; active: number; pending: number; draft: number; students: number }
}>()

defineOptions({ layout: AppLayout })

// ── Filters ───────────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')

const applyFilters = debounce(() => {
  router.get('/tutor/courses', {
    search: search.value || undefined,
    status: status.value || undefined,
  }, { preserveState: true, replace: true })
}, 300)

watch([search, status], applyFilters)

// ── Delete ────────────────────────────────────────────────────────────────────
const deletingId   = ref<number | null>(null)
const deleteTarget = ref<Course | null>(null)

function confirmDelete(course: Course) {
  if (course.status === 'published') return
  deleteTarget.value = course
}

function doDelete() {
  if (!deleteTarget.value) return
  deletingId.value = deleteTarget.value.id
  router.delete(`/tutor/courses/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
    onFinish:  () => { deletingId.value = null },
  })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const statusConfig: Record<string, { label: string; badge: string }> = {
  draft:          { label: 'Draft',          badge: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' },
  pending_review: { label: 'Pending Review', badge: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' },
  published:      { label: 'Published',      badge: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
  archived:       { label: 'Archived',       badge: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' },
}

const levelLabels: Record<string, string> = {
  beginner: 'Beginner', intermediate: 'Intermediate', advanced: 'Advanced',
}

function coverUrl(url: string | null) {
  if (!url) return null
  return url.startsWith('http') ? url : `/storage/${url}`
}

function formatPrice(price: string) {
  const p = parseFloat(price)
  return p === 0 ? 'Free' : `${p.toLocaleString()} XAF`
}
</script>

<template>
  <div>
    <Head title="My Courses — Tutor" />

    <!-- Page header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">My Courses</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage all courses you teach</p>
      </div>
      <Link
        href="/tutor/courses/create"
        class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928]"
      >
        <PlusCircle class="h-4 w-4" /> New Course
      </Link>
    </div>

    <!-- ── Stat cards (adapted from prototype CourseManagementPage) ── -->
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-5">
      <!-- Total -->
      <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
        <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-[#381998]/10">
          <BookOpen class="h-5 w-5 text-[#381998]" />
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Total Courses</p>
      </div>
      <!-- Published -->
      <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
        <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
          <CheckSquare class="h-5 w-5 text-green-600 dark:text-green-400" />
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.active }}</p>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Published</p>
      </div>
      <!-- Pending -->
      <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
        <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-yellow-100 dark:bg-yellow-900/30">
          <Clock class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.pending }}</p>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Pending Review</p>
      </div>
      <!-- Draft -->
      <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
        <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
          <Edit2 class="h-5 w-5 text-gray-500 dark:text-gray-400" />
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.draft }}</p>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Drafts</p>
      </div>
      <!-- Students -->
      <div class="col-span-2 rounded-xl bg-white p-4 shadow dark:bg-gray-800 sm:col-span-4 lg:col-span-1">
        <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-[#42b6c5]/10">
          <Users class="h-5 w-5 text-[#42b6c5]" />
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.students }}</p>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Total Students</p>
      </div>
    </div>

    <!-- ── Search & status filter ── -->
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input
          v-model="search"
          type="text"
          placeholder="Search courses…"
          class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-4 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
        />
      </div>
      <select
        v-model="status"
        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
      >
        <option value="">All Statuses</option>
        <option value="draft">Draft</option>
        <option value="pending_review">Pending Review</option>
        <option value="published">Published</option>
        <option value="archived">Archived</option>
      </select>
    </div>

    <!-- ── Empty state ── -->
    <div
      v-if="courses.data.length === 0"
      class="rounded-2xl border-2 border-dashed border-gray-200 bg-white py-24 text-center dark:border-gray-700 dark:bg-gray-800"
    >
      <BookOpen class="mx-auto mb-4 h-14 w-14 text-gray-300" />
      <h3 class="mb-1 text-lg font-bold text-gray-700 dark:text-gray-200">
        {{ search || status ? 'No courses match your filters' : 'No courses yet' }}
      </h3>
      <p class="mb-6 text-sm text-gray-500">
        {{ search || status ? 'Try adjusting your search or filters.' : 'Create your first course and start sharing your knowledge.' }}
      </p>
      <Link
        v-if="!search && !status"
        href="/tutor/courses/create"
        class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928]"
      >
        <PlusCircle class="h-4 w-4" /> Create Course
      </Link>
    </div>

    <!-- ── Course grid ── -->
    <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="course in courses.data"
        :key="course.id"
        class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
      >
        <!-- Thumbnail -->
        <div class="relative h-40 overflow-hidden bg-linear-to-br from-[#381998] to-[#42b6c5]">
          <img
            v-if="coverUrl(course.cover_image)"
            :src="coverUrl(course.cover_image) ?? undefined"
            :alt="course.title"
            class="h-full w-full object-cover opacity-80"
          />
          <div v-else class="flex h-full items-center justify-center">
            <BookOpen class="h-12 w-12 text-white/40" />
          </div>
          <!-- Status badge -->
          <span :class="['absolute left-3 top-3 rounded-full px-2.5 py-0.5 text-xs font-bold shadow', statusConfig[course.status]?.badge]">
            {{ statusConfig[course.status]?.label }}
          </span>
        </div>

        <!-- Content -->
        <div class="flex flex-1 flex-col p-4">
          <div class="mb-1 flex items-center gap-2 text-xs text-gray-400">
            <span v-if="course.category">{{ course.category.name }}</span>
            <span class="text-gray-300" v-if="course.category">·</span>
            <span>{{ levelLabels[course.level] }}</span>
          </div>

          <h3 class="mb-3 line-clamp-2 text-sm font-bold leading-snug text-[#000928] dark:text-white">
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
              class="flex items-center justify-center rounded-xl border border-red-200 p-2 text-red-400 transition-colors hover:border-red-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-50 dark:border-red-800 dark:hover:bg-red-900/20"
              title="Delete course"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Pagination ── -->
    <div v-if="courses.last_page > 1" class="mt-8 flex items-center justify-center gap-2">
      <template v-for="link in courses.links" :key="link.label">
        <Link
          v-if="link.url"
          :href="link.url"
          :class="[
            'flex h-9 min-w-[36px] items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors',
            link.active
              ? 'bg-[#42b6c5] text-white shadow'
              : 'border border-gray-200 bg-white text-gray-600 hover:border-[#42b6c5] hover:text-[#42b6c5] dark:border-gray-600 dark:bg-gray-800',
          ]"
        ><span v-html="link.label" /></Link>
        <span
          v-else
          class="flex h-9 min-w-[36px] cursor-not-allowed items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-300"
          v-html="link.label"
        />
      </template>
    </div>

    <!-- ── Delete confirmation (uses project's ConfirmationModal) ── -->
    <ConfirmationModal
      :open="!!deleteTarget"
      title="Delete Course"
      :description="deleteTarget ? `Are you sure you want to delete &quot;${deleteTarget.title}&quot;? This action cannot be undone.` : ''"
      confirm-text="Delete"
      cancel-text="Cancel"
      variant="destructive"
      @update:open="if (!$event) deleteTarget = null"
      @confirm="doDelete"
    />
  </div>
</template>
