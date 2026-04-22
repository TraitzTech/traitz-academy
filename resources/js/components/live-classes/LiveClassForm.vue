<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

interface Option { id: number; name?: string; title?: string; email?: string }

const props = defineProps<{
  submitUrl: string
  method?: 'post' | 'put'
  tutors?: Option[]
  courses: Option[]
  students: Option[]
  initial?: Record<string, any>
  showTutorSelect?: boolean
}>()

function toLocalDateTimeInput(value: string | null | undefined): string {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000)
  return local.toISOString().slice(0, 16)
}

function toUtcIsoString(value: string): string {
  const date = new Date(value)
  return date.toISOString()
}

const form = useForm({
  title: props.initial?.title ?? '',
  description: props.initial?.description ?? '',
  tutor_id: props.initial?.tutor_id ?? '',
  start_time: toLocalDateTimeInput(props.initial?.start_time),
  duration: props.initial?.duration ?? 60,
  access_type: props.initial?.access_type ?? 'course',
  course_ids: props.initial?.courses?.map((c: any) => c.id) ?? [],
  student_ids: props.initial?.students?.map((s: any) => s.id) ?? [],
})

function submit() {
  const payload = {
    ...form.data(),
    start_time: toUtcIsoString(form.start_time),
  }

  if (props.method === 'put') {
    form.transform(() => payload).put(props.submitUrl)
    return
  }
  form.transform(() => payload).post(props.submitUrl)
}
</script>

<template>
  <form class="space-y-4" @submit.prevent="submit">
    <div class="grid gap-4 md:grid-cols-2">
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Title</label>
        <input v-model="form.title" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Description</label>
        <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
      </div>
      <div v-if="showTutorSelect">
        <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Tutor</label>
        <select v-model="form.tutor_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
          <option value="">Select tutor</option>
          <option v-for="t in tutors || []" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Start time</label>
        <input v-model="form.start_time" type="datetime-local" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Duration (minutes)</label>
        <input v-model.number="form.duration" type="number" min="5" max="600" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
      </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
      <p class="mb-2 text-sm font-semibold text-[#000928] dark:text-white">Audience</p>
      <div class="mb-3 flex gap-4 text-sm">
        <label class="inline-flex items-center gap-2">
          <input v-model="form.access_type" value="course" type="radio" />
          <span>Course-based</span>
        </label>
        <label class="inline-flex items-center gap-2">
          <input v-model="form.access_type" value="custom" type="radio" />
          <span>Custom students</span>
        </label>
      </div>

      <div v-if="form.access_type === 'course'" class="grid gap-2">
        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Select course(s)</label>
        <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-2 dark:border-gray-600">
          <label v-for="course in courses" :key="course.id" class="flex items-center gap-2 text-sm">
            <input v-model="form.course_ids" :value="course.id" type="checkbox" />
            <span>{{ course.title }}</span>
          </label>
        </div>
      </div>
      <div v-else class="grid gap-2">
        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Select students</label>
        <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-2 dark:border-gray-600">
          <label v-for="student in students" :key="student.id" class="flex items-center gap-2 text-sm">
            <input v-model="form.student_ids" :value="student.id" type="checkbox" />
            <span>{{ student.name }} <span class="text-xs text-gray-400">({{ student.email }})</span></span>
          </label>
        </div>
      </div>
    </div>

    <div class="flex justify-end">
      <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
        {{ form.processing ? 'Saving...' : 'Save Live Class' }}
      </button>
    </div>
  </form>
</template>
