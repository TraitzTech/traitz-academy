<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface AssignmentRow {
  id: number
  title: string
  instructions: string
  audience: 'all_course_students' | 'selected_students'
  attachable: {
    type: 'course' | 'cohort' | 'program' | null
    id: number | null
    title: string | null
  }
  created_by: string | null
  due_at: string | null
  attachment_url: string | null
  selected_students_count: number | null
  created_at: string | null
}

defineProps<{
  assignments: AssignmentRow[]
}>()

function formatDateTime(value: string | null) {
  if (!value) return 'No due date'
  return new Date(value).toLocaleString()
}
</script>

<template>
  <div>
    <Head title="My Assignments" />

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">My assignments</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All assignments sent to you by tutors and admins.</p>
    </div>

    <div v-if="assignments.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800">
      No assignments yet.
    </div>

    <div v-else class="space-y-4">
      <div v-for="assignment in assignments" :key="assignment.id" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ assignment.title }}</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              {{ assignment.attachable.title }} • Assigned by {{ assignment.created_by ?? 'Instructor' }}
            </p>
          </div>
          <div class="text-right text-xs text-gray-500 dark:text-gray-400">
            <div>Due: {{ formatDateTime(assignment.due_at) }}</div>
            <div class="mt-1">Posted: {{ formatDateTime(assignment.created_at) }}</div>
          </div>
        </div>
        <p class="mt-3 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ assignment.instructions }}</p>
        <a
          v-if="assignment.attachment_url"
          :href="assignment.attachment_url"
          target="_blank"
          class="mt-4 inline-flex rounded-lg border border-[#381998] px-3 py-1.5 text-sm font-semibold text-[#381998]"
        >
          Open attachment
        </a>
      </div>
    </div>
  </div>
</template>
