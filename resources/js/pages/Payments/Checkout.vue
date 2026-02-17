<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Application {
  id: number
  first_name: string
  last_name: string
  email: string
  phone: string
  program: {
    id: number
    title: string
    category: string
    price: number
    max_installments: number
  }
}

interface Summary {
  program_price: number
  paid_amount: number
  remaining_amount: number
  max_installments: number
  installment_amount: number
  completed_installments: number
  next_installment_number: number
  can_pay: boolean
}

interface Props {
  application: Application
  summary: Summary
}

const props = defineProps<Props>()
defineOptions({ layout: AppLayout })

const toast = useToast()

const form = useForm({
  payer_phone: props.application.phone || '',
  provider: 'MTN',
  payment_mode: 'full',
})

const installmentDue = computed(() => Math.min(props.summary.installment_amount, props.summary.remaining_amount))
const selectedAmount = computed(() => form.payment_mode === 'installment' ? installmentDue.value : props.summary.remaining_amount)
const balanceAfterPayment = computed(() => Math.max(0, props.summary.remaining_amount - selectedAmount.value))
const installmentsAfterPayment = computed(() => {
  if (form.payment_mode === 'installment' && props.summary.remaining_amount > 0) {
    return Math.min(props.summary.max_installments, props.summary.completed_installments + 1)
  }

  if (form.payment_mode === 'full' && props.summary.remaining_amount > 0) {
    return props.summary.max_installments
  }

  return props.summary.completed_installments
})

const submit = () => {
  form.post(`/payments/${props.application.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Payment initiated successfully.')
    },
    onError: () => {
      toast.error('Payment could not be completed. Please verify your details.')
    },
  })
}

const formatMoney = (amount: number) => {
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(amount || 0)
}
</script>

<template>
  <div>
    <Head title="Pay Program Fees" />

    <div class="mb-8">
      <Link href="/dashboard#payments" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Payments
      </Link>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Complete Program Payment</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Secure mobile money payment powered by MeSomb</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Payment Details</h3>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mobile Money Number</label>
            <input
              v-model="form.payer_phone"
              type="text"
              placeholder="e.g. 670000000"
              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.payer_phone" class="mt-1 text-sm text-red-600">{{ form.errors.payer_phone }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Provider</label>
            <div class="grid grid-cols-2 gap-3">
              <label class="cursor-pointer">
                <input v-model="form.provider" type="radio" value="MTN" class="sr-only" />
                <div :class="[
                  'rounded-lg border p-3 text-center font-semibold transition-colors',
                  form.provider === 'MTN'
                    ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'
                ]">
                  MTN
                </div>
              </label>
              <label class="cursor-pointer">
                <input v-model="form.provider" type="radio" value="ORANGE" class="sr-only" />
                <div :class="[
                  'rounded-lg border p-3 text-center font-semibold transition-colors',
                  form.provider === 'ORANGE'
                    ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'
                ]">
                  ORANGE
                </div>
              </label>
            </div>
            <p v-if="form.errors.provider" class="mt-1 text-sm text-red-600">{{ form.errors.provider }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Mode</label>
            <div class="space-y-3">
              <label class="block cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600 p-4" :class="form.payment_mode === 'full' ? 'ring-2 ring-[#42b6c5] border-[#42b6c5]' : ''">
                <input v-model="form.payment_mode" type="radio" value="full" class="mr-2" />
                <span class="font-semibold text-gray-900 dark:text-gray-100">Pay Full Amount</span>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pay {{ formatMoney(summary.remaining_amount) }} now.</p>
              </label>

              <label
                class="block cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600 p-4"
                :class="form.payment_mode === 'installment' ? 'ring-2 ring-[#42b6c5] border-[#42b6c5]' : ''"
              >
                <input v-model="form.payment_mode" type="radio" value="installment" class="mr-2" :disabled="summary.max_installments <= 1" />
                <span class="font-semibold text-gray-900 dark:text-gray-100">Pay Next Installment</span>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  Installment {{ summary.next_installment_number }} of {{ summary.max_installments }}
                  • {{ formatMoney(installmentDue) }}
                </p>
              </label>
            </div>
            <p v-if="form.errors.payment_mode" class="mt-1 text-sm text-red-600">{{ form.errors.payment_mode }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full inline-flex justify-center items-center px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-60"
          >
            {{ form.processing ? 'Processing...' : 'Pay Now' }}
          </button>
        </form>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 h-fit">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Payment Summary</h3>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400">Program</span>
            <span class="font-medium text-gray-900 dark:text-gray-100 text-right">{{ application.program.title }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400">Total Fee</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatMoney(summary.program_price) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400">Paid So Far</span>
            <span class="font-medium text-green-700 dark:text-green-400">{{ formatMoney(summary.paid_amount) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400">Remaining</span>
            <span class="font-semibold text-amber-700 dark:text-amber-400">{{ formatMoney(summary.remaining_amount) }}</span>
          </div>
          <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-3">
            <span class="text-gray-700 dark:text-gray-300 font-medium">You pay now</span>
            <span class="font-bold text-[#42b6c5]">{{ formatMoney(selectedAmount) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400">Balance after payment</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatMoney(balanceAfterPayment) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400">Installments</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ installmentsAfterPayment }} / {{ summary.max_installments }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
