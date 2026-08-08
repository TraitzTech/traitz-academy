<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Search, Users } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface Intern {
  id: number
  name: string | null
  email: string | null
  program: string | null
  cohort: string | null
  status: string
  pending_reviews: number
  missed_logbook_days: number
  supervisor: string | null
  start_date: string | null
}

const props = defineProps<{ interns: Intern[]; viewAll?: boolean }>()

// ─── Filters (client-side — list sizes here are small) ──────────────────────
const search = ref('')
const programFilter = ref('')
const cohortFilter = ref('')
const statusFilter = ref('')

const programs = computed(() => [...new Set(props.interns.map((i) => i.program).filter(Boolean))] as string[])
const cohorts = computed(() => [...new Set(props.interns.map((i) => i.cohort).filter(Boolean))] as string[])
const statuses = computed(() => [...new Set(props.interns.map((i) => i.status))])

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  return props.interns.filter((i) => {
    if (q && !`${i.name} ${i.email}`.toLowerCase().includes(q)) return false
    if (programFilter.value && i.program !== programFilter.value) return false
    if (cohortFilter.value && i.cohort !== cohortFilter.value) return false
    if (statusFilter.value && i.status !== statusFilter.value) return false
    return true
  })
})

function fmtDate(d: string | null) {
  return d ? new Date(d).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : '—'
}
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <Head :title="viewAll ? 'Intern Activity' : 'My Interns'" />

    <div class="mb-6 flex items-center gap-3">
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#381998]/10">
        <Users class="h-5 w-5 text-[#381998]" />
      </div>
      <div>
        <h1 class="text-2xl font-bold text-[#000928] dark:text-white">{{ viewAll ? 'Intern Activity' : 'My Interns' }}</h1>
        <p class="text-sm text-gray-500">
          {{ viewAll ? 'Every intern across all cohorts — review logbooks and attendance.' : 'Interns you supervise — review their logbooks and attendance.' }}
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div v-if="interns.length > 0" class="mb-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-[220px]">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input
          v-model="search"
          type="text"
          placeholder="Search by name or email…"
          class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-4 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
        />
      </div>
      <select v-model="programFilter" class="rounded-xl border border-gray-200 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
        <option value="">All programs</option>
        <option v-for="p in programs" :key="p" :value="p">{{ p }}</option>
      </select>
      <select v-model="cohortFilter" class="rounded-xl border border-gray-200 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
        <option value="">All cohorts</option>
        <option v-for="c in cohorts" :key="c" :value="c">{{ c }}</option>
      </select>
      <select v-model="statusFilter" class="rounded-xl border border-gray-200 px-3 py-2.5 text-sm capitalize dark:border-gray-600 dark:bg-gray-900 dark:text-white">
        <option value="">All statuses</option>
        <option v-for="s in statuses" :key="s" :value="s" class="capitalize">{{ s }}</option>
      </select>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div v-if="interns.length === 0" class="p-10 text-center text-sm text-gray-500">
        {{ viewAll ? 'No interns yet. Accept an internship application to create one.' : 'No interns are assigned to you yet.' }}
      </div>
      <div v-else-if="filtered.length === 0" class="p-10 text-center text-sm text-gray-500">
        No interns match your filters.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full table-fixed text-sm">
          <colgroup>
            <col class="w-[16%]" />
            <col class="w-[13%]" />
            <col class="w-[11%]" />
            <col v-if="viewAll" class="w-[10%]" />
            <col class="w-[9%]" />
            <col class="w-[8%]" />
            <col class="w-[12%]" />
            <col class="w-[12%]" />
            <col class="w-[8%]" />
          </colgroup>
          <thead class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700">
            <tr>
              <th class="px-5 py-3">Intern</th>
              <th class="px-5 py-3">Program</th>
              <th class="px-5 py-3">Cohort</th>
              <th v-if="viewAll" class="px-5 py-3">Supervisor</th>
              <th class="px-5 py-3">Started</th>
              <th class="px-5 py-3">Status</th>
              <th class="px-5 py-3">To review</th>
              <th class="px-5 py-3">Missed logs</th>
              <th class="px-5 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
            <tr v-for="i in filtered" :key="i.id" class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
              <td class="px-5 py-3">
                <p class="truncate font-semibold text-[#000928] dark:text-white">{{ i.name }}</p>
                <p class="truncate text-xs text-gray-400">{{ i.email }}</p>
              </td>
              <td class="truncate px-5 py-3 text-gray-600 dark:text-gray-300">{{ i.program || '—' }}</td>
              <td class="truncate px-5 py-3 text-gray-600 dark:text-gray-300">{{ i.cohort || '—' }}</td>
              <td v-if="viewAll" class="truncate px-5 py-3 text-gray-600 dark:text-gray-300">{{ i.supervisor || '—' }}</td>
              <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ fmtDate(i.start_date) }}</td>
              <td class="truncate px-5 py-3 capitalize text-gray-600 dark:text-gray-300">{{ i.status }}</td>
              <td class="px-5 py-3">
                <span v-if="i.pending_reviews > 0" class="inline-block whitespace-nowrap rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">{{ i.pending_reviews }} pending</span>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
              <td class="px-5 py-3">
                <span v-if="i.missed_logbook_days > 0" class="inline-block whitespace-nowrap rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">{{ i.missed_logbook_days }} missed</span>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
              <td class="px-5 py-3 text-right">
                <Link :href="`/supervisor/interns/${i.id}`" class="text-xs font-semibold text-[#381998] hover:underline">Open</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
