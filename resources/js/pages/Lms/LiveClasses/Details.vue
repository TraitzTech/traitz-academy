<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface LiveClassDetails {
  id: number
  title: string
  description?: string | null
  start_time: string
  end_time?: string | null
  duration: number
  recordings?: Array<{ id: number; youtube_url?: string | null; status?: string | null; created_at?: string | null }>
  tutor?: { id: number; name: string } | null
}

const props = defineProps<{
  liveClass: LiveClassDetails
  now: string
  canJoinNow: boolean
  joinOpensAt: string
  hostOnline: boolean
}>()

function classWindowState(): 'not_open' | 'in_window' | 'ended' {
  const nowMs = new Date(props.now).getTime()
  const startMs = new Date(props.liveClass.start_time).getTime()
  const endMs = startMs + props.liveClass.duration * 60000
  const joinOpenMs = new Date(props.joinOpensAt).getTime()

  if (nowMs > endMs) return 'ended'
  if (nowMs >= joinOpenMs && nowMs <= endMs) return 'in_window'
  return 'not_open'
}

function availabilityLabel(): string {
  if (props.canJoinNow) return 'Join available'
  if (classWindowState() === 'ended') return 'Class ended'
  if (classWindowState() === 'in_window' && !props.hostOnline) return 'Waiting for host'
  return 'Not open yet'
}

function startsInText(): string {
  const nowMs = new Date(props.now).getTime()
  const startMs = new Date(props.liveClass.start_time).getTime()
  const diffMs = startMs - nowMs

  if (diffMs <= 0) return 'Class should be live now.'

  const minutes = Math.ceil(diffMs / 60000)
  return `Starts in about ${minutes} minute${minutes === 1 ? '' : 's'}.`
}
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-6">
    <Head :title="`Live Class: ${liveClass.title}`" />

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <Link href="/dashboard/live-classes" class="mb-4 inline-flex rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
        Back to all live classes
      </Link>
      <div class="mb-4 flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ liveClass.title }}</h1>
          <p class="mt-1 text-sm text-gray-500">
            Tutor: {{ liveClass.tutor?.name || '—' }} · {{ new Date(liveClass.start_time).toLocaleString() }} · {{ liveClass.duration }} min
          </p>
        </div>
        <span
          :class="[
            'rounded-full px-3 py-1 text-xs font-semibold',
            canJoinNow
              ? 'bg-green-100 text-green-700'
              : classWindowState() === 'ended'
                ? 'bg-gray-100 text-gray-700'
                : classWindowState() === 'in_window' && !hostOnline
                  ? 'bg-amber-100 text-amber-700'
                  : 'bg-blue-100 text-blue-700',
          ]"
        >
          {{ availabilityLabel() }}
        </span>
      </div>

      <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
        {{ liveClass.description || 'No description provided for this class.' }}
      </p>

      <div class="mt-6 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
        <p v-if="canJoinNow">You can now join this meeting.</p>
        <p v-else-if="classWindowState() === 'ended'">
          This class has ended. You can no longer join the live room.
        </p>
        <p v-else-if="!hostOnline">
          Waiting for tutor/admin to start the room. Join opens automatically once the host is inside.
        </p>
        <p v-else>
          Join opens at {{ new Date(joinOpensAt).toLocaleString() }}. {{ startsInText() }}
        </p>
      </div>

      <div v-if="classWindowState() === 'ended'" class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-600 dark:bg-gray-900/30">
        <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">After class details</h2>
        <div class="grid gap-2 text-gray-600 dark:text-gray-300 md:grid-cols-2">
          <p><span class="font-semibold text-gray-800 dark:text-gray-100">Started:</span> {{ new Date(liveClass.start_time).toLocaleString() }}</p>
          <p><span class="font-semibold text-gray-800 dark:text-gray-100">Ended:</span> {{ liveClass.end_time ? new Date(liveClass.end_time).toLocaleString() : '—' }}</p>
          <p><span class="font-semibold text-gray-800 dark:text-gray-100">Duration:</span> {{ liveClass.duration }} minutes</p>
          <p><span class="font-semibold text-gray-800 dark:text-gray-100">Recordings:</span> {{ liveClass.recordings?.length || 0 }}</p>
        </div>

        <div class="mt-4 space-y-2">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Available recordings</p>
          <div
            v-if="!liveClass.recordings || liveClass.recordings.length === 0"
            class="rounded-lg border border-dashed border-gray-300 px-3 py-2 text-xs text-gray-500 dark:border-gray-600"
          >
            No recording has been published for this class yet.
          </div>
          <div
            v-for="recording in liveClass.recordings || []"
            :key="recording.id"
            class="rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800"
          >
            <div class="flex items-center justify-between gap-2">
              <p class="text-xs text-gray-500">Added {{ recording.created_at ? new Date(recording.created_at).toLocaleString() : '—' }}</p>
              <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ recording.status || 'uploaded' }}</span>
            </div>
            <a
              v-if="recording.youtube_url"
              :href="recording.youtube_url"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-1 inline-block text-xs font-semibold text-[#381998] hover:underline"
            >
              Watch recording
            </a>
          </div>
        </div>
      </div>

      <div class="mt-6 flex flex-wrap items-center gap-3">
        <Link href="/dashboard/live-classes" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
          Back to classes
        </Link>
        <Link
          :href="`/dashboard/live-classes/${liveClass.id}`"
          :class="['rounded-lg px-4 py-2 text-sm font-semibold', canJoinNow ? 'bg-[#381998] text-white hover:bg-[#000928]' : 'cursor-not-allowed bg-gray-200 text-gray-500 pointer-events-none']"
        >
          Join meeting
        </Link>
      </div>
    </div>
  </div>
</template>
