<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import { debounce } from 'lodash-es'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'

interface Application {
  id: number
  first_name: string
  last_name: string
  email: string
  phone: string
  country: string
  status: 'pending' | 'accepted' | 'rejected'
  created_at: string
  reviewed_at: string | null
  program: {
    id: number
    title: string
    category: string
  }
  user: {
    id: number
    name: string
    email: string
  } | null
}

interface Program {
  id: number
  title: string
}

interface Props {
  applications: {
    data: Application[]
    links: any[]
  }
  filters: {
    search?: string
    status?: string
    program_id?: string
  }
  stats: {
    total: number
    pending: number
    accepted: number
    rejected: number
  }
  programs: Program[]
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')
const programId = ref(props.filters.program_id || '')
const selectedIds = ref<number[]>([])
const showRejectModal = ref(false)
const rejectingApp = ref<Application | null>(null)
const rejectNotes = ref('')

const applyFilters = debounce(() => {
  router.get('/admin/applications', {
    search: search.value || undefined,
    status: status.value || undefined,
    program_id: programId.value || undefined,
  }, { preserveState: true, replace: true })
}, 300)

watch([search, status, programId], applyFilters)

const allSelected = computed(() => {
  return props.applications.data.length > 0 && 
    selectedIds.value.length === props.applications.data.length
})

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = props.applications.data.map(a => a.id)
  }
}

const acceptApplication = (app: Application) => {
  router.post(`/admin/applications/${app.id}/accept`, {}, {
    preserveState: true,
    onSuccess: () => {
      toast.success(`Application from ${app.first_name} ${app.last_name} accepted!`)
    },
    onError: () => {
      toast.error('Failed to accept application.')
    },
  })
}

const openRejectModal = (app: Application) => {
  rejectingApp.value = app
  rejectNotes.value = ''
  showRejectModal.value = true
}

const rejectApplication = () => {
  if (!rejectingApp.value) return
  const appName = `${rejectingApp.value.first_name} ${rejectingApp.value.last_name}`
  router.post(`/admin/applications/${rejectingApp.value.id}/reject`, {
    notes: rejectNotes.value,
  }, {
    preserveState: true,
    onSuccess: () => {
      showRejectModal.value = false
      rejectingApp.value = null
      toast.success(`Application from ${appName} rejected.`)
    },
    onError: () => {
      toast.error('Failed to reject application.')
    },
  })
}

const showDeleteModal = ref(false)
const deletingApp = ref<Application | null>(null)

const openDeleteModal = (app: Application) => {
  deletingApp.value = app
  showDeleteModal.value = true
}

const confirmDelete = () => {
  if (!deletingApp.value) return
  router.delete(`/admin/applications/${deletingApp.value.id}`, {
    onSuccess: () => {
      toast.success('Application deleted successfully!')
      showDeleteModal.value = false
      deletingApp.value = null
    },
    onError: () => {
      toast.error('Failed to delete application.')
    },
  })
}

const showBulkDeleteModal = ref(false)

const bulkAction = (action: string) => {
  if (selectedIds.value.length === 0) {
    toast.warning('Please select at least one application')
    return
  }
  if (action === 'delete') {
    showBulkDeleteModal.value = true
    return
  }
  router.post('/admin/applications/bulk', {
    ids: selectedIds.value,
    action,
  }, {
    preserveState: true,
    onSuccess: () => {
      const count = selectedIds.value.length
      selectedIds.value = []
      toast.success(`Bulk ${action} completed for ${count} application(s)!`)
    },
    onError: () => {
      toast.error(`Failed to perform bulk ${action}.`)
    },
  })
}

const confirmBulkDelete = () => {
  router.post('/admin/applications/bulk', {
    ids: selectedIds.value,
    action: 'delete',
  }, {
    preserveState: true,
    onSuccess: () => {
      const count = selectedIds.value.length
      selectedIds.value = []
      showBulkDeleteModal.value = false
      toast.success(`Deleted ${count} application(s)!`)
    },
    onError: () => {
      toast.error('Failed to delete selected applications.')
    },
  })
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'accepted': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    case 'rejected': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    default: return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  }
}
</script>

<template>
  <div>
    <Head title="Application Reviews" />

    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Application Reviews</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Review and manage all program applications</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-blue-500">
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Total Applications</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-yellow-500">
        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Pending Review</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-green-500">
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.accepted }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Accepted</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-red-500">
        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.rejected }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Rejected</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
          <input
            v-model="search"
            type="text"
            placeholder="Search by name or email..."
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
          <select
            v-model="status"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
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
        <div class="flex items-end">
          <div v-if="selectedIds.length > 0" class="flex gap-2">
            <button
              @click="bulkAction('accept')"
              class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors text-sm"
            >
              Accept ({{ selectedIds.length }})
            </button>
            <button
              @click="bulkAction('reject')"
              class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors text-sm"
            >
              Reject
            </button>
            <button
              @click="bulkAction('delete')"
              class="px-4 py-2 bg-gray-800 text-white font-medium rounded-lg hover:bg-black transition-colors text-sm"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Applications Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="toggleSelectAll"
                  class="rounded border-gray-300 dark:border-gray-600 text-[#42b6c5] focus:ring-[#42b6c5]"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Applicant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Program</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="app in applications.data" :key="app.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4">
                <input
                  type="checkbox"
                  :value="app.id"
                  v-model="selectedIds"
                  class="rounded border-gray-300 dark:border-gray-600 text-[#42b6c5] focus:ring-[#42b6c5]"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ app.first_name }} {{ app.last_name }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ app.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-gray-100">{{ app.program?.title || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(app.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(app.status)]">
                  {{ app.status.charAt(0).toUpperCase() + app.status.slice(1) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <Link
                    :href="`/admin/applications/${app.id}`"
                    class="text-[#42b6c5] hover:text-[#35919e]"
                  >
                    View
                  </Link>
                  <template v-if="app.status === 'pending'">
                    <button
                      @click="acceptApplication(app)"
                      class="text-green-600 hover:text-green-900"
                    >
                      Accept
                    </button>
                    <button
                      @click="openRejectModal(app)"
                      class="text-red-600 hover:text-red-900"
                    >
                      Reject
                    </button>
                  </template>
                  <button
                    @click="openDeleteModal(app)"
                    class="text-gray-400 hover:text-red-600"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="applications.data.length === 0">
              <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                No applications found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="applications.links && applications.links.length > 3" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
        <div class="flex items-center justify-center">
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <Link
              v-for="(link, index) in applications.links"
              :key="index"
              :href="link.url || '#'"
              :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                link.active ? 'z-10 bg-[#42b6c5] border-[#42b6c5] text-white' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                !link.url ? 'cursor-not-allowed opacity-50' : '',
                index === 0 ? 'rounded-l-md' : '',
                index === applications.links.length - 1 ? 'rounded-r-md' : ''
              ]"
              v-html="link.label"
            />
          </nav>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <Teleport to="body">
      <div v-if="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity" @click="showRejectModal = false"></div>
          <div class="relative inline-block align-middle bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-md sm:w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Reject Application</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Are you sure you want to reject the application from 
            <strong class="text-gray-900 dark:text-gray-100">{{ rejectingApp?.first_name }} {{ rejectingApp?.last_name }}</strong>?
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
    </Teleport>

    <!-- Delete Modal -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity" @click="showDeleteModal = false"></div>
          <div class="relative inline-block align-middle bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-md sm:w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Delete Application</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Are you sure you want to delete the application from
            <strong class="text-gray-900 dark:text-gray-100">{{ deletingApp?.first_name }} {{ deletingApp?.last_name }}</strong>? This action cannot be undone.
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="confirmDelete"
              class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors"
            >
              Delete
            </button>
          </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Bulk Delete Modal -->
    <Teleport to="body">
      <div v-if="showBulkDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <div class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity" @click="showBulkDeleteModal = false"></div>
          <div class="relative inline-block align-middle bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-md sm:w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Delete Selected Applications</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Are you sure you want to delete <strong class="text-gray-900 dark:text-gray-100">{{ selectedIds.length }}</strong> selected application(s)? This action cannot be undone.
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="showBulkDeleteModal = false"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="confirmBulkDelete"
              class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors"
            >
              Delete Selected
            </button>
          </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
