<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import {
  ArrowDownRight,
  ArrowUpRight,
  BarChart3,
  DollarSign,
  Download,
  Filter,
  PieChart,
  Plus,
  Search,
  Trash2,
  TrendingDown,
  TrendingUp,
  Pencil,
  X,
} from 'lucide-vue-next'
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

interface ExpenseCategory {
  id: number
  name: string
  color: string
}

interface ExpenseRow {
  id: number
  title: string
  description: string | null
  amount: number
  currency: string
  payment_method: string | null
  receipt_reference: string | null
  vendor: string | null
  expense_date: string
  status: string
  notes: string | null
  created_at: string
  category: ExpenseCategory | null
  recorder: { id: number; name: string; role: string } | null
  program: { id: number; title: string } | null
}

interface Props {
  expenses: {
    data: ExpenseRow[]
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  filters: {
    search?: string
    category_id?: string
    program_id?: string
    recorded_by?: string
    date_from?: string
    date_to?: string
    payment_method?: string
  }
  categories: ExpenseCategory[]
  programs: Array<{ id: number; title: string }>
  collectors: Array<{ id: number; name: string; role: string }>
  stats: {
    total_expenses: number
    expense_count: number
    total_collections: number
    balance: number
    avg_expense: number
    this_month_expenses: number
  }
  chartData: {
    monthly: Array<{ month: string; expenses: number; collections: number }>
    by_category: Array<{ name: string; color: string; amount: number }>
  }
  isExecutive: boolean
}

const props = defineProps<Props>()
defineOptions({ layout: AppLayout })
const toast = useToast()

// Filters
const search = ref(props.filters.search || '')
const categoryId = ref(props.filters.category_id || '')
const programId = ref(props.filters.program_id || '')
const recordedBy = ref(props.filters.recorded_by || '')
const dateFrom = ref(props.filters.date_from || '')
const dateTo = ref(props.filters.date_to || '')
const paymentMethod = ref(props.filters.payment_method || '')
const showFilters = ref(false)

const applyFilters = debounce(() => {
  router.get('/admin/expenses', {
    search: search.value || undefined,
    category_id: categoryId.value || undefined,
    program_id: programId.value || undefined,
    recorded_by: recordedBy.value || undefined,
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
    payment_method: paymentMethod.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}, 300)

watch([search, categoryId, programId, recordedBy, dateFrom, dateTo, paymentMethod], applyFilters)

const clearFilters = () => {
  search.value = ''
  categoryId.value = ''
  programId.value = ''
  recordedBy.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  paymentMethod.value = ''
}

const hasActiveFilters = computed(() => {
  return !!(search.value || categoryId.value || programId.value || recordedBy.value || dateFrom.value || dateTo.value || paymentMethod.value)
})

// Formatting helpers
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

const formatMethod = (method: string | null) => {
  if (!method) return '—'
  return method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatRole = (role: string) => {
  if (role === 'admin') return 'CTO (Legacy)'
  return role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const hasRows = computed(() => props.expenses.data.length > 0)

// Export URL
const exportUrl = computed(() => {
  const params = new URLSearchParams()
  if (search.value) params.set('search', search.value)
  if (categoryId.value) params.set('category_id', String(categoryId.value))
  if (programId.value) params.set('program_id', String(programId.value))
  if (recordedBy.value) params.set('recorded_by', String(recordedBy.value))
  if (dateFrom.value) params.set('date_from', dateFrom.value)
  if (dateTo.value) params.set('date_to', dateTo.value)
  if (paymentMethod.value) params.set('payment_method', paymentMethod.value)
  const query = params.toString()
  return query ? `/admin/expenses/export?${query}` : '/admin/expenses/export'
})

// Selection for bulk actions
const selectedIds = ref<number[]>([])
const allSelected = computed({
  get: () => props.expenses.data.length > 0 && selectedIds.value.length === props.expenses.data.length,
  set: (val: boolean) => {
    selectedIds.value = val ? props.expenses.data.map(e => e.id) : []
  },
})

// Create Modal
const showCreateModal = ref(false)
const createForm = useForm({
  expense_category_id: '',
  program_id: '',
  title: '',
  description: '',
  amount: '',
  payment_method: '',
  receipt_reference: '',
  vendor: '',
  expense_date: new Date().toISOString().split('T')[0],
  notes: '',
})

const submitCreate = () => {
  createForm.post('/admin/expenses', {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    },
  })
}

// Edit Modal
const showEditModal = ref(false)
const editingExpense = ref<ExpenseRow | null>(null)
const editForm = useForm({
  expense_category_id: '',
  program_id: '',
  title: '',
  description: '',
  amount: '',
  payment_method: '',
  receipt_reference: '',
  vendor: '',
  expense_date: '',
  notes: '',
})

const openEdit = (expense: ExpenseRow) => {
  editingExpense.value = expense
  editForm.expense_category_id = String(expense.category?.id || '')
  editForm.program_id = expense.program ? String(expense.program.id) : ''
  editForm.title = expense.title
  editForm.description = expense.description || ''
  editForm.amount = String(expense.amount)
  editForm.payment_method = expense.payment_method || ''
  editForm.receipt_reference = expense.receipt_reference || ''
  editForm.vendor = expense.vendor || ''
  editForm.expense_date = expense.expense_date ? expense.expense_date.split('T')[0] : ''
  editForm.notes = expense.notes || ''
  showEditModal.value = true
}

const submitEdit = () => {
  if (!editingExpense.value) return
  editForm.patch(`/admin/expenses/${editingExpense.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false
      editingExpense.value = null
      editForm.reset()
    },
  })
}

// Delete
const showDeleteModal = ref(false)
const deletingExpense = ref<ExpenseRow | null>(null)

const confirmDelete = (expense: ExpenseRow) => {
  deletingExpense.value = expense
  showDeleteModal.value = true
}

const submitDelete = () => {
  if (!deletingExpense.value) return
  router.delete(`/admin/expenses/${deletingExpense.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      deletingExpense.value = null
    },
  })
}

// Bulk delete
const showBulkDeleteModal = ref(false)
const submitBulkDelete = () => {
  router.post('/admin/expenses/bulk-destroy', {
    ids: selectedIds.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showBulkDeleteModal.value = false
      selectedIds.value = []
    },
  })
}

// Chart rendering
const chartMaxExpense = computed(() => {
  const maxExp = Math.max(...props.chartData.monthly.map(m => m.expenses), 1)
  const maxCol = Math.max(...props.chartData.monthly.map(m => m.collections), 1)
  return Math.max(maxExp, maxCol)
})

const categoryTotal = computed(() => {
  return props.chartData.by_category.reduce((sum, c) => sum + c.amount, 0)
})

const showCharts = ref(true)
</script>

<template>
  <div>
    <Head title="Expense Tracking" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Expense Tracking
          </h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ isExecutive ? 'Manage all organizational expenses and view financial overview' : 'Manage your recorded expenses' }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="showCharts = !showCharts"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            <BarChart3 class="h-4 w-4" />
            {{ showCharts ? 'Hide' : 'Show' }} Charts
          </button>

          <a
            :href="exportUrl"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            <Download class="h-4 w-4" />
            Export
          </a>

          <Link
            v-if="isExecutive"
            href="/admin/expense-categories"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            <PieChart class="h-4 w-4" />
            Categories
          </Link>

          <button
            @click="showCreateModal = true"
            class="inline-flex items-center gap-2 rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#3aa3b1] focus:outline-none focus:ring-2 focus:ring-[#42b6c5] focus:ring-offset-2"
          >
            <Plus class="h-4 w-4" />
            Record Expense
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Expenses -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Expenses</p>
              <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {{ formatMoney(stats.total_expenses) }}
              </p>
            </div>
            <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">
              <TrendingDown class="h-5 w-5 text-red-600 dark:text-red-400" />
            </div>
          </div>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ stats.expense_count }} expense{{ stats.expense_count !== 1 ? 's' : '' }} recorded
          </p>
        </div>

        <!-- This Month -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">This Month</p>
              <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {{ formatMoney(stats.this_month_expenses) }}
              </p>
            </div>
            <div class="rounded-full bg-orange-100 p-3 dark:bg-orange-900/30">
              <DollarSign class="h-5 w-5 text-orange-600 dark:text-orange-400" />
            </div>
          </div>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Avg: {{ formatMoney(stats.avg_expense) }} per expense
          </p>
        </div>

        <!-- Total Collections (executive only) -->
        <div v-if="isExecutive" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Collections</p>
              <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {{ formatMoney(stats.total_collections) }}
              </p>
            </div>
            <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
              <TrendingUp class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
          </div>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Successful payments collected
          </p>
        </div>

        <!-- Balance (executive only) -->
        <div v-if="isExecutive" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Balance</p>
              <p
                class="mt-1 text-2xl font-bold"
                :class="stats.balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
              >
                {{ formatMoney(stats.balance) }}
              </p>
            </div>
            <div
              class="rounded-full p-3"
              :class="stats.balance >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'"
            >
              <component
                :is="stats.balance >= 0 ? ArrowUpRight : ArrowDownRight"
                class="h-5 w-5"
                :class="stats.balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
              />
            </div>
          </div>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Collections minus expenses
          </p>
        </div>

        <!-- Non-executive: duplicate expense stats -->
        <div v-if="!isExecutive" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg/Expense</p>
              <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                {{ formatMoney(stats.avg_expense) }}
              </p>
            </div>
            <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
              <BarChart3 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div v-if="showCharts" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Monthly Trend Chart -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-gray-700 dark:bg-gray-800">
          <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            {{ isExecutive ? 'Collections vs Expenses (12 months)' : 'Monthly Expenses (12 months)' }}
          </h3>

          <div v-if="chartData.monthly.length > 0" class="space-y-2">
            <!-- Chart legend -->
            <div class="mb-4 flex flex-wrap items-center gap-4 text-sm">
              <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-red-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Expenses</span>
              </div>
              <div v-if="isExecutive" class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-green-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Collections</span>
              </div>
            </div>

            <!-- Bar chart -->
            <div class="flex items-end gap-1 sm:gap-2" style="height: 200px">
              <div
                v-for="(m, idx) in chartData.monthly"
                :key="idx"
                class="group relative flex flex-1 flex-col items-center justify-end gap-0.5"
                style="min-width: 0"
              >
                <!-- Collections bar (behind) -->
                <div
                  v-if="isExecutive && m.collections > 0"
                  class="w-full max-w-[28px] rounded-t-sm bg-green-400/70 transition-all dark:bg-green-500/50"
                  :style="{ height: `${Math.max((m.collections / chartMaxExpense) * 100, 2)}%` }"
                  :title="`Collections: ${formatMoney(m.collections)}`"
                ></div>

                <!-- Expense bar -->
                <div
                  v-if="m.expenses > 0"
                  class="w-full max-w-[28px] rounded-t-sm bg-red-400 transition-all dark:bg-red-500/80"
                  :style="{ height: `${Math.max((m.expenses / chartMaxExpense) * 100, 2)}%` }"
                  :title="`Expenses: ${formatMoney(m.expenses)}`"
                ></div>

                <!-- Zero state -->
                <div
                  v-if="m.expenses === 0 && (!isExecutive || m.collections === 0)"
                  class="w-full max-w-[28px] rounded-t-sm bg-gray-200 dark:bg-gray-600"
                  style="height: 2%"
                ></div>

                <!-- Month label -->
                <span class="mt-1 text-center text-[9px] leading-tight text-gray-500 sm:text-[10px] dark:text-gray-400">
                  {{ m.month.split(' ')[0] }}
                </span>

                <!-- Tooltip -->
                <div class="pointer-events-none absolute -top-20 left-1/2 z-10 hidden -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-2 text-xs shadow-lg group-hover:block dark:border-gray-600 dark:bg-gray-700">
                  <p class="whitespace-nowrap font-semibold text-gray-900 dark:text-white">{{ m.month }}</p>
                  <p class="text-red-500">Exp: {{ formatMoney(m.expenses) }}</p>
                  <p v-if="isExecutive" class="text-green-500">Col: {{ formatMoney(m.collections) }}</p>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="flex h-48 items-center justify-center text-sm text-gray-400">
            No data to display
          </div>
        </div>

        <!-- Category Breakdown -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            By Category
          </h3>

          <div v-if="chartData.by_category.length > 0" class="space-y-3">
            <div
              v-for="(cat, idx) in chartData.by_category"
              :key="idx"
              class="group"
            >
              <div class="mb-1 flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                  <div
                    class="h-3 w-3 rounded-full"
                    :style="{ backgroundColor: cat.color }"
                  ></div>
                  <span class="font-medium text-gray-700 dark:text-gray-300">{{ cat.name }}</span>
                </div>
                <span class="text-gray-500 dark:text-gray-400">{{ formatMoney(cat.amount) }}</span>
              </div>
              <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                <div
                  class="h-full rounded-full transition-all"
                  :style="{
                    width: `${categoryTotal > 0 ? (cat.amount / categoryTotal) * 100 : 0}%`,
                    backgroundColor: cat.color,
                  }"
                ></div>
              </div>
              <p class="mt-0.5 text-right text-[10px] text-gray-400">
                {{ categoryTotal > 0 ? ((cat.amount / categoryTotal) * 100).toFixed(1) : 0 }}%
              </p>
            </div>
          </div>

          <div v-else class="flex h-48 items-center justify-center text-sm text-gray-400">
            No categories to display
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <!-- Search -->
            <div class="relative max-w-md flex-1">
              <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input
                v-model="search"
                type="text"
                placeholder="Search expenses..."
                class="w-full rounded-lg border border-gray-300 py-2 pr-4 pl-10 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
              />
            </div>

            <div class="flex items-center gap-2">
              <button
                @click="showFilters = !showFilters"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                :class="{ 'bg-[#42b6c5]/10 border-[#42b6c5] text-[#42b6c5]': hasActiveFilters }"
              >
                <Filter class="h-4 w-4" />
                Filters
                <span v-if="hasActiveFilters" class="ml-1 rounded-full bg-[#42b6c5] px-1.5 py-0.5 text-[10px] font-bold text-white">!</span>
              </button>

              <button
                v-if="hasActiveFilters"
                @click="clearFilters"
                class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
              >
                <X class="h-3 w-3" />
                Clear
              </button>

              <button
                v-if="selectedIds.length > 0"
                @click="showBulkDeleteModal = true"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
              >
                <Trash2 class="h-4 w-4" />
                Delete ({{ selectedIds.length }})
              </button>
            </div>
          </div>

          <!-- Extended Filters -->
          <div
            v-if="showFilters"
            class="mt-4 grid grid-cols-1 gap-3 border-t border-gray-200 pt-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-700"
          >
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Category</label>
              <select
                v-model="categoryId"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">All Categories</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Program</label>
              <select
                v-model="programId"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">All Programs</option>
                <option v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.title }}</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
              <select
                v-model="paymentMethod"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">All Methods</option>
                <option value="cash">Cash</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
              </select>
            </div>

            <div v-if="isExecutive && collectors.length > 0">
              <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Recorded By</label>
              <select
                v-model="recordedBy"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">All Staff</option>
                <option v-for="c in collectors" :key="c.id" :value="c.id">{{ c.name }} ({{ formatRole(c.role) }})</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Date From</label>
              <input
                v-model="dateFrom"
                type="date"
                class="w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Date To</label>
              <input
                v-model="dateTo"
                type="date"
                class="w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
            </div>
          </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden overflow-x-auto md:block">
          <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
              <tr>
                <th class="px-4 py-3">
                  <input
                    type="checkbox"
                    v-model="allSelected"
                    class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                  />
                </th>
                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Date</th>
                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Title</th>
                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Category</th>
                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Amount</th>
                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Method</th>
                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Vendor</th>
                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Program</th>
                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Recorded By</th>
                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="expense in expenses.data"
                :key="expense.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
              >
                <td class="px-4 py-3">
                  <input
                    type="checkbox"
                    :value="expense.id"
                    v-model="selectedIds"
                    class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                  />
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-gray-900 dark:text-white">
                  {{ formatDate(expense.expense_date) }}
                </td>
                <td class="max-w-[200px] truncate px-4 py-3 font-medium text-gray-900 dark:text-white">
                  {{ expense.title }}
                  <p v-if="expense.receipt_reference" class="text-[10px] text-gray-400">
                    {{ expense.receipt_reference }}
                  </p>
                </td>
                <td class="px-4 py-3">
                  <span
                    v-if="expense.category"
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :style="{ backgroundColor: expense.category.color + '20', color: expense.category.color }"
                  >
                    <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: expense.category.color }"></span>
                    {{ expense.category.name }}
                  </span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 font-semibold text-red-600 dark:text-red-400">
                  -{{ formatMoney(expense.amount) }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-400">
                  {{ formatMethod(expense.payment_method) }}
                </td>
                <td class="max-w-[140px] truncate px-4 py-3 text-gray-600 dark:text-gray-400">
                  {{ expense.vendor || '—' }}
                </td>
                <td class="max-w-[140px] truncate px-4 py-3 text-gray-600 dark:text-gray-400">
                  {{ expense.program?.title || '—' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3">
                  <span v-if="expense.recorder" class="text-gray-700 dark:text-gray-300">
                    {{ expense.recorder.name }}
                    <span class="ml-1 text-[10px] text-gray-400">({{ formatRole(expense.recorder.role) }})</span>
                  </span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="whitespace-nowrap px-4 py-3">
                  <div class="flex items-center gap-1">
                    <button
                      @click="openEdit(expense)"
                      class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-[#42b6c5] dark:hover:bg-gray-600"
                      title="Edit"
                    >
                      <Pencil class="h-4 w-4" />
                    </button>
                    <button
                      @click="confirmDelete(expense)"
                      class="rounded-md p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                      title="Delete"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="!hasRows" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
            No expenses found. Click "Record Expense" to add one.
          </div>
        </div>

        <!-- Mobile Cards -->
        <div class="space-y-3 p-4 md:hidden">
          <div
            v-for="expense in expenses.data"
            :key="expense.id"
            class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/50"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <input
                    type="checkbox"
                    :value="expense.id"
                    v-model="selectedIds"
                    class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                  />
                  <h4 class="font-medium text-gray-900 dark:text-white">{{ expense.title }}</h4>
                </div>
                <p class="mt-1 text-xs text-gray-500">{{ formatDate(expense.expense_date) }}</p>
              </div>
              <p class="text-lg font-bold text-red-600 dark:text-red-400">-{{ formatMoney(expense.amount) }}</p>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
              <span
                v-if="expense.category"
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium"
                :style="{ backgroundColor: expense.category.color + '20', color: expense.category.color }"
              >
                {{ expense.category.name }}
              </span>
              <span v-if="expense.payment_method" class="rounded-full bg-gray-200 px-2 py-0.5 text-[10px] text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                {{ formatMethod(expense.payment_method) }}
              </span>
              <span v-if="expense.vendor" class="text-[10px] text-gray-500">{{ expense.vendor }}</span>
            </div>

            <div class="mt-2 flex items-center justify-between">
              <span v-if="expense.recorder" class="text-xs text-gray-500">
                By: {{ expense.recorder.name }}
              </span>
              <div class="flex gap-1">
                <button @click="openEdit(expense)" class="rounded-md p-1.5 text-gray-500 hover:text-[#42b6c5]">
                  <Pencil class="h-4 w-4" />
                </button>
                <button @click="confirmDelete(expense)" class="rounded-md p-1.5 text-gray-500 hover:text-red-600">
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>

          <div v-if="!hasRows" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
            No expenses found.
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="expenses.links.length > 3" class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
          <nav class="flex flex-wrap items-center justify-center gap-1">
            <Link
              v-for="link in expenses.links"
              :key="link.label"
              :href="link.url || ''"
              class="inline-flex items-center rounded-md px-3 py-1.5 text-sm"
              :class="{
                'bg-[#42b6c5] text-white font-semibold': link.active,
                'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700': !link.active && link.url,
                'text-gray-400 cursor-not-allowed': !link.url,
              }"
              v-html="link.label"
              :preserve-state="true"
            />
          </nav>
        </div>
      </div>
    </div>

    <!-- Create Expense Modal -->
    <Dialog :open="showCreateModal" @update:open="showCreateModal = $event">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Record New Expense</DialogTitle>
          <DialogDescription>Fill in the details below to record an expense.</DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitCreate" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title *</label>
              <input
                v-model="createForm.title"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                placeholder="e.g. Office supplies purchase"
              />
              <p v-if="createForm.errors.title" class="mt-1 text-xs text-red-500">{{ createForm.errors.title }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Category *</label>
              <select
                v-model="createForm.expense_category_id"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Select category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <p v-if="createForm.errors.expense_category_id" class="mt-1 text-xs text-red-500">{{ createForm.errors.expense_category_id }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Amount (XAF) *</label>
              <input
                v-model="createForm.amount"
                type="number"
                step="0.01"
                min="0.01"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                placeholder="0.00"
              />
              <p v-if="createForm.errors.amount" class="mt-1 text-xs text-red-500">{{ createForm.errors.amount }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date *</label>
              <input
                v-model="createForm.expense_date"
                type="date"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
              <p v-if="createForm.errors.expense_date" class="mt-1 text-xs text-red-500">{{ createForm.errors.expense_date }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
              <select
                v-model="createForm.payment_method"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Select method</option>
                <option value="cash">Cash</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
              </select>
              <p v-if="createForm.errors.payment_method" class="mt-1 text-xs text-red-500">{{ createForm.errors.payment_method }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Vendor</label>
              <input
                v-model="createForm.vendor"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                placeholder="e.g. Office Depot"
              />
              <p v-if="createForm.errors.vendor" class="mt-1 text-xs text-red-500">{{ createForm.errors.vendor }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Receipt Reference</label>
              <input
                v-model="createForm.receipt_reference"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                placeholder="Auto-generated if empty"
              />
              <p v-if="createForm.errors.receipt_reference" class="mt-1 text-xs text-red-500">{{ createForm.errors.receipt_reference }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
              <select
                v-model="createForm.program_id"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">No program</option>
                <option v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.title }}</option>
              </select>
            </div>

            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
              <textarea
                v-model="createForm.description"
                rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                placeholder="Brief description of the expense..."
              ></textarea>
              <p v-if="createForm.errors.description" class="mt-1 text-xs text-red-500">{{ createForm.errors.description }}</p>
            </div>

            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
              <textarea
                v-model="createForm.notes"
                rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                placeholder="Any additional notes..."
              ></textarea>
            </div>
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
              {{ createForm.processing ? 'Recording...' : 'Record Expense' }}
            </button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Edit Expense Modal -->
    <Dialog :open="showEditModal" @update:open="showEditModal = $event">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Edit Expense</DialogTitle>
          <DialogDescription>Update the expense details below.</DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitEdit" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title *</label>
              <input
                v-model="editForm.title"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
              <p v-if="editForm.errors.title" class="mt-1 text-xs text-red-500">{{ editForm.errors.title }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Category *</label>
              <select
                v-model="editForm.expense_category_id"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Select category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <p v-if="editForm.errors.expense_category_id" class="mt-1 text-xs text-red-500">{{ editForm.errors.expense_category_id }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Amount (XAF) *</label>
              <input
                v-model="editForm.amount"
                type="number"
                step="0.01"
                min="0.01"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
              <p v-if="editForm.errors.amount" class="mt-1 text-xs text-red-500">{{ editForm.errors.amount }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date *</label>
              <input
                v-model="editForm.expense_date"
                type="date"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
              <p v-if="editForm.errors.expense_date" class="mt-1 text-xs text-red-500">{{ editForm.errors.expense_date }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
              <select
                v-model="editForm.payment_method"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Select method</option>
                <option value="cash">Cash</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Vendor</label>
              <input
                v-model="editForm.vendor"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Receipt Reference</label>
              <input
                v-model="editForm.receipt_reference"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
              <select
                v-model="editForm.program_id"
                class="w-full rounded-lg border border-gray-300 py-2 pr-8 pl-3 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
                <option value="">No program</option>
                <option v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.title }}</option>
              </select>
            </div>

            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
              <textarea
                v-model="editForm.description"
                rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              ></textarea>
            </div>

            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
              <textarea
                v-model="editForm.notes"
                rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-1 focus:ring-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              ></textarea>
            </div>
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
          <DialogTitle>Delete Expense</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete "{{ deletingExpense?.title }}"? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter class="gap-2">
          <DialogClose as-child>
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
              Cancel
            </button>
          </DialogClose>
          <button
            @click="submitDelete"
            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
          >
            Delete
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Bulk Delete Confirmation Modal -->
    <Dialog :open="showBulkDeleteModal" @update:open="showBulkDeleteModal = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Delete {{ selectedIds.length }} Expense(s)</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete {{ selectedIds.length }} selected expense(s)? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter class="gap-2">
          <DialogClose as-child>
            <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
              Cancel
            </button>
          </DialogClose>
          <button
            @click="submitBulkDelete"
            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
          >
            Delete All
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
