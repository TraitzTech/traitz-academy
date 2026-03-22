<script setup lang="ts">
import { Head, router, useForm, usePage, WhenVisible } from '@inertiajs/vue3'
import {
  AlertTriangle,
  ArrowDownToLine,
  Banknote,
  CheckCircle2,
  Clock,
  KeyRound,
  Loader2,
  Phone,
  Search,
  Send,
  Shield,
  User,
  Wallet,
  XCircle,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  InputOTP,
  InputOTPGroup,
  InputOTPSlot,
} from '@/components/ui/input-otp'
import AppLayout from '@/layouts/AppLayout.vue'

interface WithdrawalRow {
  id: number
  amount: number
  service: string
  receiver: string
  receiver_name: string | null
  currency: string
  status: string
  mesomb_transaction_id: string | null
  reference: string | null
  failure_reason: string | null
  created_at: string
  user: { id: number; name: string; role: string } | null
}

interface Props {
  withdrawals: {
    data: WithdrawalRow[]
    links: Array<{ url: string | null; label: string; active: boolean }>
    current_page: number
    last_page: number
    total: number
  }
  stats: {
    total_withdrawn: number
    pending_count: number
    successful_count: number
    failed_count: number
  }
  hasPin: boolean
  services: string[]
  mesombBalance: {
    total: number
    balances: Array<{ provider: string; country: string; value: number }>
  } | null
}

const props = defineProps<Props>()

defineOptions({
  layout: AppLayout,
})

const page = usePage()

// State
const showPinSetup = ref(false)
const showWithdrawForm = ref(false)
const showAccountVerification = ref(false)
const showPinConfirm = ref(false)

// Account verification state
const verifying = ref(false)
const verifyError = ref('')
const verifiedName = ref('')
const canProceedManually = ref(false)
const manualName = ref('')
const verifiedByMesomb = ref(false)

// PIN Setup Form
const pinForm = useForm({
  current_pin: '',
  pin: '',
  pin_confirmation: '',
})

// Withdrawal Form
const withdrawForm = useForm({
  amount: null as number | null,
  service: '',
  receiver: '',
  receiver_name: '',
  pin: '',
})

// Computed
const formattedAmount = computed(() => {
  if (!withdrawForm.amount) return '0'
  return new Intl.NumberFormat('en-CM', { maximumFractionDigits: 0 }).format(withdrawForm.amount)
})

const serviceLabel = (service: string) => {
  const labels: Record<string, string> = {
    MTN: 'MTN Mobile Money',
    ORANGE: 'Orange Money',
  }
  return labels[service] || service
}

const serviceColor = (service: string) => {
  const colors: Record<string, string> = {
    MTN: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    ORANGE: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  }
  return colors[service] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const statusBadge = (status: string) => {
  const badges: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    success: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  }
  return badges[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const formatMoney = (amount: number) => {
  return new Intl.NumberFormat('en-CM', { maximumFractionDigits: 0 }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// PIN Setup
const openPinSetup = () => {
  pinForm.reset()
  pinForm.clearErrors()
  showPinSetup.value = true
}

const handlePinSetup = () => {
  pinForm.post('/admin/withdrawals/set-pin', {
    preserveScroll: true,
    onSuccess: () => {
      showPinSetup.value = false
      pinForm.reset()
    },
  })
}

// Withdrawal — Step 1: Open Form
const openWithdrawForm = () => {
  withdrawForm.reset()
  withdrawForm.clearErrors()
  verifiedName.value = ''
  verifyError.value = ''
  canProceedManually.value = false
  manualName.value = ''
  verifiedByMesomb.value = false
  showWithdrawForm.value = true
}

// Withdrawal — Step 2: Verify Account via MeSomb
const verifyAccount = async () => {
  // Client-side validation
  if (!withdrawForm.amount || withdrawForm.amount < 100) {
    withdrawForm.setError('amount', 'Minimum withdrawal amount is 100 XAF.')
    return
  }
  if (!withdrawForm.service) {
    withdrawForm.setError('service', 'Please select a mobile money provider.')
    return
  }
  if (!withdrawForm.receiver || !/^6\d{8}$/.test(withdrawForm.receiver)) {
    withdrawForm.setError('receiver', 'Enter a valid Cameroon number (e.g. 6XXXXXXXX).')
    return
  }

  withdrawForm.clearErrors()
  verifying.value = true
  verifyError.value = ''
  verifiedName.value = ''

  try {
    const response = await fetch('/admin/withdrawals/verify-account', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        service: withdrawForm.service,
        receiver: withdrawForm.receiver,
      }),
    })

    const data = await response.json()

    if (data.success) {
      verifiedName.value = data.account_name
      withdrawForm.receiver_name = data.account_name
      verifiedByMesomb.value = true
      canProceedManually.value = false
      showWithdrawForm.value = false
      showAccountVerification.value = true
    } else {
      verifyError.value = data.message || 'Could not verify account automatically.'
      canProceedManually.value = data.can_proceed_manually ?? false
    }
  } catch (e) {
    verifyError.value = 'Network error. Please check your connection and try again.'
    canProceedManually.value = false
  } finally {
    verifying.value = false
  }
}

// Withdrawal — Step 3: Confirm account name → go to PIN
const confirmAccountAndProceed = () => {
  showAccountVerification.value = false
  showPinConfirm.value = true
  withdrawForm.pin = ''
}

// Withdrawal — Step 4: Enter PIN and submit
const handleWithdraw = () => {
  if (withdrawForm.pin.length !== 4) {
    withdrawForm.setError('pin', 'Please enter your complete 4-digit PIN.')
    return
  }

  withdrawForm.post('/admin/withdrawals', {
    preserveScroll: true,
    onSuccess: () => {
      showPinConfirm.value = false
      withdrawForm.reset()
      verifiedName.value = ''
    },
    onError: () => {
      withdrawForm.pin = ''
    },
  })
}

// Manual name entry fallback
const proceedManually = () => {
  const name = manualName.value.trim()
  if (!name) return
  verifiedName.value = name
  withdrawForm.receiver_name = name
  verifiedByMesomb.value = false
  showWithdrawForm.value = false
  showAccountVerification.value = true
}

// Navigation helpers
const goBackToFormFromVerification = () => {
  showAccountVerification.value = false
  showWithdrawForm.value = true
}

const goBackToVerificationFromPin = () => {
  showPinConfirm.value = false
  showAccountVerification.value = true
  withdrawForm.pin = ''
}
</script>

<template>
  <Head title="Withdrawals" />
  <div>
    <!-- Page Header -->
    <div class="mb-6 lg:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-100">Withdrawals</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm lg:text-base">Withdraw funds from your MeSomb account to mobile money.</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          @click="openPinSetup"
          class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          <KeyRound class="w-4 h-4" />
          {{ hasPin ? 'Change PIN' : 'Set PIN' }}
        </button>
        <button
          v-if="hasPin"
          @click="openWithdrawForm"
          class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg bg-[#42b6c5] text-white hover:bg-[#3aa3b1] transition-colors shadow-sm"
        >
          <Send class="w-4 h-4" />
          New Withdrawal
        </button>
      </div>
    </div>

    <!-- PIN Required Banner -->
    <div
      v-if="!hasPin"
      class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 lg:p-6 flex items-start gap-4"
    >
      <div class="flex-shrink-0 p-2 bg-amber-100 dark:bg-amber-900/40 rounded-lg">
        <Shield class="w-6 h-6 text-amber-600 dark:text-amber-400" />
      </div>
      <div>
        <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">Security PIN Required</h3>
        <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
          You need to set a 4-digit security PIN before you can make withdrawals. This PIN confirms your identity for each transaction.
        </p>
        <button
          @click="openPinSetup"
          class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors"
        >
          <KeyRound class="w-4 h-4" />
          Set Your PIN Now
        </button>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6 mb-6 lg:mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 lg:p-6 border-l-4 border-[#42b6c5]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">Total Withdrawn</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ formatMoney(stats.total_withdrawn) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">XAF</p>
          </div>
          <div class="p-2 lg:p-3 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
            <Wallet class="w-5 h-5 lg:w-7 lg:h-7 text-cyan-600 dark:text-cyan-400" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 lg:p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">Successful</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ stats.successful_count }}</p>
          </div>
          <div class="p-2 lg:p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <CheckCircle2 class="w-5 h-5 lg:w-7 lg:h-7 text-green-600 dark:text-green-400" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 lg:p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">Pending</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ stats.pending_count }}</p>
          </div>
          <div class="p-2 lg:p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
            <Clock class="w-5 h-5 lg:w-7 lg:h-7 text-yellow-600 dark:text-yellow-400" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 lg:p-6 border-l-4 border-red-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs lg:text-sm text-gray-600 dark:text-gray-400 font-semibold">Failed</p>
            <p class="text-xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100 mt-1 lg:mt-2">{{ stats.failed_count }}</p>
          </div>
          <div class="p-2 lg:p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
            <XCircle class="w-5 h-5 lg:w-7 lg:h-7 text-red-600 dark:text-red-400" />
          </div>
        </div>
      </div>
    </div>

    <!-- MeSomb Account Balance -->
    <WhenVisible data="mesombBalance">
      <template #fallback>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 lg:p-6 mb-6 lg:mb-8 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-4">
            <div class="h-5 w-32 bg-gray-200 dark:bg-gray-700 rounded animate-pulse" />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div v-for="i in 3" :key="i" class="h-16 bg-gray-100 dark:bg-gray-700 rounded-lg animate-pulse" />
          </div>
        </div>
      </template>

      <div v-if="mesombBalance !== null" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 lg:p-6 mb-6 lg:mb-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2 mb-4">
          <Banknote class="w-5 h-5 text-[#42b6c5]" />
          <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">MeSomb Account Balance</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <!-- Total -->
          <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-lg p-4 border-l-4 border-[#42b6c5]">
            <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">Total Balance</p>
            <p class="text-2xl font-bold text-[#000928] dark:text-gray-100">{{ formatMoney(mesombBalance.total) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">XAF</p>
          </div>

          <!-- Per provider -->
          <template v-for="balance in mesombBalance.balances" :key="balance.provider + balance.country">
            <div
              :class="[
                'rounded-lg p-4 border-l-4',
                balance.provider === 'MTN'
                  ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400'
                  : 'bg-orange-50 dark:bg-orange-900/20 border-orange-500',
              ]"
            >
              <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">{{ balance.provider }}</p>
              <p class="text-2xl font-bold text-[#000928] dark:text-gray-100">{{ formatMoney(balance.value) }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ balance.country }} · XAF</p>
            </div>
          </template>
        </div>
      </div>
    </WhenVisible>

    <!-- Withdrawal History -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
      <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-base lg:text-lg font-bold text-gray-900 dark:text-gray-100">Withdrawal History</h3>
      </div>

      <!-- Mobile Card View -->
      <div v-if="withdrawals.data.length > 0" class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
        <div v-for="w in withdrawals.data" :key="w.id" class="p-4">
          <div class="flex items-start justify-between mb-2">
            <div>
              <p class="font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(w.amount) }} {{ w.currency }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ w.receiver_name || w.receiver }}</p>
            </div>
            <span :class="['px-2 py-1 rounded-full text-xs font-semibold', statusBadge(w.status)]">
              {{ w.status.charAt(0).toUpperCase() + w.status.slice(1) }}
            </span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span :class="['px-2 py-0.5 rounded text-xs font-medium', serviceColor(w.service)]">
              {{ w.service }}
            </span>
            <span class="text-gray-400 dark:text-gray-500 text-xs">{{ formatDate(w.created_at) }}</span>
          </div>
          <div v-if="w.user" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            By: {{ w.user.name }}
          </div>
          <div v-if="w.failure_reason && w.status === 'failed'" class="mt-2 text-xs text-red-600 dark:text-red-400">
            {{ w.failure_reason }}
          </div>
        </div>
      </div>

      <!-- Desktop Table View -->
      <div v-if="withdrawals.data.length > 0" class="hidden lg:block overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Receiver</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Service</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">By</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="w in withdrawals.data" :key="w.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ formatMoney(w.amount) }} <span class="text-xs text-gray-500 font-normal">{{ w.currency }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                <div>{{ w.receiver_name || '—' }}</div>
                <div class="text-xs text-gray-400 dark:text-gray-500">{{ w.receiver }}</div>
              </td>
              <td class="px-6 py-4 text-sm">
                <span :class="['px-2 py-1 rounded text-xs font-medium', serviceColor(w.service)]">
                  {{ w.service }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm">
                <span :class="['px-3 py-1 rounded-full text-xs font-semibold', statusBadge(w.status)]">
                  {{ w.status.charAt(0).toUpperCase() + w.status.slice(1) }}
                </span>
                <p v-if="w.failure_reason && w.status === 'failed'" class="mt-1 text-xs text-red-500 dark:text-red-400 max-w-xs truncate" :title="w.failure_reason">
                  {{ w.failure_reason }}
                </p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ w.user?.name ?? '—' }}</td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ formatDate(w.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="withdrawals.data.length === 0" class="px-4 lg:px-6 py-16 text-center">
        <ArrowDownToLine class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <p class="text-gray-500 dark:text-gray-400 font-medium">No withdrawals yet</p>
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Make your first withdrawal to see it here.</p>
      </div>

      <!-- Pagination -->
      <div v-if="withdrawals.last_page > 1" class="px-4 lg:px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Page {{ withdrawals.current_page }} of {{ withdrawals.last_page }} ({{ withdrawals.total }} total)
        </p>
        <div class="flex gap-1">
          <template v-for="link in withdrawals.links" :key="link.label">
            <a
              v-if="link.url"
              :href="link.url"
              :class="[
                'px-3 py-1.5 text-sm rounded-lg transition-colors',
                link.active
                  ? 'bg-[#42b6c5] text-white'
                  : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
              ]"
              v-html="link.label"
            />
            <span
              v-else
              class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-600"
              v-html="link.label"
            />
          </template>
        </div>
      </div>
    </div>

    <!-- PIN Setup Dialog -->
    <Dialog :open="showPinSetup" @update:open="showPinSetup = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Shield class="w-5 h-5 text-[#42b6c5]" />
            {{ hasPin ? 'Change Withdrawal PIN' : 'Set Withdrawal PIN' }}
          </DialogTitle>
          <DialogDescription>
            {{ hasPin ? 'Enter your current PIN and set a new 4-digit PIN.' : 'Create a secure 4-digit PIN to authorize withdrawals.' }}
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="handlePinSetup" class="space-y-6 py-4">
          <!-- Current PIN (if changing) -->
          <div v-if="hasPin" class="space-y-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current PIN</label>
            <div class="flex justify-center">
              <InputOTP v-model="pinForm.current_pin" :maxlength="4" inputmode="numeric">
                <InputOTPGroup>
                  <InputOTPSlot :index="0" class="!w-14 !h-14 !text-xl font-bold" />
                  <InputOTPSlot :index="1" class="!w-14 !h-14 !text-xl font-bold" />
                  <InputOTPSlot :index="2" class="!w-14 !h-14 !text-xl font-bold" />
                  <InputOTPSlot :index="3" class="!w-14 !h-14 !text-xl font-bold" />
                </InputOTPGroup>
              </InputOTP>
            </div>
            <p v-if="pinForm.errors.current_pin" class="text-sm text-red-600 dark:text-red-400 text-center">{{ pinForm.errors.current_pin }}</p>
          </div>

          <!-- New PIN -->
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ hasPin ? 'New PIN' : 'Enter PIN' }}</label>
            <div class="flex justify-center">
              <InputOTP v-model="pinForm.pin" :maxlength="4" inputmode="numeric">
                <InputOTPGroup>
                  <InputOTPSlot :index="0" class="!w-14 !h-14 !text-xl font-bold" />
                  <InputOTPSlot :index="1" class="!w-14 !h-14 !text-xl font-bold" />
                  <InputOTPSlot :index="2" class="!w-14 !h-14 !text-xl font-bold" />
                  <InputOTPSlot :index="3" class="!w-14 !h-14 !text-xl font-bold" />
                </InputOTPGroup>
              </InputOTP>
            </div>
            <p v-if="pinForm.errors.pin" class="text-sm text-red-600 dark:text-red-400 text-center">{{ pinForm.errors.pin }}</p>
          </div>

          <!-- Confirm PIN -->
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm PIN</label>
            <div class="flex justify-center">
              <InputOTP v-model="pinForm.pin_confirmation" :maxlength="4" inputmode="numeric">
                <InputOTPGroup>
                  <InputOTPSlot :index="0" class="!w-14 !h-14 !text-xl font-bold" />
                  <InputOTPSlot :index="1" class="!w-14 !h-14 !text-xl font-bold" />
                  <InputOTPSlot :index="2" class="!w-14 !h-14 !text-xl font-bold" />
                  <InputOTPSlot :index="3" class="!w-14 !h-14 !text-xl font-bold" />
                </InputOTPGroup>
              </InputOTP>
            </div>
            <p v-if="pinForm.errors.pin_confirmation" class="text-sm text-red-600 dark:text-red-400 text-center">{{ pinForm.errors.pin_confirmation }}</p>
          </div>

          <DialogFooter class="gap-2 sm:gap-3">
            <DialogClose as-child>
              <button type="button" class="px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Cancel
              </button>
            </DialogClose>
            <button
              type="submit"
              :disabled="pinForm.processing || pinForm.pin.length !== 4 || pinForm.pin_confirmation.length !== 4"
              class="px-4 py-2.5 text-sm font-medium rounded-lg bg-[#42b6c5] text-white hover:bg-[#3aa3b1] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ pinForm.processing ? 'Saving...' : (hasPin ? 'Update PIN' : 'Set PIN') }}
            </button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Step 1: Withdrawal Form Dialog -->
    <Dialog :open="showWithdrawForm" @update:open="showWithdrawForm = $event">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Banknote class="w-5 h-5 text-[#42b6c5]" />
            New Withdrawal
          </DialogTitle>
          <DialogDescription>
            Enter the withdrawal details. We'll verify the account holder before proceeding.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="verifyAccount" class="space-y-5 py-4">
          <!-- Amount -->
          <div class="space-y-2">
            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Amount (XAF)
            </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <Banknote class="h-5 w-5 text-gray-400" />
              </div>
              <input
                id="amount"
                v-model.number="withdrawForm.amount"
                type="number"
                min="100"
                step="1"
                placeholder="Enter amount"
                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-3 pl-10 pr-4 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none transition-colors"
              />
            </div>
            <p v-if="withdrawForm.errors.amount" class="text-sm text-red-600 dark:text-red-400">{{ withdrawForm.errors.amount }}</p>
          </div>

          <!-- Service -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Mobile Money Provider
            </label>
            <div class="grid grid-cols-2 gap-3">
              <button
                v-for="service in services"
                :key="service"
                type="button"
                @click="withdrawForm.service = service"
                :class="[
                  'relative flex items-center gap-3 rounded-lg border-2 p-4 transition-all',
                  withdrawForm.service === service
                    ? 'border-[#42b6c5] bg-[#42b6c5]/5 dark:bg-[#42b6c5]/10 ring-2 ring-[#42b6c5]/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                ]"
              >
                <div :class="[
                  'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold',
                  service === 'MTN' ? 'bg-yellow-400 text-yellow-900' : 'bg-orange-500 text-white'
                ]">
                  {{ service.charAt(0) }}
                </div>
                <div class="text-left">
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ service }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ serviceLabel(service) }}</p>
                </div>
                <div v-if="withdrawForm.service === service" class="absolute top-2 right-2">
                  <CheckCircle2 class="w-5 h-5 text-[#42b6c5]" />
                </div>
              </button>
            </div>
            <p v-if="withdrawForm.errors.service" class="text-sm text-red-600 dark:text-red-400">{{ withdrawForm.errors.service }}</p>
          </div>

          <!-- Receiver Phone -->
          <div class="space-y-2">
            <label for="receiver" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Receiver Phone Number
            </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <Phone class="h-5 w-5 text-gray-400" />
              </div>
              <input
                id="receiver"
                v-model="withdrawForm.receiver"
                type="text"
                maxlength="9"
                placeholder="6XXXXXXXX"
                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-3 pl-10 pr-4 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none transition-colors"
              />
            </div>
            <p v-if="withdrawForm.errors.receiver" class="text-sm text-red-600 dark:text-red-400">{{ withdrawForm.errors.receiver }}</p>
          </div>

          <!-- Verify Error -->
          <div v-if="verifyError" class="space-y-3">
            <div :class="['rounded-lg p-3 flex items-start gap-2', canProceedManually ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800']">
              <AlertTriangle :class="['w-5 h-5 flex-shrink-0 mt-0.5', canProceedManually ? 'text-amber-500' : 'text-red-500']" />
              <p :class="['text-sm', canProceedManually ? 'text-amber-700 dark:text-amber-300' : 'text-red-700 dark:text-red-300']">{{ verifyError }}</p>
            </div>
            <!-- Manual name entry fallback -->
            <div v-if="canProceedManually" class="space-y-2 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10 p-3">
              <p class="text-xs font-medium text-amber-800 dark:text-amber-300">Enter the account holder name manually to proceed:</p>
              <div class="flex gap-2">
                <input
                  v-model="manualName"
                  type="text"
                  placeholder="e.g. John Doe"
                  class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-2 px-3 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 focus:outline-none transition-colors"
                  @keydown.enter.prevent="proceedManually"
                />
                <button
                  type="button"
                  :disabled="!manualName.trim()"
                  @click="proceedManually"
                  class="px-3 py-2 text-sm font-medium rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors disabled:opacity-40 disabled:cursor-not-allowed whitespace-nowrap"
                >
                  Proceed
                </button>
              </div>
            </div>
          </div>

          <DialogFooter class="gap-2 sm:gap-3">
            <DialogClose as-child>
              <button type="button" class="px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Cancel
              </button>
            </DialogClose>
            <button
              type="submit"
              :disabled="verifying"
              class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg bg-[#42b6c5] text-white hover:bg-[#3aa3b1] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Loader2 v-if="verifying" class="w-4 h-4 animate-spin" />
              <Search v-else class="w-4 h-4" />
              {{ verifying ? 'Verifying...' : 'Verify Account' }}
            </button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Step 2: Account Verification Confirmation Dialog -->
    <Dialog :open="showAccountVerification" @update:open="val => { if (!val) goBackToFormFromVerification() }">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <CheckCircle2 v-if="verifiedByMesomb" class="w-5 h-5 text-green-500" />
            <AlertTriangle v-else class="w-5 h-5 text-amber-500" />
            {{ verifiedByMesomb ? 'Account Verified' : 'Manual Verification' }}
          </DialogTitle>
          <DialogDescription>
            {{ verifiedByMesomb ? 'Please confirm this is the correct account holder before continuing.' : 'Auto-verification was unavailable. Please confirm the details below are correct before continuing.' }}
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-5 py-4">
          <!-- Verified Account Info Card -->
          <div :class="['rounded-xl p-5', verifiedByMesomb ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800']">
            <div class="flex items-center gap-4">
              <div :class="['flex-shrink-0 w-14 h-14 rounded-full flex items-center justify-center', verifiedByMesomb ? 'bg-green-100 dark:bg-green-900/40' : 'bg-amber-100 dark:bg-amber-900/40']">
                <User :class="['w-7 h-7', verifiedByMesomb ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400']" />
              </div>
              <div>
                <p :class="['text-xs font-medium uppercase tracking-wider', verifiedByMesomb ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400']">
                  {{ verifiedByMesomb ? 'Account Holder (MeSomb verified)' : 'Account Holder (manually entered)' }}
                </p>
                <p :class="['text-xl font-bold mt-0.5', verifiedByMesomb ? 'text-green-800 dark:text-green-200' : 'text-amber-800 dark:text-amber-200']">{{ verifiedName }}</p>
                <p :class="['text-sm mt-0.5', verifiedByMesomb ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400']">{{ withdrawForm.receiver }}</p>
              </div>
            </div>
          </div>

          <!-- Transaction Summary -->
          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-5 space-y-3">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500 dark:text-gray-400">Amount</span>
              <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ formattedAmount }} <span class="text-xs font-normal text-gray-500">XAF</span></span>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3 space-y-2.5">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Provider</span>
                <span :class="['px-2 py-0.5 rounded text-xs font-semibold', serviceColor(withdrawForm.service)]">
                  {{ serviceLabel(withdrawForm.service) }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Phone</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ withdrawForm.receiver }}</span>
              </div>
            </div>
          </div>

          <!-- Warning -->
          <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 bg-amber-50 dark:bg-amber-900/10 p-3 rounded-lg">
            <AlertTriangle class="w-4 h-4 text-amber-500 flex-shrink-0" />
            <span>Make sure this is the correct recipient. You'll enter your PIN in the next step to authorize the transfer.</span>
          </div>

          <div class="flex gap-3">
            <button
              type="button"
              @click="goBackToFormFromVerification"
              class="flex-1 px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Back
            </button>
            <button
              type="button"
              @click="confirmAccountAndProceed"
              class="flex-1 px-4 py-2.5 text-sm font-medium rounded-lg bg-[#42b6c5] text-white hover:bg-[#3aa3b1] transition-colors flex items-center justify-center gap-2"
            >
              <Shield class="w-4 h-4" />
              Confirm &amp; Enter PIN
            </button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Step 3: PIN Entry + Final Confirmation Dialog -->
    <Dialog :open="showPinConfirm" @update:open="val => { if (!val) goBackToVerificationFromPin() }">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <Shield class="w-5 h-5 text-[#42b6c5]" />
            Authorize Withdrawal
          </DialogTitle>
          <DialogDescription>
            Enter your 4-digit PIN to complete this withdrawal.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-6 py-4">
          <!-- Compact Summary -->
          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500 dark:text-gray-400">Amount</span>
              <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formattedAmount }} <span class="text-sm font-normal text-gray-500">XAF</span></span>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-600 pt-3 space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">To</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ verifiedName }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Phone</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ withdrawForm.receiver }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Provider</span>
                <span :class="['px-2 py-0.5 rounded text-xs font-semibold', serviceColor(withdrawForm.service)]">
                  {{ withdrawForm.service }}
                </span>
              </div>
            </div>
          </div>

          <!-- PIN Entry -->
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 text-center">
              Enter your 4-digit PIN
            </label>
            <div class="flex justify-center">
              <InputOTP v-model="withdrawForm.pin" :maxlength="4" inputmode="numeric">
                <InputOTPGroup>
                  <InputOTPSlot :index="0" class="!w-16 !h-16 !text-2xl font-bold" />
                  <InputOTPSlot :index="1" class="!w-16 !h-16 !text-2xl font-bold" />
                  <InputOTPSlot :index="2" class="!w-16 !h-16 !text-2xl font-bold" />
                  <InputOTPSlot :index="3" class="!w-16 !h-16 !text-2xl font-bold" />
                </InputOTPGroup>
              </InputOTP>
            </div>
            <p v-if="withdrawForm.errors.pin" class="text-sm text-red-600 dark:text-red-400 text-center">{{ withdrawForm.errors.pin }}</p>
          </div>

          <!-- Withdrawal-level Error -->
          <div v-if="(withdrawForm.errors as Record<string, string>).withdrawal" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
            <div class="flex items-start gap-2">
              <AlertTriangle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-700 dark:text-red-300">{{ (withdrawForm.errors as Record<string, string>).withdrawal }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 bg-amber-50 dark:bg-amber-900/10 p-3 rounded-lg">
            <AlertTriangle class="w-4 h-4 text-amber-500 flex-shrink-0" />
            <span>This action is irreversible. Funds will be sent immediately upon confirmation.</span>
          </div>

          <div class="flex gap-3">
            <button
              type="button"
              @click="goBackToVerificationFromPin"
              class="flex-1 px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Back
            </button>
            <button
              type="button"
              @click="handleWithdraw"
              :disabled="withdrawForm.processing || withdrawForm.pin.length !== 4"
              class="flex-1 px-4 py-2.5 text-sm font-medium rounded-lg bg-[#42b6c5] text-white hover:bg-[#3aa3b1] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <span v-if="withdrawForm.processing" class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ withdrawForm.processing ? 'Processing...' : 'Confirm & Send' }}
            </button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
