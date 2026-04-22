<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

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

interface AssignmentRow {
  id: number
  title: string
  instructions: string
  audience: 'all_course_students' | 'selected_students'
  course: {
    id: number | null
    title: string | null
  }
  created_by: string | null
  due_at: string | null
  attachment_url: string | null
  selected_students_count: number | null
  created_at: string | null
}

const props = defineProps<{
  courses: CourseRow[]
  assignments: AssignmentRow[]
  submitUrl: string
}>()

const form = useForm({
  course_id: '' as number | '',
  title: '',
  instructions: '',
  audience: 'all_course_students' as 'all_course_students' | 'selected_students',
  student_ids: [] as number[],
  due_at: '',
  attachment: null as File | null,
})

const selectedCourse = computed(() => props.courses.find((course: CourseRow) => course.id === Number(form.course_id)) ?? null)
const availableStudents = computed(() => selectedCourse.value?.students ?? [])
const allStudentsSelected = computed(() => {
  return availableStudents.value.length > 0 && form.student_ids.length === availableStudents.value.length
})

watch(
  () => form.course_id,
  () => {
    form.student_ids = []
  }
)

const canSubmit = computed(() => {
  if (!form.course_id || !form.title.trim() || !form.instructions.trim()) return false
  if (form.audience === 'selected_students' && form.student_ids.length === 0) return false
  return true
})

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  form.attachment = target.files?.[0] ?? null
}

function submit() {
  form.post(props.submitUrl, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      form.audience = 'all_course_students'
      form.course_id = ''
      form.student_ids = []
    },
  })
}

function toggleStudent(studentId: number) {
  if (form.student_ids.includes(studentId)) {
    form.student_ids = form.student_ids.filter((id: number) => id !== studentId)
    return
  }

  form.student_ids = [...form.student_ids, studentId]
}

function toggleAllStudents() {
  if (allStudentsSelected.value) {
    form.student_ids = []
    return
  }

  form.student_ids = availableStudents.value.map((student: CourseStudent) => student.id)
}

function formatDateTime(value: string | null) {
  if (!value) return 'No due date'
  return new Date(value).toLocaleString()
}
</script>

<template>
  <div class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Assignments</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Send assignments to all students in a course or a selected subset.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Create assignment</h2>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Course</label>
        <select v-model="form.course_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
          <option value="">Select a course</option>
          <option v-for="course in courses" :key="course.id" :value="course.id">
            {{ course.title }} ({{ course.student_count }} students)
          </option>
        </select>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
          <input v-model="form.title" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Due date (optional)</label>
          <input v-model="form.due_at" type="datetime-local" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Instructions</label>
        <textarea
          v-model="form.instructions"
          rows="5"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
          placeholder="Describe what learners should do, submit, or revise."
        />
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Audience</label>
          <select v-model="form.audience" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
            <option value="all_course_students">All students in selected course</option>
            <option value="selected_students">Selected students only</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Attachment (optional)</label>
          <input type="file" @change="onFileChange" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
        </div>
      </div>

      <div v-if="form.audience === 'selected_students'">
        <div class="mb-1 flex items-center justify-between">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select students</label>
          <button type="button" class="text-xs font-semibold text-[#381998]" @click="toggleAllStudents">
            {{ allStudentsSelected ? 'Clear all' : 'Select all' }}
          </button>
        </div>
        <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
          <label
            v-for="student in availableStudents"
            :key="student.id"
            class="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-700/50"
          >
            <input
              type="checkbox"
              :checked="form.student_ids.includes(student.id)"
              class="mt-0.5"
              @change="toggleStudent(student.id)"
            />
            <span>{{ student.name }} ({{ student.email }})</span>
          </label>
          <p v-if="availableStudents.length === 0" class="text-xs text-gray-500 dark:text-gray-400">
            Select a course with enrolled students first.
          </p>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="form.processing || !canSubmit"
          class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ form.processing ? 'Sending...' : 'Send assignment' }}
        </button>
      </div>
    </form>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent assignments</h2>
      </div>
      <div v-if="assignments.length === 0" class="p-6 text-sm text-gray-500 dark:text-gray-400">
        No assignments created yet.
      </div>
      <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <div v-for="assignment in assignments" :key="assignment.id" class="p-6">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ assignment.title }}</h3>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ assignment.course.title }} • Due: {{ formatDateTime(assignment.due_at) }}
              </p>
              <p class="mt-2 line-clamp-3 text-sm text-gray-700 dark:text-gray-300">{{ assignment.instructions }}</p>
            </div>
            <div class="text-right text-xs text-gray-500 dark:text-gray-400">
              <div class="capitalize">
                {{ assignment.audience === 'all_course_students' ? 'All course students' : `${assignment.selected_students_count ?? 0} selected students` }}
              </div>
              <div class="mt-1">Created {{ formatDateTime(assignment.created_at) }}</div>
              <a v-if="assignment.attachment_url" :href="assignment.attachment_url" target="_blank" class="mt-2 inline-block text-[#381998]">
                View attachment
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
