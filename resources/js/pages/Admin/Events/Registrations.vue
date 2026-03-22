<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { ref, watch, computed } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface User {
  id: number
  name: string
  email: string
}

interface Registration {
  id: number
  user_id: number | null
  event_id: number
  first_name: string
  last_name: string
  email: string
  phone: string
  country: string | null
  status: string
  attended_at: string | null
  created_at: string
  user: User | null
}

interface Event {
  id: number
  title: string
  slug: string
  event_date: string
  location: string
  is_online: boolean
  capacity: number
  registrations_count: number
}

interface Props {
  event: Event
  registrations: {
    data: Registration[]
    links: any[]
  }
  filters: {
    search?: string
    status?: string
  }
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')

const showReminderModal = ref(false)
const showStatusModal = ref(false)
const showBulkStatusModal = ref(false)
const showBulkDeleteModal = ref(false)
const selectedRegistration = ref<Registration | null>(null)
const selectedStatus = ref('')
const bulkStatus = ref('')

// Selection state
const selectedIds = ref<number[]>([])

const allSelected = computed(() => {
  return props.registrations.data.length > 0 && selectedIds.value.length === props.registrations.data.length
})

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = props.registrations.data.map(r => r.id)
  }
}

const toggleSelect = (id: number) => {
  const index = selectedIds.value.indexOf(id)
  if (index > -1) {
    selectedIds.value.splice(index, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const applyFilters = debounce(() => {
  router.get(`/admin/events/${props.event.id}/registrations`, {
    search: search.value || undefined,
    status: status.value || undefined,
  }, { preserveState: true, replace: true })
}, 300)

watch([search, status], applyFilters)

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const isUpcoming = (date: string) => new Date(date) > new Date()

const statusColors: Record<string, string> = {
  registered: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
  confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  attended: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
}

const getStatusColor = (s: string) => statusColors[s] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'

const openStatusModal = (registration: Registration, newStatus: string) => {
  selectedRegistration.value = registration
  selectedStatus.value = newStatus
  showStatusModal.value = true
}

const confirmStatusChange = () => {
  if (!selectedRegistration.value) return

  router.patch(`/admin/events/${props.event.id}/registrations/${selectedRegistration.value.id}`, {
    status: selectedStatus.value,
  }, {
    preserveState: true,
    onSuccess: () => {
      toast.success('Registration status updated!')
      showStatusModal.value = false
      selectedRegistration.value = null
    },
    onError: () => {
      toast.error('Failed to update status.')
    },
  })
}

const sendReminder = () => {
  router.post(`/admin/events/${props.event.id}/send-reminder`, {}, {
    onSuccess: () => {
      toast.success('Reminder emails sent successfully!')
      showReminderModal.value = false
    },
    onError: () => {
      toast.error('Failed to send reminders.')
    },
  })
}

const openBulkStatusModal = (newStatus: string) => {
  if (selectedIds.value.length === 0) {
    toast.error('Please select at least one registration.')
    return
  }
  bulkStatus.value = newStatus
  showBulkStatusModal.value = true
}

const confirmBulkStatusChange = () => {
  router.post(`/admin/events/${props.event.id}/registrations/bulk-update`, {
    ids: selectedIds.value,
    status: bulkStatus.value,
  }, {
    preserveState: true,
    onSuccess: () => {
      toast.success(`${selectedIds.value.length} registration(s) updated!`)
      selectedIds.value = []
      showBulkStatusModal.value = false
    },
    onError: () => {
      toast.error('Failed to update registrations.')
    },
  })
}

const openBulkDeleteModal = () => {
  if (selectedIds.value.length === 0) {
    toast.error('Please select at least one registration.')
    return
  }
  showBulkDeleteModal.value = true
}

const confirmBulkDelete = () => {
  router.post(`/admin/events/${props.event.id}/registrations/bulk-destroy`, {
    ids: selectedIds.value,
  }, {
    preserveState: true,
    onSuccess: () => {
      toast.success(`${selectedIds.value.length} registration(s) deleted!`)
      selectedIds.value = []
      showBulkDeleteModal.value = false
    },
    onError: () => {
      toast.error('Failed to delete registrations.')
    },
  })
}

const exportCsv = () => {
  const rows = [['First Name', 'Last Name', 'Email', 'Phone', 'Country', 'Status', 'Registered At']]
  props.registrations.data.forEach(r => {
    rows.push([r.first_name, r.last_name, r.email, r.phone, r.country || '', r.status, r.created_at])
  })
  const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${props.event.title.replace(/\s+/g, '_')}_registrations.csv`
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div>
    <Head :title="`Registrations - ${event.title}`" />

    <!-- Header -->
    <div class="mb-8">
      <Link href="/admin/events" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Events
      </Link>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ event.title }} — Registrations</h2>
          <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
            <span>📅 {{ formatDate(event.event_date) }}</span>
            <span>{{ event.is_online ? '🌐 Online' : `📍 ${event.location}` }}</span>
            <span>👥 {{ event.registrations_count }} / {{ event.capacity }} registered</span>
            <span :class="isUpcoming(event.event_date) ? 'text-green-600 dark:text-green-400' : 'text-gray-500'">
              {{ isUpcoming(event.event_date) ? '✅ Upcoming' : '⏰ Past' }}
            </span>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <!-- Bulk Actions -->
          <template v-if="selectedIds.length > 0">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ selectedIds.length }} selected:</span>
            <button
              @click="openBulkStatusModal('confirmed')"
              class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 font-semibold rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors text-sm"
            >
              Confirm
            </button>
            <button
              @click="openBulkStatusModal('attended')"
              class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 font-semibold rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors text-sm"
            >
              Mark Attended
            </button>
            <button
              @click="openBulkStatusModal('cancelled')"
              class="inline-flex items-center px-3 py-1.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 font-semibold rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors text-sm"
            >
              Cancel
            </button>
            <button
              @click="openBulkDeleteModal"
              class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors text-sm"
            >
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Delete ({{ selectedIds.length }})
            </button>
          </template>
          <!-- Default Actions -->
          <template v-else>
            <button
              @click="exportCsv"
              class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
          </button>
          <button
            v-if="isUpcoming(event.event_date) && registrations.data.length > 0"
            @click="showReminderModal = true"
            class="inline-flex items-center px-4 py-2 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            Send Reminder
          </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ event.registrations_count }}</p>
        <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
        <p class="text-2xl font-bold text-yellow-600">{{ registrations.data.filter(r => r.status === 'registered').length }}</p>
        <p class="text-sm text-gray-600 dark:text-gray-400">Registered</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
        <p class="text-2xl font-bold text-green-600">{{ registrations.data.filter(r => r.status === 'confirmed').length }}</p>
        <p class="text-sm text-gray-600 dark:text-gray-400">Confirmed</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ registrations.data.filter(r => r.status === 'attended').length }}</p>
        <p class="text-sm text-gray-600 dark:text-gray-400">Attended</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
        <p class="text-2xl font-bold text-red-600">{{ registrations.data.filter(r => r.status === 'cancelled').length }}</p>
        <p class="text-sm text-gray-600 dark:text-gray-400">Cancelled</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
          <input
            v-model="search"
            type="text"
            placeholder="Search by name, email, phone..."
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
          <select
            v-model="status"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100"
          >
            <option value="">All Statuses</option>
            <option value="registered">Registered</option>
            <option value="confirmed">Confirmed</option>
            <option value="attended">Attended</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Registrations Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div v-if="registrations.data.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="toggleSelectAll"
                  class="h-4 w-4 text-[#42b6c5] focus:ring-[#42b6c5] border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Registrant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Country</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Registered</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="registration in registrations.data" :key="registration.id" :class="['hover:bg-gray-50 dark:hover:bg-gray-700', selectedIds.includes(registration.id) ? 'bg-cyan-50 dark:bg-cyan-900/20' : '']">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(registration.id)"
                  @change="toggleSelect(registration.id)"
                  class="h-4 w-4 text-[#42b6c5] focus:ring-[#42b6c5] border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ registration.first_name }} {{ registration.last_name }}
                  </div>
                  <div v-if="registration.user" class="text-xs text-[#42b6c5]">
                    Registered User
                  </div>
                  <div v-else class="text-xs text-gray-500 dark:text-gray-400">
                    Guest
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-gray-100">{{ registration.email }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ registration.phone }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ registration.country || '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['px-2 py-1 text-xs font-medium rounded-full capitalize', getStatusColor(registration.status)]">
                  {{ registration.status }}
                </span>
                <div v-if="registration.attended_at" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Attended {{ formatDate(registration.attended_at) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ formatDate(registration.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="registration.status !== 'attended'"
                    @click="openStatusModal(registration, 'attended')"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                    title="Mark as Attended"
                  >
                    ✓ Attended
                  </button>
                  <button
                    v-if="registration.status !== 'confirmed'"
                    @click="openStatusModal(registration, 'confirmed')"
                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                    title="Confirm"
                  >
                    Confirm
                  </button>
                  <button
                    v-if="registration.status !== 'cancelled'"
                    @click="openStatusModal(registration, 'cancelled')"
                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                    title="Cancel"
                  >
                    Cancel
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="px-6 py-16 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10h.01M11 20h4M11 10h.01M7 20h4M7 10h.01M3 20h4m0-2a3 3 0 00-5.856-1.487M3 10h.01" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No registrations yet</h3>
        <p class="text-gray-500 dark:text-gray-400">No one has registered for this event yet.</p>
      </div>

      <!-- Pagination -->
      <div v-if="registrations.links && registrations.links.length > 3" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
        <div class="flex items-center justify-center">
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <Link
              v-for="(link, index) in registrations.links"
              :key="index"
              :href="link.url || '#'"
              :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                link.active ? 'z-10 bg-[#42b6c5] border-[#42b6c5] text-white' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                !link.url ? 'cursor-not-allowed opacity-50' : '',
                index === 0 ? 'rounded-l-md' : '',
                index === registrations.links.length - 1 ? 'rounded-r-md' : ''
              ]"
              v-html="link.label"
            />
          </nav>
        </div>
      </div>
    </div>

    <!-- Send Reminder Modal -->
    <ConfirmationModal
      :open="showReminderModal"
      title="Send Event Reminder"
      :description="`Send a reminder email to all ${event.registrations_count} registrant(s) for &quot;${event.title}&quot;?`"
      confirm-text="Send Reminder"
      @update:open="showReminderModal = $event"
      @confirm="sendReminder"
    />

    <!-- Status Change Modal -->
    <ConfirmationModal
      :open="showStatusModal"
      title="Update Registration Status"
      :description="`Mark ${selectedRegistration?.first_name} ${selectedRegistration?.last_name}'s registration as &quot;${selectedStatus}&quot;?`"
      confirm-text="Update Status"
      @update:open="showStatusModal = $event"
      @confirm="confirmStatusChange"
    />

    <!-- Bulk Status Change Modal -->
    <ConfirmationModal
      :open="showBulkStatusModal"
      title="Bulk Update Status"
      :description="`Update ${selectedIds.length} registration(s) to &quot;${bulkStatus}&quot;?`"
      :confirm-text="`Update ${selectedIds.length} Registration(s)`"
      @update:open="showBulkStatusModal = $event"
      @confirm="confirmBulkStatusChange"
    />

    <!-- Bulk Delete Modal -->
    <ConfirmationModal
      :open="showBulkDeleteModal"
      title="Delete Registrations"
      :description="`Are you sure you want to delete ${selectedIds.length} registration(s)? This action cannot be undone.`"
      :confirm-text="`Delete ${selectedIds.length} Registration(s)`"
      variant="destructive"
      @update:open="showBulkDeleteModal = $event"
      @confirm="confirmBulkDelete"
    />
  </div>
</template>
