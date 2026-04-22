<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface LiveClass { id: number; title: string; start_time: string; duration: number; host_online?: boolean; tutor?: { id: number; name: string } | null }
const props = defineProps<{ classes: LiveClass[]; now: string }>()

function statusOf(row: LiveClass): 'starting_soon' | 'live' | 'ended' | 'upcoming' {
  const now = new Date(props.now).getTime()
  const start = new Date(row.start_time).getTime()
  const end = start + row.duration * 60000
  if (now >= start - 5 * 60000 && now < start) return 'starting_soon'
  if (now >= start && now <= end) return 'live'
  if (now > end) return 'ended'
  return 'upcoming'
}

function canJoin(row: LiveClass): boolean {
  const status = statusOf(row)
  return (status === 'starting_soon' || status === 'live') && Boolean(row.host_online)
}
</script>

<template>
  <div>
    <Head title="Live Classes" />
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Live classes</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Join your scheduled sessions and recordings.</p>
      <div class="mt-3">
        <Link href="/dashboard/live-classes/recordings" class="inline-flex rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
          View recorded live classes
        </Link>
      </div>
    </div>
    <div v-if="classes.length === 0" class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-600">
      No live classes yet.
    </div>
    <div v-else class="grid gap-3">
      <div v-for="row in classes" :key="row.id" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="font-semibold text-[#000928] dark:text-white">{{ row.title }}</p>
            <p class="text-xs text-gray-500">Tutor: {{ row.tutor?.name || '—' }} · {{ new Date(row.start_time).toLocaleString() }} · {{ row.duration }} min</p>
          </div>
          <div class="flex items-center gap-2">
            <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold',statusOf(row) === 'live' ? 'bg-red-100 text-red-700' : statusOf(row) === 'starting_soon' ? 'bg-amber-100 text-amber-700' : statusOf(row) === 'ended' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700']">
              {{ statusOf(row) === 'live' ? 'Live Now' : statusOf(row) === 'starting_soon' ? 'Starting Soon' : statusOf(row) === 'ended' ? 'Ended' : 'Upcoming' }}
            </span>
            <Link :href="`/dashboard/live-classes/${row.id}/details`" class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
              View
            </Link>
            <Link :href="`/dashboard/live-classes/${row.id}`" :class="['rounded-lg px-3 py-2 text-xs font-semibold', canJoin(row) ? 'bg-[#381998] text-white hover:bg-[#000928]' : 'bg-gray-200 text-gray-500']">
              Join
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
