<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

interface CourseStudent {
  id: number
  name: string
  email: string
}

interface CourseRow {
  id: number
  title: string
  student_count: number
  students: CourseStudent[]
}

interface ScheduleRow {
  id: number
  title: string
  description: string | null
  audience: 'all_students' | 'course_students' | 'selected_students'
  location: string | null
  starts_at: string | null
  ends_at: string | null
  created_at: string | null
  created_by: string | null
  course: {
    id: number | null
    title: string | null
  }
  selected_students_count: number | null
}

const props = defineProps<{
  courses: CourseRow[]
  schedules: ScheduleRow[]
  submitUrl: string
  mode: 'admin' | 'tutor'
}>()

const form = useForm({
  id: null as number | null,
  title: '',
  description: '',
  course_id: '' as number | '',
  audience: (props.mode === 'admin' ? 'all_students' : 'course_students') as 'all_students' | 'course_students' | 'selected_students',
  student_ids: [] as number[],
  location: '',
  starts_at: '',
  ends_at: '',
})

const requiresCourse = computed(() => form.audience !== 'all_students')
const requiresStudents = computed(() => form.audience === 'selected_students')
const selectedCourse = computed(() => props.courses.find((course: CourseRow) => course.id === Number(form.course_id)) ?? null)
const availableStudents = computed(() => selectedCourse.value?.students ?? [])
const editingId = computed(() => form.id)

function submit() {
  const target = editingId.value ? `${props.submitUrl}/${editingId.value}` : props.submitUrl
  const action = editingId.value ? form.put : form.post

  action(target, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      form.id = null
      form.audience = props.mode === 'admin' ? 'all_students' : 'course_students'
      form.course_id = ''
      form.student_ids = []
    },
  })
}

function editSchedule(row: ScheduleRow) {
  form.id = row.id
  form.title = row.title
  form.description = row.description ?? ''
  form.course_id = row.course.id ?? ''
  form.audience = row.audience
  form.location = row.location ?? ''
  form.starts_at = row.starts_at ? row.starts_at.slice(0, 16) : ''
  form.ends_at = row.ends_at ? row.ends_at.slice(0, 16) : ''
  form.student_ids = []
}

function deleteSchedule(row: ScheduleRow) {
  if (!confirm(`Delete schedule "${row.title}"?`)) return
  router.delete(`${props.submitUrl}/${row.id}`, { preserveScroll: true })
}

function formatDate(value: string | null) {
  if (!value) return 'N/A'
  return new Date(value).toLocaleString()
}

function audienceLabel(value: ScheduleRow['audience']) {
  if (value === 'all_students') return 'All students'
  if (value === 'course_students') return 'All students in course'
  return 'Selected students'
}
</script>

<template>
  <div class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Schedule</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create and publish schedule entries for learners.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        {{ editingId ? 'Edit schedule item' : 'Create schedule item' }}
      </h2>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
          <input v-model="form.title" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Location (optional)</label>
          <input v-model="form.location" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description (optional)</label>
        <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Starts at</label>
          <input v-model="form.starts_at" type="datetime-local" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Ends at (optional)</label>
          <input v-model="form.ends_at" type="datetime-local" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Audience</label>
          <select v-model="form.audience" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
            <option v-if="mode === 'admin'" value="all_students">All students</option>
            <option value="course_students">All students in a course</option>
            <option value="selected_students">Selected students in a course</option>
          </select>
        </div>
        <div v-if="requiresCourse">
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Course</label>
          <select v-model="form.course_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
            <option value="">Select a course</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">
              {{ course.title }} ({{ course.student_count }} students)
            </option>
          </select>
        </div>
      </div>

      <div v-if="requiresStudents">
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Select students</label>
        <select v-model="form.student_ids" multiple class="h-40 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
          <option v-for="student in availableStudents" :key="student.id" :value="student.id">
            {{ student.name }} ({{ student.email }})
          </option>
        </select>
      </div>

      <div class="flex justify-end">
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
          {{ form.processing ? 'Saving...' : editingId ? 'Update schedule item' : 'Create schedule item' }}
        </button>
      </div>
    </form>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent schedule items</h2>
      </div>
      <div v-if="schedules.length === 0" class="p-6 text-sm text-gray-500 dark:text-gray-400">No schedule items yet.</div>
      <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <div v-for="row in schedules" :key="row.id" class="p-6">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ row.title }}</h3>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ audienceLabel(row.audience) }}<span v-if="row.course.title"> • {{ row.course.title }}</span>
              </p>
              <p v-if="row.description" class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ row.description }}</p>
            </div>
            <div class="text-right text-xs text-gray-500 dark:text-gray-400">
              <div>Starts: {{ formatDate(row.starts_at) }}</div>
              <div v-if="row.ends_at" class="mt-1">Ends: {{ formatDate(row.ends_at) }}</div>
              <div v-if="row.location" class="mt-1">Location: {{ row.location }}</div>
              <div class="mt-3 flex justify-end gap-2">
                <button
                  type="button"
                  class="rounded border border-[#381998] px-2 py-1 text-[11px] font-semibold text-[#381998]"
                  @click="editSchedule(row)"
                >
                  Edit
                </button>
                <button
                  type="button"
                  class="rounded border border-red-300 px-2 py-1 text-[11px] font-semibold text-red-600"
                  @click="deleteSchedule(row)"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
