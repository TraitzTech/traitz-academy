<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { ref, watch } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Program {
  id: number
  title: string
}

interface Interview {
  id: number
  title: string
  description: string | null
  program_id: number | null
  passing_score: number
  time_limit_minutes: number | null
  is_active: boolean
  questions_count: number
  responses_count: number
  created_at: string
  program: Program | null
  creator: { id: number; name: string } | null
}

interface Props {
  interviews: {
    data: Interview[]
    links: any[]
  }
  filters: {
    search?: string
    program_id?: string
  }
  programs: Program[]
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const search = ref(props.filters.search || '')
const programFilter = ref(props.filters.program_id || '')

const applyFilters = debounce(() => {
  router.get('/admin/interviews', {
    search: search.value || undefined,
    program_id: programFilter.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}, 300)

watch([search, programFilter], () => applyFilters())

const showDeleteModal = ref(false)
const interviewToDelete = ref<Interview | null>(null)

const toggleStatus = (interview: Interview) => {
  router.post(`/admin/interviews/${interview.id}/toggle-status`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Interview ${interview.is_active ? 'deactivated' : 'activated'} successfully.`)
    },
  })
}

const openDeleteModal = (interview: Interview) => {
  interviewToDelete.value = interview
  showDeleteModal.value = true
}

const confirmDelete = () => {
  if (!interviewToDelete.value) return
  router.delete(`/admin/interviews/${interviewToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      interviewToDelete.value = null
      toast.success('Interview deleted successfully.')
    },
  })
}

const formatDate = (date: string) => new Date(date).toLocaleDateString('en-US', {
  year: 'numeric', month: 'short', day: 'numeric'
})
</script>

<template>
  <div>
    <Head title="Manage Interviews" />

    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Interviews</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Manage interview assessments for applicants</p>
      </div>
      <Link
        href="/admin/interviews/create"
        class="px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors"
      >
        + Create Interview
      </Link>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <input
            v-model="search"
            type="text"
            placeholder="Search interviews..."
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          />
        </div>
        <div>
          <select
            v-model="programFilter"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
          >
            <option value="">All Programs</option>
            <option v-for="program in programs" :key="program.id" :value="program.id">{{ program.title }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Interviews List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div v-if="interviews.data.length" class="divide-y divide-gray-200 dark:divide-gray-700">
        <div v-for="interview in interviews.data" :key="interview.id" class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ interview.title }}</h3>
                <span
                  :class="interview.is_active
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                  class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                >
                  {{ interview.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <p v-if="interview.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{{ interview.description }}</p>
              <div class="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                <span v-if="interview.program">📚 {{ interview.program.title }}</span>
                <span v-else class="italic">No program linked</span>
                <span>📝 {{ interview.questions_count }} questions</span>
                <span>👥 {{ interview.responses_count }} responses</span>
                <span>🎯 Pass: {{ interview.passing_score }}%</span>
                <span v-if="interview.time_limit_minutes">⏱ {{ interview.time_limit_minutes }} min</span>
                <span>📅 {{ formatDate(interview.created_at) }}</span>
              </div>
            </div>
            <div class="flex items-center gap-2 ml-4">
              <Link
                :href="`/admin/interviews/${interview.id}/responses`"
                class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              >
                Responses
              </Link>
              <Link
                :href="`/admin/interviews/${interview.id}/edit`"
                class="px-3 py-1.5 text-sm bg-[#42b6c5] text-white rounded-lg hover:bg-[#35919e] transition-colors"
              >
                Edit
              </Link>
              <button
                @click="toggleStatus(interview)"
                :class="interview.is_active
                  ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400'
                  : 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'"
                class="px-3 py-1.5 text-sm rounded-lg transition-colors"
              >
                {{ interview.is_active ? 'Deactivate' : 'Activate' }}
              </button>
              <button
                @click="openDeleteModal(interview)"
                class="px-3 py-1.5 text-sm bg-red-100 text-red-800 rounded-lg hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 transition-colors"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="p-12 text-center">
        <div class="inline-block p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
          <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <p class="text-gray-600 dark:text-gray-300 font-medium mb-2">No Interviews Yet</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Create your first interview to start assessing applicants.</p>
        <Link href="/admin/interviews/create" class="inline-flex items-center px-4 py-2 bg-[#42b6c5] text-white rounded-lg text-sm font-medium hover:bg-[#35919e] transition-colors">
          Create Interview
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="interviews.links && interviews.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-1">
        <template v-for="link in interviews.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            v-html="link.label"
            :class="[
              'px-3 py-1.5 text-sm rounded',
              link.active ? 'bg-[#42b6c5] text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
            ]"
            preserve-scroll
          />
          <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500" />
        </template>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      :open="showDeleteModal"
      title="Delete Interview"
      :description="`Are you sure you want to delete &quot;${interviewToDelete?.title}&quot;? This will also delete all questions and responses. This action cannot be undone.`"
      confirm-text="Delete"
      variant="destructive"
      @update:open="showDeleteModal = $event"
      @confirm="confirmDelete"
    />
  </div>
</template>
