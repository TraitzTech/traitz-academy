<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { BookOpen, MessageCircle, PlusCircle, Users, Clock, Star } from 'lucide-vue-next'
import { computed } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

interface Enrollment {
  id: number
  student: string | null
  course: string | null
  cover_image: string | null
  enrolled_at: string
  status: string
}

interface Course {
  id: number
  title: string
  status: string
  cover_image: string | null
  enrollments_count: number
  level: string
  category: { name: string; color: string | null } | null
  price: number
}

interface Stats {
  total_students: number
  active_courses: number
  pending_courses: number
  total_courses: number
}

const props = defineProps<{
  stats: Stats
  recentEnrollments: Enrollment[]
  myCourses: Course[]
}>()

defineOptions({ layout: AppLayout })

const page = usePage()
const firstName = computed(() => {
  const name = (page.props.auth as any)?.user?.name ?? ''
  return name.split(' ')[0]
})

const statusLabels: Record<string, string> = {
  draft: 'Draft',
  pending_review: 'Pending Review',
  published: 'Published',
  archived: 'Archived',
}

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
  pending_review: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  archived: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
}

const enrollmentStatusColors: Record<string, string> = {
  active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  cancelled: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
  refunded: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
}

function coverSrc(path: string | null) {
  if (!path) return null
  return path.startsWith('http') ? path : `/storage/${path}`
}

function initials(name: string | null) {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
</script>

<template>
  <div>
    <Head title="Tutor Dashboard" />

    <!-- Page Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        Welcome back, {{ firstName }}! 👋
      </h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Here's an overview of your teaching activity
      </p>
    </div>

    <!-- ── Stat Cards ── -->
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-5">

      <!-- Total Students -->
      <div class="rounded-lg bg-white shadow p-4 lg:p-6 border-l-4 border-[#42b6c5] dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 lg:text-sm">Total Students</p>
            <p class="mt-1 text-2xl font-bold text-[#000928] dark:text-gray-100 lg:mt-2 lg:text-3xl">
              {{ stats.total_students }}
            </p>
            <p class="mt-1 text-xs text-gray-400">across all courses</p>
          </div>
          <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30 lg:p-3">
            <Users class="h-5 w-5 text-blue-600 dark:text-blue-400 lg:h-7 lg:w-7" />
          </div>
        </div>
      </div>

      <!-- Active Courses -->
      <div class="rounded-lg bg-white shadow p-4 lg:p-6 border-l-4 border-green-500 dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 lg:text-sm">Active Courses</p>
            <p class="mt-1 text-2xl font-bold text-[#000928] dark:text-gray-100 lg:mt-2 lg:text-3xl">
              {{ stats.active_courses }}
            </p>
            <p class="mt-1 text-xs text-gray-400">published courses</p>
          </div>
          <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30 lg:p-3">
            <BookOpen class="h-5 w-5 text-green-600 dark:text-green-400 lg:h-7 lg:w-7" />
          </div>
        </div>
      </div>

      <!-- Pending Review -->
      <div class="rounded-lg bg-white shadow p-4 lg:p-6 border-l-4 border-amber-400 dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 lg:text-sm">Pending Review</p>
            <p class="mt-1 text-2xl font-bold text-[#000928] dark:text-gray-100 lg:mt-2 lg:text-3xl">
              {{ stats.pending_courses }}
            </p>
            <p class="mt-1 text-xs text-gray-400">awaiting admin approval</p>
          </div>
          <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/30 lg:p-3">
            <Clock class="h-5 w-5 text-amber-500 dark:text-amber-400 lg:h-7 lg:w-7" />
          </div>
        </div>
      </div>

      <!-- Total Courses -->
      <div class="rounded-lg bg-white shadow p-4 lg:p-6 border-l-4 border-[#381998] dark:bg-gray-800">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 lg:text-sm">Total Courses</p>
            <p class="mt-1 text-2xl font-bold text-[#000928] dark:text-gray-100 lg:mt-2 lg:text-3xl">
              {{ stats.total_courses }}
            </p>
            <p class="mt-1 text-xs text-gray-400">all time created</p>
          </div>
          <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30 lg:p-3">
            <Star class="h-5 w-5 text-purple-600 dark:text-purple-400 lg:h-7 lg:w-7" />
          </div>
        </div>
      </div>
    </div>

    <!-- ── Main Content ── -->
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

      <!-- Left 2/3: My Courses + Course Status -->
      <div class="space-y-5 lg:col-span-2">

        <!-- My Courses -->
        <div class="rounded-xl bg-white shadow dark:bg-gray-800">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Course Performance</h3>
            <Link href="/tutor/courses" class="text-xs font-medium text-[#42b6c5] hover:text-[#35919e]">
              View All →
            </Link>
          </div>

          <!-- Empty state -->
          <div v-if="myCourses.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
            <BookOpen class="mb-3 h-10 w-10 text-gray-300" />
            <p class="font-medium text-gray-500 dark:text-gray-400">No courses yet</p>
            <p class="mt-1 text-sm text-gray-400">Create your first course to get started.</p>
            <Link
              href="/tutor/courses/create"
              class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white hover:bg-[#35919e]"
            >
              <PlusCircle class="h-4 w-4" /> New Course
            </Link>
          </div>

          <!-- Course list with per-course student bar -->
          <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
            <div
              v-for="course in myCourses"
              :key="course.id"
              class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
            >
              <!-- Cover thumbnail -->
              <div class="h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-[#381998] to-[#42b6c5]">
                <img
                  v-if="coverSrc(course.cover_image)"
                  :src="coverSrc(course.cover_image)!"
                  :alt="course.title"
                  class="h-full w-full object-cover"
                />
              </div>

              <!-- Info + progress bar -->
              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                  <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ course.title }}</p>
                  <span :class="['shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold', statusColors[course.status]]">
                    {{ statusLabels[course.status] }}
                  </span>
                </div>
                <div class="mt-1.5 flex items-center gap-2">
                  <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                    <div
                      class="h-1.5 rounded-full bg-[#42b6c5] transition-all"
                      :style="{ width: stats.total_students > 0 ? Math.min((course.enrollments_count / Math.max(stats.total_students, 1)) * 100 * 3, 100) + '%' : '0%' }"
                    />
                  </div>
                  <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ course.enrollments_count }}</span> students
                  </span>
                </div>
                <div class="mt-1 flex items-center gap-2">
                  <span
                    v-if="course.category"
                    class="rounded-full px-2 py-0.5 text-xs font-medium text-white"
                    :style="{ backgroundColor: course.category.color || '#381998' }"
                  >
                    {{ course.category.name }}
                  </span>
                  <span class="text-xs capitalize text-gray-400">{{ course.level }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Course Status Breakdown -->
        <div class="rounded-xl bg-white shadow dark:bg-gray-800 p-5">
          <h3 class="mb-4 font-semibold text-gray-900 dark:text-gray-100">Course Status Overview</h3>

          <div v-if="stats.total_courses === 0" class="rounded-xl border-2 border-dashed border-[#42b6c5]/40 bg-[#42b6c5]/5 py-8 text-center dark:border-[#42b6c5]/20">
            <BookOpen class="mx-auto mb-2 h-8 w-8 text-[#42b6c5]" />
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Ready to teach?</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Create your first course to get started.</p>
            <Link
              href="/tutor/courses/create"
              class="mt-3 inline-flex items-center gap-2 rounded-lg bg-[#42b6c5] px-4 py-2 text-xs font-semibold text-white hover:bg-[#35919e]"
            >
              <PlusCircle class="h-3.5 w-3.5" /> Get Started
            </Link>
          </div>

          <div v-else class="grid grid-cols-3 gap-4">
            <div class="rounded-lg bg-green-50 p-4 text-center dark:bg-green-900/10">
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.active_courses }}</p>
              <p class="mt-1 text-xs font-medium text-green-700 dark:text-green-500">Published</p>
              <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-green-100 dark:bg-green-900/30">
                <div class="h-1.5 rounded-full bg-green-500" :style="{ width: stats.total_courses ? (stats.active_courses / stats.total_courses * 100) + '%' : '0%' }" />
              </div>
            </div>
            <div class="rounded-lg bg-amber-50 p-4 text-center dark:bg-amber-900/10">
              <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.pending_courses }}</p>
              <p class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-500">In Review</p>
              <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-amber-100 dark:bg-amber-900/30">
                <div class="h-1.5 rounded-full bg-amber-400" :style="{ width: stats.total_courses ? (stats.pending_courses / stats.total_courses * 100) + '%' : '0%' }" />
              </div>
            </div>
            <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-700/30">
              <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ stats.total_courses - stats.active_courses - stats.pending_courses }}</p>
              <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">Drafts</p>
              <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-600">
                <div class="h-1.5 rounded-full bg-gray-400" :style="{ width: stats.total_courses ? ((stats.total_courses - stats.active_courses - stats.pending_courses) / stats.total_courses * 100) + '%' : '0%' }" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right 1/3: Recent Enrollments + Quick Actions (mirrors prototype structure) -->
      <div class="space-y-5">

        <!-- Recent Enrollments (top right — like Pending Reviews in prototype) -->
        <div class="rounded-xl bg-white shadow dark:bg-gray-800">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Recent Enrollments</h3>
            <span class="text-xs text-gray-400">Latest students</span>
          </div>

          <div v-if="recentEnrollments.length === 0" class="py-8 text-center text-sm text-gray-400 px-4">
            No enrollments yet. Students will appear here once they join your courses.
          </div>

          <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
            <div
              v-for="enrollment in recentEnrollments"
              :key="enrollment.id"
              class="flex items-center gap-3 px-5 py-3"
            >
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#381998] text-xs font-bold text-white">
                {{ initials(enrollment.student) }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-medium text-gray-900 dark:text-gray-100">{{ enrollment.student }}</p>
                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ enrollment.course }}</p>
              </div>
              <span :class="['shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold', enrollmentStatusColors[enrollment.status] ?? 'bg-gray-100 text-gray-500']">
                {{ enrollment.status }}
              </span>
            </div>
          </div>
        </div>

        <!-- Quick Actions (bottom right — same position as prototype) -->
        <div class="rounded-xl bg-white shadow dark:bg-gray-800 p-5">
          <h3 class="mb-3 font-semibold text-gray-900 dark:text-gray-100">Quick Actions</h3>
          <div class="space-y-2">
            <Link
              href="/tutor/courses/create"
              class="flex w-full items-center gap-3 rounded-lg border border-[#381998]/30 bg-[#381998]/5 px-4 py-2.5 text-sm font-medium text-[#381998] transition-colors hover:bg-[#381998]/10 dark:border-purple-700/30 dark:text-purple-300 dark:hover:bg-purple-900/20"
            >
              <PlusCircle class="h-4 w-4 shrink-0" /> Create New Course
            </Link>
            <Link
              href="/tutor/courses"
              class="flex w-full items-center gap-3 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/40"
            >
              <BookOpen class="h-4 w-4 shrink-0" /> Manage My Courses
            </Link>
            <Link
              href="/tutor/discussions"
              class="flex w-full items-center gap-3 rounded-lg border border-green-200 px-4 py-2.5 text-sm font-medium text-green-700 transition-colors hover:bg-green-50 dark:border-green-700/30 dark:text-green-400 dark:hover:bg-green-900/10"
            >
              <MessageCircle class="h-4 w-4 shrink-0" /> View Discussions
            </Link>
            <Link
              href="/tutor/students"
              class="flex w-full items-center gap-3 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/40"
            >
              <Users class="h-4 w-4 shrink-0" /> My Students
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
