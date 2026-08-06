<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface ScheduleRow {
  id: string
  uid: string
  source_type: 'schedule' | 'assignment' | 'live_class' | 'personal'
  source_label: string
  title: string
  description: string | null
  location: string | null
  starts_at: string | null
  ends_at: string | null
  attachable: {
    title: string | null
  }
  can_edit: boolean
  personal_event_id?: number
}

const props = defineProps<{
  schedules: ScheduleRow[]
  googleConnected: boolean
  googleEmail: string | null
}>()

const tab = ref<'upcoming' | 'past' | 'all'>('upcoming')
const now = new Date()
const currentMonth = ref(now.getMonth())
const currentYear = ref(now.getFullYear())
const selectedEvent = ref<ScheduleRow | null>(null)
const showEventModal = ref(false)

const filteredSchedules = computed(() => {
  if (tab.value === 'all') return props.schedules

  const now = Date.now()
  return props.schedules.filter((row: ScheduleRow) => {
    const startsAt = row.starts_at ? new Date(row.starts_at).getTime() : 0
    if (tab.value === 'upcoming') return startsAt >= now
    return startsAt < now
  })
})

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const calendarDays = computed(() => {
  const first = new Date(currentYear.value, currentMonth.value, 1)
  const last = new Date(currentYear.value, currentMonth.value + 1, 0)
  const days: Array<number | null> = []
  for (let i = 0; i < first.getDay(); i++) days.push(null)
  for (let d = 1; d <= last.getDate(); d++) days.push(d)
  return days
})

function formatDate(value: string | null) {
  if (!value) return 'N/A'
  return new Date(value).toLocaleString()
}

function typeColor(sourceType: ScheduleRow['source_type']) {
  if (sourceType === 'assignment') return 'bg-blue-500'
  if (sourceType === 'live_class') return 'bg-green-500'
  if (sourceType === 'schedule') return 'bg-orange-500'
  return 'bg-purple-500'
}

function sourceLabel(sourceType: ScheduleRow['source_type']) {
  if (sourceType === 'assignment') return 'assignment'
  if (sourceType === 'live_class') return 'session'
  if (sourceType === 'schedule') return 'schedule'
  return 'personal'
}

function getEventsForDay(day: number | null) {
  if (!day) return []
  const year = currentYear.value
  const month = currentMonth.value

  return filteredSchedules.value.filter((item: ScheduleRow) => {
    if (!item.starts_at) return false
    const date = new Date(item.starts_at)
    return date.getFullYear() === year && date.getMonth() === month && date.getDate() === day
  })
}

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
    return
  }
  currentMonth.value--
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
    return
  }
  currentMonth.value++
}

const form = useForm({
  title: '',
  description: '',
  location: '',
  starts_at: '',
  ends_at: '',
})

const editingEventId = ref<number | null>(null)

function resetForm() {
  editingEventId.value = null
  form.reset()
  form.clearErrors()
  showEventModal.value = false
}

function submitPersonalEvent() {
  if (editingEventId.value) {
    form.put(`/dashboard/schedules/personal-events/${editingEventId.value}`, {
      preserveScroll: true,
      onSuccess: () => {
        resetForm()
        selectedEvent.value = null
      },
    })
    return
  }

  form.post('/dashboard/schedules/personal-events', {
    preserveScroll: true,
    onSuccess: () => resetForm(),
  })
}

function openCreateEventModal() {
  editingEventId.value = null
  form.reset()
  form.clearErrors()
  showEventModal.value = true
}

function closeEventModal() {
  resetForm()
}

function editPersonalEvent(row: ScheduleRow) {
  if (!row.personal_event_id) return
  editingEventId.value = row.personal_event_id
  form.title = row.title
  form.description = row.description ?? ''
  form.location = row.location ?? ''
  form.starts_at = row.starts_at ? row.starts_at.slice(0, 16) : ''
  form.ends_at = row.ends_at ? row.ends_at.slice(0, 16) : ''
  showEventModal.value = true
}

function deletePersonalEvent(row: ScheduleRow) {
  if (!row.personal_event_id) return
  form.delete(`/dashboard/schedules/personal-events/${row.personal_event_id}`, {
    preserveScroll: true,
    onSuccess: () => {
      selectedEvent.value = null
      resetForm()
    },
  })
}

function deleteEditingEvent() {
  if (!editingEventId.value) return
  form.delete(`/dashboard/schedules/personal-events/${editingEventId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      selectedEvent.value = null
      resetForm()
    },
  })
}

function syncGoogleCalendar() {
  router.post('/dashboard/schedules/google/sync', {}, { preserveScroll: true })
}
</script>

<template>
  <div>
    <Head title="My Schedule" />
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">My schedule</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Assignments, live classes, LMS events, and your personal events in one calendar.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-[#381998] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#2f147e]"
        @click="openCreateEventModal"
      >
        + Add event
      </button>
    </div>

    <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Google Calendar sync</h2>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            <span v-if="googleConnected">Connected as {{ googleEmail || 'Google account' }}</span>
            <span v-else>Connect your Google account to sync this full schedule.</span>
          </p>
        </div>
        <div class="flex gap-2">
          <a
            v-if="!googleConnected"
            href="/dashboard/schedules/google/connect"
            class="rounded-lg bg-[#381998] px-3 py-2 text-xs font-semibold text-white"
          >
            Connect Google
          </a>
          <button
            v-else
            type="button"
            class="rounded-lg bg-[#381998] px-3 py-2 text-xs font-semibold text-white"
            @click="syncGoogleCalendar"
          >
            Sync now
          </button>
        </div>
      </div>
    </div>

    <div class="mb-4 flex items-center gap-2">
      <button
        type="button"
        class="rounded-lg px-3 py-1.5 text-sm font-semibold"
        :class="tab === 'upcoming' ? 'bg-[#381998] text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'"
        @click="tab = 'upcoming'"
      >
        Upcoming
      </button>
      <button
        type="button"
        class="rounded-lg px-3 py-1.5 text-sm font-semibold"
        :class="tab === 'past' ? 'bg-[#381998] text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'"
        @click="tab = 'past'"
      >
        Past
      </button>
      <button
        type="button"
        class="rounded-lg px-3 py-1.5 text-sm font-semibold"
        :class="tab === 'all' ? 'bg-[#381998] text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'"
        @click="tab = 'all'"
      >
        All
      </button>
    </div>

    <div class="mb-4 flex flex-wrap gap-4 text-xs">
      <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400 capitalize">
        <span class="h-2.5 w-2.5 rounded-full bg-blue-500" /> assignment
      </span>
      <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400 capitalize">
        <span class="h-2.5 w-2.5 rounded-full bg-green-500" /> session
      </span>
      <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400 capitalize">
        <span class="h-2.5 w-2.5 rounded-full bg-orange-500" /> schedule
      </span>
      <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400 capitalize">
        <span class="h-2.5 w-2.5 rounded-full bg-purple-500" /> personal
      </span>
    </div>

    <div v-if="filteredSchedules.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800">
      No schedule items yet.
    </div>

    <div v-else class="space-y-4">
      <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ monthNames[currentMonth] }} {{ currentYear }}</h2>
          <div class="flex gap-1">
            <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" @click="prevMonth">
              <ChevronLeft :size="16" />
            </button>
            <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" @click="nextMonth">
              <ChevronRight :size="16" />
            </button>
          </div>
        </div>

        <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-700">
          <div v-for="day in dayNames" :key="day" class="px-1 py-2 text-center text-xs font-semibold uppercase text-gray-400">{{ day }}</div>
        </div>

        <div class="grid grid-cols-7">
          <div
            v-for="(day, i) in calendarDays"
            :key="i"
            class="min-h-[84px] border-b border-r border-gray-50 p-1.5 dark:border-gray-700/50"
            :class="day && day === now.getDate() && currentMonth === now.getMonth() && currentYear === now.getFullYear() ? 'bg-[#381998]/5 dark:bg-[#381998]/10' : ''"
          >
            <p v-if="day" class="mb-1 flex h-5 w-5 items-center justify-center rounded-full text-xs font-medium" :class="day === now.getDate() && currentMonth === now.getMonth() && currentYear === now.getFullYear() ? 'bg-[#381998] text-white' : 'text-gray-700 dark:text-gray-200'">
              {{ day }}
            </p>
            <div class="space-y-0.5">
              <button
                v-for="evt in getEventsForDay(day)"
                :key="evt.id"
                type="button"
                class="w-full truncate rounded px-1.5 py-0.5 text-left text-[10px] text-white"
                :class="typeColor(evt.source_type)"
                :title="evt.title"
                @click="selectedEvent = evt"
              >
                {{ evt.title.slice(0, 12) }}{{ evt.title.length > 12 ? '...' : '' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="selectedEvent" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div class="flex min-w-0 items-start gap-3">
            <span class="mt-1 h-3 w-3 shrink-0 rounded-full" :class="typeColor(selectedEvent.source_type)" />
            <div class="min-w-0">
              <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ selectedEvent.title }}</p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <span class="capitalize">{{ sourceLabel(selectedEvent.source_type) }}</span>
                <span v-if="selectedEvent.attachable.title"> · {{ selectedEvent.attachable.title }}</span>
              </p>
              <dl class="mt-3 space-y-1 text-xs text-gray-600 dark:text-gray-300">
                <div class="flex flex-wrap gap-x-2">
                  <dt class="font-medium text-gray-500 dark:text-gray-400">Starts</dt>
                  <dd>{{ formatDate(selectedEvent.starts_at) }}</dd>
                </div>
                <div v-if="selectedEvent.ends_at" class="flex flex-wrap gap-x-2">
                  <dt class="font-medium text-gray-500 dark:text-gray-400">Ends</dt>
                  <dd>{{ formatDate(selectedEvent.ends_at) }}</dd>
                </div>
                <div v-if="selectedEvent.location" class="flex flex-wrap gap-x-2">
                  <dt class="font-medium text-gray-500 dark:text-gray-400">Location</dt>
                  <dd>{{ selectedEvent.location }}</dd>
                </div>
              </dl>
              <p v-if="selectedEvent.description" class="mt-3 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">
                {{ selectedEvent.description }}
              </p>
            </div>
          </div>
          <div class="flex shrink-0 flex-col items-end gap-2 sm:flex-row sm:items-center">
            <div v-if="selectedEvent.can_edit" class="flex gap-2">
              <button
                type="button"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-800 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                @click="editPersonalEvent(selectedEvent)"
              >
                Edit
              </button>
              <button
                type="button"
                class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300"
                @click="deletePersonalEvent(selectedEvent)"
              >
                Delete
              </button>
            </div>
            <button
              type="button"
              class="text-2xl leading-none text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
              aria-label="Close"
              @click="selectedEvent = null"
            >
              ×
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add / edit personal event modal -->
    <Teleport to="body">
      <div
        v-if="showEventModal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="schedule-event-modal-title"
        @click.self="closeEventModal"
      >
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
          <div class="sticky top-0 flex items-center justify-between border-b border-gray-100 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h2 id="schedule-event-modal-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              {{ editingEventId ? 'Edit event' : 'Add personal event' }}
            </h2>
            <button
              type="button"
              class="rounded-lg p-1 text-2xl leading-none text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
              aria-label="Close"
              @click="closeEventModal"
            >
              ×
            </button>
          </div>

          <form class="space-y-4 p-5" @submit.prevent="submitPersonalEvent">
            <div>
              <label for="evt-title" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</label>
              <input
                id="evt-title"
                v-model="form.title"
                type="text"
                required
                placeholder="e.g. Study session, doctor appointment"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-[#381998] focus:outline-none focus:ring-2 focus:ring-[#381998]/20 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
              />
              <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label for="evt-start" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Starts</label>
                <input
                  id="evt-start"
                  v-model="form.starts_at"
                  type="datetime-local"
                  required
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[#381998] focus:outline-none focus:ring-2 focus:ring-[#381998]/20 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                />
                <p v-if="form.errors.starts_at" class="mt-1 text-xs text-red-600">{{ form.errors.starts_at }}</p>
              </div>
              <div>
                <label for="evt-end" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ends (optional)</label>
                <input
                  id="evt-end"
                  v-model="form.ends_at"
                  type="datetime-local"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[#381998] focus:outline-none focus:ring-2 focus:ring-[#381998]/20 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                />
                <p v-if="form.errors.ends_at" class="mt-1 text-xs text-red-600">{{ form.errors.ends_at }}</p>
              </div>
            </div>

            <div>
              <label for="evt-loc" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Location (optional)</label>
              <input
                id="evt-loc"
                v-model="form.location"
                type="text"
                placeholder="Online, room name, address…"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[#381998] focus:outline-none focus:ring-2 focus:ring-[#381998]/20 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
              />
              <p v-if="form.errors.location" class="mt-1 text-xs text-red-600">{{ form.errors.location }}</p>
            </div>

            <div>
              <label for="evt-desc" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description (optional)</label>
              <textarea
                id="evt-desc"
                v-model="form.description"
                rows="3"
                placeholder="Notes for yourself…"
                class="w-full resize-y rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[#381998] focus:outline-none focus:ring-2 focus:ring-[#381998]/20 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
              />
              <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
              <button
                v-if="editingEventId"
                type="button"
                class="rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50 dark:bg-red-900/30 dark:text-red-300"
                :disabled="form.processing"
                @click="deleteEditingEvent"
              >
                Delete
              </button>
              <span v-else />

              <div class="ml-auto flex gap-2">
                <button
                  type="button"
                  class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                  :disabled="form.processing"
                  @click="closeEventModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white hover:bg-[#2f147e] disabled:opacity-60"
                  :disabled="form.processing"
                >
                  {{ form.processing ? 'Saving…' : editingEventId ? 'Save changes' : 'Add event' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
