<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  summary: { type: Object, required: true },
})

const formatMoney = (amount) => {
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(amount || 0)
}

const progressPercent = (summary) => {
  return Math.min(100, Math.round((summary.paid_amount / Math.max(summary.program_price, 1)) * 100))
}
</script>

<template>
  <div class="p-4 sm:p-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
      <div class="flex-1">
        <div class="flex items-center gap-3 mb-2">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ summary.program_title }}</h3>
          <span :class="[
            'px-2.5 py-1 rounded-full text-xs font-semibold uppercase',
            summary.status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
            summary.status === 'partially-paid' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
            summary.status === 'unpaid' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' :
            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
          ]">
            {{ summary.status === 'partially-paid' ? 'Partially Paid' : summary.status.replace('-', ' ') }}
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm mt-3">
          <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Program Fee</p>
            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(summary.program_price) }}</p>
          </div>
          <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid</p>
            <p class="font-semibold text-green-700 dark:text-green-400">{{ formatMoney(summary.paid_amount) }}</p>
          </div>
          <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Remaining</p>
            <p class="font-semibold text-amber-700 dark:text-amber-400">{{ formatMoney(summary.remaining_amount) }}</p>
          </div>
          <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Installments</p>
            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ summary.completed_installments }} / {{ summary.max_installments }}</p>
          </div>
        </div>

        <div class="mt-4">
          <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
            <span>Payment Progress</span>
            <span>{{ progressPercent(summary) }}%</span>
          </div>
          <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full bg-gradient-to-r from-[#42b6c5] to-[#381998] rounded-full"
              :style="{ width: `${progressPercent(summary)}%` }"
            ></div>
          </div>
        </div>
      </div>

      <div class="flex gap-2 lg:flex-col lg:w-48">
        <Link
          v-if="summary.remaining_amount > 0"
          :href="summary.checkout_url"
          class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-[#42b6c5] text-white rounded-lg text-sm font-semibold hover:bg-[#35919e] transition-colors"
        >
          Pay Now
        </Link>
        <Link
          v-if="summary.latest_receipt_url"
          :href="summary.latest_receipt_url"
          class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          View Receipt
        </Link>
      </div>
    </div>
  </div>
</template>
