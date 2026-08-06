<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Clock, LogIn, LogOut, MapPin, NotebookPen } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface Attendance { clock_in_at: string | null; clock_out_at: string | null; hours: string | number | null; status: string }
interface Logbook { content: string; hours_spent: string | number | null; learnings: string | null; blockers: string | null; status: string; supervisor_feedback: string | null }

const props = defineProps<{
  internship: { id: number; program: string | null; cohort: string | null; supervisor: string | null; start_date: string | null; end_date: string | null; status: string; work_mode: string }
  today: string
  attendance: Attendance | null
  logbook: Logbook | null
  requiresLocation: boolean
  requiresLogbookBeforeClockOut: boolean
}>()

const toast = useToast()
const busy = ref(false)

const clockedIn = computed(() => !!props.attendance?.clock_in_at)
const clockedOut = computed(() => !!props.attendance?.clock_out_at)
const logbookDone = computed(() => !!props.logbook && ['submitted', 'approved'].includes(props.logbook.status))
const canClockOut = computed(() => clockedIn.value && !clockedOut.value && (!props.requiresLogbookBeforeClockOut || logbookDone.value))
const logbookLocked = computed(() => props.logbook?.status === 'approved')

function fmtTime(iso: string | null): string {
  return iso ? new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—'
}

function post(url: string, data: Record<string, unknown> = {}) {
  busy.value = true
  useForm(data).post(url, {
    preserveScroll: true,
    onSuccess: () => { /* flash handled by layout */ },
    onError: (errors) => {
      const first = Object.values(errors)[0] as string
      if (first) toast.error(first)
    },
    onFinish: () => { busy.value = false },
  })
}

function clockIn() {
  if (!props.requiresLocation) {
    post('/dashboard/internship/attendance/clock-in')
    return
  }
  if (!navigator.geolocation) {
    toast.error('Your browser does not support location. Ask an admin to check the office is set up.')
    return
  }
  busy.value = true
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      busy.value = false
      post('/dashboard/internship/attendance/clock-in', {
        latitude: pos.coords.latitude,
        longitude: pos.coords.longitude,
      })
    },
    () => {
      busy.value = false
      toast.error('Could not get your location. Allow location access (needs HTTPS) and make sure you are at the office.')
    },
    { enableHighAccuracy: true, timeout: 10000 },
  )
}

function clockOut() {
  post('/dashboard/internship/attendance/clock-out')
}

const logbookForm = useForm({
  content: props.logbook?.content ?? '',
  hours_spent: props.logbook?.hours_spent ?? '',
  learnings: props.logbook?.learnings ?? '',
  blockers: props.logbook?.blockers ?? '',
})

function saveLogbook() {
  logbookForm.post('/dashboard/internship/logbook', {
    preserveScroll: true,
    onError: () => toast.error('Please write what you did today.'),
  })
}
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <Head title="My Internship" />

    <!-- Overview -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="h-1.5 bg-gradient-to-r from-[#381998] via-[#42b6c5] to-[#000928]"></div>
      <div class="p-6">
        <h1 class="text-xl font-bold text-[#000928] dark:text-white">{{ internship.program || 'Internship' }}</h1>
        <div class="mt-2 grid gap-1 text-sm text-gray-500 sm:grid-cols-2">
          <p v-if="internship.cohort">Cohort: <span class="text-gray-700 dark:text-gray-200">{{ internship.cohort }}</span></p>
          <p>Supervisor: <span class="text-gray-700 dark:text-gray-200">{{ internship.supervisor || 'To be assigned' }}</span></p>
          <p>Period: <span class="text-gray-700 dark:text-gray-200">{{ internship.start_date || '—' }} → {{ internship.end_date || '—' }}</span></p>
          <p>Mode: <span class="text-gray-700 dark:text-gray-200 capitalize">{{ internship.work_mode }}</span></p>
        </div>
      </div>
    </div>

    <!-- Attendance -->
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="mb-4 flex items-center gap-2">
        <Clock class="h-5 w-5 text-[#381998]" />
        <h2 class="font-bold text-[#000928] dark:text-white">Today · {{ today }}</h2>
      </div>

      <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900/40">
          <p class="text-xs uppercase tracking-wide text-gray-400">Clocked in</p>
          <p class="mt-1 text-lg font-semibold text-[#000928] dark:text-white">{{ fmtTime(attendance?.clock_in_at ?? null) }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900/40">
          <p class="text-xs uppercase tracking-wide text-gray-400">Clocked out</p>
          <p class="mt-1 text-lg font-semibold text-[#000928] dark:text-white">{{ fmtTime(attendance?.clock_out_at ?? null) }}</p>
          <p v-if="attendance?.hours" class="text-xs text-gray-500">{{ attendance.hours }} hours</p>
        </div>
      </div>

      <p v-if="requiresLocation" class="mb-3 inline-flex items-center gap-1.5 text-xs text-gray-500">
        <MapPin class="h-3.5 w-3.5" /> You can only clock in from the office. We'll check your location.
      </p>

      <div class="flex flex-wrap gap-3">
        <button
          v-if="!clockedIn"
          :disabled="busy"
          class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-60"
          @click="clockIn"
        >
          <LogIn class="h-4 w-4" /> {{ busy ? 'Checking…' : 'Clock In' }}
        </button>

        <template v-else-if="!clockedOut">
          <button
            :disabled="busy || !canClockOut"
            class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed"
            :class="canClockOut ? 'bg-[#381998] hover:bg-[#000928]' : 'bg-gray-300 text-gray-500'"
            @click="clockOut"
          >
            <LogOut class="h-4 w-4" /> Clock Out
          </button>
          <p v-if="!canClockOut" class="self-center text-xs text-amber-600">Fill and submit your logbook below before clocking out.</p>
        </template>

        <p v-else class="self-center text-sm font-medium text-green-600">✓ You're done for today. See you next time!</p>
      </div>
    </div>

    <!-- Logbook -->
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="mb-1 flex items-center gap-2">
        <NotebookPen class="h-5 w-5 text-[#42b6c5]" />
        <h2 class="font-bold text-[#000928] dark:text-white">Today's logbook</h2>
        <span v-if="logbook" class="ml-auto rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600 capitalize dark:bg-gray-700 dark:text-gray-200">{{ logbook.status.replace('_', ' ') }}</span>
      </div>
      <p class="mb-4 text-xs text-gray-500">Record what you worked on today. This must be submitted before you clock out.</p>

      <div v-if="logbook?.supervisor_feedback" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
        <p class="font-semibold">Supervisor feedback</p>
        <p class="mt-0.5">{{ logbook.supervisor_feedback }}</p>
      </div>

      <fieldset :disabled="logbookLocked" class="space-y-4">
        <div>
          <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">What did you do today? *</label>
          <textarea v-model="logbookForm.content" rows="5" placeholder="Tasks worked on, progress made…" class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
          <p v-if="logbookForm.errors.content" class="mt-1 text-xs text-red-500">{{ logbookForm.errors.content }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Hours spent</label>
            <input v-model="logbookForm.hours_spent" type="number" min="0" max="24" step="0.5" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">What did you learn?</label>
            <textarea v-model="logbookForm.learnings" rows="2" class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">Blockers / challenges</label>
            <textarea v-model="logbookForm.blockers" rows="2" class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
          </div>
        </div>
        <div v-if="!logbookLocked" class="flex justify-end">
          <button :disabled="logbookForm.processing" class="rounded-xl bg-[#42b6c5] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#35919e] disabled:opacity-60" @click="saveLogbook">
            {{ logbookForm.processing ? 'Saving…' : 'Submit logbook' }}
          </button>
        </div>
        <p v-else class="text-sm font-medium text-green-600">✓ Approved by your supervisor — locked.</p>
      </fieldset>
    </div>
  </div>
</template>
