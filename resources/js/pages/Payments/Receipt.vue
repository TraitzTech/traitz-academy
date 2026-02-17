<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

interface Payment {
  id: number
  reference: string
  receipt_number: string | null
  status: string
  amount: number
  currency: string
  provider: string
  payer_phone: string
  payment_type: string
  installment_number: number
  total_installments: number
  paid_at: string | null
  created_at: string
  mesomb_transaction_id: string | null
  application: {
    id: number
    first_name: string
    last_name: string
    email: string
    program: {
      title: string
      category: string
      price: number
    }
  }
}

interface Props {
  payment: Payment
}

interface PageProps {
  auth?: {
    user?: {
      role?: string
    }
  }
}

const props = defineProps<Props>()
defineOptions({ layout: AppLayout })

const page = usePage()
const backHref = computed(() => (page.props as unknown as PageProps).auth?.user?.role === 'admin' ? '/admin/dashboard' : '/dashboard#payments')
const backLabel = computed(() => (page.props as unknown as PageProps).auth?.user?.role === 'admin' ? 'Back to Admin Dashboard' : 'Back to Dashboard')

const formatMoney = (amount: number, currency = 'XAF') => {
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency }).format(amount || 0)
}

const formatDate = (value: string | null) => {
  if (!value) return 'N/A'
  return new Date(value).toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const printReceipt = () => {
  globalThis.window?.open(`/payments/receipts/${props.payment.id}/print`, '_blank')
}
</script>

<template>
  <div>
    <Head title="Payment Receipt" />

    <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Payment Receipt</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Receipt #{{ payment.receipt_number || 'Pending' }}</p>
      </div>
      <div class="flex gap-2">
        <Link :href="backHref" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
          {{ backLabel }}
        </Link>
        <a
          :href="`/payments/receipts/${payment.id}/download`"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors"
        >
          Download PDF
        </a>
        <button
          type="button"
          class="px-4 py-2 bg-[#42b6c5] text-white rounded-lg font-medium hover:bg-[#35919e] transition-colors"
          @click="printReceipt"
        >
          Print Receipt
        </button>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
      <div class="px-6 py-5 bg-gradient-to-r from-[#000928] to-[#381998] text-white">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-widest text-cyan-200">Traitz Academy</p>
            <h3 class="text-2xl font-semibold mt-1">Official Payment Receipt</h3>
          </div>
          <span :class="[
            'px-3 py-1 rounded-full text-xs font-semibold uppercase',
            payment.status === 'successful'
              ? 'bg-green-100 text-green-800'
              : payment.status === 'pending'
              ? 'bg-amber-100 text-amber-800'
              : 'bg-red-100 text-red-800'
          ]">
            {{ payment.status }}
          </span>
        </div>
      </div>

      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-3">
          <h4 class="text-sm uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment Details</h4>
          <div class="space-y-2 text-sm">
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Receipt Number</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.receipt_number || 'N/A' }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Reference</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.reference }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Provider</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.provider }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Phone</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.payer_phone }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Paid At</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(payment.paid_at || payment.created_at) }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Amount</span><span class="font-semibold text-[#42b6c5]">{{ formatMoney(payment.amount, payment.currency) }}</span></p>
          </div>
        </div>

        <div class="space-y-3">
          <h4 class="text-sm uppercase tracking-wide text-gray-500 dark:text-gray-400">Applicant & Program</h4>
          <div class="space-y-2 text-sm">
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Applicant</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.application.first_name }} {{ payment.application.last_name }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Email</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.application.email }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Program</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.application.program.title }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Payment Type</span><span class="font-medium text-gray-900 dark:text-gray-100 capitalize">{{ payment.payment_type }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">Installment</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.installment_number }} / {{ payment.total_installments }}</span></p>
            <p class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">MeSomb Tx</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.mesomb_transaction_id || 'N/A' }}</span></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
