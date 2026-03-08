<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { BarChart3, CheckCircle, ChevronDown, Copy, ExternalLink, Hash, MessageSquare, Trash2, Type, Users } from 'lucide-vue-next'
import { computed, ref } from 'vue'

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

const showDeleteResponseModal = ref(false)
const deleteResponseId = ref<number | null>(null)
const deleteResponseProcessing = ref(false)

const openDeleteResponseModal = (responseId: number) => {
  deleteResponseId.value = responseId
  showDeleteResponseModal.value = true
}

const confirmDeleteResponse = () => {
  if (!deleteResponseId.value) { return }
  deleteResponseProcessing.value = true
  router.delete(`/admin/feedback/${props.form.id}/responses/${deleteResponseId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Response deleted.')
      showDeleteResponseModal.value = false
      deleteResponseId.value = null
    },
    onFinish: () => {
      deleteResponseProcessing.value = false
    },
  })
}

const maxBarValue = (data?: number[]) => Math.max(...(data ?? [1]), 1)

const barWidth = (value: number, max: number) => `${Math.round((value / max) * 100)}%`

const barColors = ['bg-[#42b6c5]', 'bg-blue-400', 'bg-purple-400', 'bg-pink-400', 'bg-amber-400', 'bg-green-400']

const expandedAnalytics = ref<Set<number>>(new Set(
  props.analytics.slice(0, 3).map((a) => a.question_id),
))

const toggleAnalytic = (id: number) => {
  const next = new Set(expandedAnalytics.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  expandedAnalytics.value = next
}

const isAnalyticExpanded = (id: number) => expandedAnalytics.value.has(id)

const expandAllAnalytics = () => {
  expandedAnalytics.value = new Set(props.analytics.map((a) => a.question_id))
}

const collapseAllAnalytics = () => {
  expandedAnalytics.value = new Set()
}

const textResponsesLimit = ref<Record<number, number>>({})

const visibleTextResponses = (item: QuestionStat) => {
  const limit = textResponsesLimit.value[item.question_id] ?? 5
  return item.stats.responses?.slice(0, limit) ?? []
}

const showMoreTexts = (questionId: number) => {
  textResponsesLimit.value[questionId] = (textResponsesLimit.value[questionId] ?? 5) + 10
}

const barPercent = (value: number, total: number) => {
  if (total === 0) { return '0%' }
  return `${Math.round((value / total) * 100)}%`
}

const analyticSummary = (item: QuestionStat) => {
  if (item.stats.type === 'chart' && item.stats.labels && item.stats.data) {
    const maxIdx = item.stats.data.indexOf(Math.max(...item.stats.data))
    return `Top: ${item.stats.labels[maxIdx]} (${barPercent(item.stats.data[maxIdx], item.stats.total)})`
  }
  return `${item.stats.total} text ${item.stats.total === 1 ? 'response' : 'responses'}`
}

const answeredAnalyticsCount = computed(() =>
  props.analytics.filter((a) => a.stats.total > 0).length,
)

const expandedResponses = ref<Set<number>>(new Set())

const toggleResponse = (id: number) => {
  const next = new Set(expandedResponses.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  expandedResponses.value = next
}

const isExpanded = (id: number) => expandedResponses.value.has(id)

const expandAll = () => {
  expandedResponses.value = new Set(props.responses.data.map((r) => r.id))
}

const collapseAll = () => {
  expandedResponses.value = new Set()
}

const answeredCount = (response: ResponseRow) => {
  return response.answers.filter((a) => a.answer?.trim()).length
}

const answerPreview = (response: ResponseRow) => {
  const first = response.answers.find((a) => a.answer?.trim())
  if (!first?.answer) { return '' }
  return first.answer.length > 80 ? first.answer.slice(0, 80) + '…' : first.answer
}
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

      <div v-else>
        <!-- Summary bar + controls -->
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ analytics.length }} questions &middot; {{ answeredAnalyticsCount }} with responses
          </p>
          <div class="flex items-center gap-2">
            <button
              @click="expandAllAnalytics"
              class="text-xs font-medium text-[#42b6c5] hover:text-[#35a3b2] transition-colors"
            >
              Expand all
            </button>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <button
              @click="collapseAllAnalytics"
              class="text-xs font-medium text-[#42b6c5] hover:text-[#35a3b2] transition-colors"
            >
              Collapse all
            </button>
          </div>
        </div>

        <div class="space-y-3">
          <div
            v-for="(item, index) in analytics"
            :key="item.question_id"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-shadow hover:shadow-md"
          >
            <!-- Clickable header -->
            <div
              class="flex items-center justify-between px-5 py-3.5 cursor-pointer select-none"
              @click="toggleAnalytic(item.question_id)"
            >
              <div class="flex items-center gap-3 min-w-0 flex-1">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center text-sm font-bold rounded-full"
                  :class="item.stats.type === 'chart'
                    ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'bg-purple-100 text-purple-500 dark:bg-purple-900/30 dark:text-purple-400'"
                >
                  {{ index + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <p class="font-semibold text-sm text-gray-800 dark:text-gray-200 truncate">{{ item.question }}</p>
                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium rounded-full"
                      :class="item.stats.type === 'chart'
                        ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                        : 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400'"
                    >
                      <Hash v-if="item.stats.type === 'chart'" class="w-2.5 h-2.5" />
                      <Type v-else class="w-2.5 h-2.5" />
                      {{ item.stats.type === 'chart' ? 'Choice' : 'Text' }}
                    </span>
                  </div>
                  <div class="flex items-center gap-2 mt-0.5">
                    <p class="text-xs text-gray-400">{{ item.stats.total }} {{ item.stats.total === 1 ? 'response' : 'responses' }}</p>
                    <!-- Collapsed summary -->
                    <template v-if="!isAnalyticExpanded(item.question_id) && item.stats.total > 0">
                      <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                      <p class="text-xs text-gray-400 italic truncate">{{ analyticSummary(item) }}</p>
                    </template>
                  </div>
                </div>
              </div>
              <ChevronDown
                :class="[
                  'w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-3',
                  isAnalyticExpanded(item.question_id) ? 'rotate-180' : '',
                ]"
              />
            </div>

            <!-- Collapsible body -->
            <transition
              enter-active-class="transition-all duration-200 ease-out"
              leave-active-class="transition-all duration-150 ease-in"
              enter-from-class="max-h-0 opacity-0"
              enter-to-class="max-h-[3000px] opacity-100"
              leave-from-class="max-h-[3000px] opacity-100"
              leave-to-class="max-h-0 opacity-0"
            >
              <div v-show="isAnalyticExpanded(item.question_id)" class="overflow-hidden">
                <div class="border-t border-gray-100 dark:border-gray-700 px-5 py-4">

                  <!-- Bar chart for multiple choice -->
                  <div v-if="item.stats.type === 'chart' && item.stats.labels && item.stats.data" class="space-y-3">
                    <div
                      v-for="(label, li) in item.stats.labels"
                      :key="li"
                      class="flex items-center gap-3"
                    >
                      <span class="w-32 text-xs text-gray-600 dark:text-gray-300 text-right flex-shrink-0 truncate" :title="label">{{ label }}</span>
                      <div class="flex-1 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden relative">
                        <div
                          :class="['h-full rounded-lg transition-all duration-700', barColors[li % barColors.length]]"
                          :style="{ width: barWidth(item.stats.data![li], maxBarValue(item.stats.data)) }"
                        />
                        <span
                          v-if="item.stats.data![li] > 0"
                          class="absolute inset-y-0 right-2 flex items-center text-[10px] font-bold text-gray-500 dark:text-gray-400"
                        >
                          {{ barPercent(item.stats.data![li], item.stats.total) }}
                        </span>
                      </div>
                      <span class="w-8 text-xs font-bold text-gray-700 dark:text-gray-300 flex-shrink-0 text-right">
                        {{ item.stats.data![li] }}
                      </span>
                    </div>
                  </div>

                  <!-- Text responses -->
                  <div v-else-if="item.stats.type === 'text' && item.stats.responses" class="space-y-2">
                    <div v-if="item.stats.responses.length === 0" class="text-sm text-gray-400 italic py-2">
                      No text responses yet.
                    </div>
                    <template v-else>
                      <div
                        v-for="(resp, ri) in visibleTextResponses(item)"
                        :key="ri"
                        class="flex gap-3 items-start"
                      >
                        <div class="flex-shrink-0 w-1 self-stretch rounded-full bg-purple-200 dark:bg-purple-800" />
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed py-1">
                          {{ resp }}
                        </p>
                      </div>
                      <button
                        v-if="(textResponsesLimit[item.question_id] ?? 5) < item.stats.responses.length"
                        @click.stop="showMoreTexts(item.question_id)"
                        class="mt-2 text-xs font-medium text-[#42b6c5] hover:text-[#35a3b2] transition-colors"
                      >
                        Show more ({{ item.stats.responses.length - (textResponsesLimit[item.question_id] ?? 5) }} remaining)
                      </button>
                      <p v-if="item.stats.total > 50" class="text-xs text-gray-400 italic mt-1">
                        Showing top 50 of {{ item.stats.total }} responses.
                      </p>
                    </template>
                  </div>

                </div>
              </div>
            </transition>
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

      <div v-else>
        <!-- Expand / Collapse controls -->
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Showing {{ responses.data.length }} of {{ responses.total }} responses
          </p>
          <div class="flex items-center gap-2">
            <button
              @click="expandAll"
              class="text-xs font-medium text-[#42b6c5] hover:text-[#35a3b2] transition-colors"
            >
              Expand all
            </button>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <button
              @click="collapseAll"
              class="text-xs font-medium text-[#42b6c5] hover:text-[#35a3b2] transition-colors"
            >
              Collapse all
            </button>
          </div>
        </div>

        <div class="space-y-3">
        <div
          v-for="response in responses.data"
          :key="response.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-shadow hover:shadow-md"
        >
          <!-- Clickable header -->
          <div
            class="flex items-center justify-between px-5 py-3.5 cursor-pointer select-none"
            @click="toggleResponse(response.id)"
          >
            <div class="flex items-center gap-3 min-w-0 flex-1">
              <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold"
                :class="response.is_anonymous
                  ? 'bg-purple-100 text-purple-500 dark:bg-purple-900/30 dark:text-purple-400'
                  : 'bg-[#42b6c5]/20 text-[#42b6c5]'"
              >
                {{ response.is_anonymous ? '?' : (displayName(response).charAt(0).toUpperCase()) }}
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ displayName(response) }}</p>
                  <span class="flex-shrink-0 px-1.5 py-0.5 text-[10px] font-medium rounded-full"
                    :class="response.is_anonymous
                      ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400'
                      : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'"
                  >
                    {{ response.is_anonymous ? 'Anonymous' : 'Identified' }}
                  </span>
                </div>
                <div class="flex items-center gap-2 mt-0.5">
                  <p class="text-xs text-gray-400">{{ formatDate(response.created_at) }}</p>
                  <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                  <p class="text-xs text-gray-400">{{ answeredCount(response) }}/{{ response.answers.length }} answered</p>
                </div>
                <!-- Preview snippet when collapsed -->
                <p v-if="!isExpanded(response.id) && answerPreview(response)" class="mt-1 text-xs text-gray-400 dark:text-gray-500 truncate italic">
                  "{{ answerPreview(response) }}"
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0 ml-3">
              <button
                @click.stop="openDeleteResponseModal(response.id)"
                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                title="Delete response"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
              <ChevronDown
                :class="[
                  'w-4 h-4 text-gray-400 transition-transform duration-200',
                  isExpanded(response.id) ? 'rotate-180' : '',
                ]"
              />
            </div>
          </div>

          <!-- Collapsible answers body -->
          <transition
            enter-active-class="transition-all duration-200 ease-out"
            leave-active-class="transition-all duration-150 ease-in"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[2000px] opacity-100"
            leave-from-class="max-h-[2000px] opacity-100"
            leave-to-class="max-h-0 opacity-0"
          >
            <div v-show="isExpanded(response.id)" class="overflow-hidden">
              <div class="border-t border-gray-100 dark:border-gray-700">
                <!-- Meta info row -->
                <div v-if="response.respondent_email || response.ip_address" class="px-5 py-2.5 bg-gray-50/50 dark:bg-gray-700/20 flex items-center gap-4 flex-wrap text-xs text-gray-400">
                  <span v-if="!response.is_anonymous && response.respondent_email" class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ response.respondent_email }}
                  </span>
                  <span v-if="response.ip_address" class="flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    {{ response.ip_address }}
                  </span>
                </div>

                <!-- Answers -->
                <div class="px-5 py-4 space-y-3">
                  <div v-for="answer in response.answers" :key="answer.question.id" class="flex gap-3">
                    <div class="flex-shrink-0 w-1 self-stretch rounded-full"
                      :class="answer.answer ? 'bg-[#42b6c5]/30' : 'bg-gray-200 dark:bg-gray-700'"
                    />
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-0.5">
                        {{ answer.question.question }}
                      </p>
                      <p v-if="answer.answer" class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed">
                        {{ answer.answer }}
                      </p>
                      <p v-else class="text-sm text-gray-400 italic">No answer provided</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </transition>
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

  <ConfirmationModal
    :open="showDeleteResponseModal"
    title="Delete Response"
    description="Are you sure you want to delete this response? This action cannot be undone."
    confirm-text="Delete"
    variant="destructive"
    :processing="deleteResponseProcessing"
    @confirm="confirmDeleteResponse"
    @update:open="showDeleteResponseModal = $event"
  />
</template>
