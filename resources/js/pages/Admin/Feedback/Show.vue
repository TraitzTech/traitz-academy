<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { BarChart3, CheckCircle, Copy, ExternalLink, MessageSquare, Trash2, Users } from 'lucide-vue-next'
import { ref } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
const toast = useToast()

interface Answer {
  question: { id: number; question: string; type: string }
  answer: string | null
}

interface ResponseRow {
  id: number
  is_anonymous: boolean
  respondent_name: string | null
  respondent_email: string | null
  ip_address: string | null
  created_at: string
  user: { id: number; name: string } | null
  answers: Answer[]
}

interface QuestionStat {
  question_id: number
  question: string
  type: string
  stats: {
    type: 'chart' | 'text'
    labels?: string[]
    data?: number[]
    total: number
    responses?: string[]
  }
}

interface Props {
  form: {
    id: number
    title: string
    description: string | null
    slug: string
    is_active: boolean
    allow_anonymous: boolean
    send_thank_you_email: boolean
    closes_at: string | null
    questions: any[]
    creator: { name: string } | null
  }
  responses: {
    data: ResponseRow[]
    links: any[]
    total: number
  }
  analytics: QuestionStat[]
  shareUrl: string
  stats: {
    total_responses: number
    anonymous_responses: number
    identified_responses: number
  }
}

const props = defineProps<Props>()

const activeTab = ref<'analytics' | 'responses'>('analytics')

const copyShareUrl = () => {
  navigator.clipboard.writeText(props.shareUrl)
  toast.success('Share link copied to clipboard!')
}

const formatDate = (d: string) =>
  new Date(d).toLocaleString('en-GB', {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })

const displayName = (r: ResponseRow) => {
  if (r.is_anonymous) { return 'Anonymous' }
  return r.respondent_name ?? r.user?.name ?? 'Unknown'
}

const toggleStatus = () => {
  router.post(`/admin/feedback/${props.form.id}/toggle-status`, {}, {
    preserveScroll: true,
    onSuccess: () => toast.success('Status updated.'),
  })
}

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const confirmDelete = () => {
  deleteProcessing.value = true
  router.delete(`/admin/feedback/${props.form.id}`, {
    onSuccess: () => {
      toast.success('Feedback form deleted.')
      showDeleteModal.value = false
    },
    onFinish: () => {
      deleteProcessing.value = false
    },
  })
}

const maxBarValue = (data?: number[]) => Math.max(...(data ?? [1]), 1)

const barWidth = (value: number, max: number) => `${Math.round((value / max) * 100)}%`

const barColors = ['bg-[#42b6c5]', 'bg-blue-400', 'bg-purple-400', 'bg-pink-400', 'bg-amber-400', 'bg-green-400']
</script>

<template>
  <Head :title="`${form.title} — Feedback`" />

  <div class="p-4 lg:p-8 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-6 lg:mb-8">
      <div class="flex items-start gap-3">
        <Link
          href="/admin/feedback"
          class="mt-1 p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </Link>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ form.title }}</h1>
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
          </div>
          <p v-if="form.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ form.description }}</p>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">By {{ form.creator?.name ?? 'Admin' }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <Link
          :href="`/admin/feedback/${form.id}/edit`"
          class="px-3 py-2 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
          Edit
        </Link>
        <button
          @click="toggleStatus"
          :class="[
            'px-3 py-2 text-sm font-semibold rounded-lg transition-colors',
            form.is_active
              ? 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50'
              : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50',
          ]"
        >
          {{ form.is_active ? 'Deactivate' : 'Activate' }}
        </button>
        <a
          :href="shareUrl"
          target="_blank"
          class="flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-[#42b6c5] bg-[#42b6c5]/10 rounded-lg hover:bg-[#42b6c5]/20 transition-colors"
        >
          <ExternalLink class="w-3.5 h-3.5" />
          Preview
        </a>
        <button
          @click="showDeleteModal = true"
          class="flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-red-600 bg-red-50 rounded-lg hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 transition-colors"
        >
          <Trash2 class="w-3.5 h-3.5" />
          Delete
        </button>
      </div>
    </div>

    <!-- Share Link banner -->
    <div class="bg-gradient-to-r from-[#42b6c5]/10 to-blue-500/10 border border-[#42b6c5]/20 rounded-xl p-4 mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-0.5">Share Link</p>
        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ shareUrl }}</p>
      </div>
      <button
        @click="copyShareUrl"
        class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-[#42b6c5] text-white text-sm font-semibold rounded-lg hover:bg-[#35a3b2] transition-colors"
      >
        <Copy class="w-4 h-4" />
        Copy Link
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-3 lg:gap-6 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 lg:p-6 border-l-4 border-[#42b6c5]">
        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">Total Responses</p>
        <p class="text-2xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100">{{ stats.total_responses }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 lg:p-6 border-l-4 border-purple-400">
        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">Anonymous</p>
        <p class="text-2xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100">{{ stats.anonymous_responses }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 lg:p-6 border-l-4 border-green-400">
        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">Identified</p>
        <p class="text-2xl lg:text-3xl font-bold text-[#000928] dark:text-gray-100">{{ stats.identified_responses }}</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
      <button
        @click="activeTab = 'analytics'"
        :class="[
          'flex items-center gap-2 px-5 py-3 text-sm font-semibold border-b-2 transition-colors',
          activeTab === 'analytics'
            ? 'border-[#42b6c5] text-[#42b6c5]'
            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
        ]"
      >
        <BarChart3 class="w-4 h-4" />
        Analytics
      </button>
      <button
        @click="activeTab = 'responses'"
        :class="[
          'flex items-center gap-2 px-5 py-3 text-sm font-semibold border-b-2 transition-colors',
          activeTab === 'responses'
            ? 'border-[#42b6c5] text-[#42b6c5]'
            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
        ]"
      >
        <Users class="w-4 h-4" />
        Responses
        <span class="ml-0.5 px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs rounded-full">
          {{ stats.total_responses }}
        </span>
      </button>
    </div>

    <!-- Analytics Tab -->
    <div v-if="activeTab === 'analytics'">
      <div v-if="analytics.length === 0" class="text-center py-16 text-gray-400">
        No analytics available yet.
      </div>

      <div v-else class="space-y-6">
        <div
          v-for="(item, index) in analytics"
          :key="item.question_id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 lg:p-6 border border-gray-200 dark:border-gray-700"
        >
          <div class="flex items-start gap-3 mb-4">
            <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-[#42b6c5]/10 text-[#42b6c5] text-sm font-bold rounded-full">
              {{ index + 1 }}
            </span>
            <div>
              <p class="font-semibold text-gray-800 dark:text-gray-200">{{ item.question }}</p>
              <p class="text-xs text-gray-400 mt-0.5">
                {{ item.stats.total }} {{ item.stats.total === 1 ? 'response' : 'responses' }}
              </p>
            </div>
          </div>

          <!-- Bar chart for multiple choice -->
          <div v-if="item.stats.type === 'chart' && item.stats.labels && item.stats.data" class="space-y-2.5">
            <div
              v-for="(label, li) in item.stats.labels"
              :key="li"
              class="flex items-center gap-3"
            >
              <span class="w-28 text-xs text-gray-600 dark:text-gray-300 text-right flex-shrink-0 truncate">{{ label }}</span>
              <div class="flex-1 h-7 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                <div
                  :class="['h-full rounded-lg transition-all duration-700', barColors[li % barColors.length]]"
                  :style="{ width: barWidth(item.stats.data![li], maxBarValue(item.stats.data)) }"
                />
              </div>
              <span class="w-8 text-xs font-bold text-gray-700 dark:text-gray-300 flex-shrink-0 text-right">
                {{ item.stats.data![li] }}
              </span>
            </div>
          </div>

          <!-- Text responses -->
          <div v-else-if="item.stats.type === 'text' && item.stats.responses" class="space-y-2">
            <div v-if="item.stats.responses.length === 0" class="text-sm text-gray-400 italic">
              No text responses yet.
            </div>
            <div
              v-for="(resp, ri) in item.stats.responses"
              :key="ri"
              class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300"
            >
              {{ resp }}
            </div>
            <p v-if="item.stats.total > 50" class="text-xs text-gray-400 italic">
              Showing 50 of {{ item.stats.total }} responses.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Responses Tab -->
    <div v-else-if="activeTab === 'responses'">
      <div v-if="responses.data.length === 0" class="text-center py-16">
        <MessageSquare class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
        <p class="text-gray-500 dark:text-gray-400">No responses yet. Share the link to start collecting feedback.</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="response in responses.data"
          :key="response.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
          <div class="flex items-center justify-between px-5 py-4 bg-gray-50 dark:bg-gray-700/40 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-[#42b6c5]/20 flex items-center justify-center text-sm font-bold text-[#42b6c5]">
                {{ response.is_anonymous ? '?' : (displayName(response).charAt(0).toUpperCase()) }}
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ displayName(response) }}</p>
                <p v-if="!response.is_anonymous && response.respondent_email" class="text-xs text-gray-400">{{ response.respondent_email }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-400">{{ formatDate(response.created_at) }}</p>
              <p v-if="response.ip_address" class="text-xs text-gray-300 dark:text-gray-500 mt-0.5">IP: {{ response.ip_address }}</p>
            </div>
          </div>

          <div class="p-5 space-y-4">
            <div v-for="answer in response.answers" :key="answer.question.id">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                {{ answer.question.question }}
              </p>
              <p v-if="answer.answer" class="text-sm text-gray-800 dark:text-gray-200 px-3 py-2 bg-gray-50 dark:bg-gray-900 rounded-lg">
                {{ answer.answer }}
              </p>
              <p v-else class="text-sm text-gray-400 italic">No answer provided</p>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="responses.links && responses.links.length > 3" class="flex justify-center gap-1 mt-6">
          <template v-for="link in responses.links" :key="link.label">
            <Link
              v-if="link.url" :href="link.url"
              :class="[
                'px-3 py-2 text-sm rounded-lg transition-colors',
                link.active
                  ? 'bg-[#42b6c5] text-white font-semibold'
                  : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
              ]"
              v-html="link.label"
            />
            <span v-else class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </div>

  <ConfirmationModal
    :open="showDeleteModal"
    title="Delete Feedback Form"
    :description="`Are you sure you want to delete '${form.title}'? All responses will be permanently deleted.`"
    confirm-text="Delete"
    variant="destructive"
    :processing="deleteProcessing"
    @confirm="confirmDelete"
    @update:open="showDeleteModal = $event"
  />
</template>
