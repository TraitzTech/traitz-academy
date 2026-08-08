<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { AlertTriangle, ArrowRight, Briefcase, Building2, CalendarRange, CheckCircle2, Clock, Home, Laptop, LogIn, LogOut, MapPin, NotebookPen, Sparkles, UserCheck, Users } from 'lucide-vue-next'
import { computed, onMounted, onUnmounted, ref } from 'vue'

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
  todayIsOfficeDay: boolean | null
  officeDaysLabel: string | null
}>()

const toast = useToast()
const busy = ref(false)

const clockedIn = computed(() => !!props.attendance?.clock_in_at)
const clockedOut = computed(() => !!props.attendance?.clock_out_at)
const logbookDone = computed(() => !!props.logbook && ['submitted', 'approved'].includes(props.logbook.status))
const canClockOut = computed(() => clockedIn.value && !clockedOut.value && (!props.requiresLogbookBeforeClockOut || logbookDone.value))

// Live "how long have I been here" readout while clocked in — fills the
// timeline with something useful instead of an empty line to nowhere.
const now = ref(Date.now())
let clock: ReturnType<typeof setInterval> | undefined
onMounted(() => { clock = setInterval(() => { now.value = Date.now() }, 30_000) })
onUnmounted(() => { if (clock) clearInterval(clock) })

const elapsedLabel = computed(() => {
  if (!props.attendance?.clock_in_at || clockedOut.value) return null
  const minutes = Math.max(0, Math.floor((now.value - new Date(props.attendance.clock_in_at).getTime()) / 60000))
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return h > 0 ? `${h}h ${m}m` : `${m}m`
})

function fmtTime(iso: string | null): string {
  return iso ? new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—'
}

function fmtDate(value: string | null): string {
  if (!value) return '—'
  return new Date(value).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

const statusStyles: Record<string, string> = {
  active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  paused: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
  terminated: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  withdrawn: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
}
const statusBadgeClass = computed(() => statusStyles[props.internship.status] ?? statusStyles.withdrawn)

const isRemoteInternship = computed(() => props.internship.work_mode === 'remote')

// If the program has a fixed office-day schedule, default today's mode from
// it (guide only — the intern can still override via the "Switch" link).
// Otherwise fall back to a free daily choice.
const scheduleConfigured = computed(() => props.todayIsOfficeDay !== null)
const skippingOffice = ref(scheduleConfigured.value ? !props.todayIsOfficeDay : false)
const scheduleIcon = computed(() => (skippingOffice.value ? Home : Building2))

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

const logbookStatusStyles: Record<string, string> = {
  approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  needs_revision: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
  submitted: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200',
  draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200',
}
const logbookStatusClass = computed(() => logbookStatusStyles[props.logbook?.status ?? 'draft'] ?? logbookStatusStyles.draft)
</script>

<template>
  <div class="space-y-6">
    <Head title="My Internship" />

    <!-- Overview -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="h-1.5 bg-gradient-to-r from-[#381998] via-[#42b6c5] to-[#000928]"></div>
      <div class="p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#381998]/10">
              <Briefcase class="h-5 w-5 text-[#381998]" />
            </div>
            <div>
              <h1 class="text-xl font-bold text-[#000928] dark:text-white">{{ internship.program || 'Internship' }}</h1>
              <p v-if="internship.cohort" class="mt-0.5 inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                <Users class="h-3.5 w-3.5" /> {{ internship.cohort }}
              </p>
            </div>
          </div>
          <span :class="['shrink-0 rounded-full px-3 py-1 text-xs font-semibold capitalize', statusBadgeClass]">{{ internship.status }}</span>
        </div>

        <div class="mt-5 grid gap-3 border-t border-gray-100 pt-4 dark:border-gray-700 sm:grid-cols-3">
          <div class="flex items-center gap-2 text-sm">
            <UserCheck class="h-4 w-4 shrink-0 text-gray-400" />
            <span class="text-gray-500 dark:text-gray-400">Supervisor:</span>
            <span class="truncate font-medium text-gray-800 dark:text-gray-100">{{ internship.supervisor || 'To be assigned' }}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <CalendarRange class="h-4 w-4 shrink-0 text-gray-400" />
            <span class="text-gray-500 dark:text-gray-400">Period:</span>
            <span class="font-medium text-gray-800 dark:text-gray-100">{{ fmtDate(internship.start_date) }} → {{ fmtDate(internship.end_date) }}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <Laptop class="h-4 w-4 shrink-0 text-gray-400" />
            <span class="text-gray-500 dark:text-gray-400">Mode:</span>
            <span class="font-medium capitalize text-gray-800 dark:text-gray-100">{{ internship.work_mode }}</span>
            <span v-if="officeDaysLabel" class="text-gray-400">· office {{ officeDaysLabel }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Today: one unified journal panel, not a wall of separate boxes -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="h-1.5 bg-gradient-to-r from-[#42b6c5] to-[#381998]"></div>

      <div class="p-6 sm:p-8">
        <!-- Header: date + status pills -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Today's entry</p>
            <h2 class="text-lg font-bold text-[#000928] dark:text-white">{{ today }}</h2>
          </div>
          <span
            v-if="logbook"
            :class="[
              'shrink-0 rounded-full px-3 py-1 text-xs font-semibold capitalize',
              logbook.status === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                : logbook.status === 'needs_revision' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200',
            ]"
          >Logbook {{ logbook.status.replace('_', ' ') }}</span>
        </div>

        <!-- Attendance -->
        <div class="mb-6 rounded-xl border border-gray-100 dark:border-gray-700">
          <div v-if="isRemoteInternship" class="flex items-center gap-2.5 p-4 text-sm text-gray-600 dark:text-gray-300">
            <Laptop class="h-4 w-4 shrink-0 text-[#2a8a96]" />
            Fully remote internship — no office clock-in needed today.
          </div>

          <template v-else>
            <!-- Fixed schedule: today's expected mode, informational with an override -->
            <div
              v-if="!clockedIn && !clockedOut && scheduleConfigured"
              class="flex items-center justify-between gap-3 rounded-t-xl bg-gray-50 px-4 py-3 dark:bg-gray-900/40"
            >
              <p class="flex items-center gap-2 text-sm">
                <component :is="scheduleIcon" class="h-4 w-4 shrink-0 text-[#381998]" />
                <span class="text-gray-600 dark:text-gray-300">
                  <strong class="text-[#000928] dark:text-white">{{ skippingOffice ? 'Remote day' : 'Office day' }}</strong>
                  per your schedule<template v-if="officeDaysLabel"> (office days: {{ officeDaysLabel }})</template>
                </span>
              </p>
              <button type="button" class="shrink-0 text-xs font-semibold text-[#381998] hover:underline" @click="skippingOffice = !skippingOffice">
                Not today? Switch
              </button>
            </div>

            <!-- No fixed schedule: free daily choice -->
            <div v-else-if="!clockedIn && !clockedOut" class="flex divide-x divide-gray-100 dark:divide-gray-700">
              <button
                type="button"
                :class="[
                  'flex flex-1 items-center justify-center gap-2 rounded-tl-xl py-3 text-sm font-semibold transition-colors',
                  !skippingOffice ? 'bg-[#381998]/5 text-[#381998]' : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900/40',
                ]"
                @click="skippingOffice = false"
              >
                <Building2 class="h-4 w-4" /> Working from the office
              </button>
              <button
                type="button"
                :class="[
                  'flex flex-1 items-center justify-center gap-2 rounded-tr-xl py-3 text-sm font-semibold transition-colors',
                  skippingOffice ? 'bg-[#381998]/5 text-[#381998]' : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900/40',
                ]"
                @click="skippingOffice = true"
              >
                <Home class="h-4 w-4" /> Working elsewhere
              </button>
            </div>

            <div class="border-t border-gray-100 p-4 dark:border-gray-700" :class="{ '!border-t-0': clockedIn || clockedOut }">
              <!-- Elsewhere: no timeline needed -->
              <div v-if="skippingOffice && !clockedIn && !clockedOut" class="flex items-center gap-2.5 text-sm text-gray-500 dark:text-gray-400">
                <Home class="h-4 w-4 shrink-0" /> No clock-in needed today — just submit your logbook below.
              </div>

              <!-- Timeline: clock-in chip — connector — clock-out chip/button -->
              <div v-else class="flex items-center gap-3">
                <div class="flex flex-1 items-center gap-3">
                  <div
                    :class="[
                      'flex shrink-0 items-center gap-2 rounded-xl border px-3.5 py-2.5',
                      clockedIn ? 'border-[#381998]/20 bg-[#381998]/5' : 'border-dashed border-gray-200 dark:border-gray-600',
                    ]"
                  >
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#381998]/10">
                      <LogIn class="h-3.5 w-3.5 text-[#381998]" />
                    </div>
                    <div class="leading-tight">
                      <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">In</p>
                      <p class="text-sm font-bold text-[#000928] dark:text-white">{{ fmtTime(attendance?.clock_in_at ?? null) }}</p>
                    </div>
                  </div>

                  <div class="relative h-0.5 flex-1 rounded-full" :class="clockedOut ? 'bg-gradient-to-r from-[#381998] to-[#42b6c5]' : clockedIn ? 'bg-[#42b6c5]/30' : 'bg-gray-100 dark:bg-gray-700'">
                    <span
                      v-if="clockedOut && attendance?.hours"
                      class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 whitespace-nowrap rounded-full border border-gray-100 bg-white px-2 py-0.5 text-[10px] font-semibold text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                    >{{ attendance.hours }}h</span>
                    <span
                      v-else-if="clockedIn && elapsedLabel"
                      class="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center gap-1 whitespace-nowrap rounded-full border border-[#42b6c5]/30 bg-white px-2 py-0.5 text-[10px] font-semibold text-[#2a8a96] shadow-sm dark:bg-gray-800"
                    >
                      <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#42b6c5]"></span> {{ elapsedLabel }}
                    </span>
                  </div>

                  <div
                    v-if="clockedOut"
                    class="flex shrink-0 items-center gap-2 rounded-xl border border-[#42b6c5]/20 bg-[#42b6c5]/5 px-3.5 py-2.5"
                  >
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#42b6c5]/10">
                      <LogOut class="h-3.5 w-3.5 text-[#2a8a96]" />
                    </div>
                    <div class="leading-tight">
                      <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Out</p>
                      <p class="text-sm font-bold text-[#000928] dark:text-white">{{ fmtTime(attendance?.clock_out_at ?? null) }}</p>
                    </div>
                  </div>
                </div>

                <button
                  v-if="!clockedIn"
                  :disabled="busy"
                  class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-[#381998] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928] disabled:cursor-not-allowed disabled:opacity-60"
                  @click="clockIn"
                >
                  <LogIn class="h-4 w-4" /> {{ busy ? 'Checking…' : 'Clock In' }}
                </button>
                <button
                  v-else-if="!clockedOut && canClockOut"
                  :disabled="busy"
                  class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-[#381998] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928] disabled:cursor-not-allowed disabled:opacity-60"
                  @click="clockOut"
                >
                  <LogOut class="h-4 w-4" /> Clock Out
                </button>
                <Link
                  v-else-if="!clockedOut"
                  href="/dashboard/internship/logbook"
                  class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300"
                >
                  <AlertTriangle class="h-4 w-4" /> Fill logbook to clock out
                </Link>
                <span v-else class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-100 px-3.5 py-2 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                  <CheckCircle2 class="h-3.5 w-3.5" /> Done for today
                </span>
              </div>

              <p v-if="requiresLocation && !clockedIn && !clockedOut && !skippingOffice" class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
                <MapPin class="h-3.5 w-3.5 shrink-0" /> We'll check your location when you clock in.
              </p>
            </div>
          </template>
        </div>

        <!-- Logbook summary + link to the full logbook page -->
        <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-700">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#42b6c5]/10">
                <NotebookPen class="h-4 w-4 text-[#2a8a96]" />
              </div>
              <div>
                <p class="text-sm font-semibold text-[#000928] dark:text-white">Today's logbook</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Required every working day — office or not.</p>
              </div>
              <span v-if="logbook" :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize', logbookStatusClass]">
                {{ logbook.status.replace('_', ' ') }}
              </span>
              <span v-else class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                Not filled yet
              </span>
            </div>
            <Link
              href="/dashboard/internship/logbook"
              class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-[#42b6c5] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35919e]"
            >
              {{ logbook ? 'View / edit logbook' : 'Fill today\'s logbook' }} <ArrowRight class="h-4 w-4" />
            </Link>
          </div>

          <div v-if="logbook?.supervisor_feedback" class="mt-4 flex gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
            <Sparkles class="mt-0.5 h-4 w-4 shrink-0" />
            <div>
              <p class="font-semibold">Supervisor feedback</p>
              <p class="mt-0.5">{{ logbook.supervisor_feedback }}</p>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>
