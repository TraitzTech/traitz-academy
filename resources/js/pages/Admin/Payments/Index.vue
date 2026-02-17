<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { computed, ref, watch } from 'vue'

import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface PaymentRow {
  id: number
  reference: string
  receipt_number: string | null
  mesomb_transaction_id: string | null
  payer_phone: string
  provider: string
  amount: number
  currency: string
  status: 'pending' | 'successful' | 'failed'
  payment_type: 'full' | 'installment'
  installment_number: number
  total_installments: number
  payment_channel: string | null
  manual_entry: boolean
  admin_notes: string | null
  failure_reason: string | null
  updated_by: {
    id: number
    name: string
  } | null
  paid_at: string | null
  application: {
    first_name: string
    last_name: string
    email: string
  }
  program: {
    id: number
    title: string
  }
}

interface AcceptedApplicationOption {
  id: number
  applicant_name: string
  email: string
  phone: string | null
  program_id: number
  program_title: string | null
  program_price: number
  paid_amount: number
  remaining_amount: number
  max_installments: number
  completed_installments: number
}

interface Props {
  payments: {
    data: PaymentRow[]
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  filters: {
    search?: string
    status?: string
    program_id?: string
  }
  programs: Array<{ id: number; title: string }>
  acceptedApplications: AcceptedApplicationOption[]
  stats: {
    successful_count: number
    pending_count: number
    failed_count: number
    total_collected: number
    total_outstanding: number
  }
}

const props = defineProps<Props>()
defineOptions({ layout: AppLayout })
const toast = useToast()

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')
const programId = ref(props.filters.program_id || '')

const applyFilters = debounce(() => {
  router.get('/admin/payments', {
    search: search.value || undefined,
    status: status.value || undefined,
    program_id: programId.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}, 300)

watch([search, status, programId], applyFilters)

const formatMoney = (amount: number, currency = 'XAF') => {
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency }).format(amount || 0)
}

const formatDate = (date: string | null) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const hasRows = computed(() => props.payments.data.length > 0)
const acceptedApplications = computed(() => props.acceptedApplications)

const showManualModal = ref(false)
const showEditModal = ref(false)
const editingPayment = ref<PaymentRow | null>(null)

const manualForm = useForm({
  application_id: '',
  amount: '',
  provider: 'CASH',
  payment_channel: 'ONSITE',
  payer_phone: '',
  status: 'successful',
  payment_type: 'full',
  paid_at: '',
  failure_reason: '',
  admin_notes: '',
})

const editForm = useForm({
  reference: '',
  receipt_number: '',
  payer_phone: '',
  provider: 'MTN',
  payment_channel: '',
  amount: '',
  payment_type: 'full',
  installment_number: 1,
  total_installments: 1,
  status: 'pending',
  paid_at: '',
  mesomb_transaction_id: '',
  failure_reason: '',
  admin_notes: '',
  manual_entry: false,
})

const selectedApplication = computed(() => {
  return props.acceptedApplications.find((application) => String(application.id) === String(manualForm.application_id)) || null
})

const manualAmountNumber = computed(() => {
  const parsed = Number(manualForm.amount)
  return Number.isFinite(parsed) ? Math.max(parsed, 0) : 0
})

const manualRemainingAfter = computed(() => {
  const application = selectedApplication.value
  if (!application) {
    return 0
  }

  return Math.max(0, Number(application.remaining_amount) - manualAmountNumber.value)
})

const manualInstallmentsAfter = computed(() => {
  const application = selectedApplication.value
  if (!application) {
    return 0
  }

  if (manualForm.status !== 'successful' || manualAmountNumber.value <= 0) {
    return application.completed_installments
  }

  const isInstallment = manualAmountNumber.value < Number(application.remaining_amount)
  if (isInstallment) {
    return Math.min(application.max_installments, application.completed_installments + 1)
  }

  return application.max_installments
})

const manualEffectivePaymentType = computed(() => {
  const application = selectedApplication.value
  if (!application) {
    return manualForm.payment_type
  }

  return manualAmountNumber.value < Number(application.remaining_amount) ? 'installment' : 'full'
})

const openManualModal = () => {
  manualForm.reset()
  manualForm.clearErrors()
  manualForm.provider = 'CASH'
  manualForm.payment_channel = 'ONSITE'
  manualForm.status = 'successful'
  manualForm.payment_type = 'full'
  showManualModal.value = true
}

watch(() => manualForm.application_id, () => {
  const application = selectedApplication.value

  if (!application) {
    return
  }

  manualForm.amount = application.remaining_amount > 0 ? String(application.remaining_amount) : String(application.program_price)
  manualForm.payer_phone = application.phone || ''
})

watch([() => manualForm.amount, () => manualForm.status, () => manualForm.application_id], () => {
  if (!selectedApplication.value || manualForm.status !== 'successful') {
    return
  }

  manualForm.payment_type = manualEffectivePaymentType.value
})

const recordManualPayment = () => {
  manualForm.post('/admin/payments/manual', {
    preserveScroll: true,
    onSuccess: () => {
      showManualModal.value = false
      toast.success('Manual payment recorded successfully.')
    },
    onError: () => {
      toast.error('Failed to record manual payment.')
    },
  })
}

const openEditModal = (payment: PaymentRow) => {
  editingPayment.value = payment
  editForm.clearErrors()
  editForm.reference = payment.reference
  editForm.receipt_number = payment.receipt_number || ''
  editForm.payer_phone = payment.payer_phone
  editForm.provider = payment.provider
  editForm.payment_channel = payment.payment_channel || ''
  editForm.amount = String(payment.amount)
  editForm.payment_type = payment.payment_type
  editForm.installment_number = payment.installment_number
  editForm.total_installments = payment.total_installments
  editForm.status = payment.status
  editForm.paid_at = payment.paid_at ? new Date(payment.paid_at).toISOString().slice(0, 16) : ''
  editForm.mesomb_transaction_id = payment.mesomb_transaction_id || ''
  editForm.failure_reason = payment.failure_reason || ''
  editForm.admin_notes = payment.admin_notes || ''
  editForm.manual_entry = payment.manual_entry
  showEditModal.value = true
}

const updatePayment = () => {
  if (!editingPayment.value) {
    return
  }

  editForm.patch(`/admin/payments/${editingPayment.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false
      editingPayment.value = null
      toast.success('Payment updated successfully.')
    },
    onError: () => {
      toast.error('Failed to update payment.')
    },
  })
}
</script>

<template>
  <div>
    <Head title="Payments" />

    <div class="mb-8">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Payments</h2>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Track all program fee transactions and installment progress</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Link
            href="/admin/payments/verify"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            Verify Receipt
          </Link>
          <button
            type="button"
            class="px-4 py-2 bg-[#42b6c5] text-white rounded-lg font-medium hover:bg-[#35919e] transition-colors"
            @click="openManualModal"
          >
            Record Onsite Payment
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-green-500">
        <p class="text-sm text-gray-500 dark:text-gray-400">Successful</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.successful_count }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-yellow-500">
        <p class="text-sm text-gray-500 dark:text-gray-400">Pending</p>
        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ stats.pending_count }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-red-500">
        <p class="text-sm text-gray-500 dark:text-gray-400">Failed</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ stats.failed_count }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-[#42b6c5]">
        <p class="text-sm text-gray-500 dark:text-gray-400">Total Collected</p>
        <p class="text-xl font-bold text-[#42b6c5] mt-1">{{ formatMoney(stats.total_collected) }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-indigo-500 sm:col-span-2 xl:col-span-1">
        <p class="text-sm text-gray-500 dark:text-gray-400">Still Collectable</p>
        <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ formatMoney(stats.total_outstanding) }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
          <input
            v-model="search"
            type="text"
            placeholder="Reference, receipt, phone, applicant..."
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
          <select
            v-model="status"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          >
            <option value="">All</option>
            <option value="successful">Successful</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program</label>
          <select
            v-model="programId"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          >
            <option value="">All Programs</option>
            <option v-for="program in programs" :key="program.id" :value="program.id">{{ program.title }}</option>
          </select>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700/40">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Receipt</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Applicant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Program</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Source</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                <p class="font-semibold">{{ payment.receipt_number || 'Pending' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ payment.reference }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                <p class="font-semibold">{{ payment.application.first_name }} {{ payment.application.last_name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ payment.application.email }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ payment.program?.title || 'Program' }}</td>
              <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(payment.amount, payment.currency) }}</td>
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 capitalize">
                {{ payment.payment_type }}
                <span class="text-xs text-gray-500 dark:text-gray-400">({{ payment.installment_number }}/{{ payment.total_installments }})</span>
              </td>
              <td class="px-6 py-4 text-sm">
                <span :class="[
                  'px-2 py-1 rounded-full text-xs font-semibold uppercase',
                  payment.status === 'successful' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                  payment.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                  'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                ]">
                  {{ payment.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                <div class="space-y-1">
                  <span :class="[
                    'px-2 py-1 rounded-full text-xs font-semibold uppercase',
                    payment.manual_entry
                      ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400'
                      : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400'
                  ]">
                    {{ payment.manual_entry ? 'Manual' : 'Online' }}
                  </span>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ payment.payment_channel || '—' }}</p>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(payment.paid_at) }}</td>
              <td class="px-6 py-4 text-right text-sm">
                <div class="flex items-center justify-end gap-3">
                  <button
                    type="button"
                    class="text-indigo-600 hover:text-indigo-800 font-medium"
                    @click="openEditModal(payment)"
                  >
                    Edit
                  </button>
                  <Link :href="`/payments/receipts/${payment.id}`" class="text-[#42b6c5] hover:text-[#35919e] font-medium">Receipt</Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!hasRows" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
        No payments found for the current filters.
      </div>

      <div v-if="payments.links && payments.links.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-center">
        <nav class="inline-flex -space-x-px rounded-md shadow-sm">
          <Link
            v-for="(link, index) in payments.links"
            :key="index"
            :href="link.url || '#'"
            :class="[
              'px-4 py-2 border text-sm font-medium',
              link.active ? 'bg-[#42b6c5] text-white border-[#42b6c5]' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600',
              !link.url ? 'opacity-50 cursor-not-allowed' : '',
            ]"
            v-html="link.label"
          />
        </nav>
      </div>
    </div>

    <Dialog :open="showManualModal" @update:open="showManualModal = $event">
      <DialogContent class="sm:max-w-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
        <DialogHeader class="space-y-2">
          <DialogTitle class="text-gray-900 dark:text-gray-100">Record Manual Payment</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Record onsite or bank-transfer payment into the same payment records.
          </DialogDescription>
        </DialogHeader>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[70vh] overflow-y-auto pr-1">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Accepted Application</label>
            <select v-model="manualForm.application_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
              <option value="">Select application</option>
              <option v-for="application in acceptedApplications" :key="application.id" :value="String(application.id)">
                #{{ application.id }} - {{ application.applicant_name }} ({{ application.program_title }})
              </option>
            </select>
            <p v-if="manualForm.errors.application_id" class="text-xs text-red-600 mt-1">{{ manualForm.errors.application_id }}</p>
          </div>

          <div class="md:col-span-2 rounded-lg border border-cyan-200 dark:border-cyan-800 bg-cyan-50 dark:bg-cyan-900/20 px-3 py-3" v-if="selectedApplication">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
              <p class="text-cyan-800 dark:text-cyan-200">
                Paid: {{ formatMoney(selectedApplication.paid_amount) }} / {{ formatMoney(selectedApplication.program_price) }}
              </p>
              <p class="text-cyan-800 dark:text-cyan-200">
                Remaining before: {{ formatMoney(selectedApplication.remaining_amount) }}
              </p>
              <p class="text-cyan-800 dark:text-cyan-200 font-semibold">
                Remaining after this record: {{ formatMoney(manualRemainingAfter) }}
              </p>
              <p class="text-cyan-800 dark:text-cyan-200">
                Installments after record: {{ manualInstallmentsAfter }} / {{ selectedApplication.max_installments }}
              </p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
            <input v-model="manualForm.amount" type="number" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
            <p v-if="selectedApplication" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              For successful payments, amount below remaining is treated as installment automatically.
            </p>
            <p v-if="manualForm.errors.amount" class="text-xs text-red-600 mt-1">{{ manualForm.errors.amount }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
            <input v-model="manualForm.payer_phone" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
            <p v-if="manualForm.errors.payer_phone" class="text-xs text-red-600 mt-1">{{ manualForm.errors.payer_phone }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Provider</label>
            <select v-model="manualForm.provider" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
              <option value="CASH">CASH</option>
              <option value="BANK_TRANSFER">BANK TRANSFER</option>
              <option value="MTN">MTN</option>
              <option value="ORANGE">ORANGE</option>
              <option value="OTHER">OTHER</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Channel</label>
            <select v-model="manualForm.payment_channel" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
              <option value="ONSITE">ONSITE</option>
              <option value="BANK_TRANSFER">BANK TRANSFER</option>
              <option value="CASH">CASH</option>
              <option value="OTHER">OTHER</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select v-model="manualForm.status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
              <option value="successful">successful</option>
              <option value="pending">pending</option>
              <option value="failed">failed</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Type</label>
            <select v-model="manualForm.payment_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
              <option value="full">full</option>
              <option value="installment">installment</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Paid At (optional)</label>
            <input v-model="manualForm.paid_at" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Failure Reason (if failed)</label>
            <input v-model="manualForm.failure_reason" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Admin Notes</label>
            <textarea v-model="manualForm.admin_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"></textarea>
          </div>
        </div>

        <DialogFooter class="gap-2">
          <DialogClose as-child>
            <button type="button" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
          </DialogClose>
          <button
            type="button"
            class="px-4 py-2 bg-[#42b6c5] text-white rounded-lg font-medium hover:bg-[#35919e]"
            :disabled="manualForm.processing"
            @click="recordManualPayment"
          >
            {{ manualForm.processing ? 'Saving...' : 'Record Payment' }}
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="showEditModal" @update:open="showEditModal = $event">
      <DialogContent class="sm:max-w-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
        <DialogHeader class="space-y-2">
          <DialogTitle class="text-gray-900 dark:text-gray-100">Edit Payment</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Update payment status, amount, source, and metadata.
          </DialogDescription>
        </DialogHeader>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[70vh] overflow-y-auto pr-1">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference</label>
            <input v-model="editForm.reference" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Receipt Number</label>
            <input v-model="editForm.receipt_number" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
            <input v-model="editForm.amount" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
            <input v-model="editForm.payer_phone" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Provider</label>
            <select v-model="editForm.provider" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
              <option value="MTN">MTN</option>
              <option value="ORANGE">ORANGE</option>
              <option value="CASH">CASH</option>
              <option value="BANK_TRANSFER">BANK TRANSFER</option>
              <option value="OTHER">OTHER</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Channel</label>
            <select v-model="editForm.payment_channel" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
              <option value="">—</option>
              <option value="ONLINE">ONLINE</option>
              <option value="ONSITE">ONSITE</option>
              <option value="BANK_TRANSFER">BANK TRANSFER</option>
              <option value="CASH">CASH</option>
              <option value="OTHER">OTHER</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select v-model="editForm.status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
              <option value="successful">successful</option>
              <option value="pending">pending</option>
              <option value="failed">failed</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Type</label>
            <select v-model="editForm.payment_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
              <option value="full">full</option>
              <option value="installment">installment</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Installment No</label>
            <input v-model="editForm.installment_number" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Installments</label>
            <input v-model="editForm.total_installments" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Paid At</label>
            <input v-model="editForm.paid_at" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">MeSomb Transaction ID</label>
            <input v-model="editForm.mesomb_transaction_id" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Failure Reason</label>
            <input v-model="editForm.failure_reason" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Admin Notes</label>
            <textarea v-model="editForm.admin_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"></textarea>
          </div>

          <label class="md:col-span-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input v-model="editForm.manual_entry" type="checkbox" class="rounded border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-800 text-[#42b6c5] focus:ring-[#42b6c5]" />
            Mark as manual entry
          </label>
        </div>

        <DialogFooter class="gap-2">
          <DialogClose as-child>
            <button type="button" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
          </DialogClose>
          <button
            type="button"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700"
            :disabled="editForm.processing"
            @click="updatePayment"
          >
            {{ editForm.processing ? 'Updating...' : 'Update Payment' }}
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
