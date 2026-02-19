<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface Payment {
  id: number
  receipt_number: string | null
  reference: string
  manual_entry: boolean
  status: 'pending' | 'successful' | 'failed'
  amount: number
  currency: string
  payment_type: string
  installment_number: number
  total_installments: number
  paid_at: string | null
  mesomb_transaction_id: string | null
  application: {
    first_name: string
    last_name: string
    email: string
  } | null
  program: {
    title: string
  } | null
  recorded_by: {
    name: string
    role: string
  } | null
  updated_by: {
    name: string
    role: string
  } | null
}

const props = defineProps<{
  search: string
  payment: Payment | null
}>()

const search = ref(props.search || '')

const submit = () => {
  router.get('/admin/payments/verify', {
    receipt: search.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}

const formatMoney = (amount: number, currency = 'XAF') => {
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency }).format(amount || 0)
}

const formatDate = (value: string | null) => {
  if (!value) return 'N/A'

  return new Date(value).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatRole = (role: string) => {
  if (role === 'admin') return 'CTO (Legacy)'
  return role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
</script>

<template>
  <div>
    <Head title="Verify Receipt" />

    <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Verify Receipt</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Search by receipt number, reference, or transaction id.</p>
      </div>
      <Link href="/admin/payments" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
        Back to Payments
      </Link>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
      <form class="flex flex-col sm:flex-row gap-3" @submit.prevent="submit">
        <input
          v-model="search"
          type="text"
          placeholder="e.g. RCT-20260217-000123"
          class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
        />
        <button type="submit" class="px-5 py-2 bg-[#42b6c5] text-white rounded-lg font-medium hover:bg-[#35919e] transition-colors">
          Verify
        </button>
      </form>
    </div>

    <div v-if="props.search && !payment" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center text-red-600 dark:text-red-400">
      No receipt found for "{{ props.search }}".
    </div>

    <div v-if="payment" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Receipt Verified</h3>
        <span :class="[
          'px-3 py-1 rounded-full text-xs font-semibold uppercase',
          payment.status === 'successful' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
          payment.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
          'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
        ]">
          {{ payment.status }}
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Receipt:</span> {{ payment.receipt_number || 'N/A' }}</p>
        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Reference:</span> {{ payment.reference }}</p>
        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Applicant:</span> {{ payment.application?.first_name }} {{ payment.application?.last_name }}</p>
        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Email:</span> {{ payment.application?.email }}</p>
        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Program:</span> {{ payment.program?.title }}</p>
        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Amount:</span> {{ formatMoney(payment.amount, payment.currency) }}</p>
        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Payment Type:</span> {{ payment.payment_type }} ({{ payment.installment_number }}/{{ payment.total_installments }})</p>
        <p class="text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Paid At:</span> {{ formatDate(payment.paid_at) }}</p>
        <p class="text-gray-700 dark:text-gray-300 md:col-span-2"><span class="text-gray-500 dark:text-gray-400">Transaction ID:</span> {{ payment.mesomb_transaction_id || 'N/A' }}</p>
        <p v-if="payment.manual_entry" class="text-gray-700 dark:text-gray-300 md:col-span-2">
          <span class="text-gray-500 dark:text-gray-400">Collected By:</span>
          {{ payment.recorded_by?.name || payment.updated_by?.name || 'Unknown' }}
          <span v-if="payment.recorded_by?.role || payment.updated_by?.role" class="text-gray-500 dark:text-gray-400">
            ({{ formatRole(payment.recorded_by?.role || payment.updated_by?.role || '') }})
          </span>
        </p>
      </div>

      <div>
        <a :href="`/payments/receipts/${payment.id}/download`" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
          Download Receipt PDF
        </a>
      </div>
    </div>
  </div>
</template>
