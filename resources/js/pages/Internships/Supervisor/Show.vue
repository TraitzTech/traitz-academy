<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Download } from 'lucide-vue-next'
import { reactive } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'
import { lessonBodyHtml } from '@/utils/lessonContentHtml'

defineOptions({ layout: AppLayout })

interface Attendance { id: number; date: string; clock_in_at: string | null; clock_out_at: string | null; hours: string | number | null; status: string }
interface Logbook { id: number; date: string; content: string; hours_spent: string | number | null; learnings: string | null; blockers: string | null; status: string; supervisor_feedback: string | null }
interface Compliance { working_days_elapsed: number; logbook_entries_submitted: number; missed_logbook_days: number }

const props = defineProps<{
  internship: { id: number; name: string | null; email: string | null; program: string | null; cohort: string | null; status: string }
  attendance: Attendance[]
  logbook: Logbook[]
  compliance: Compliance
}>()

const toast = useToast()
const feedback = reactive<Record<number, string>>({})

function fmt(iso: string | null) {
  return iso ? new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—'
}

function review(entryId: number, status: 'approved' | 'needs_revision') {
  router.put(`/supervisor/interns/logbook/${entryId}/review`, {
    status,
    supervisor_feedback: feedback[entryId] ?? '',
  }, {
    preserveScroll: true,
    onError: () => toast.error('Could not save the review.'),
  })
}

function markAttendance(date: string, status: string) {
  router.post(`/supervisor/interns/${props.internship.id}/attendance`, { date, status }, {
    preserveScroll: true,
  })
}

const attStatus: Record<string, string> = {
  present: 'bg-green-100 text-green-700',
  late: 'bg-amber-100 text-amber-700',
  absent: 'bg-red-100 text-red-700',
  excused: 'bg-blue-100 text-blue-700',
}
</script>

<template>
  <div class="mx-auto max-w-6xl space-y-6">
    <Head :title="`Intern: ${internship.name}`" />

    <Link href="/supervisor/interns" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-[#381998] dark:text-gray-300">
      <ArrowLeft class="h-4 w-4" /> My interns
    </Link>

    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#000928] dark:text-white">{{ internship.name }}</h1>
        <p class="mt-0.5 text-sm text-gray-500">{{ internship.program }}<span v-if="internship.cohort"> · {{ internship.cohort }}</span> · {{ internship.email }}</p>
      </div>
      <a
        :href="`/supervisor/interns/${internship.id}/logbook.pdf`"
        class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:border-[#381998] hover:text-[#381998] dark:border-gray-600 dark:text-gray-200"
      >
        <Download class="h-4 w-4" /> Logbook PDF
      </a>
    </div>

    <!-- Logbook compliance -->
    <div class="grid grid-cols-3 gap-4">
      <div class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="text-2xl font-bold text-[#000928] dark:text-white">{{ compliance.working_days_elapsed }}</p>
        <p class="mt-1 text-xs text-gray-500">Working days so far</p>
      </div>
      <div class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="text-2xl font-bold text-emerald-600">{{ compliance.logbook_entries_submitted }}</p>
        <p class="mt-1 text-xs text-gray-500">Logbook entries submitted</p>
      </div>
      <div class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p :class="['text-2xl font-bold', compliance.missed_logbook_days > 0 ? 'text-rose-600' : 'text-gray-400']">{{ compliance.missed_logbook_days }}</p>
        <p class="mt-1 text-xs text-gray-500">Missed working days</p>
      </div>
    </div>

    <!-- Logbook review -->
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="mb-4 font-bold text-[#000928] dark:text-white">Logbook</h2>
      <div v-if="logbook.length === 0" class="text-sm text-gray-500">No logbook entries yet.</div>
      <div v-else class="space-y-4">
        <div v-for="e in logbook" :key="e.id" class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
          <div class="mb-2 flex items-center justify-between">
            <span class="text-sm font-semibold text-[#000928] dark:text-white">{{ e.date }}</span>
            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold capitalize text-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ e.status.replace('_', ' ') }}</span>
          </div>
          <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-200 dark:prose-invert" v-html="lessonBodyHtml(e.content, 'No entry text.')" />
          <div class="mt-2 grid gap-2 text-xs text-gray-500 sm:grid-cols-2">
            <p v-if="e.hours_spent">Hours: {{ e.hours_spent }}</p>
            <p v-if="e.learnings">Learnings: {{ e.learnings }}</p>
            <p v-if="e.blockers">Blockers: {{ e.blockers }}</p>
          </div>
          <p v-if="e.supervisor_feedback" class="mt-2 rounded-lg bg-amber-50 px-3 py-1.5 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">Feedback: {{ e.supervisor_feedback }}</p>

          <div v-if="e.status === 'submitted'" class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
            <textarea v-model="feedback[e.id]" rows="2" placeholder="Feedback (optional)…" class="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
            <div class="mt-2 flex gap-2">
              <button class="rounded-lg bg-green-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-green-700" @click="review(e.id, 'approved')">Approve</button>
              <button class="rounded-lg bg-amber-500 px-4 py-1.5 text-xs font-semibold text-white hover:bg-amber-600" @click="review(e.id, 'needs_revision')">Request revision</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <h2 class="border-b border-gray-100 px-6 py-4 font-bold text-[#000928] dark:border-gray-700 dark:text-white">Attendance</h2>
      <div v-if="attendance.length === 0" class="p-6 text-sm text-gray-500">No attendance recorded yet.</div>
      <table v-else class="w-full text-sm">
        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
          <tr>
            <th class="px-6 py-3">Date</th>
            <th class="px-6 py-3">In</th>
            <th class="px-6 py-3">Out</th>
            <th class="px-6 py-3">Hours</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Mark</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
          <tr v-for="a in attendance" :key="a.id">
            <td class="px-6 py-3 font-medium text-gray-700 dark:text-gray-200">{{ a.date }}</td>
            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ fmt(a.clock_in_at) }}</td>
            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ fmt(a.clock_out_at) }}</td>
            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ a.hours ?? '—' }}</td>
            <td class="px-6 py-3"><span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize', attStatus[a.status] || 'bg-gray-100 text-gray-700']">{{ a.status }}</span></td>
            <td class="px-6 py-3">
              <select :value="a.status" class="rounded-lg border border-gray-200 px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white" @change="markAttendance(a.date, ($event.target as HTMLSelectElement).value)">
                <option value="present">Present</option>
                <option value="late">Late</option>
                <option value="absent">Absent</option>
                <option value="excused">Excused</option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
