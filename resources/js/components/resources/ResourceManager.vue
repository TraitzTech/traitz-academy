<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import { useToast } from '@/composables/useToast'

const toast = useToast()

interface GroupStudent {
  id: number
  name: string
  email: string
}

interface GroupProgram {
  id: number
  title: string
  student_count: number
  students: GroupStudent[]
}

type ResourceType = 'document' | 'youtube_video' | 'writing' | 'external_link'

interface ResourceRow {
  id: number
  title: string
  type: ResourceType
  description: string | null
  youtube_url: string | null
  external_url: string | null
  content: string | null
  document_url: string | null
  audience: 'all_program_interns' | 'selected_students'
  program: { id: number; title: string } | null
  created_by: string | null
  selected_students_count: number | null
  created_at: string | null
}

const props = defineProps<{
  programs: GroupProgram[]
  resources: ResourceRow[]
}>()

const showCreateForm = ref(false)

const typeLabels: Record<ResourceType, string> = {
  document: 'Document',
  youtube_video: 'YouTube Video',
  writing: 'Writing',
  external_link: 'External Link',
}

const form = useForm({
  program_id: '' as number | '',
  title: '',
  type: 'document' as ResourceType,
  description: '',
  document: null as File | null,
  youtube_url: '',
  external_url: '',
  content: '',
  audience: 'all_program_interns' as 'all_program_interns' | 'selected_students',
  student_ids: [] as number[],
})

const selectedProgram = computed(() => props.programs.find((p) => p.id === Number(form.program_id)) ?? null)
const availableStudents = computed(() => selectedProgram.value?.students ?? [])
const allStudentsSelected = computed(() => availableStudents.value.length > 0 && form.student_ids.length === availableStudents.value.length)

watch(
  () => props.programs,
  (programs) => {
    if (programs.length && !form.program_id) form.program_id = programs[0].id
  },
  { immediate: true }
)

watch(
  () => form.program_id,
  () => {
    form.student_ids = []
  }
)

const canSubmit = computed(() => {
  if (!form.program_id || !form.title.trim()) return false
  if (form.audience === 'selected_students' && form.student_ids.length === 0) return false
  if (form.type === 'document' && !form.document) return false
  if (form.type === 'youtube_video' && !form.youtube_url.trim()) return false
  if (form.type === 'external_link' && !form.external_url.trim()) return false
  if (form.type === 'writing' && !form.content.trim()) return false
  return true
})

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  form.document = target.files?.[0] ?? null
}

function submit() {
  form.post('/tutor/resources', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      form.program_id = props.programs[0]?.id ?? ''
      form.type = 'document'
      form.audience = 'all_program_interns'
      form.student_ids = []
      showCreateForm.value = false
    },
    onError: (errors: Record<string, string>) => {
      const first = Object.values(errors)[0]
      if (first) toast.error(first)
    },
  })
}

function toggleStudent(studentId: number) {
  form.student_ids = form.student_ids.includes(studentId)
    ? form.student_ids.filter((id) => id !== studentId)
    : [...form.student_ids, studentId]
}

function toggleAllStudents() {
  form.student_ids = allStudentsSelected.value ? [] : availableStudents.value.map((s) => s.id)
}

function removeResource(resource: ResourceRow) {
  if (!confirm(`Remove "${resource.title}"? Interns will no longer see it.`)) return
  useForm({}).delete(`/tutor/resources/${resource.id}`, { preserveScroll: true })
}

function formatDateTime(value: string | null) {
  if (!value) return ''
  return new Date(value).toLocaleString()
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Resources</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Share documents, videos, writing, or links with the interns in your programs.</p>
      </div>
      <button
        v-if="programs.length"
        type="button"
        class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white"
        @click="showCreateForm = !showCreateForm"
      >
        {{ showCreateForm ? 'Cancel' : '+ Share resource' }}
      </button>
    </div>

    <div v-if="programs.length === 0" class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-600">
      You don't supervise any programs yet, so there's nowhere to share a resource.
    </div>

    <form v-if="showCreateForm" @submit.prevent="submit" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Share resource</h2>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
          <select v-model="form.program_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
            <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.title }} ({{ p.student_count }} interns)</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
          <select v-model="form.type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
            <option v-for="(label, value) in typeLabels" :key="value" :value="value">{{ label }}</option>
          </select>
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
        <input v-model="form.title" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description (optional)</label>
        <textarea v-model="form.description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
      </div>

      <div v-if="form.type === 'document'">
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Document</label>
        <input type="file" @change="onFileChange" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
      </div>
      <div v-else-if="form.type === 'youtube_video'">
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">YouTube URL</label>
        <input v-model="form.youtube_url" type="url" placeholder="https://youtube.com/watch?v=..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
      </div>
      <div v-else-if="form.type === 'external_link'">
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">External URL</label>
        <input v-model="form.external_url" type="url" placeholder="https://..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
      </div>
      <div v-else-if="form.type === 'writing'">
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Content</label>
        <textarea v-model="form.content" rows="6" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Audience</label>
        <select v-model="form.audience" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
          <option value="all_program_interns">Everyone in this program</option>
          <option value="selected_students">Selected interns only</option>
        </select>
      </div>

      <div v-if="form.audience === 'selected_students'">
        <div class="mb-1 flex items-center justify-between">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select interns</label>
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
            <input type="checkbox" :checked="form.student_ids.includes(student.id)" class="mt-0.5" @change="toggleStudent(student.id)" />
            <span>{{ student.name }} ({{ student.email }})</span>
          </label>
          <p v-if="availableStudents.length === 0" class="text-xs text-gray-500 dark:text-gray-400">Select a program with interns first.</p>
        </div>
      </div>


      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="form.processing || !canSubmit"
          class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ form.processing ? 'Sharing...' : 'Share resource' }}
        </button>
      </div>
    </form>

    <div v-if="resources.length" class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Shared resources <span class="text-sm font-normal text-gray-400">({{ resources.length }})</span></h2>
      </div>
      <div class="divide-y divide-gray-100 dark:divide-gray-700">
        <div v-for="resource in resources" :key="resource.id" class="flex flex-wrap items-start justify-between gap-4 p-6">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ resource.title }}</h3>
              <span class="rounded-full bg-[#42b6c5]/10 px-2 py-0.5 text-xs font-semibold text-[#2a8a96]">{{ typeLabels[resource.type] }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ resource.program?.title }}</p>
            <p v-if="resource.description" class="mt-2 line-clamp-2 text-sm text-gray-700 dark:text-gray-300">{{ resource.description }}</p>
          </div>
          <div class="flex shrink-0 flex-col items-end gap-1.5 text-xs text-gray-500 dark:text-gray-400">
            <div>{{ resource.audience === 'all_program_interns' ? 'Everyone in program' : `${resource.selected_students_count ?? 0} selected interns` }}</div>
            <div class="text-gray-400 dark:text-gray-500">Shared {{ formatDateTime(resource.created_at) }}</div>
            <button type="button" class="text-red-500 hover:underline" @click="removeResource(resource)">Remove</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
