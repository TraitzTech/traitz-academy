<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

import RichTextEditor from '@/components/RichTextEditor.vue'
import { useToast } from '@/composables/useToast'

interface CourseStudent {
  id: number
  name: string
  email: string
}

interface CourseOption {
  id: number
  title: string
  students: CourseStudent[]
  student_count: number
}

interface Props {
  mode: 'admin' | 'tutor'
  courses: CourseOption[]
  submitUrl: string
}

const props = defineProps<Props>()
const toast = useToast()

const form = useForm({
  audience: props.mode === 'admin' ? 'all_students' : 'course_all',
  course_id: '' as number | '',
  student_ids: [] as number[],
  subject: '',
  message: '',
  action_text: '',
  action_url: '',
})

const selectedCourse = computed(() => {
  return props.courses.find((course: CourseOption) => course.id === Number(form.course_id)) ?? null
})

const availableStudents = computed(() => selectedCourse.value?.students ?? [])

const recipientCount = computed(() => {
  if (form.audience === 'all_students' && props.mode === 'admin') {
    return props.courses.reduce((count: number, course: CourseOption) => count + course.student_count, 0)
  }

  if (form.audience === 'course_selected') {
    return form.student_ids.length
  }

  return selectedCourse.value?.student_count ?? 0
})

const requiresCourse = computed(() => form.audience !== 'all_students')
const requiresStudents = computed(() => form.audience === 'course_selected')
const allStudentsSelected = computed(() => {
  return availableStudents.value.length > 0 && form.student_ids.length === availableStudents.value.length
})

watch(
  () => form.course_id,
  () => {
    form.student_ids = []
  }
)

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

const sendNotification = () => {
  if (requiresCourse.value && !form.course_id) {
    toast.error('Please select a course.')
    return
  }

  if (requiresStudents.value && form.student_ids.length === 0) {
    toast.error('Please select at least one student.')
    return
  }

  form.post(props.submitUrl, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('subject', 'message', 'action_text', 'action_url', 'student_ids')
      if (form.audience !== 'all_students') {
        form.course_id = ''
      }
    },
  })
}
</script>

<template>
  <div class="space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900/60">
      <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Send LMS notification</h2>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Send in-app and email notifications to learners.
      </p>
    </div>

    <form @submit.prevent="sendNotification" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900/60">
      <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Audience</label>
        <div class="grid gap-3 md:grid-cols-3">
          <label
            v-if="mode === 'admin'"
            class="rounded-lg border p-3 text-sm cursor-pointer"
            :class="form.audience === 'all_students' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-300 dark:border-gray-600'"
          >
            <input v-model="form.audience" type="radio" value="all_students" class="mr-2" />
            All LMS students
          </label>
          <label
            class="rounded-lg border p-3 text-sm cursor-pointer"
            :class="form.audience === 'course_all' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-300 dark:border-gray-600'"
          >
            <input v-model="form.audience" type="radio" value="course_all" class="mr-2" />
            All students in a course
          </label>
          <label
            class="rounded-lg border p-3 text-sm cursor-pointer"
            :class="form.audience === 'course_selected' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-300 dark:border-gray-600'"
          >
            <input v-model="form.audience" type="radio" value="course_selected" class="mr-2" />
            Selected students in a course
          </label>
        </div>
      </div>

      <div v-if="requiresCourse" class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Course</label>
        <select
          v-model="form.course_id"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
        >
          <option value="">Select a course</option>
          <option v-for="course in courses" :key="course.id" :value="course.id">
            {{ course.title }} ({{ course.student_count }} students)
          </option>
        </select>
      </div>

      <div v-if="requiresStudents" class="space-y-2">
        <div class="flex items-center justify-between">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Students</label>
          <button type="button" class="text-xs font-semibold text-cyan-600" @click="toggleAllStudents">
            {{ allStudentsSelected ? 'Clear all' : 'Select all' }}
          </button>
        </div>
        <div class="max-h-56 space-y-2 overflow-y-auto rounded-lg border border-gray-300 bg-white p-3 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
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

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Subject</label>
        <input
          v-model="form.subject"
          type="text"
          maxlength="255"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
          placeholder="Class update, assignment reminder, schedule change..."
        />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
        <RichTextEditor v-model="form.message" placeholder="Write the notification content..." />
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Action text (optional)</label>
          <input
            v-model="form.action_text"
            type="text"
            maxlength="100"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
            placeholder="Open course"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Action URL (optional)</label>
          <input
            v-model="form.action_url"
            type="url"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
            placeholder="https://..."
          />
        </div>
      </div>

      <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Estimated recipients: <span class="font-semibold">{{ recipientCount }}</span>
        </p>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ form.processing ? 'Sending...' : 'Send notification' }}
        </button>
      </div>
    </form>
  </div>
</template>
