<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { AlertTriangle, CalendarClock, CheckCircle2, ClipboardCheck, ClipboardList, Clock, UserCheck, Users } from 'lucide-vue-next'
import { computed } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface AttentionRow {
  id: number
  name: string | null
  email: string | null
  program: string | null
  cohort: string | null
  status: string
  pending_reviews: number
  missed_logbook_days: number
  clocked_in_today: boolean
}

const props = defineProps<{
  stats: { interns: number; pending_reviews: number; behind: number; in_today: number }
  attention: AttentionRow[]
}>()

const cards = computed(() => [
  { label: 'Interns', value: props.stats.interns, icon: Users, tone: 'text-[#381998]', bg: 'bg-[#381998]/10' },
  { label: 'Pending reviews', value: props.stats.pending_reviews, icon: ClipboardCheck, tone: 'text-amber-600', bg: 'bg-amber-100 dark:bg-amber-900/30' },
  { label: 'Behind on logs', value: props.stats.behind, icon: AlertTriangle, tone: 'text-rose-600', bg: 'bg-rose-100 dark:bg-rose-900/30' },
  { label: 'In today', value: props.stats.in_today, icon: UserCheck, tone: 'text-emerald-600', bg: 'bg-emerald-100 dark:bg-emerald-900/30' },
])
</script>

<template>
  <div class="space-y-6">
    <Head title="Supervisor Dashboard" />

    <div>
      <h1 class="text-2xl font-bold text-[#000928] dark:text-white">Supervisor dashboard</h1>
      <p class="mt-0.5 text-sm text-gray-500">Everything across the interns you supervise.</p>
    </div>

    <!-- Stat cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="c in cards"
        :key="c.label"
        class="overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
      >
        <div class="flex items-center gap-3">
          <div :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', c.bg]">
            <component :is="c.icon" :class="['h-5 w-5', c.tone]" />
          </div>
          <div>
            <p class="text-2xl font-bold text-[#000928] dark:text-white">{{ c.value }}</p>
            <p class="text-xs font-medium text-gray-500">{{ c.label }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="flex flex-wrap gap-3">
      <Link href="/supervisor/interns" class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928]">
        <ClipboardList class="h-4 w-4" /> Intern activity
      </Link>
      <Link href="/tutor/assignments" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-[#381998] hover:text-[#381998] dark:border-gray-600 dark:text-gray-200">
        <ClipboardList class="h-4 w-4" /> Tasks
      </Link>
      <Link href="/tutor/schedules" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-[#381998] hover:text-[#381998] dark:border-gray-600 dark:text-gray-200">
        <CalendarClock class="h-4 w-4" /> Schedule
      </Link>
    </div>

    <!-- Needs attention -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
        <h2 class="text-sm font-bold text-[#000928] dark:text-white">Needs your attention</h2>
        <p class="text-xs text-gray-400">Interns with logbooks to review or missed days.</p>
      </div>

      <div v-if="attention.length === 0" class="p-10 text-center">
        <CheckCircle2 class="mx-auto h-8 w-8 text-emerald-500" />
        <p class="mt-2 text-sm text-gray-500">All caught up — nothing pending right now.</p>
      </div>

      <div v-else class="divide-y divide-gray-50 dark:divide-gray-700/50">
        <Link
          v-for="row in attention"
          :key="row.id"
          :href="`/supervisor/interns/${row.id}`"
          class="flex items-center gap-3 px-6 py-3 transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-700/30"
        >
          <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-[#000928] dark:text-white">{{ row.name }}</p>
            <p class="truncate text-xs text-gray-400">{{ row.program }}<span v-if="row.cohort"> · {{ row.cohort }}</span></p>
          </div>
          <span v-if="row.clocked_in_today" class="hidden shrink-0 items-center gap-1 text-xs text-emerald-600 sm:inline-flex">
            <Clock class="h-3 w-3" /> In today
          </span>
          <span v-if="row.pending_reviews > 0" class="inline-block shrink-0 whitespace-nowrap rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
            {{ row.pending_reviews }} to review
          </span>
          <span v-if="row.missed_logbook_days > 0" class="inline-block shrink-0 whitespace-nowrap rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
            {{ row.missed_logbook_days }} missed
          </span>
        </Link>
      </div>
    </div>
  </div>
</template>
