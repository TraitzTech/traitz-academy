<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface AppNotification {
  id: string
  type: string
  data: Record<string, any>
  read_at: string | null
  created_at: string | null
}

const props = defineProps<{
  notifications: AppNotification[]
}>()

const form = useForm({})

function markAllRead() {
  form.post('/dashboard/notifications/read-all', { preserveScroll: true })
}

function titleFor(notification: AppNotification): string {
  return notification.data?.subject || notification.data?.title || 'Notification'
}

function bodyFor(notification: AppNotification): string {
  if (notification.data?.message) return String(notification.data.message)
  if (notification.data?.body) return String(notification.data.body)
  return 'Open this notification to see more details.'
}

function when(value: string | null): string {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}

function hasHtmlBody(notification: AppNotification): boolean {
  return Boolean(notification.data?.message_html)
}
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-6">
    <Head title="My Notifications" />

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">My notifications</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Announcements from admins and tutors, plus LMS activity updates.
          </p>
        </div>
        <button
          type="button"
          :disabled="form.processing || notifications.length === 0"
          class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
          @click="markAllRead"
        >
          {{ form.processing ? 'Marking...' : 'Mark all as read' }}
        </button>
      </div>
    </div>

    <div v-if="notifications.length === 0" class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-600">
      No notifications yet.
    </div>

    <div v-else class="space-y-3">
      <article
        v-for="notification in notifications"
        :key="notification.id"
        :class="[
          'rounded-xl border bg-white p-5 shadow-sm dark:bg-gray-800',
          notification.read_at ? 'border-gray-200 dark:border-gray-700' : 'border-[#42b6c5] dark:border-[#42b6c5]',
        ]"
      >
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ titleFor(notification) }}</h2>
          <span class="text-xs text-gray-500">{{ when(notification.created_at) }}</span>
        </div>

        <div
          v-if="hasHtmlBody(notification)"
          class="prose prose-sm max-w-none text-gray-700 dark:prose-invert dark:text-gray-300"
          v-html="notification.data.message_html"
        />
        <p v-else class="text-sm text-gray-700 dark:text-gray-300">
          {{ bodyFor(notification) }}
        </p>

        <div class="mt-3 flex items-center justify-between">
          <span class="text-xs text-gray-400">{{ notification.type }}</span>
          <a
            v-if="notification.data?.action_url"
            :href="notification.data.action_url"
            class="rounded-lg bg-[#381998] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#000928]"
          >
            {{ notification.data?.action_text || 'Open' }}
          </a>
        </div>
      </article>
    </div>
  </div>
</template>

