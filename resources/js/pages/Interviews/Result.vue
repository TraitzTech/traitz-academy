<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

interface Answer {
  id: number
  answer: string | null
  is_correct: boolean
  points_earned: number
  question: {
    id: number
    question: string
    type: string
    options: string[] | null
    correct_answer: string | null
    points: number
  }
}

interface InterviewResponse {
  id: number
  score: number
  total_points: number
  percentage: number
  passed: boolean
  status: string
  requires_manual_review: boolean
  reviewed_at: string | null
  started_at: string
  completed_at: string
  answers: Answer[]
}

interface Interview {
  id: number
  title: string
  description: string | null
  passing_score: number
}

interface Props {
  interview: Interview
  response: InterviewResponse
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const isReviewed = computed(() => !props.response.requires_manual_review || props.response.reviewed_at !== null)
const isPendingReview = computed(() => props.response.requires_manual_review && props.response.reviewed_at === null)

const correctCount = computed(() => props.response.answers.filter(a => a.is_correct).length)
const totalCount = computed(() => props.response.answers.length)
const gradeColor = computed(() => {
  if (props.response.percentage >= 80) return 'text-green-600 dark:text-green-400'
  if (props.response.percentage >= 60) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
})

const gradeLabel = computed(() => {
  if (props.response.percentage >= 90) return 'Excellent!'
  if (props.response.percentage >= 80) return 'Great Job!'
  if (props.response.percentage >= 70) return 'Good'
  if (props.response.percentage >= 60) return 'Fair'
  return 'Needs Improvement'
})
</script>

<template>
  <div>
    <Head :title="`Results - ${interview.title}`" />

    <div class="max-w-4xl mx-auto">

      <!-- Pending Review State -->
      <template v-if="isPendingReview">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 mb-8 text-center">
          <div class="mb-6">
            <div class="text-6xl mb-4">⏳</div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Interview Submitted</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ interview.title }}</p>
          </div>

          <div class="max-w-md mx-auto mb-8">
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
              <div class="flex items-center justify-center gap-2 mb-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-semibold text-amber-800 dark:text-amber-300">Pending Admin Review</span>
              </div>
              <p class="text-sm text-amber-700 dark:text-amber-400">
                Your interview contains open-ended questions that require manual review by the admissions team.
                You will be notified once your results are ready.
              </p>
            </div>
          </div>

          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 inline-block">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Submitted on {{ new Date(response.completed_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}
            </p>
          </div>
        </div>
      </template>

      <!-- Full Results State (auto-graded or reviewed) -->
      <template v-else>
        <!-- Result Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 mb-8 text-center">
          <div class="mb-6">
            <div
              :class="response.passed ? 'text-green-500' : 'text-red-500'"
              class="text-6xl mb-4"
            >
              {{ response.passed ? '🎉' : '📝' }}
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
              {{ response.passed ? 'Congratulations!' : 'Interview Completed' }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400">{{ interview.title }}</p>
          </div>

          <!-- Score Circle -->
          <div class="flex justify-center mb-6">
            <div class="relative w-40 h-40">
              <svg class="w-40 h-40 transform -rotate-90" viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="54" fill="none" stroke-width="8" class="stroke-gray-200 dark:stroke-gray-700" />
                <circle
                  cx="60" cy="60" r="54" fill="none" stroke-width="8"
                  :stroke-dasharray="`${(response.percentage / 100) * 339.292} 339.292`"
                  stroke-linecap="round"
                  :class="response.passed ? 'stroke-green-500' : 'stroke-red-500'"
                />
              </svg>
              <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span :class="gradeColor" class="text-3xl font-bold">{{ response.percentage }}%</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">Score</span>
              </div>
            </div>
          </div>

          <!-- Stats Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ response.score }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Points Earned</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ response.total_points }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Total Points</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ correctCount }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Correct</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
              <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ totalCount - correctCount }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">Incorrect</p>
            </div>
          </div>

          <!-- Pass/Fail Badge -->
          <div class="flex justify-center gap-4 mb-6">
            <span
              :class="response.passed
                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800'
                : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800'"
              class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold border"
            >
              {{ response.passed ? '✓ Passed' : '✗ Not Passed' }} — {{ gradeLabel }}
            </span>
          </div>
          <p class="text-sm text-gray-500 dark:text-gray-400">Passing score: {{ interview.passing_score }}%</p>
        </div>

        <!-- Detailed Answers -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-8">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Answers</h2>
          </div>

          <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <div v-for="(answer, index) in response.answers" :key="answer.id" class="p-6">
              <div class="flex items-start gap-4">
                <span
                  :class="answer.is_correct
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                  class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold"
                >
                  {{ answer.is_correct ? '✓' : '✗' }}
                </span>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                    <span class="text-gray-500 dark:text-gray-400">Q{{ index + 1 }}.</span> {{ answer.question.question }}
                  </p>
                  <div class="flex gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3">
                    <span>{{ answer.points_earned }} / {{ answer.question.points }} pts</span>
                  </div>

                  <div class="space-y-2 text-sm">
                    <div class="flex items-start gap-2">
                      <span class="font-medium text-gray-500 dark:text-gray-400 min-w-[90px]">Your answer:</span>
                      <span :class="answer.is_correct ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                        {{ answer.answer || '(no answer)' }}
                      </span>
                    </div>
                    <div v-if="!answer.is_correct && answer.question.correct_answer" class="flex items-start gap-2">
                      <span class="font-medium text-gray-500 dark:text-gray-400 min-w-[90px]">Correct:</span>
                      <span class="text-green-700 dark:text-green-400">{{ answer.question.correct_answer }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Actions -->
      <div class="flex justify-center gap-4">
        <Link
          href="/dashboard"
          class="px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors"
        >
          Back to Dashboard
        </Link>
      </div>
    </div>
  </div>
</template>
