<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { CalendarDays, CheckCheck, Plus, Trash2, Users } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface Option { id: number; title?: string; name?: string; role?: string }
interface Cohort {
  id: number
  name: string
  status: string
  is_intake: boolean
  start_date: string | null
  end_date: string | null
  internships_count: number
  programs: string[]
}

const props = defineProps<{
  cohorts: { data: Cohort[]; links: { url: string | null; label: string; active: boolean }[] }
  programs: Option[]
  supervisors: Option[]
}>()

const toast = useToast()
const showCreate = ref(false)

const form = useForm({
  name: '',
  description: '',
  start_date: '',
  end_date: '',
  intake_opens_at: '',
  intake_closes_at: '',
  is_intake: false,
  timezone: '',
  expected_hours_per_day: '' as string | number,
  working_days: [1, 2, 3, 4, 5] as number[],
  programs: [{ program_id: '' as string | number, supervisor_id: '' as string | number }],
})

const WEEKDAYS = [
  { value: 1, label: 'Mon' },
  { value: 2, label: 'Tue' },
  { value: 3, label: 'Wed' },
  { value: 4, label: 'Thu' },
  { value: 5, label: 'Fri' },
  { value: 6, label: 'Sat' },
  { value: 7, label: 'Sun' },
]
function toggleDay(days: number[], day: number) {
  const i = days.indexOf(day)
  if (i === -1) days.push(day)
  else days.splice(i, 1)
}

function addProgramRow() {
  form.programs.push({ program_id: '', supervisor_id: '' })
}
function removeProgramRow(i: number) {
  form.programs.splice(i, 1)
}
const allProgramsSelected = computed(() => {
  const selected = new Set(form.programs.map((r) => r.program_id).filter(Boolean))
  return props.programs.length > 0 && props.programs.every((p) => selected.has(p.id))
})
function selectAllPrograms() {
  // Keep any supervisor already chosen for a program, just fill in the rest.
  const existing = new Map(form.programs.filter((r) => r.program_id).map((r) => [r.program_id, r.supervisor_id]))
  form.programs = props.programs.map((p) => ({ program_id: p.id, supervisor_id: existing.get(p.id) ?? '' }))
}

function deleteCohort(c: Cohort) {
  const warning = c.internships_count > 0
    ? `Delete "${c.name}"? Its ${c.internships_count} intern(s) will be unassigned, not deleted.`
    : `Delete "${c.name}"? This cannot be undone.`
  if (!confirm(warning)) return
  router.delete(`/admin/internships/cohorts/${c.id}`, { preserveScroll: true })
}

const PROGRAM_BADGE_LIMIT = 3
const expandedPrograms = ref<Set<number>>(new Set())
function toggleProgramsExpanded(cohortId: number) {
  if (expandedPrograms.value.has(cohortId)) expandedPrograms.value.delete(cohortId)
  else expandedPrograms.value.add(cohortId)
}

function submit() {
  form.transform((data) => ({
    ...data,
    programs: data.programs.filter((p) => p.program_id),
  })).post('/admin/internships/cohorts', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      form.programs = [{ program_id: '', supervisor_id: '' }]
      showCreate.value = false
    },
    onError: () => toast.error('Please check the form and try again.'),
  })
}

const statusClass: Record<string, string> = {
  upcoming: 'bg-blue-100 text-blue-700',
  active: 'bg-green-100 text-green-700',
  completed: 'bg-gray-100 text-gray-700',
  cancelled: 'bg-red-100 text-red-700',
}
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <Head title="Internship Cohorts" />

    <div class="mb-6 flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#000928] dark:text-white">Internship Cohorts</h1>
        <p class="mt-0.5 text-sm text-gray-500">A dated batch of interns spanning one or more programs, each with its own supervisor.</p>
      </div>
      <button
        class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#000928]"
        @click="showCreate = !showCreate"
      >
        <Plus class="h-4 w-4" /> New Cohort
      </button>
    </div>

    <!-- Create form -->
    <div v-if="showCreate" class="mb-6 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="h-1.5 bg-gradient-to-r from-[#381998] via-[#42b6c5] to-[#000928]"></div>
      <form class="space-y-4 p-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Cohort name *</label>
            <input v-model="form.name" type="text" placeholder="e.g. August 2026" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Start date *</label>
            <input v-model="form.start_date" type="date" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
            <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-500">{{ form.errors.start_date }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">End date *</label>
            <input v-model="form.end_date" type="date" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
            <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-500">{{ form.errors.end_date }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Expected hours / day</label>
            <input v-model="form.expected_hours_per_day" type="number" min="0" max="24" step="0.5" placeholder="e.g. 8" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Working days (logbook)</label>
          <div class="flex flex-wrap gap-2">
            <label
              v-for="d in WEEKDAYS"
              :key="d.value"
              class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium dark:border-gray-600"
              :class="form.working_days.includes(d.value) ? 'bg-[#381998]/10 text-[#381998] border-[#381998]/40' : 'text-gray-500 dark:text-gray-300'"
            >
              <input type="checkbox" class="hidden" :checked="form.working_days.includes(d.value)" @change="toggleDay(form.working_days, d.value)" />
              {{ d.label }}
            </label>
          </div>
          <p class="mt-1 text-xs text-gray-500">Interns in this cohort must submit a logbook entry on these days. Leave the app default (Mon–Fri) unless this cohort works a different schedule.</p>
        </div>

        <!-- Application window (stamped onto all programs in this cohort) -->
        <div class="rounded-xl border border-dashed border-gray-300 p-4 dark:border-gray-600">
          <p class="mb-1 text-sm font-semibold text-gray-700 dark:text-gray-200">Application window <span class="font-normal text-gray-400">(optional)</span></p>
          <p class="mb-3 text-xs text-gray-500">When set, this opens/closes applications for <strong>every program in this cohort</strong> — enter the intake dates once here. Leave blank to keep each program's own window (or rolling).</p>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Applications open</label>
              <input v-model="form.intake_opens_at" type="date" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Applications close</label>
              <input v-model="form.intake_closes_at" type="date" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
            </div>
          </div>
        </div>

        <!-- Programs + supervisors -->
        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-600">
          <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Programs in this cohort</p>
          <p class="mb-3 text-xs text-gray-500">Each program gets its own supervisor. Accepted applicants for a program join this cohort under that supervisor.</p>
          <div v-for="(row, i) in form.programs" :key="i" class="mb-2 flex items-center gap-2">
            <select v-model="row.program_id" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
              <option value="">Select program…</option>
              <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.title }}</option>
            </select>
            <select v-model="row.supervisor_id" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
              <option value="">Supervisor (optional)</option>
              <option v-for="s in supervisors" :key="s.id" :value="s.id">{{ s.name }} ({{ s.role }})</option>
            </select>
            <button v-if="form.programs.length > 1" type="button" class="rounded-lg p-2 text-gray-400 hover:text-red-500" @click="removeProgramRow(i)">
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
          <div class="mt-1 flex flex-wrap items-center gap-4">
            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-[#381998] hover:underline" @click="addProgramRow">
              <Plus class="h-3.5 w-3.5" /> Add another program
            </button>
            <button
              v-if="!allProgramsSelected"
              type="button"
              class="inline-flex items-center gap-1 text-xs font-semibold text-[#381998] hover:underline"
              @click="selectAllPrograms"
            >
              <CheckCheck class="h-3.5 w-3.5" /> Select all programs
            </button>
          </div>
        </div>

        <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-600">
          <input v-model="form.is_intake" type="checkbox" class="mt-0.5 h-4 w-4 accent-[#381998]" />
          <span>
            <span class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Open for intake</span>
            <span class="block text-xs text-gray-500">Newly-accepted applicants are auto-placed in this cohort (under their program's supervisor). Only one intake cohort at a time.</span>
          </span>
        </label>

        <div class="flex items-center justify-end gap-3">
          <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium text-gray-600 hover:text-[#381998]" @click="showCreate = false">Cancel</button>
          <button type="submit" :disabled="form.processing" class="rounded-xl bg-[#381998] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-60">
            {{ form.processing ? 'Creating…' : 'Create Cohort' }}
          </button>
        </div>
      </form>
    </div>

    <!-- List -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div v-if="cohorts.data.length === 0" class="p-10 text-center text-sm text-gray-500">
        No cohorts yet. Create one and add its programs to start receiving accepted interns.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700">
          <tr>
            <th class="px-5 py-3">Cohort</th>
            <th class="px-5 py-3">Programs</th>
            <th class="px-5 py-3">Dates</th>
            <th class="px-5 py-3">Interns</th>
            <th class="px-5 py-3">Status</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
          <tr v-for="c in cohorts.data" :key="c.id" class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
            <td class="px-5 py-3 font-semibold text-[#000928] dark:text-white">
              {{ c.name }}
              <span v-if="c.is_intake" class="ml-1.5 rounded-full bg-[#42b6c5]/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#2a8a96]">Intake</span>
            </td>
            <td class="px-5 py-3 max-w-xs">
              <span v-if="c.programs.length === 0" class="text-gray-400">—</span>
              <div v-else class="flex flex-wrap gap-1">
                <span
                  v-for="p in (expandedPrograms.has(c.id) ? c.programs : c.programs.slice(0, PROGRAM_BADGE_LIMIT))"
                  :key="p"
                  class="inline-block rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-200"
                >{{ p }}</span>
                <button
                  v-if="c.programs.length > PROGRAM_BADGE_LIMIT"
                  type="button"
                  class="inline-block rounded-md px-2 py-0.5 text-xs font-semibold text-[#381998] hover:underline"
                  @click="toggleProgramsExpanded(c.id)"
                >
                  {{ expandedPrograms.has(c.id) ? 'Show less' : `+${c.programs.length - PROGRAM_BADGE_LIMIT} more` }}
                </button>
              </div>
            </td>
            <td class="px-5 py-3 text-gray-500">
              <span class="inline-flex items-center gap-1"><CalendarDays class="h-3.5 w-3.5" />{{ c.start_date }} → {{ c.end_date }}</span>
            </td>
            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
              <span class="inline-flex items-center gap-1"><Users class="h-3.5 w-3.5" />{{ c.internships_count }}</span>
            </td>
            <td class="px-5 py-3">
              <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', statusClass[c.status] || 'bg-gray-100 text-gray-700']">{{ c.status }}</span>
            </td>
            <td class="px-5 py-3 text-right">
              <div class="flex items-center justify-end gap-3">
                <Link :href="`/admin/internships/cohorts/${c.id}`" class="text-xs font-semibold text-[#381998] hover:underline">Manage</Link>
                <button type="button" class="text-xs font-semibold text-red-500 hover:underline" @click="deleteCohort(c)">Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="cohorts.links.length > 3" class="mt-4 flex flex-wrap gap-1">
      <button
        v-for="link in cohorts.links"
        :key="link.label"
        :disabled="!link.url"
        class="rounded-lg px-3 py-1.5 text-xs"
        :class="link.active ? 'bg-[#381998] text-white' : 'text-gray-600 hover:bg-gray-100 disabled:opacity-40 dark:text-gray-300'"
        @click="link.url && router.get(link.url, {}, { preserveScroll: true })"
        v-html="link.label"
      />
    </div>
  </div>
</template>
