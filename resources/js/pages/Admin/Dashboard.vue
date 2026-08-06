<script setup>
import { router } from '@inertiajs/vue3'
import { BookOpen, Calendar, CheckCircle2, Clock, DollarSign, FileClock, Users } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  stats: { type: Object, required: true },
  recentApplications: { type: Array, required: true },
  pendingCourses: { type: Array, default: () => [] },
  recentLmsEnrollments: { type: Array, default: () => [] },
})

defineOptions({ layout: AppLayout })

// ─── Approve / Reject ────────────────────────────────────────────────────────

const showApproveModal = ref(false)
const showRejectModal  = ref(false)
const showDeleteModal  = ref(false)
const courseTarget     = ref(null)

function approveCourse(course) {
  courseTarget.value    = course
  showApproveModal.value = true
}

function rejectCourse(course) {
  courseTarget.value   = course
  showRejectModal.value = true
}

function confirmApprove() {
  router.post(`/admin/courses/${courseTarget.value.id}/approve`, {}, {
    preserveScroll: true,
    onFinish: () => { showApproveModal.value = false; courseTarget.value = null },
  })
}

function confirmReject() {
  router.post(`/admin/courses/${courseTarget.value.id}/reject`, {}, {
    preserveScroll: true,
    onFinish: () => { showRejectModal.value = false; courseTarget.value = null },
  })
}

function confirmDelete() {
  router.delete(`/admin/courses/${courseTarget.value.id}`, {
    preserveScroll: true,
    onFinish: () => { showDeleteModal.value = false; courseTarget.value = null },
  })
}

// ─── Formatters ──────────────────────────────────────────────────────────────

const formatDate = (date) => new Date(date).toLocaleDateString('en-US', {
  year: 'numeric', month: 'short', day: 'numeric',
})

const formatMoney = (amount) =>
  new Intl.NumberFormat('en-CM', { maximumFractionDigits: 0 }).format(amount)

// ─── Shared status badge (was 3 copy-pasted ternary chains) ────────────────

const applicationBadgeClass = {
  pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
  accepted: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
}
function applicationStatusClass(status) {
  return applicationBadgeClass[status] || 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
}

const enrollmentBadgeClass = {
  active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
  completed: 'bg-[#42b6c5]/15 text-[#2a8a96] dark:bg-[#42b6c5]/20 dark:text-[#7ee8f9]',
  suspended: 'bg-amber-100 text-amber-900 dark:bg-amber-900/30 dark:text-amber-300',
}
function enrollmentStatusClass(status) {
  return enrollmentBadgeClass[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
}

// Stat cards: one brand-tinted icon-chip system instead of six unrelated hues,
// laid out as one uniform 6-up grid (was 5 cards + an odd col-span-2 tack-on).
const allStatCards = computed(() => [
  { key: 'total_programs', label: 'Programs', icon: BookOpen, tint: 'accent', value: props.stats.total_programs },
  { key: 'total_events', label: 'Events', icon: Calendar, tint: 'secondary', value: props.stats.total_events },
  { key: 'pending_applications', label: 'Pending', icon: FileClock, tint: 'amber', value: props.stats.pending_applications },
  { key: 'total_users', label: 'Users', icon: Users, tint: 'primary', value: props.stats.total_users },
  { key: 'pending_courses', label: 'Pending Courses', icon: Clock, tint: 'amber', value: props.stats.pending_courses },
  { key: 'total_collected', label: props.stats.collected_label || 'Collected', icon: DollarSign, tint: 'accent', value: formatMoney(props.stats.total_collected || 0) },
])
const tintClass = {
  accent: 'bg-[#42b6c5]/10 text-[#2a8a96] dark:bg-[#42b6c5]/15 dark:text-[#7ee8f9]',
  secondary: 'bg-[#381998]/10 text-[#381998] dark:bg-[#381998]/20 dark:text-purple-300',
  primary: 'bg-[#000928]/10 text-[#000928] dark:bg-white/10 dark:text-gray-200',
  amber: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
}
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div>
      <h1 class="text-2xl font-bold text-[#000928] dark:text-white lg:text-3xl">Dashboard</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Welcome back! Here's an overview of your system.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
      <div
        v-for="card in allStatCards"
        :key="card.key"
        class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
      >
        <div :class="['inline-flex h-10 w-10 items-center justify-center rounded-xl transition-transform group-hover:scale-105', tintClass[card.tint]]">
          <component :is="card.icon" class="h-5 w-5" />
        </div>
        <p class="mt-4 text-2xl font-bold tabular-nums text-[#000928] dark:text-white">{{ card.value }}</p>
        <p class="mt-0.5 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ card.label }}</p>
      </div>
    </div>

    <!-- LMS (online courses) -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">LMS learners</p>
        <p class="mt-1 text-3xl font-bold text-[#000928] dark:text-white">{{ stats.lms_distinct_learners ?? 0 }}</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Unique users with at least one non-revoked enrollment</p>
      </div>
      <div class="flex items-start justify-between gap-3 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">LMS enrollments</p>
          <p class="mt-1 text-3xl font-bold text-[#000928] dark:text-white">{{ stats.lms_total_enrollments ?? 0 }}</p>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Active enrollment rows (excludes revoked)</p>
        </div>
        <a
          href="/admin/enrollments"
          class="shrink-0 rounded-xl bg-[#42b6c5] px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#35919e]"
        >
          View all
        </a>
      </div>
    </div>

    <!-- LMS Reports -->
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">LMS Reports</p>
      <div class="flex flex-wrap gap-2">
        <a v-for="report in [
          { href: '/admin/lms/platform-summary', label: 'Platform summary' },
          { href: '/admin/lms/course-reports', label: 'Per-course report' },
          { href: '/admin/lms/user-reports', label: 'Per-user report' },
          { href: '/admin/lms/discussions', label: 'Discussions' },
        ]" :key="report.href" :href="report.href"
          class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:border-[#42b6c5] hover:text-[#42b6c5] dark:border-gray-600 dark:text-gray-300"
        >
          {{ report.label }}
        </a>
      </div>
    </div>

    <!-- Recent LMS enrollments -->
    <div
      v-if="recentLmsEnrollments && recentLmsEnrollments.length > 0"
      class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
        <h2 class="text-sm font-bold text-[#000928] dark:text-white">Recent course enrollments</h2>
        <a href="/admin/enrollments" class="text-xs font-semibold text-[#42b6c5] hover:text-[#35919e]">View all</a>
      </div>
      <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
        <div
          v-for="row in recentLmsEnrollments"
          :key="row.id"
          class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="min-w-0">
            <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ row.student_name }}</p>
            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ row.course_title }} · {{ row.tutor_name }}</p>
          </div>
          <div class="flex shrink-0 items-center gap-3">
            <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', enrollmentStatusClass(row.access_status)]">
              {{ row.access_status }}
            </span>
            <span class="text-xs text-gray-400">{{ row.student_email }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Applications -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
        <h2 class="text-sm font-bold text-[#000928] dark:text-white">Recent Applications</h2>
      </div>

      <!-- Mobile Card View -->
      <div v-if="recentApplications.length > 0" class="divide-y divide-gray-50 dark:divide-gray-700/50 lg:hidden">
        <div v-for="app in recentApplications" :key="app.id" class="p-4">
          <div class="mb-2 flex items-start justify-between">
            <div>
              <p class="font-semibold text-gray-900 dark:text-gray-100">{{ app.first_name }} {{ app.last_name }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ app.email }}</p>
            </div>
            <span :class="['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold', applicationStatusClass(app.status)]">
              {{ app.status.charAt(0).toUpperCase() + app.status.slice(1) }}
            </span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="max-w-[60%] truncate text-gray-600 dark:text-gray-400">{{ app.program.title }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(app.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Desktop Table View -->
      <div v-if="recentApplications.length > 0" class="hidden overflow-x-auto lg:block">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
            <tr>
              <th class="px-5 py-3 font-semibold">Name</th>
              <th class="px-5 py-3 font-semibold">Program</th>
              <th class="px-5 py-3 font-semibold">Email</th>
              <th class="px-5 py-3 font-semibold">Status</th>
              <th class="px-5 py-3 font-semibold">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
            <tr v-for="app in recentApplications" :key="app.id" class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
              <td class="px-5 py-3 font-semibold text-gray-900 dark:text-gray-100">{{ app.first_name }} {{ app.last_name }}</td>
              <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ app.program.title }}</td>
              <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ app.email }}</td>
              <td class="px-5 py-3">
                <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', applicationStatusClass(app.status)]">
                  {{ app.status.charAt(0).toUpperCase() + app.status.slice(1) }}
                </span>
              </td>
              <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(app.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="recentApplications.length === 0" class="px-5 py-10 text-center">
        <p class="text-gray-500 dark:text-gray-400">No applications yet</p>
      </div>
    </div>

    <!-- Pending Courses for Review -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
        <div class="flex items-center gap-2.5">
          <h2 class="text-sm font-bold text-[#000928] dark:text-white">Pending Course Reviews</h2>
          <span v-if="pendingCourses.length > 0" class="inline-flex items-center justify-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
            {{ pendingCourses.length }}
          </span>
        </div>
        <div class="flex items-center gap-2">
          <a href="/admin/courses" class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors hover:border-gray-400 dark:border-gray-600 dark:text-gray-300">
            Manage Courses
          </a>
          <a href="/admin/courses?status=pending_review" class="rounded-xl bg-[#42b6c5] px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#35919e]">
            Pending Queue
          </a>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="pendingCourses.length === 0" class="px-6 py-12 text-center">
        <CheckCircle2 class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" />
        <p class="text-sm text-gray-500 dark:text-gray-400">No courses awaiting review — you're all caught up!</p>
        <div class="mt-4 flex items-center justify-center gap-2">
          <a href="/admin/courses" class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors hover:border-gray-400 dark:border-gray-600 dark:text-gray-300">
            Open Courses
          </a>
          <a href="/admin/course-categories" class="rounded-xl border border-[#381998]/30 px-3 py-1.5 text-xs font-semibold text-[#381998] transition-colors hover:bg-[#381998]/5 dark:border-[#42b6c5]/40 dark:text-[#42b6c5]">
            Manage Categories
          </a>
        </div>
      </div>

      <!-- Course cards -->
      <div v-else class="divide-y divide-gray-50 dark:divide-gray-700/50">
        <div
          v-for="course in pendingCourses"
          :key="course.id"
          class="flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-700/30 sm:flex-row sm:items-center sm:justify-between"
        >
          <!-- Course info -->
          <div class="flex min-w-0 items-start gap-4">
            <div class="h-14 w-20 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-[#381998] to-[#42b6c5]">
              <img
                v-if="course.cover_image"
                :src="course.cover_image.startsWith('http') ? course.cover_image : `/storage/${course.cover_image}`"
                :alt="course.title"
                class="h-full w-full object-cover"
              />
            </div>
            <div class="min-w-0">
              <p class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ course.title }}</p>
              <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span v-if="course.instructor">by {{ course.instructor.name }}</span>
                <span v-if="course.category" class="rounded-full bg-[#381998]/10 px-2 py-0.5 font-medium text-[#381998] dark:bg-[#381998]/20 dark:text-purple-300">
                  {{ course.category.name }}
                </span>
                <span>{{ course.sections_count }} section{{ course.sections_count !== 1 ? 's' : '' }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex shrink-0 items-center gap-2 self-end sm:self-auto">
            <a
              :href="`/admin/courses/${course.id}`"
              class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:border-gray-400 dark:border-gray-600 dark:text-gray-300"
            >
              Preview
            </a>
            <a
              :href="`/admin/courses/${course.id}/pricing`"
              class="rounded-xl border border-[#381998]/30 px-3 py-1.5 text-xs font-semibold text-[#381998] transition-colors hover:bg-[#381998]/5 dark:border-[#42b6c5]/40 dark:text-[#42b6c5]"
            >
              Pricing
            </a>
            <button
              @click="rejectCourse(course)"
              class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition-colors hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20"
            >
              Reject
            </button>
            <button
              @click="approveCourse(course)"
              class="rounded-xl bg-green-600 px-4 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-green-700"
            >
              Approve
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Approve confirmation -->
    <ConfirmationModal
      :open="showApproveModal"
      title="Approve & Publish Course"
      :description="`Approve &quot;${courseTarget?.title}&quot;? It will immediately go live and be visible to students.`"
      confirm-text="Yes, Publish"
      cancel-text="Cancel"
      variant="default"
      @update:open="showApproveModal = $event"
      @confirm="confirmApprove"
    />

    <!-- Reject confirmation -->
    <ConfirmationModal
      :open="showRejectModal"
      title="Reject Course"
      :description="`Return &quot;${courseTarget?.title}&quot; to the tutor as a draft? They will need to make changes and re-submit.`"
      confirm-text="Yes, Reject"
      cancel-text="Cancel"
      variant="destructive"
      @update:open="showRejectModal = $event"
      @confirm="confirmReject"
    />

    <!-- Delete confirmation -->
    <ConfirmationModal
      :open="showDeleteModal"
      title="Delete Course"
      :description="`Delete &quot;${courseTarget?.title}&quot;? This cannot be undone.`"
      confirm-text="Yes, Delete"
      cancel-text="Cancel"
      variant="destructive"
      @update:open="showDeleteModal = $event"
      @confirm="confirmDelete"
    />
  </div>
</template>
