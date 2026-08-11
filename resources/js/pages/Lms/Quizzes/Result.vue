<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'

defineProps<{
  quiz: {
    id: number
    title: string
    pass_mark_percentage: number
    reveal_answers: boolean
    questions: Array<{
      id: number
      question: string
      type: string
      options: string[]
      points: number
      submitted: unknown
      correct: unknown[] | null
      explanation: string | null
    }>
  }
  attempt: {
    id: number
    score_percentage: number
    passed: boolean
    submitted_at: string | null
    instructor_feedback: string | null
  }
}>()

function formatSubmitted(question: any) {
  if (question.type === 'multiple_choice') {
    return question.options?.[Number(question.submitted)] ?? 'No answer'
  }
  if (question.type === 'multiple_select') {
    if (!Array.isArray(question.submitted) || question.submitted.length === 0) return 'No answer'
    return question.submitted.map((i: number) => question.options?.[i] ?? '').join(', ')
  }
  if (question.type === 'true_false') {
    return String(question.submitted ?? 'No answer')
  }
  return String(question.submitted ?? 'No answer')
}

function formatCorrect(question: any) {
  if (!question.correct) return 'Hidden'
  if (question.type === 'multiple_choice') {
    return question.options?.[Number(question.correct[0])] ?? ''
  }
  if (question.type === 'multiple_select') {
    return question.correct.map((i: number) => question.options?.[i] ?? '').join(', ')
  }
  return String(question.correct[0] ?? '')
}
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'My Courses', href: '/dashboard/my-courses' },
    { title: 'Quiz Result', href: `/dashboard/quizzes/${quiz.id}/attempts/${attempt.id}/result` },
  ]">
    <Head :title="`${quiz.title} - Result`" />

    <div class="lms-page mx-auto max-w-3xl">
    <div class="mb-6 lms-panel">
      <h1 class="lms-title">{{ quiz.title }} — Result</h1>
      <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
        <span :class="attempt.passed ? 'rounded-full bg-green-100 px-3 py-1 font-semibold text-green-700' : 'rounded-full bg-red-100 px-3 py-1 font-semibold text-red-700'">
          {{ attempt.passed ? 'Passed' : 'Not Passed' }}
        </span>
        <span class="text-gray-600 dark:text-gray-300">Score: <strong>{{ Number(attempt.score_percentage).toFixed(2) }}%</strong></span>
        <span class="text-gray-600 dark:text-gray-300">Pass mark (reference): <strong>{{ quiz.pass_mark_percentage }}%</strong></span>
      </div>
      <p v-if="attempt.instructor_feedback" class="mt-4 rounded-lg border border-[#42b6c5]/30 bg-[#42b6c5]/5 p-4 text-left text-sm text-gray-700 dark:text-gray-200">
        <span class="font-semibold text-[#000928] dark:text-white">Instructor feedback</span><br />
        <span class="mt-1 block whitespace-pre-wrap">{{ attempt.instructor_feedback }}</span>
      </p>
    </div>

    <div class="space-y-4">
      <div v-for="(q, idx) in quiz.questions" :key="q.id" class="lms-panel-soft p-5">
        <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ idx + 1 }}. {{ q.question }}</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300"><span class="font-medium">Your answer:</span> {{ formatSubmitted(q) }}</p>
        <p v-if="quiz.reveal_answers" class="mt-1 text-sm text-gray-700 dark:text-gray-200"><span class="font-medium">Correct answer:</span> {{ formatCorrect(q) }}</p>
        <p v-if="quiz.reveal_answers && q.explanation" class="lms-subtitle">{{ q.explanation }}</p>
      </div>
    </div>

    <div class="mt-6">
      <Link :href="`/dashboard/quizzes/${quiz.id}`" class="lms-btn-primary">Retake / Continue</Link>
    </div>
    </div>
  </AppLayout>
</template>
