<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'

interface User {
  id: number
  name: string
  email: string
}

interface InterviewResponse {
  id: number
  user_id: number
  score: number
  total_points: number
  percentage: number
  passed: boolean
  status: string
  requires_manual_review: boolean
  reviewed_at: string | null
  started_at: string
  completed_at: string
  user: User
  application: { id: number; program: { title: string } } | null
}

interface Interview {
  id: number
  title: string
  description: string | null
  passing_score: number
  questions_count: number
  program: { id: number; title: string } | null
}

interface Props {
  interview: Interview
  responses: {
    data: InterviewResponse[]
    links: any[]
  }
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const formatDate = (date: string) => new Date(date).toLocaleDateString('en-US', {
  year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
})
</script>

<template>
  <div>
    <Head :title="`Responses - ${interview.title}`" />

    <!-- Header -->
    <div class="mb-8">
      <Link href="/admin/interviews" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Interviews
      </Link>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ interview.title }} — Responses</h2>
      <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
        <span v-if="interview.program">📚 {{ interview.program.title }}</span>
        <span>🎯 Passing: {{ interview.passing_score }}%</span>
        <span>👥 {{ responses.data.length }} response(s)</span>
      </div>
    </div>

    <!-- Responses Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div v-if="responses.data.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Candidate</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Score</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Percentage</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Result</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completed</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="response in responses.data" :key="response.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ response.user.name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ response.user.email }}</p>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ response.score }} / {{ response.total_points }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                    <div
                      class="h-2 rounded-full"
                      :class="response.passed ? 'bg-green-500' : 'bg-red-500'"
                      :style="{ width: `${Math.min(response.percentage, 100)}%` }"
                    ></div>
                  </div>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ response.percentage }}%</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="response.requires_manual_review && !response.reviewed_at"
                  class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                >
                  ⏳ Needs Review
                </span>
                <span v-else
                  :class="response.passed
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                  class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                >
                  {{ response.passed ? 'Passed' : 'Failed' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(response.completed_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <Link
                  :href="`/admin/interviews/${interview.id}/responses/${response.id}`"
                  class="text-[#42b6c5] hover:text-[#35919e] text-sm font-medium"
                >
                  {{ response.requires_manual_review && !response.reviewed_at ? 'Review & Score' : 'View Details' }}
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="p-12 text-center">
        <div class="inline-block p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
          <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <p class="text-gray-600 dark:text-gray-300 font-medium mb-2">No Responses Yet</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">No one has completed this interview yet.</p>
      </div>

      <!-- Pagination -->
      <div v-if="responses.links && responses.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-1">
        <template v-for="link in responses.links" :key="link.label">
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
  </div>
</template>
