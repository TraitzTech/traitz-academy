<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { MessageSquare, Plus, Search, ToggleLeft, ToggleRight, Trash2 } from 'lucide-vue-next'
import { ref, watch } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface FeedbackForm {
  id: number
  title: string
  description: string | null
  slug: string
  is_active: boolean
  allow_anonymous: boolean
  responses_count: number
  closes_at: string | null
  created_at: string
  creator: { id: number; name: string } | null
}

interface Props {
  forms: {
    data: FeedbackForm[]
    links: any[]
    total: number
  }
  filters: { search?: string }
}

const props = defineProps<Props>()
defineOptions({ layout: AppLayout })
const toast = useToast()

const search = ref(props.filters.search || '')

const applyFilters = debounce(() => {
  router.get('/admin/feedback', { search: search.value || undefined }, { preserveState: true, preserveScroll: true })
}, 300)

watch(search, applyFilters)

const showDeleteModal = ref(false)
const formToDelete = ref<FeedbackForm | null>(null)

const toggleStatus = (form: FeedbackForm) => {
  router.post(`/admin/feedback/${form.id}/toggle-status`, {}, {
    preserveScroll: true,
    onSuccess: () => toast.success(`Form ${form.is_active ? 'deactivated' : 'activated'}.`),
  })
}

const openDeleteModal = (form: FeedbackForm) => {
  formToDelete.value = form
  showDeleteModal.value = true
}

const confirmDelete = () => {
  if (!formToDelete.value) { return }
  router.delete(`/admin/feedback/${formToDelete.value.id}`, {
    onSuccess: () => {
      toast.success('Form deleted.')
      showDeleteModal.value = false
    },
  })
}

const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })

const shareLink = (slug: string) => `${window.location.origin}/feedback/${slug}`

const copyLink = (slug: string) => {
  navigator.clipboard.writeText(shareLink(slug))
  toast.success('Share link copied!')
}
</script>

<template>
  <Head title="Feedback Forms" />

  <div class="p-4 lg:p-8 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 lg:mb-8">
      <div>
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-100">Feedback Forms</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Create and manage feedback forms for interns.
        </p>
      </div>
      <Link
        href="/admin/feedback/create"
        class="inline-flex items-center gap-2 px-4 py-2 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35a3b2] transition-colors"
      >
        <Plus class="w-4 h-4" />
        New Form
      </Link>
    </div>

    <!-- Search -->
    <div class="relative mb-6">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
      <input
        v-model="search"
        type="text"
        placeholder="Search forms..."
        class="w-full pl-9 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40"
      />
    </div>

    <!-- Empty state -->
    <div
      v-if="forms.data.length === 0"
      class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center"
    >
      <MessageSquare class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No feedback forms yet</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        Create your first feedback form to start collecting responses from interns.
      </p>
      <Link
        href="/admin/feedback/create"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35a3b2] transition-colors"
      >
        <Plus class="w-4 h-4" />
        Create Form
      </Link>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
      <div
        v-for="form in forms.data"
        :key="form.id"
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden"
      >
        <!-- Card top -->
        <div class="p-5 flex-1">
          <div class="flex items-start justify-between gap-2 mb-3">
            <span
              :class="[
                'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold',
                form.is_active
                  ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                  : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
              ]"
            >
              {{ form.is_active ? 'Active' : 'Inactive' }}
            </span>
            <span
              v-if="form.allow_anonymous"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"
            >
              Allows Anonymous
            </span>
          </div>
          <h3 class="font-bold text-gray-900 dark:text-gray-100 text-lg leading-tight mb-1">{{ form.title }}</h3>
          <p v-if="form.description" class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-3">
            {{ form.description }}
          </p>
          <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1">
              <MessageSquare class="w-3.5 h-3.5" />
              {{ form.responses_count }} {{ form.responses_count === 1 ? 'response' : 'responses' }}
            </span>
            <span>Created {{ formatDate(form.created_at) }}</span>
          </div>
          <div v-if="form.closes_at" class="mt-2 text-xs text-amber-600 dark:text-amber-400">
            Closes {{ formatDate(form.closes_at) }}
          </div>
        </div>

        <!-- Copy link -->
        <div class="px-5 pb-3">
          <button
            @click="copyLink(form.slug)"
            class="w-full text-left text-xs px-3 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-[#42b6c5]/10 hover:text-[#42b6c5] transition-colors truncate"
          >
            📋 {{ shareLink(form.slug) }}
          </button>
        </div>

        <!-- Actions -->
        <div class="px-5 pb-5 flex items-center gap-2 flex-wrap">
          <Link
            :href="`/admin/feedback/${form.id}`"
            class="flex-1 text-center px-3 py-2 bg-[#42b6c5]/10 text-[#42b6c5] text-sm font-semibold rounded-lg hover:bg-[#42b6c5]/20 transition-colors"
          >
            View
          </Link>
          <Link
            :href="`/admin/feedback/${form.id}/edit`"
            class="flex-1 text-center px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            Edit
          </Link>
          <button
            @click="toggleStatus(form)"
            :title="form.is_active ? 'Deactivate' : 'Activate'"
            class="p-2 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
          >
            <ToggleRight v-if="form.is_active" class="w-5 h-5 text-green-500" />
            <ToggleLeft v-else class="w-5 h-5" />
          </button>
          <button
            @click="openDeleteModal(form)"
            class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="forms.links && forms.links.length > 3" class="mt-8 flex justify-center gap-1">
      <template v-for="link in forms.links" :key="link.label">
        <Link
          v-if="link.url"
          :href="link.url"
          :class="[
            'px-3 py-2 text-sm rounded-lg transition-colors',
            link.active
              ? 'bg-[#42b6c5] text-white font-semibold'
              : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
          ]"
          v-html="link.label"
        />
        <span
          v-else
          class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed"
          v-html="link.label"
        />
      </template>
    </div>
  </div>

  <ConfirmationModal
    :show="showDeleteModal"
    title="Delete Feedback Form"
    :message="`Are you sure you want to delete '${formToDelete?.title}'? All responses will be permanently deleted.`"
    confirm-text="Delete"
    @confirm="confirmDelete"
    @cancel="showDeleteModal = false"
  />
</template>
