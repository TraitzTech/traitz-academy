<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { Button } from '@/components/ui/button'
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

interface Application {
  id: number
  first_name: string
  last_name: string
  email: string
  phone: string
  country: string
  city: string
  education_level: string
  experience: string
  motivation: string
  internship_letter_path: string | null
  status: 'pending' | 'accepted' | 'rejected'
  notes: string | null
  created_at: string
  reviewed_at: string | null
  reviewed_by: number | null
  interview_id: number | null
  interview_scheduled_at: string | null
  interview_status: string | null
  interview: {
    id: number
    title: string
    description: string | null
    passing_score: number
    time_limit_minutes: number | null
  } | null
  program: {
    id: number
    title: string
    category: string
    price: number
    start_date: string
    end_date: string
  }
  user: {
    id: number
    name: string
    email: string
  } | null
  reviewer: {
    id: number
    name: string
  } | null
}

interface AvailableInterview {
  id: number
  title: string
  description: string | null
  passing_score: number
  time_limit_minutes: number | null
  questions_count: number
}

interface InterviewAnswer {
  id: number
  answer: string | null
  is_correct: boolean
  points_earned: number
  question: {
    id: number
    question: string
    type: string
    correct_answer: string | null
    points: number
  }
}

interface InterviewResponse {
  id: number
  score: number
  total_points: number
  percentage: number
  status: string
  passed: boolean
  started_at: string | null
  completed_at: string | null
  answers: InterviewAnswer[]
}

interface PaymentSummary {
  program_price: number
  paid_amount: number
  remaining_amount: number
  max_installments: number
  completed_installments: number
  status: 'paid' | 'partially-paid' | 'unpaid' | 'not-required'
  can_send_reminder: boolean
}

interface SuccessfulPayment {
  id: number
  amount: number
  installment_number: number | null
  total_installments: number | null
  receipt_number: string | null
  paid_at: string | null
}

const props = defineProps<{
  application: Application
  availableInterviews: AvailableInterview[]
  interviewResponse: InterviewResponse | null
  paymentSummary: PaymentSummary
  successfulPayments: SuccessfulPayment[]
}>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const showRejectModal = ref(false)
const showInterviewModal = ref(false)
const showDeleteModal = ref(false)
const showAcceptModal = ref(false)
const rejectNotes = ref('')
const selectedInterviewId = ref<number | null>(null)
const schedulingInterview = ref(false)

const paymentStatusLabel = computed(() => {
  if (props.paymentSummary.status === 'partially-paid') {
    return 'Partially Paid'
  }

  return props.paymentSummary.status.replace('-', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (price: number) => {
  if (price === 0) return 'Free'
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(price)
}

const getPaymentStatusColor = (status: string) => {
  switch (status) {
    case 'paid': return 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800'
    case 'partially-paid': return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800'
    case 'unpaid': return 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800'
    default: return 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600'
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'accepted': return 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800'
    case 'rejected': return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800'
    default: return 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800'
  }
}

const acceptApplication = () => {
  router.post(`/admin/applications/${props.application.id}/accept`, {}, {
    preserveState: true,
    onSuccess: () => {
      showAcceptModal.value = false
      toast.success(`Application from ${props.application.first_name} ${props.application.last_name} accepted!`)
    },
    onError: () => {
      toast.error('Failed to accept application.')
    },
  })
}

const rejectApplication = () => {
  router.post(`/admin/applications/${props.application.id}/reject`, {
    notes: rejectNotes.value,
  }, {
    preserveState: true,
    onSuccess: () => {
      showRejectModal.value = false
      toast.success(`Application from ${props.application.first_name} ${props.application.last_name} rejected.`)
    },
    onError: () => {
      toast.error('Failed to reject application.')
    },
  })
}

const deleteApplication = () => {
  router.delete(`/admin/applications/${props.application.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false
      toast.success('Application deleted successfully!')
    },
    onError: () => {
      toast.error('Failed to delete application.')
    },
  })
}

const openInterviewModal = () => {
  selectedInterviewId.value = null
  showInterviewModal.value = true
}

const scheduleInterview = () => {
  if (!selectedInterviewId.value) {
    toast.error('Please select an interview.')
    return
  }

  schedulingInterview.value = true
  router.post(`/admin/applications/${props.application.id}/schedule-interview`, {
    interview_id: selectedInterviewId.value,
  }, {
    preserveState: true,
    onSuccess: () => {
      showInterviewModal.value = false
      schedulingInterview.value = false
      toast.success('Interview scheduled and invitation email sent!')
    },
    onError: (errors) => {
      schedulingInterview.value = false
      const errorMessage = Object.values(errors)[0] || 'Failed to schedule interview.'
      toast.error(errorMessage as string)
    },
  })
}

const getInterviewStatusColor = (status: string | null) => {
  switch (status) {
    case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    case 'scheduled': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
    case 'expired': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400'
    default: return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
  }
}

const sendPaymentReminder = () => {
  router.post(`/admin/applications/${props.application.id}/payment-reminder`, {}, {
    preserveState: true,
    onSuccess: () => {
      toast.success('Payment reminder sent successfully.')
    },
    onError: () => {
      toast.error('Failed to send payment reminder.')
    },
  })
}
</script>

<template>
  <div>
    <Head :title="`Application - ${application.first_name} ${application.last_name}`" />

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <Link href="/admin/applications" class="text-[#42b6c5] hover:text-[#35919e] text-sm mb-2 inline-block">
          ← Back to Applications
        </Link>
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Application Details</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Submitted on {{ formatDate(application.created_at) }}</p>
      </div>
      <div class="flex items-center gap-3">
        <Link
          :href="`/admin/applications/${application.id}/edit`"
          class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors"
        >
          Edit
        </Link>
        <span :class="['px-4 py-2 text-sm font-medium rounded-full border', getStatusColor(application.status)]">
          {{ application.status.charAt(0).toUpperCase() + application.status.slice(1) }}
        </span>
        <template v-if="application.status === 'pending'">
          <button
            @click="showAcceptModal = true"
            class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors"
          >
            Accept
          </button>
          <button
            v-if="application.user && availableInterviews.length > 0"
            @click="openInterviewModal"
            class="px-4 py-2 bg-[#42b6c5] text-white font-medium rounded-lg hover:bg-[#35919e] transition-colors"
          >
            Schedule Interview
          </button>
          <button
            @click="showRejectModal = true"
            class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors"
          >
            Reject
          </button>
        </template>
        <button
          @click="showDeleteModal = true"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          Delete
        </button>
        <button
          v-if="paymentSummary.can_send_reminder"
          @click="sendPaymentReminder"
          class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors"
        >
          Send Payment Reminder
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Applicant Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Applicant Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Full Name</label>
              <p class="mt-1 text-gray-900 dark:text-gray-100">{{ application.first_name }} {{ application.last_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Email Address</label>
              <p class="mt-1">
                <a :href="`mailto:${application.email}`" class="text-[#42b6c5] hover:underline">{{ application.email }}</a>
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Phone Number</label>
              <p class="mt-1 text-gray-900 dark:text-gray-100">{{ application.phone || 'Not provided' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Location</label>
              <p class="mt-1 text-gray-900 dark:text-gray-100">{{ [application.city, application.country].filter(Boolean).join(', ') || 'Not provided' }}</p>
            </div>
          </div>
        </div>

        <!-- Background -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Background</h3>
          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Education Level</label>
              <p class="mt-1 text-gray-900 dark:text-gray-100">{{ application.education_level || 'Not provided' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Relevant Experience</label>
              <p class="mt-1 text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ application.experience || 'Not provided' }}</p>
            </div>
          </div>
        </div>

        <!-- Motivation -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Motivation</h3>
          <p class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ application.motivation || 'Not provided' }}</p>
        </div>

        <!-- Internship Letter -->
        <div v-if="application.internship_letter_path" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Internship Letter</h3>
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-gray-100">Internship Letter Uploaded</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ application.internship_letter_path.split('/').pop() }}</p>
            </div>
            <a
              :href="`/storage/${application.internship_letter_path}`"
              target="_blank"
              class="inline-flex items-center px-4 py-2 bg-[#42b6c5] text-white font-medium rounded-lg hover:bg-[#35919e] transition-colors text-sm"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              View / Download
            </a>
          </div>
        </div>

        <!-- Review Notes (if rejected) -->
        <div v-if="application.notes" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-red-500">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Review Notes</h3>
          <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ application.notes }}</p>
        </div>

        <!-- Payment Tracking -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Payment Tracking</h3>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/40">
              <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Program Fee</p>
              <p class="font-semibold text-gray-900 dark:text-gray-100">{{ formatPrice(paymentSummary.program_price) }}</p>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/40">
              <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid</p>
              <p class="font-semibold text-green-700 dark:text-green-400">{{ formatPrice(paymentSummary.paid_amount) }}</p>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/40">
              <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Remaining</p>
              <p class="font-semibold text-amber-700 dark:text-amber-400">{{ formatPrice(paymentSummary.remaining_amount) }}</p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3 mb-4">
            <span :class="['px-3 py-1.5 text-xs font-semibold rounded-full border', getPaymentStatusColor(paymentSummary.status)]">
              {{ paymentStatusLabel }}
            </span>
            <span class="text-sm text-gray-600 dark:text-gray-400">
              Installments paid: {{ paymentSummary.completed_installments }}/{{ paymentSummary.max_installments }}
            </span>
          </div>

          <div v-if="successfulPayments.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Installment</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Receipt</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <tr v-for="payment in successfulPayments" :key="payment.id">
                  <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ payment.paid_at ? formatDate(payment.paid_at) : 'N/A' }}</td>
                  <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatPrice(payment.amount) }}</td>
                  <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                    {{ payment.installment_number || 1 }}/{{ payment.total_installments || paymentSummary.max_installments }}
                  </td>
                  <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ payment.receipt_number || 'N/A' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">No successful payments recorded yet.</p>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Program Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Applied Program</h3>
          <div class="space-y-4">
            <div>
              <Link :href="`/admin/programs/${application.program?.id}/edit`" class="text-lg font-medium text-[#42b6c5] hover:underline">
                {{ application.program?.title || 'Unknown Program' }}
              </Link>
              <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-full">
                {{ application.program?.category }}
              </span>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Price</label>
              <p class="mt-1 text-gray-900 dark:text-gray-100 font-semibold">{{ formatPrice(application.program?.price || 0) }}</p>
            </div>
            <div v-if="application.program?.start_date">
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Program Dates</label>
              <p class="mt-1 text-gray-900 dark:text-gray-100">
                {{ new Date(application.program.start_date).toLocaleDateString() }}
                <span v-if="application.program.end_date"> - {{ new Date(application.program.end_date).toLocaleDateString() }}</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Review Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Review Status</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Current Status</label>
              <p class="mt-1">
                <span :class="['px-2 py-1 text-sm font-medium rounded', getStatusColor(application.status)]">
                  {{ application.status.charAt(0).toUpperCase() + application.status.slice(1) }}
                </span>
              </p>
            </div>
            <div v-if="application.reviewed_at">
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Reviewed On</label>
              <p class="mt-1 text-gray-900 dark:text-gray-100">{{ formatDate(application.reviewed_at) }}</p>
            </div>
            <div v-if="application.reviewer">
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Reviewed By</label>
              <p class="mt-1 text-gray-900 dark:text-gray-100">{{ application.reviewer.name }}</p>
            </div>
          </div>
        </div>

        <!-- Linked User -->
        <div v-if="application.user" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Linked Account</h3>
          <div class="space-y-2">
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ application.user.name }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ application.user.email }}</p>
            <Link :href="`/admin/users/${application.user.id}`" class="text-[#42b6c5] hover:underline text-sm inline-block mt-2">
              View User Profile →
            </Link>
          </div>
        </div>

        <!-- Interview Status -->
        <div v-if="application.interview" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-[#42b6c5]">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Scheduled Interview</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Interview</label>
              <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ application.interview.title }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
              <p class="mt-1">
                <span :class="['px-2 py-1 text-xs font-semibold rounded-full', getInterviewStatusColor(application.interview_status)]">
                  {{ application.interview_status ? application.interview_status.charAt(0).toUpperCase() + application.interview_status.slice(1) : 'Unknown' }}
                </span>
              </p>
            </div>
            <div v-if="application.interview_scheduled_at">
              <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled On</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(application.interview_scheduled_at) }}</p>
            </div>

            <!-- Interview Result -->
            <template v-if="interviewResponse && interviewResponse.status === 'completed'">
              <div class="mt-2 p-4 rounded-lg" :class="interviewResponse.passed ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-semibold" :class="interviewResponse.passed ? 'text-green-800 dark:text-green-400' : 'text-red-800 dark:text-red-400'">
                    {{ interviewResponse.passed ? '✅ Passed' : '❌ Not Passed' }}
                  </span>
                  <span class="text-lg font-bold" :class="interviewResponse.passed ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                    {{ interviewResponse.percentage }}%
                  </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all duration-500"
                    :class="interviewResponse.passed ? 'bg-green-500' : 'bg-red-500'"
                    :style="{ width: `${Math.min(interviewResponse.percentage, 100)}%` }"
                  ></div>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                  Score: {{ interviewResponse.score }}/{{ interviewResponse.total_points }} • Passing: {{ application.interview.passing_score }}%
                </p>
              </div>
              <Link
                :href="`/admin/interviews/${application.interview.id}/responses/${interviewResponse.id}`"
                class="inline-flex items-center text-sm text-[#42b6c5] hover:text-[#35919e] font-medium"
              >
                View Full Response →
              </Link>
            </template>

            <!-- Resend / Change Interview -->
            <div v-if="application.status === 'pending' && availableInterviews.length > 0" class="pt-2 border-t dark:border-gray-700">
              <button
                @click="openInterviewModal"
                class="text-sm text-[#42b6c5] hover:text-[#35919e] font-medium"
              >
                {{ application.interview_status === 'completed' ? 'Schedule Different Interview' : 'Change or Resend Interview' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Accept Confirmation Modal -->
    <ConfirmationModal
      :open="showAcceptModal"
      title="Accept Application"
      :description="`Are you sure you want to accept the application from ${application.first_name} ${application.last_name}? They will be notified via email.`"
      confirm-text="Accept Application"
      variant="default"
      @update:open="showAcceptModal = $event"
      @confirm="acceptApplication"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      :open="showDeleteModal"
      title="Delete Application"
      :description="`Are you sure you want to delete the application from ${application.first_name} ${application.last_name}? This action cannot be undone.`"
      confirm-text="Delete"
      variant="destructive"
      @update:open="showDeleteModal = $event"
      @confirm="deleteApplication"
    />

    <!-- Reject Modal -->
    <Dialog :open="showRejectModal" @update:open="showRejectModal = $event">
      <DialogContent>
        <DialogHeader class="space-y-3">
          <DialogTitle>Reject Application</DialogTitle>
          <DialogDescription>
            Are you sure you want to reject the application from
            {{ application.first_name }} {{ application.last_name }}?
          </DialogDescription>
        </DialogHeader>
        <div class="grid gap-2">
          <label class="text-sm font-medium">Notes (optional)</label>
          <textarea
            v-model="rejectNotes"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            placeholder="Internal notes about rejection reason..."
          ></textarea>
        </div>
        <DialogFooter class="gap-2">
          <DialogClose as-child>
            <Button variant="secondary">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" @click="rejectApplication">
            Reject Application
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Schedule Interview Modal -->
    <Dialog :open="showInterviewModal" @update:open="showInterviewModal = $event">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader class="space-y-3">
          <DialogTitle>Schedule Interview</DialogTitle>
          <DialogDescription>
            Select an interview to send to {{ application.first_name }} {{ application.last_name }}.
            They will receive an email invitation with a link to begin.
          </DialogDescription>
        </DialogHeader>

        <div v-if="availableInterviews.length === 0" class="text-center py-6">
          <p class="text-gray-500 dark:text-gray-400">No active interviews available for this program.</p>
          <Link href="/admin/interviews/create" class="text-[#42b6c5] hover:underline text-sm mt-2 inline-block">
            Create an Interview →
          </Link>
        </div>

        <div v-else class="space-y-3 max-h-72 overflow-y-auto">
          <label
            v-for="interview in availableInterviews"
            :key="interview.id"
            :class="[
              'block p-4 border-2 rounded-xl cursor-pointer transition-all',
              selectedInterviewId === interview.id
                ? 'border-[#42b6c5] bg-cyan-50 dark:bg-cyan-900/20 shadow-sm'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
            ]"
          >
            <div class="flex items-start gap-3">
              <input
                type="radio"
                :value="interview.id"
                v-model="selectedInterviewId"
                class="mt-1 text-[#42b6c5] focus:ring-[#42b6c5]"
              />
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ interview.title }}</p>
                <p v-if="interview.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ interview.description }}</p>
                <div class="flex flex-wrap gap-3 mt-2">
                  <span class="inline-flex items-center text-xs text-gray-600 dark:text-gray-400">
                    📝 {{ interview.questions_count }} question{{ interview.questions_count !== 1 ? 's' : '' }}
                  </span>
                  <span v-if="interview.time_limit_minutes" class="inline-flex items-center text-xs text-gray-600 dark:text-gray-400">
                    ⏱️ {{ interview.time_limit_minutes }} min
                  </span>
                  <span class="inline-flex items-center text-xs text-gray-600 dark:text-gray-400">
                    🎯 Pass: {{ interview.passing_score }}%
                  </span>
                </div>
              </div>
            </div>
          </label>
        </div>

        <div v-if="application.interview_id" class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
          <p class="text-sm text-amber-800 dark:text-amber-300">
            ⚠️ This applicant already has an interview scheduled. Proceeding will replace it and send a new invitation email.
          </p>
        </div>

        <DialogFooter class="gap-2">
          <DialogClose as-child>
            <Button variant="secondary">Cancel</Button>
          </DialogClose>
          <Button
            :disabled="!selectedInterviewId || schedulingInterview"
            @click="scheduleInterview"
          >
            <span v-if="schedulingInterview">Sending...</span>
            <span v-else>Send Interview Invitation</span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
