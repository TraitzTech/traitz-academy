<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Plus, Trash2, UserMinus, UserPlus } from 'lucide-vue-next'
import { ref } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface Option { id: number; title?: string; name?: string; role?: string }
interface Intern {
  id: number
  name: string | null
  email: string | null
  program: string | null
  status: string
  supervisor: string | null
  effective_supervisor_id: number | null
}
interface CohortProgram { id: number; title: string; supervisor_id: number | null }
interface Cohort {
  id: number
  name: string
  start_date: string | null
  end_date: string | null
  intake_opens_at: string | null
  intake_closes_at: string | null
  status: string
  is_intake: boolean
  timezone: string | null
  programs: CohortProgram[]
}

const props = defineProps<{
  cohort: Cohort
  interns: Intern[]
  available: { id: number; name: string | null; email: string | null; program: string | null }[]
  programs: Option[]
  supervisors: Option[]
}>()

const toast = useToast()

const settings = useForm({
  name: props.cohort.name,
  start_date: props.cohort.start_date ?? '',
  end_date: props.cohort.end_date ?? '',
  intake_opens_at: props.cohort.intake_opens_at ?? '',
  intake_closes_at: props.cohort.intake_closes_at ?? '',
  status: props.cohort.status,
  is_intake: props.cohort.is_intake ?? false,
  timezone: props.cohort.timezone ?? '',
  programs: props.cohort.programs.map((p) => ({ program_id: p.id as number | string, supervisor_id: (p.supervisor_id ?? '') as number | string })),
})

function addProgramRow() {
  settings.programs.push({ program_id: '', supervisor_id: '' })
}
function removeProgramRow(i: number) {
  settings.programs.splice(i, 1)
}

function saveSettings() {
  settings.transform((data) => ({
    ...data,
    programs: data.programs.filter((p) => p.program_id),
  })).put(`/admin/internships/cohorts/${props.cohort.id}`, {
    preserveScroll: true,
    onError: () => toast.error('Could not update cohort.'),
  })
}

const selectedIntern = ref<number | ''>('')
function addIntern() {
  if (!selectedIntern.value) return
  router.post(`/admin/internships/cohorts/${props.cohort.id}/interns`, { internship_id: selectedIntern.value }, {
    preserveScroll: true,
    onSuccess: () => { selectedIntern.value = '' },
    onError: (e) => toast.error((Object.values(e)[0] as string) || 'Could not add intern.'),
  })
}
function removeIntern(internshipId: number) {
  router.delete(`/admin/internships/cohorts/${props.cohort.id}/interns/${internshipId}`, { preserveScroll: true })
}
function setInternSupervisor(internshipId: number, supervisorId: string) {
  router.put(`/admin/internships/internships/${internshipId}/supervisor`, { supervisor_id: supervisorId || null }, { preserveScroll: true })
}
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-6">
    <Head :title="`Cohort: ${cohort.name}`" />

    <Link href="/admin/internships/cohorts" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-[#381998] dark:text-gray-300">
      <ArrowLeft class="h-4 w-4" /> All cohorts
    </Link>

    <div>
      <h1 class="text-2xl font-bold text-[#000928] dark:text-white">{{ cohort.name }}</h1>
      <p class="mt-0.5 text-sm text-gray-500">{{ cohort.start_date }} → {{ cohort.end_date }}</p>
    </div>

    <!-- Settings -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="h-1.5 bg-gradient-to-r from-[#381998] via-[#42b6c5] to-[#000928]"></div>
      <div class="space-y-4 p-6">
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Name</label>
            <input v-model="settings.name" type="text" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Status</label>
            <select v-model="settings.status" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
              <option value="upcoming">Upcoming</option>
              <option value="active">Active</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Start date</label>
            <input v-model="settings.start_date" type="date" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">End date</label>
            <input v-model="settings.end_date" type="date" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
          </div>
        </div>

        <!-- Application window -->
        <div class="rounded-xl border border-dashed border-gray-300 p-4 dark:border-gray-600">
          <p class="mb-1 text-sm font-semibold text-gray-700 dark:text-gray-200">Application window <span class="font-normal text-gray-400">(optional)</span></p>
          <p class="mb-3 text-xs text-gray-500">Saving stamps these dates onto every program in this cohort. Leave blank to keep each program's own window.</p>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Applications open</label>
              <input v-model="settings.intake_opens_at" type="date" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Applications close</label>
              <input v-model="settings.intake_closes_at" type="date" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
            </div>
          </div>
        </div>

        <!-- Programs + supervisors -->
        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-600">
          <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Programs & supervisors</p>
          <p class="mb-3 text-xs text-gray-500">Interns for each program are placed under its supervisor.</p>
          <div v-for="(row, i) in settings.programs" :key="i" class="mb-2 flex items-center gap-2">
            <select v-model="row.program_id" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
              <option value="">Select program…</option>
              <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.title }}</option>
            </select>
            <select v-model="row.supervisor_id" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
              <option value="">Supervisor (optional)</option>
              <option v-for="s in supervisors" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <button type="button" class="rounded-lg p-2 text-gray-400 hover:text-red-500" @click="removeProgramRow(i)">
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
          <button type="button" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[#381998] hover:underline" @click="addProgramRow">
            <Plus class="h-3.5 w-3.5" /> Add program
          </button>
        </div>

        <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-600">
          <input v-model="settings.is_intake" type="checkbox" class="mt-0.5 h-4 w-4 accent-[#381998]" />
          <span>
            <span class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Open for intake</span>
            <span class="block text-xs text-gray-500">Newly-accepted applicants are auto-placed here under their program's supervisor. Only one intake cohort at a time.</span>
          </span>
        </label>

        <div class="flex justify-end">
          <button class="rounded-xl bg-[#381998] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#000928]" @click="saveSettings">Save</button>
        </div>
      </div>
    </div>

    <!-- Add intern -->
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="mb-3 text-sm font-bold text-[#000928] dark:text-white">Add an intern</h2>
      <div v-if="available.length === 0" class="text-sm text-gray-500">
        No unassigned interns for this cohort's programs. Interns appear here once their application is accepted (or auto-join if this is the intake cohort).
      </div>
      <div v-else class="flex flex-wrap items-center gap-3">
        <select v-model="selectedIntern" class="min-w-[260px] flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
          <option value="">Select an accepted intern…</option>
          <option v-for="a in available" :key="a.id" :value="a.id">{{ a.name }} — {{ a.program }} ({{ a.email }})</option>
        </select>
        <button class="inline-flex items-center gap-2 rounded-xl bg-[#42b6c5] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#35919e]" @click="addIntern">
          <UserPlus class="h-4 w-4" /> Add
        </button>
      </div>
    </div>

    <!-- Interns -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="border-b border-gray-100 px-6 py-4 text-sm font-bold text-[#000928] dark:border-gray-700 dark:text-white">Interns ({{ interns.length }})</h2>
      <div v-if="interns.length === 0" class="p-8 text-center text-sm text-gray-500">No interns in this cohort yet.</div>
      <table v-else class="w-full text-sm">
        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
          <tr>
            <th class="px-6 py-3">Intern</th>
            <th class="px-6 py-3">Program</th>
            <th class="px-6 py-3">Supervisor (override)</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
          <tr v-for="i in interns" :key="i.id">
            <td class="px-6 py-3">
              <p class="font-semibold text-[#000928] dark:text-white">{{ i.name }}</p>
              <p class="text-xs text-gray-400">{{ i.email }}</p>
            </td>
            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ i.program }}</td>
            <td class="px-6 py-3">
              <select
                :value="i.effective_supervisor_id ?? ''"
                class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                @change="setInternSupervisor(i.id, ($event.target as HTMLSelectElement).value)"
              >
                <option value="">Use program supervisor</option>
                <option v-for="s in supervisors" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </td>
            <td class="px-6 py-3 text-right">
              <button class="inline-flex items-center gap-1 text-xs font-semibold text-red-500 hover:underline" @click="removeIntern(i.id)">
                <UserMinus class="h-3.5 w-3.5" /> Remove
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
