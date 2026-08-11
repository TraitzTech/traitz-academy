<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { CalendarDays, Layers, Users } from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface Cohort {
  id: number
  name: string
  status: string
  is_intake: boolean
  start_date: string | null
  end_date: string | null
  intake_opens_at: string | null
  intake_closes_at: string | null
  my_interns_count: number
  programs: string[]
}

defineProps<{ cohorts: Cohort[] }>()

const statusClass: Record<string, string> = {
  upcoming: 'bg-blue-100 text-blue-700',
  active: 'bg-green-100 text-green-700',
  completed: 'bg-gray-100 text-gray-700',
  cancelled: 'bg-red-100 text-red-700',
}
</script>

<template>
  <div class="mx-auto max-w-full">
    <Head title="Cohorts" />

    <div class="mb-6 flex items-center gap-3">
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#381998]/10">
        <Layers class="h-5 w-5 text-[#381998]" />
      </div>
      <div>
        <h1 class="text-2xl font-bold text-[#000928] dark:text-white">Cohorts</h1>
        <p class="text-sm text-gray-500">Cohorts you have interns in — dates and rosters, read-only.</p>
      </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div v-if="cohorts.length === 0" class="p-10 text-center text-sm text-gray-500">
        You don't have any interns placed in a cohort yet.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700">
          <tr>
            <th class="px-5 py-3">Cohort</th>
            <th class="px-5 py-3">Programs</th>
            <th class="px-5 py-3">Dates</th>
            <th class="px-5 py-3">Your interns</th>
            <th class="px-5 py-3">Status</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
          <tr v-for="c in cohorts" :key="c.id" class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30">
            <td class="px-5 py-3 font-semibold text-[#000928] dark:text-white">
              {{ c.name }}
              <span v-if="c.is_intake" class="ml-1.5 rounded-full bg-[#42b6c5]/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#2a8a96]">Intake</span>
            </td>
            <td class="max-w-xs px-5 py-3">
              <span v-if="c.programs.length === 0" class="text-gray-400">—</span>
              <div v-else class="flex flex-wrap gap-1">
                <span
                  v-for="p in c.programs"
                  :key="p"
                  class="inline-block rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-200"
                >{{ p }}</span>
              </div>
            </td>
            <td class="px-5 py-3 text-gray-500">
              <span class="inline-flex items-center gap-1"><CalendarDays class="h-3.5 w-3.5" />{{ c.start_date }} → {{ c.end_date }}</span>
            </td>
            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
              <span class="inline-flex items-center gap-1"><Users class="h-3.5 w-3.5" />{{ c.my_interns_count }}</span>
            </td>
            <td class="px-5 py-3">
              <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', statusClass[c.status] || 'bg-gray-100 text-gray-700']">{{ c.status }}</span>
            </td>
            <td class="px-5 py-3 text-right">
              <Link :href="`/supervisor/cohorts/${c.id}`" class="text-xs font-semibold text-[#381998] hover:underline">View</Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
