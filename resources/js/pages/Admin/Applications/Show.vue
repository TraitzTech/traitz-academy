<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'

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
  status: 'pending' | 'accepted' | 'rejected'
  notes: string | null
  created_at: string
  reviewed_at: string | null
  reviewed_by: number | null
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

const props = defineProps<{
  application: Application
}>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const showRejectModal = ref(false)
const rejectNotes = ref('')

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
  if (confirm(`Are you sure you want to delete this application?`)) {
    router.delete(`/admin/applications/${props.application.id}`, {
      onSuccess: () => {
        toast.success('Application deleted successfully!')
      },
      onError: () => {
        toast.error('Failed to delete application.')
      },
    })
  }
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
        <span :class="['px-4 py-2 text-sm font-medium rounded-full border', getStatusColor(application.status)]">
          {{ application.status.charAt(0).toUpperCase() + application.status.slice(1) }}
        </span>
        <template v-if="application.status === 'pending'">
          <button
            @click="acceptApplication"
            class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors"
          >
            Accept
          </button>
          <button
            @click="showRejectModal = true"
            class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors"
          >
            Reject
          </button>
        </template>
        <button
          @click="deleteApplication"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          Delete
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

        <!-- Review Notes (if rejected) -->
        <div v-if="application.notes" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-red-500">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-2 border-b dark:border-gray-700">Review Notes</h3>
          <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ application.notes }}</p>
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
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="showRejectModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Reject Application</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Are you sure you want to reject the application from 
            <strong>{{ application.first_name }} {{ application.last_name }}</strong>?
          </p>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (optional)</label>
            <textarea
              v-model="rejectNotes"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="Internal notes about rejection reason..."
            ></textarea>
          </div>
          <div class="flex justify-end gap-3">
            <button
              @click="showRejectModal = false"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="rejectApplication"
              class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors"
            >
              Reject Application
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
