<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { Banknote, BookOpen, CheckSquare, Clock, Eye, Users } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import AppLayout from '@/layouts/AppLayout.vue'

interface Category { id: number; name: string; slug: string }
interface Course {
  id: number
  title: string
  status: string
  cover_image: string | null
  level: string
  price: number
  enrollments_count: number
  sections_count: number
  instructor: { id: number; name: string } | null
  category: { id: number; name: string; slug: string; color: string | null; icon: string | null } | null
}
interface PaginatedCourses {
  data: Course[]
  current_page: number
  last_page: number
  from: number
  to: number
  total: number
  links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
  courses: PaginatedCourses
  categories: Category[]
  filters: { search?: string; status?: string; category?: string }
  stats: { total: number; published: number; pending: number; students: number }
}>()

defineOptions({ layout: AppLayout })

const search   = ref(props.filters.search ?? '')
const status   = ref(props.filters.status ?? '')
const category = ref(props.filters.category ?? '')

const applyFilters = debounce(() => {
  router.get('/admin/courses', {
    search:   search.value || undefined,
    status:   status.value || undefined,
    category: category.value || undefined,
  }, { preserveState: true, replace: true })
}, 300)

watch([search, status, category], applyFilters)

// Approve / Reject modals
const approveTarget  = ref<Course | null>(null)
const rejectTarget   = ref<Course | null>(null)
const archiveTarget  = ref<Course | null>(null)

function confirmApprove() {
  if (!approveTarget.value) return
  router.post(`/admin/courses/${approveTarget.value.id}/approve`, {}, {
    onSuccess: () => { approveTarget.value = null },
  })
}
function confirmReject() {
  if (!rejectTarget.value) return
  router.post(`/admin/courses/${rejectTarget.value.id}/reject`, {}, {
    onSuccess: () => { rejectTarget.value = null },
  })
}
function confirmArchive() {
  if (!archiveTarget.value) return
  router.post(`/admin/courses/${archiveTarget.value.id}/archive`, {}, {
    onSuccess: () => { archiveTarget.value = null },
  })
}

const statusLabels: Record<string, string> = {
  draft: 'Draft', pending_review: 'Pending Review',
  published: 'Published', archived: 'Archived',
}
const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
  pending_review: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  archived: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
}

function coverSrc(path: string | null) {
  if (!path) return null
  return path.startsWith('http') ? path : `/storage/${path}`
}
</script>

<template>
  <div>
    <Head title="Courses — Admin" />

    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Courses</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage and review all courses on the platform</p>
    </div>

    <!-- Stat cards (adapted from prototype) -->
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-5">
      <div class="rounded-lg border-l-4 border-[#381998] bg-white p-4 shadow dark:bg-gray-800 lg:p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 lg:text-sm">Total Courses</p>
            <p class="mt-1 text-2xl font-bold text-[#000928] dark:text-gray-100 lg:text-3xl">{{ stats.total }}</p>
          </div>
          <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30 lg:p-3">
            <BookOpen class="h-5 w-5 text-purple-600 dark:text-purple-400 lg:h-7 lg:w-7" />
          </div>
        </div>
      </div>
      <div class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow dark:bg-gray-800 lg:p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 lg:text-sm">Published</p>
            <p class="mt-1 text-2xl font-bold text-[#000928] dark:text-gray-100 lg:text-3xl">{{ stats.published }}</p>
          </div>
          <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30 lg:p-3">
            <CheckSquare class="h-5 w-5 text-green-600 dark:text-green-400 lg:h-7 lg:w-7" />
          </div>
        </div>
      </div>
      <div class="rounded-lg border-l-4 border-amber-400 bg-white p-4 shadow dark:bg-gray-800 lg:p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 lg:text-sm">Pending Approval</p>
            <p class="mt-1 text-2xl font-bold text-[#000928] dark:text-gray-100 lg:text-3xl">{{ stats.pending }}</p>
          </div>
          <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/30 lg:p-3">
            <Clock class="h-5 w-5 text-amber-500 dark:text-amber-400 lg:h-7 lg:w-7" />
          </div>
        </div>
      </div>
      <div class="rounded-lg border-l-4 border-[#42b6c5] bg-white p-4 shadow dark:bg-gray-800 lg:p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 lg:text-sm">Total Students</p>
            <p class="mt-1 text-2xl font-bold text-[#000928] dark:text-gray-100 lg:text-3xl">{{ stats.students }}</p>
          </div>
          <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30 lg:p-3">
            <Users class="h-5 w-5 text-blue-600 dark:text-blue-400 lg:h-7 lg:w-7" />
          </div>
        </div>
      </div>
    </div>

    <!-- Filters (adapted from prototype tab bar + our search pattern) -->
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <!-- Status tabs -->
      <div class="flex w-fit gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
        <button
          v-for="[val, label] in [['','All'],['published','Published'],['draft','Draft'],['pending_review','Pending'],['archived','Archived']]"
          :key="val"
          @click="status = val"
          :class="[
            'rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
            status === val
              ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white'
              : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
          ]"
        >{{ label }}</button>
      </div>

      <!-- Search + category -->
      <div class="flex gap-2">
        <input
          v-model="search"
          type="text"
          placeholder="Search courses…"
          class="w-48 rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
        />
        <select
          v-model="category"
          class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
        >
          <option value="">All categories</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }}</option>
        </select>
      </div>
    </div>

    <!-- Course list (adapted from prototype rows) -->
    <div class="rounded-xl bg-white shadow dark:bg-gray-800">
      <div v-if="courses.data.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
        <BookOpen class="mb-3 h-12 w-12 text-gray-300" />
        <p class="font-medium text-gray-500 dark:text-gray-400">No courses found</p>
        <p class="mt-1 text-sm text-gray-400">Try adjusting your filters.</p>
      </div>

      <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <div
          v-for="course in courses.data"
          :key="course.id"
          class="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40"
        >
          <!-- Thumbnail -->
          <div class="h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-[#381998] to-[#42b6c5]">
            <img
              v-if="coverSrc(course.cover_image)"
              :src="coverSrc(course.cover_image)!"
              :alt="course.title"
              class="h-full w-full object-cover"
            />
          </div>

          <!-- Info -->
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ course.title }}</span>
              <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', statusColors[course.status]]">
                {{ statusLabels[course.status] }}
              </span>
            </div>
            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
              {{ course.instructor?.name ?? '—' }}
              · {{ course.enrollments_count }} students
              · {{ course.sections_count }} sections
              <template v-if="course.category"> · {{ course.category.name }}</template>
              · {{ course.level }}
            </p>
          </div>

          <!-- Actions -->
          <div class="flex shrink-0 items-center gap-2">
            <button
              v-if="course.status === 'pending_review'"
              @click="approveTarget = course"
              class="rounded-lg bg-green-100 px-3 py-1.5 text-xs font-semibold text-green-700 transition-colors hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50"
            >
              Review
            </button>
            <button
              v-if="course.status === 'published'"
              @click="archiveTarget = course"
              class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400"
            >
              Archive
            </button>
            <Link
              :href="`/admin/courses/${course.id}/pricing`"
              class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-900/10"
              title="Pricing & instalments"
            >
              <Banknote class="h-4 w-4" />
            </Link>
            <Link
              :href="`/admin/courses/${course.id}`"
              class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-500 dark:hover:bg-blue-900/10"
              title="Preview"
            >
              <Eye class="h-4 w-4" />
            </Link>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="courses.last_page > 1" class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Showing {{ courses.from }}–{{ courses.to }} of {{ courses.total }}
        </p>
        <div class="flex gap-1">
          <template v-for="link in courses.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="[
                'flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-xs font-medium transition-colors',
                link.active
                  ? 'bg-[#381998] text-white'
                  : 'border border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700',
              ]"
              v-html="link.label"
            />
          </template>
        </div>
      </div>
    </div>

    <!-- Approve confirmation -->
    <ConfirmationModal
      :open="!!approveTarget"
      title="Approve & Publish Course"
      :description="approveTarget ? `Approve &quot;${approveTarget.title}&quot;? It will go live immediately.` : ''"
      confirm-text="Yes, Publish"
      cancel-text="Cancel"
      variant="default"
      @update:open="(val) => { if (!val) approveTarget = null }"
      @confirm="confirmApprove"
    />

    <!-- Reject confirmation -->
    <ConfirmationModal
      :open="!!rejectTarget"
      title="Reject Course"
      :description="rejectTarget ? `Return &quot;${rejectTarget.title}&quot; to the tutor as a draft?` : ''"
      confirm-text="Yes, Reject"
      cancel-text="Cancel"
      variant="destructive"
      @update:open="(val) => { if (!val) rejectTarget = null }"
      @confirm="confirmReject"
    />

    <!-- Archive confirmation -->
    <ConfirmationModal
      :open="!!archiveTarget"
      title="Archive Course"
      :description="archiveTarget ? `Archive &quot;${archiveTarget.title}&quot;? It will no longer be visible to students.` : ''"
      confirm-text="Yes, Archive"
      cancel-text="Cancel"
      variant="destructive"
      @update:open="(val) => { if (!val) archiveTarget = null }"
      @confirm="confirmArchive"
    />
  </div>
</template>
