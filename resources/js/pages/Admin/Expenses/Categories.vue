<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'

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

interface ExpenseCategory {
  id: number
  name: string
  slug: string
  description: string | null
  color: string
  is_active: boolean
  expenses_count: number
  expenses_sum_amount: number | null
}

interface Props {
  categories: ExpenseCategory[]
}

const props = defineProps<Props>()
defineOptions({ layout: AppLayout })
const toast = useToast()

const formatMoney = (amount: number | null, currency = 'XAF') => {
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency }).format(amount || 0)
}

// Create
const showCreateModal = ref(false)
const createForm = useForm({
  name: '',
  description: '',
  color: '#6366f1',
})

const submitCreate = () => {
  createForm.post('/admin/expense-categories', {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    },
  })
}

// Edit
const showEditModal = ref(false)
const editingCategory = ref<ExpenseCategory | null>(null)
const editForm = useForm({
  name: '',
  description: '',
  color: '#6366f1',
  is_active: true,
})

const openEdit = (cat: ExpenseCategory) => {
  editingCategory.value = cat
  editForm.name = cat.name
  editForm.description = cat.description || ''
  editForm.color = cat.color
  editForm.is_active = cat.is_active
  showEditModal.value = true
}

const submitEdit = () => {
  if (!editingCategory.value) return
  editForm.patch(`/admin/expense-categories/${editingCategory.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false
      editingCategory.value = null
      editForm.reset()
    },
  })
}

// Delete
const showDeleteModal = ref(false)
const deletingCategory = ref<ExpenseCategory | null>(null)

const confirmDelete = (cat: ExpenseCategory) => {
  deletingCategory.value = cat
  showDeleteModal.value = true
}

const submitDelete = () => {
  if (!deletingCategory.value) return
  router.delete(`/admin/expense-categories/${deletingCategory.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      deletingCategory.value = null
    },
  })
}
</script>

<template>
  <div>
    <Head title="Expense Categories" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Expense Categories</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Manage expense categories used across all expense records.
          </p>
        </div>

        <button
          @click="showCreateModal = true"
          class="inline-flex items-center gap-2 rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#3aa3b1] focus:outline-none focus:ring-2 focus:ring-[#42b6c5] focus:ring-offset-2"
        >
          <Plus class="h-4 w-4" />
          Add Category
        </button>
      </div>

      <!-- Categories Grid -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="cat in categories"
          :key="cat.id"
          class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div
                class="h-10 w-10 rounded-lg"
                :style="{ backgroundColor: cat.color + '20' }"
              >
                <div class="flex h-full w-full items-center justify-center">
                  <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: cat.color }"></div>
                </div>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ cat.name }}</h3>
                <p v-if="cat.description" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ cat.description }}</p>
              </div>
            </div>

            <div class="flex items-center gap-1">
              <span
                class="mr-2 rounded-full px-2 py-0.5 text-[10px] font-medium"
                :class="cat.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
              >
                {{ cat.is_active ? 'Active' : 'Inactive' }}
              </span>
              <button
                @click="openEdit(cat)"
                class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-[#42b6c5] dark:hover:bg-gray-600"
                title="Edit"
              >
                <Pencil class="h-4 w-4" />
              </button>
              <button
                @click="confirmDelete(cat)"
                class="rounded-md p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                title="Delete"
              >
                <Trash2 class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">Expenses</p>
              <p class="text-lg font-bold text-gray-900 dark:text-white">{{ cat.expenses_count }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500 dark:text-gray-400">Total Amount</p>
              <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ formatMoney(cat.expenses_sum_amount) }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="categories.length === 0" class="rounded-xl border border-gray-200 bg-white py-12 text-center dark:border-gray-700 dark:bg-gray-800">
        <p class="text-sm text-gray-500 dark:text-gray-400">No categories yet. Click "Add Category" to create one.</p>
      </div>
    </div>

    <!-- Create Category Modal -->
    <Dialog :open="showCreateModal" @update:open="showCreateModal = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Add Expense Category</DialogTitle>
          <DialogDescription>Create a new category for organizing expenses.</DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitCreate" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
            <input
              v-model="createForm.name"
              type="text"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              placeholder="e.g. Office Supplies"
            />
            <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-500">{{ createForm.errors.name }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
            <textarea
              v-model="createForm.description"
              rows="2"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              placeholder="Brief description..."
            ></textarea>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Color *</label>
            <div class="flex items-center gap-3">
              <input
                v-model="createForm.color"
                type="color"
                class="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600"
              />
              <input
                v-model="createForm.color"
                type="text"
                class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                placeholder="#6366f1"
              />
            </div>
            <p v-if="createForm.errors.color" class="mt-1 text-xs text-red-500">{{ createForm.errors.color }}</p>
          </div>

          <DialogFooter class="gap-2">
            <DialogClose as-child>
              <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Cancel
              </button>
            </DialogClose>
            <button
              type="submit"
              :disabled="createForm.processing"
              class="rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white hover:bg-[#3aa3b1] disabled:opacity-50"
            >
              {{ createForm.processing ? 'Creating...' : 'Create Category' }}
            </button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Category Modal -->
    <Dialog :open="showEditModal" @update:open="showEditModal = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Edit Category</DialogTitle>
          <DialogDescription>Update the category details below.</DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitEdit" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
            <input
              v-model="editForm.name"
              type="text"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <p v-if="editForm.errors.name" class="mt-1 text-xs text-red-500">{{ editForm.errors.name }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
            <textarea
              v-model="editForm.description"
              rows="2"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            ></textarea>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Color *</label>
            <div class="flex items-center gap-3">
              <input
                v-model="editForm.color"
                type="color"
                class="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600"
              />
              <input
                v-model="editForm.color"
                type="text"
                class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
            </div>
            <p v-if="editForm.errors.color" class="mt-1 text-xs text-red-500">{{ editForm.errors.color }}</p>
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="editForm.is_active"
              type="checkbox"
              :true-value="true"
              :false-value="false"
              class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
              id="is_active"
            />
            <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
          </div>

          <DialogFooter class="gap-2">
            <DialogClose as-child>
              <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Cancel
              </button>
            </DialogClose>
            <button
              type="submit"
              :disabled="editForm.processing"
              class="rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white hover:bg-[#3aa3b1] disabled:opacity-50"
            >
              {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
            </button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Modal -->
    <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Delete Category</DialogTitle>
          <DialogDescription>
            <template v-if="deletingCategory && deletingCategory.expenses_count > 0">
              This category has {{ deletingCategory.expenses_count }} expense(s) and cannot be deleted. Please reassign them first.
            </template>
            <template v-else>
              Are you sure you want to delete "{{ deletingCategory?.name }}"? This action cannot be undone.
            </template>
          </DialogDescription>
        </DialogHeader>
        <DialogFooter class="gap-2">
          <DialogClose as-child>
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
              {{ deletingCategory && deletingCategory.expenses_count > 0 ? 'Close' : 'Cancel' }}
            </button>
          </DialogClose>
          <button
            v-if="!deletingCategory || deletingCategory.expenses_count === 0"
            @click="submitDelete"
            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
          >
            Delete
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
