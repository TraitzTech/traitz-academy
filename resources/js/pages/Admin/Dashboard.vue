<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 lg:mb-8">
      <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1 lg:mt-2 text-sm lg:text-base">Welcome back! Here's an overview of your system.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 lg:gap-6 mb-6 lg:mb-8">
      <!-- Total Programs -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 lg:p-6 border-l-4 border-[#42b6c5]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">Programs</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ stats.total_programs }}</p>
          </div>
          <div class="p-2 lg:p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <svg class="w-5 h-5 lg:w-8 lg:h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747c5.5 0 10-4.992 10-11.747S17.5 6.253 12 6.253z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Total Events -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 lg:p-6 border-l-4 border-[#381998]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">Events</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ stats.total_events }}</p>
          </div>
          <div class="p-2 lg:p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
            <svg class="w-5 h-5 lg:w-8 lg:h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Pending Applications -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 lg:p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">Pending</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ stats.pending_applications }}</p>
          </div>
          <div class="p-2 lg:p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
            <svg class="w-5 h-5 lg:w-8 lg:h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Total Users -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 lg:p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">Users</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ stats.total_users }}</p>
          </div>
          <div class="p-2 lg:p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <svg class="w-5 h-5 lg:w-8 lg:h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.646 4 4 0 010-8.646M9 9H3v10a6 6 0 006 6h6a6 6 0 006-6V9h-6a4 4 0 00-4 4v2" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Pending Courses -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 lg:p-6 border-l-4 border-orange-400">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">Pending Courses</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ stats.pending_courses }}</p>
          </div>
          <div class="p-2 lg:p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
            <svg class="w-5 h-5 lg:w-8 lg:h-8 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Total Collected -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 lg:p-6 border-l-4 border-cyan-500 col-span-2 lg:col-span-1">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">{{ stats.collected_label || 'Collected' }}</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ formatMoney(stats.total_collected || 0) }}</p>
          </div>
          <div class="p-2 lg:p-3 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
            <svg class="w-5 h-5 lg:w-8 lg:h-8 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.105 0-2 .895-2 2s.895 2 2 2 2 .895 2 2-.895 2-2 2m0-10V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Applications -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-base lg:text-lg font-bold text-gray-900 dark:text-gray-100">Recent Applications</h3>
      </div>
      
      <!-- Mobile Card View -->
      <div v-if="recentApplications.length > 0" class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
        <div v-for="app in recentApplications" :key="app.id" class="p-4">
          <div class="flex items-start justify-between mb-2">
            <div>
              <p class="font-semibold text-gray-900 dark:text-gray-100">{{ app.first_name }} {{ app.last_name }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ app.email }}</p>
            </div>
            <span :class="[
              'px-2 py-1 rounded-full text-xs font-semibold flex-shrink-0',
              app.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
              app.status === 'accepted' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
              'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
            ]">
              {{ app.status.charAt(0).toUpperCase() + app.status.slice(1) }}
            </span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400 truncate max-w-[60%]">{{ app.program.title }}</span>
            <span class="text-gray-400 dark:text-gray-500 text-xs">{{ formatDate(app.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Desktop Table View -->
      <div v-if="recentApplications.length > 0" class="hidden lg:block overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Program</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="app in recentApplications" :key="app.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ app.first_name }} {{ app.last_name }}</td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ app.program.title }}</td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ app.email }}</td>
              <td class="px-6 py-4 text-sm">
                <span :class="[
                  'px-3 py-1 rounded-full text-xs font-semibold',
                  app.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                  app.status === 'accepted' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                  'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                ]">
                  {{ app.status.charAt(0).toUpperCase() + app.status.slice(1) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(app.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="recentApplications.length === 0" class="px-4 lg:px-6 py-10 text-center">
        <p class="text-gray-500 dark:text-gray-400">No applications yet</p>
      </div>
    </div>

    <!-- Pending Courses for Review -->
    <div class="mt-6 lg:mt-8 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <h3 class="text-base lg:text-lg font-bold text-gray-900 dark:text-gray-100">Pending Course Reviews</h3>
          <span v-if="pendingCourses.length > 0" class="inline-flex items-center justify-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-bold text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
            {{ pendingCourses.length }}
          </span>
        </div>
        <a href="/admin/courses?status=pending_review" class="text-sm font-medium text-[#42b6c5] hover:text-[#35919e] transition-colors">
          View all →
        </a>
      </div>

      <!-- Empty state -->
      <div v-if="pendingCourses.length === 0" class="px-6 py-12 text-center">
        <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm text-gray-500 dark:text-gray-400">No courses awaiting review — you're all caught up!</p>
      </div>

      <!-- Course cards -->
      <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <div
          v-for="course in pendingCourses"
          :key="course.id"
          class="flex flex-col gap-3 px-4 lg:px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/40 sm:flex-row sm:items-center sm:justify-between transition-colors"
        >
          <!-- Course info -->
          <div class="flex items-start gap-4 min-w-0">
            <!-- Cover thumbnail -->
            <div class="h-14 w-20 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-[#381998] to-[#42b6c5]">
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
              class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:border-gray-400 dark:border-gray-600 dark:text-gray-300"
            >
              Preview
            </a>
            <button
              @click="rejectCourse(course)"
              class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition-colors hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20"
            >
              Reject
            </button>
            <button
              @click="approveCourse(course)"
              class="rounded-lg bg-green-600 px-4 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-green-700"
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
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ConfirmationModal from '@/components/ConfirmationModal.vue'

defineProps({
  stats: { type: Object, required: true },
  recentApplications: { type: Array, required: true },
  pendingCourses: { type: Array, default: () => [] },
})

defineOptions({ layout: AppLayout })

// ─── Approve / Reject ────────────────────────────────────────────────────────

const showApproveModal = ref(false)
const showRejectModal  = ref(false)
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

// ─── Formatters ──────────────────────────────────────────────────────────────

const formatDate = (date) => new Date(date).toLocaleDateString('en-US', {
  year: 'numeric', month: 'short', day: 'numeric',
})

const formatMoney = (amount) =>
  new Intl.NumberFormat('en-CM', { maximumFractionDigits: 0 }).format(amount)
</script>
