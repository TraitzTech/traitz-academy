<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { Edit2, PlusCircle, Trash2, X } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Category {
  id: number
  name: string
  slug: string
  description: string | null
  icon: string | null
  color: string | null
  is_active: boolean
  sort_order: number
  courses_count: number
}

interface Filters {
  search?: string
  status?: string
}

const props = defineProps<{
  categories: Category[]
  filters: Filters
}>()

defineOptions({ layout: AppLayout })

const toast   = useToast()
const search  = ref(props.filters.search ?? '')
const status  = ref(props.filters.status ?? '')

// ─── Filters ──────────────────────────────────────────────────────────────────

const applyFilters = debounce(() => {
  router.get('/admin/course-categories', {
    search: search.value || undefined,
    status: status.value || undefined,
  }, { preserveState: true, replace: true })
}, 300)

watch([search, status], applyFilters)

// ─── Modal state ──────────────────────────────────────────────────────────────

const showModal    = ref(false)
const editingId    = ref<number | null>(null)
const showDeleteModal  = ref(false)
const categoryToDelete = ref<Category | null>(null)

const form = useForm({
  name:        '',
  description: '',
  icon:        '',
  color:       '#381998',
  sort_order:  0,
  is_active:   true,
})

const PRESET_COLORS = [
  '#381998', '#42b6c5', '#000928', '#7c3aed', '#059669',
  '#dc2626', '#d97706', '#2563eb', '#db2777', '#0891b2',
]

function openCreate() {
  editingId.value = null
  form.reset()
  form.color     = '#381998'
  form.is_active = true
  showModal.value = true
}

function openEdit(cat: Category) {
  editingId.value      = cat.id
  form.name        = cat.name
  form.description = cat.description ?? ''
  form.icon        = cat.icon ?? ''
  form.color       = cat.color ?? '#381998'
  form.sort_order  = cat.sort_order
  form.is_active   = cat.is_active
  showModal.value  = true
}

function closeModal() {
  showModal.value = false
  editingId.value = null
  form.reset()
  form.clearErrors()
}

function submitForm() {
  if (editingId.value) {
    form.put(`/admin/course-categories/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => { closeModal(); toast.success('Category updated.') },
    })
  } else {
    form.post('/admin/course-categories', {
      preserveScroll: true,
      onSuccess: () => { closeModal(); toast.success('Category created.') },
    })
  }
}

// ─── Toggle active ────────────────────────────────────────────────────────────

function toggleActive(cat: Category) {
  router.post(`/admin/course-categories/${cat.id}/toggle-active`, {}, {
    preserveScroll: true,
    onSuccess: () => toast.success(`Category ${cat.is_active ? 'deactivated' : 'activated'}.`),
    onError:   () => toast.error('Failed to update status.'),
  })
}

// ─── Delete ───────────────────────────────────────────────────────────────────

function confirmDelete(cat: Category) {
  categoryToDelete.value = cat
  showDeleteModal.value  = true
}

function doDelete() {
  if (!categoryToDelete.value) return
  router.delete(`/admin/course-categories/${categoryToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Category deleted.')
      showDeleteModal.value  = false
      categoryToDelete.value = null
    },
    onError: () => toast.error('Could not delete this category.'),
  })
}
</script>

<template>
  <div>
    <Head title="Course Categories" />

    <!-- Header -->
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Course Categories</h2>
        <p class="mt-1 text-gray-600 dark:text-gray-400">Organise courses into categories for tutors to choose from</p>
      </div>
      <button
        @click="openCreate"
        class="inline-flex items-center gap-2 rounded-lg bg-[#42b6c5] px-5 py-2.5 font-semibold text-white transition-colors hover:bg-[#35919e]"
      >
        <PlusCircle class="h-5 w-5" />
        New Category
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 rounded-lg bg-white p-5 shadow dark:bg-gray-800">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
          <input
            v-model="search"
            type="text"
            placeholder="Search categories…"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
          <select
            v-model="status"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
          >
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Category</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Description</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Courses</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Order</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">

              <!-- Category name + icon + colour swatch -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg shadow-sm"
                    :style="{ backgroundColor: cat.color ?? '#381998', opacity: cat.is_active ? 1 : 0.5 }"
                  >
                    <span v-if="cat.icon">{{ cat.icon }}</span>
                    <span v-else class="text-xs font-bold text-white">{{ cat.name.charAt(0).toUpperCase() }}</span>
                  </div>
                  <div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ cat.name }}</p>
                    <p class="text-xs text-gray-400">{{ cat.slug }}</p>
                  </div>
                </div>
              </td>

              <td class="px-6 py-4 max-w-xs">
                <p class="truncate text-sm text-gray-600 dark:text-gray-300">{{ cat.description || '—' }}</p>
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center rounded-full bg-[#42b6c5]/10 px-2.5 py-0.5 text-xs font-semibold text-[#42b6c5]">
                  {{ cat.courses_count }}
                </span>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                {{ cat.sort_order }}
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <button
                  @click="toggleActive(cat)"
                  :class="[
                    'rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors',
                    cat.is_active
                      ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400',
                  ]"
                >
                  {{ cat.is_active ? 'Active' : 'Inactive' }}
                </button>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div class="flex items-center justify-end gap-3">
                  <button @click="openEdit(cat)" class="text-[#42b6c5] hover:text-[#35919e] transition-colors">
                    <Edit2 class="h-4 w-4" />
                  </button>
                  <button
                    @click="confirmDelete(cat)"
                    :disabled="cat.courses_count > 0"
                    class="text-red-500 transition-colors hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-30"
                    :title="cat.courses_count > 0 ? 'Cannot delete — has courses assigned' : 'Delete'"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="categories.length === 0">
              <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                No categories found. Create your first one.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ═══════════════ CREATE / EDIT MODAL ═══════════════ -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeModal"
      >
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
          <!-- Modal header -->
          <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
              {{ editingId ? 'Edit Category' : 'New Category' }}
            </h3>
            <button @click="closeModal" class="rounded-lg p-1 text-gray-400 hover:text-gray-600 transition-colors">
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Modal form -->
          <form @submit.prevent="submitForm" class="space-y-4 px-6 py-5">

            <!-- Name -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                Name <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="e.g. Data Science"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
                :class="{ 'border-red-400': form.errors.name }"
              />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
            </div>

            <!-- Description -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Description</label>
              <textarea
                v-model="form.description"
                rows="2"
                maxlength="500"
                placeholder="Short description (optional)"
                class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white"
              />
            </div>

            <!-- Icon + Colour row -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                  Icon <span class="text-xs font-normal text-gray-400">(emoji)</span>
                </label>
                <input
                  v-model="form.icon"
                  type="text"
                  placeholder="🎓"
                  maxlength="4"
                  class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-center text-xl focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Colour</label>
                <div class="flex items-center gap-2">
                  <input
                    v-model="form.color"
                    type="color"
                    class="h-10 w-10 shrink-0 cursor-pointer rounded-lg border border-gray-200 p-0.5"
                  />
                  <input
                    v-model="form.color"
                    type="text"
                    placeholder="#381998"
                    class="flex-1 rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-mono focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white"
                    :class="{ 'border-red-400': form.errors.color }"
                  />
                </div>
                <!-- Preset swatches -->
                <div class="mt-2 flex flex-wrap gap-1.5">
                  <button
                    v-for="c in PRESET_COLORS"
                    :key="c"
                    type="button"
                    @click="form.color = c"
                    :style="{ backgroundColor: c }"
                    :class="[
                      'h-5 w-5 rounded-full border-2 transition-transform hover:scale-110',
                      form.color === c ? 'border-gray-800 scale-110' : 'border-transparent',
                    ]"
                  />
                </div>
                <p v-if="form.errors.color" class="mt-1 text-xs text-red-500">{{ form.errors.color }}</p>
              </div>
            </div>

            <!-- Sort order + Active toggle -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Sort Order</label>
                <input
                  v-model="form.sort_order"
                  type="number"
                  min="0"
                  class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white"
                />
              </div>
              <div class="flex flex-col justify-center">
                <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Status</label>
                <label class="flex cursor-pointer items-center gap-3">
                  <div
                    @click="form.is_active = !form.is_active"
                    :class="[
                      'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                      form.is_active ? 'bg-[#42b6c5]' : 'bg-gray-300 dark:bg-gray-600',
                    ]"
                  >
                    <span
                      :class="[
                        'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                        form.is_active ? 'translate-x-6' : 'translate-x-1',
                      ]"
                    />
                  </div>
                  <span class="text-sm text-gray-700 dark:text-gray-300">
                    {{ form.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </label>
              </div>
            </div>

            <!-- Preview -->
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 dark:bg-gray-900/40 dark:border-gray-700">
              <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Preview</p>
              <div class="flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-xl text-lg shadow-sm"
                  :style="{ backgroundColor: form.color || '#381998' }"
                >
                  <span v-if="form.icon">{{ form.icon }}</span>
                  <span v-else class="text-xs font-bold text-white">{{ (form.name || 'C').charAt(0).toUpperCase() }}</span>
                </div>
                <div>
                  <p class="font-semibold text-gray-900 dark:text-white">{{ form.name || 'Category Name' }}</p>
                  <p class="text-xs text-gray-400">{{ form.description || 'Description…' }}</p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-2">
              <button
                type="button"
                @click="closeModal"
                class="rounded-xl px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-60 transition-colors"
              >
                {{ form.processing ? 'Saving…' : editingId ? 'Save Changes' : 'Create Category' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete confirmation -->
    <ConfirmationModal
      :open="showDeleteModal"
      title="Delete Category"
      :description="`Delete &quot;${categoryToDelete?.name}&quot;? This cannot be undone.`"
      confirm-text="Delete"
      variant="destructive"
      @update:open="showDeleteModal = $event"
      @confirm="doDelete"
    />
  </div>
</template>
