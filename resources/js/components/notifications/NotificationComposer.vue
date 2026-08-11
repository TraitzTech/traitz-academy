<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import RichTextEditor from '@/components/RichTextEditor.vue'
import { useToast } from '@/composables/useToast'

interface Member {
  id: number
  name: string
  email: string
}

interface GroupOption {
  id: number
  title: string
  students: Member[]
  student_count: number
}

type GroupType = 'course' | 'cohort' | 'program'

interface Props {
  mode: 'admin' | 'tutor'
  courses?: GroupOption[]
  cohorts?: GroupOption[]
  programs?: GroupOption[]
  submitUrl: string
}

const props = withDefaults(defineProps<Props>(), {
  courses: () => [],
  cohorts: () => [],
  programs: () => [],
})
const toast = useToast()

// One flat, typed list of every targetable group, kept in labelled sections.
const sections = computed(() => [
  { type: 'course' as GroupType, label: 'Courses', groups: props.courses },
  { type: 'cohort' as GroupType, label: 'Cohorts', groups: props.cohorts },
  { type: 'program' as GroupType, label: 'Programs', groups: props.programs },
].filter((s) => s.groups.length > 0))

const form = useForm({
  audience: props.mode === 'admin' ? 'all_students' : 'all_course_students',
  attachable_type: '' as GroupType | '',
  attachable_id: '' as number | '',
  student_ids: [] as number[],
  subject: '',
  message: '',
  action_text: '',
  action_url: '',
})

// The <select> encodes type + id together (e.g. "cohort:5").
const selectedKey = computed<string>({
  get: () => (form.attachable_type && form.attachable_id ? `${form.attachable_type}:${form.attachable_id}` : ''),
  set: (value) => {
    if (!value) {
      form.attachable_type = ''
      form.attachable_id = ''
      return
    }
    const [type, id] = value.split(':')
    form.attachable_type = type as GroupType
    form.attachable_id = Number(id)
  },
})

const selectedGroup = computed<GroupOption | null>(() => {
  const section = sections.value.find((s) => s.type === form.attachable_type)
  return section?.groups.find((g) => g.id === Number(form.attachable_id)) ?? null
})

const studentSearch = ref('')
const availableStudents = computed(() => selectedGroup.value?.students ?? [])
const searchableStudents = computed(() => {
  const q = studentSearch.value.trim().toLowerCase()
  if (!q) return availableStudents.value
  return availableStudents.value.filter((m) => m.name.toLowerCase().includes(q) || m.email.toLowerCase().includes(q))
})

const uniqueStudentCount = computed(() => {
  const ids = new Set<number>()
  sections.value.forEach((s) => s.groups.forEach((g) => g.students.forEach((m) => ids.add(m.id))))
  return ids.size
})

const recipientCount = computed(() => {
  if (form.audience === 'all_students' && props.mode === 'admin') return uniqueStudentCount.value
  if (form.audience === 'selected_students') return form.student_ids.length
  return selectedGroup.value?.student_count ?? 0
})

const requiresGroup = computed(() => form.audience !== 'all_students')
const requiresStudents = computed(() => form.audience === 'selected_students')
const allStudentsSelected = computed(() => availableStudents.value.length > 0 && form.student_ids.length === availableStudents.value.length)

watch(selectedKey, () => {
  form.student_ids = []
  studentSearch.value = ''
})

function toggleStudent(id: number) {
  form.student_ids = form.student_ids.includes(id)
    ? form.student_ids.filter((s) => s !== id)
    : [...form.student_ids, id]
}

function toggleAllStudents() {
  form.student_ids = allStudentsSelected.value ? [] : availableStudents.value.map((m) => m.id)
}

function sendNotification() {
  if (requiresGroup.value && !selectedKey.value) {
    toast.error('Please select a group.')
    return
  }
  if (requiresStudents.value && form.student_ids.length === 0) {
    toast.error('Please select at least one recipient.')
    return
  }

  form.post(props.submitUrl, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('subject', 'message', 'action_text', 'action_url', 'student_ids')
      if (form.audience !== 'all_students') {
        selectedKey.value = ''
      }
    },
  })
}
</script>

<template>
  <div class="space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900/60">
      <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Send notification</h2>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Send in-app and email notifications to course students or the interns you supervise.
      </p>
    </div>

    <form @submit.prevent="sendNotification" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900/60">
      <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Audience</label>
        <div class="grid gap-3 md:grid-cols-2">
          <label
            v-if="mode === 'admin'"
            class="cursor-pointer rounded-lg border p-3 text-sm"
            :class="form.audience === 'all_students' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-300 dark:border-gray-600'"
          >
            <input v-model="form.audience" type="radio" value="all_students" class="mr-2" />
            Everyone
          </label>
          <label
            class="cursor-pointer rounded-lg border p-3 text-sm"
            :class="form.audience === 'all_course_students' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-300 dark:border-gray-600'"
          >
            <input v-model="form.audience" type="radio" value="all_course_students" class="mr-2" />
            Everyone in a group
          </label>
          <label
            class="cursor-pointer rounded-lg border p-3 text-sm"
            :class="form.audience === 'selected_students' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-300 dark:border-gray-600'"
          >
            <input v-model="form.audience" type="radio" value="selected_students" class="mr-2" />
            Selected people in a group
          </label>
        </div>
      </div>

      <div v-if="requiresGroup" class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Group</label>
        <select
          v-model="selectedKey"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
        >
          <option value="">Select a group</option>
          <optgroup v-for="section in sections" :key="section.type" :label="section.label">
            <option v-for="group in section.groups" :key="`${section.type}:${group.id}`" :value="`${section.type}:${group.id}`">
              {{ group.title }} ({{ group.student_count }})
            </option>
          </optgroup>
        </select>
        <p v-if="sections.length === 0" class="text-xs text-gray-500 dark:text-gray-400">
          You don't have any groups to notify yet.
        </p>
      </div>

      <div v-if="requiresStudents" class="space-y-2">
        <div class="flex items-center justify-between">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recipients</label>
          <button type="button" class="text-xs font-semibold text-cyan-600" @click="toggleAllStudents">
            {{ allStudentsSelected ? 'Clear all' : 'Select all' }}
          </button>
        </div>
        <div class="rounded-lg border border-gray-300 bg-white p-3 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
          <input
            v-model="studentSearch"
            type="text"
            class="mb-3 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            placeholder="Search by name or email"
          />
          <div class="max-h-56 space-y-2 overflow-y-auto">
            <label
              v-for="member in searchableStudents"
              :key="member.id"
              class="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-700/50"
            >
              <input type="checkbox" :checked="form.student_ids.includes(member.id)" class="mt-0.5" @change="toggleStudent(member.id)" />
              <span>{{ member.name }} ({{ member.email }})</span>
            </label>
            <p v-if="availableStudents.length === 0" class="text-xs text-gray-500 dark:text-gray-400">
              Select a group with members first.
            </p>
            <p v-else-if="searchableStudents.length === 0" class="text-xs text-gray-500 dark:text-gray-400">
              No one matches your search.
            </p>
          </div>
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
            placeholder="Open"
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
