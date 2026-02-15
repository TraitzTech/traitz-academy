<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { ref, watch, computed } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Program {
  id: number
  title: string
  slug: string
  category: string
  description: string
  duration: string
  price: number
  image_url: string | null
  is_featured: boolean
  is_active: boolean
  capacity: number
  enrolled_count: number
  applications_count: number
  start_date: string | null
  end_date: string | null
}

interface Props {
  programs: {
    data: Program[]
    links: any[]
  }
  filters: {
    search?: string
    category?: string
    status?: string
  }
  categories: Record<string, string>
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()
const search = ref(props.filters.search || '')
const category = ref(props.filters.category || '')
const status = ref(props.filters.status || '')

// Selection state
const selectedIds = ref<number[]>([])

const allSelected = computed(() => {
  return props.programs.data.length > 0 && selectedIds.value.length === props.programs.data.length
})

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = props.programs.data.map(p => p.id)
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

// Modal state
const showDeleteModal = ref(false)
const showBulkDeleteModal = ref(false)
const programToDelete = ref<Program | null>(null)

const applyFilters = debounce(() => {
  router.get('/admin/programs', {
    search: search.value || undefined,
    category: category.value || undefined,
    status: status.value || undefined,
  }, { preserveState: true, replace: true })
}, 300)

watch([search, category, status], applyFilters)

const toggleStatus = (program: Program) => {
  router.post(`/admin/programs/${program.id}/toggle-status`, {}, { 
    preserveState: true,
    onSuccess: () => {
      toast.success(`Program ${program.is_active ? 'deactivated' : 'activated'} successfully!`)
    },
    onError: () => {
      toast.error('Failed to update program status.')
    }
  })
}

const toggleFeatured = (program: Program) => {
  router.post(`/admin/programs/${program.id}/toggle-featured`, {}, { 
    preserveState: true,
    onSuccess: () => {
      toast.success(`Program ${program.is_featured ? 'removed from' : 'added to'} featured!`)
    },
    onError: () => {
      toast.error('Failed to update featured status.')
    }
  })
}

const deleteProgram = (program: Program) => {
  programToDelete.value = program
  showDeleteModal.value = true
}

const confirmDelete = () => {
  if (!programToDelete.value) return
  
  router.delete(`/admin/programs/${programToDelete.value.id}`, {
    onSuccess: () => {
      toast.success('Program deleted successfully!')
      showDeleteModal.value = false
      programToDelete.value = null
    },
    onError: () => {
      toast.error('Failed to delete program.')
    }
  })
}

const openBulkDeleteModal = () => {
  if (selectedIds.value.length === 0) {
    toast.error('Please select at least one program to delete.')
    return
  }
  showBulkDeleteModal.value = true
}

const confirmBulkDelete = () => {
  router.post('/admin/programs/bulk-destroy', { ids: selectedIds.value }, {
    onSuccess: () => {
      toast.success(`${selectedIds.value.length} program(s) deleted successfully!`)
      selectedIds.value = []
      showBulkDeleteModal.value = false
    },
    onError: () => {
      toast.error('Failed to delete programs.')
    }
  })
}

const formatPrice = (price: number) => {
  if (price === 0) return 'Free'
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(price)
}

const formatCategory = (cat: string) => props.categories[cat] || cat
</script>

<template>
  <div>
    <Head title="Manage Programs" />

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Manage Programs</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Create, edit, and manage all training programs</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          v-if="selectedIds.length > 0"
          @click="openBulkDeleteModal"
          class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Delete Selected ({{ selectedIds.length }})
        </button>
        <Link
          href="/admin/programs/create"
          class="inline-flex items-center px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Create Program
        </Link>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
          <input
            v-model="search"
            type="text"
            placeholder="Search programs..."
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
          <select
            v-model="category"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          >
            <option value="">All Categories</option>
            <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
          <select
            v-model="status"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Programs Table -->
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
                  class="h-4 w-4 text-[#42b6c5] focus:ring-[#42b6c5] border-gray-300 dark:border-gray-600 rounded"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Program</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Applications</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Featured</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="program in programs.data" :key="program.id" :class="['hover:bg-gray-50 dark:hover:bg-gray-700/50', selectedIds.includes(program.id) ? 'bg-cyan-50 dark:bg-cyan-900/20' : '']">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(program.id)"
                  @change="toggleSelect(program.id)"
                  class="h-4 w-4 text-[#42b6c5] focus:ring-[#42b6c5] border-gray-300 dark:border-gray-600 rounded"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-12 w-12">
                    <img
                      v-if="program.image_url"
                      :src="program.image_url.startsWith('http') ? program.image_url : `/storage/${program.image_url}`"
                      :alt="program.title"
                      class="h-12 w-12 rounded-lg object-cover"
                    />
                    <div v-else class="h-12 w-12 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                      <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ program.title }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ program.duration }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                  {{ formatCategory(program.category) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ formatPrice(program.price) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ program.applications_count }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button
                  @click="toggleStatus(program)"
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full transition-colors',
                    program.is_active ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50' : 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50'
                  ]"
                >
                  {{ program.is_active ? 'Active' : 'Inactive' }}
                </button>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button
                  @click="toggleFeatured(program)"
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full transition-colors',
                    program.is_featured ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:hover:bg-yellow-900/50' : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                  ]"
                >
                  {{ program.is_featured ? '★ Featured' : 'Regular' }}
                </button>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <Link
                    :href="`/admin/programs/${program.id}/edit`"
                    class="text-[#42b6c5] hover:text-[#35919e]"
                  >
                    Edit
                  </Link>
                  <button
                    @click="deleteProgram(program)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="programs.data.length === 0">
              <td colspan="8" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                No programs found. Create your first program to get started.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="programs.links && programs.links.length > 3" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
        <div class="flex items-center justify-center">
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <Link
              v-for="(link, index) in programs.links"
              :key="index"
              :href="link.url || '#'"
              :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                link.active ? 'z-10 bg-[#42b6c5] border-[#42b6c5] text-white' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                !link.url ? 'cursor-not-allowed opacity-50' : '',
                index === 0 ? 'rounded-l-md' : '',
                index === programs.links.length - 1 ? 'rounded-r-md' : ''
              ]"
              v-html="link.label"
            />
          </nav>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      :open="showDeleteModal"
      title="Delete Program"
      :description="`Are you sure you want to delete &quot;${programToDelete?.title}&quot;? This action cannot be undone.`"
      confirm-text="Delete"
      variant="destructive"
      @update:open="showDeleteModal = $event"
      @confirm="confirmDelete"
    />

    <!-- Bulk Delete Confirmation Modal -->
    <ConfirmationModal
      :open="showBulkDeleteModal"
      title="Delete Multiple Programs"
      :description="`Are you sure you want to delete ${selectedIds.length} program(s)? This action cannot be undone.`"
      :confirm-text="`Delete ${selectedIds.length} Program(s)`"
      variant="destructive"
      @update:open="showBulkDeleteModal = $event"
      @confirm="confirmBulkDelete"
    />
  </div>
</template>
