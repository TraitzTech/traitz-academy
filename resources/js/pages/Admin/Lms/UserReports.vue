<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface LearningRecord {
  id: number
  course_title: string | null
  status: string
  progress: number
  enrolled_at: string | null
  completed_at: string | null
}

interface PaymentRecord {
  id: number
  course_title: string | null
  amount: number
  status: string
  payment_type: string
  paid_at: string | null
}

interface UserReport {
  id: number
  name: string
  email: string
  total_enrollments: number
  total_completions: number
  total_paid: number
  learning_records: LearningRecord[]
  payment_records: PaymentRecord[]
}

const props = defineProps<{
  users: UserReport[]
  filters?: { search?: string | null; status?: string | null }
}>()
const expandedUserId = ref<number | null>(null)
const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

const applyFilters = debounce(() => {
  router.get('/admin/lms/user-reports', {
    search: search.value.trim() || undefined,
    status: status.value || undefined,
  }, { preserveState: true, replace: true })
}, 300)

const money = (value: number) =>
  new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 }).format(value || 0)

const when = (value: string | null) => (value ? new Date(value).toLocaleString() : '—')
</script>

<template>
  <div>
    <Head title="LMS Per-user Reports" />
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Per-user report</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full learning and payment records per learner.</p>
    </div>

    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div class="flex flex-col gap-2 sm:flex-row">
        <input
          v-model="search"
          type="search"
          placeholder="Filter by learner name or email..."
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 sm:w-80"
          @input="applyFilters"
        />
        <select
          v-model="status"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 sm:w-56"
          @change="applyFilters"
        >
          <option value="">All enrollment statuses</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="suspended">Suspended</option>
          <option value="revoked">Revoked</option>
        </select>
      </div>
      <a
        :href="`/admin/lms/user-reports?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&export=csv`"
        class="inline-flex items-center justify-center rounded-lg bg-[#381998] px-3 py-2 text-xs font-semibold text-white hover:bg-[#2b126f]"
      >
        Export CSV
      </a>
    </div>

    <div class="space-y-3">
      <div v-for="user in props.users" :key="user.id" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
          <div>
            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ user.name }}</p>
            <p class="text-xs text-gray-500">{{ user.email }}</p>
          </div>
          <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
            <span>Enrollments: <strong>{{ user.total_enrollments }}</strong></span>
            <span>Completions: <strong>{{ user.total_completions }}</strong></span>
            <span>Paid: <strong>{{ money(user.total_paid) }}</strong></span>
            <button class="rounded-md border border-gray-200 px-2.5 py-1 font-semibold text-[#381998] hover:bg-[#381998]/5 dark:border-gray-600" @click="expandedUserId = expandedUserId === user.id ? null : user.id">
              {{ expandedUserId === user.id ? 'Hide records' : 'View records' }}
            </button>
          </div>
        </div>

        <div v-if="expandedUserId === user.id" class="grid grid-cols-1 gap-4 border-t border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30 lg:grid-cols-2">
          <div>
            <h3 class="mb-2 text-sm font-semibold text-[#000928] dark:text-gray-100">Learning record</h3>
            <div v-if="user.learning_records.length === 0" class="text-xs text-gray-500">No learning records yet.</div>
            <div v-else class="space-y-2">
              <div v-for="item in user.learning_records" :key="item.id" class="rounded border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-800">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.course_title || 'Unknown course' }}</p>
                <p class="text-gray-500">Status: {{ item.status }} · Progress: {{ item.progress }}%</p>
                <p class="text-gray-500">Enrolled: {{ when(item.enrolled_at) }}</p>
                <p class="text-gray-500">Completed: {{ when(item.completed_at) }}</p>
              </div>
            </div>
          </div>
          <div>
            <h3 class="mb-2 text-sm font-semibold text-[#000928] dark:text-gray-100">Payment record</h3>
            <div v-if="user.payment_records.length === 0" class="text-xs text-gray-500">No payment records yet.</div>
            <div v-else class="space-y-2">
              <div v-for="item in user.payment_records" :key="item.id" class="rounded border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-800">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.course_title || 'Unknown course' }}</p>
                <p class="text-gray-500">Amount: {{ money(item.amount) }} · Type: {{ item.payment_type }}</p>
                <p class="text-gray-500">Status: {{ item.status }} · Paid: {{ when(item.paid_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
