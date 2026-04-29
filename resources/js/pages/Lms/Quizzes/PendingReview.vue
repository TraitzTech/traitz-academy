<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'

defineProps<{
  quiz: {
    id: number
    title: string
    course: { id: number; title: string }
    lesson: { id: number; title: string } | null
    pass_mark_percentage: number
  }
  attempt: {
    id: number
    submitted_at: string | null
  }
}>()
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'My Courses', href: '/dashboard/my-courses' },
    { title: 'Quiz', href: `/dashboard/quizzes/${quiz.id}` },
  ]">
    <Head :title="`${quiz.title} — Submitted`" />

    <div class="lms-page">
    <div class="mx-auto max-w-2xl lms-panel border-[#42b6c5]/30 bg-[#42b6c5]/5 text-center dark:bg-[#42b6c5]/10">
      <h1 class="lms-title">{{ quiz.title }}</h1>
      <p class="lms-subtitle">
        {{ quiz.course.title }}<span v-if="quiz.lesson"> · {{ quiz.lesson.title }}</span>
      </p>
      <p class="mt-6 text-gray-700 dark:text-gray-200">
        Your answers have been submitted to your instructor for review. You will see your score and feedback here after they grade your attempt.
      </p>
      <p v-if="attempt.submitted_at" class="mt-2 text-xs text-gray-500">
        Submitted {{ new Date(attempt.submitted_at).toLocaleString() }}
      </p>
      <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
        Pass mark for reference: <strong>{{ quiz.pass_mark_percentage }}%</strong> (your instructor decides the final result).
      </p>
      <div class="mt-8 flex flex-wrap justify-center gap-3">
        <Link
          :href="`/dashboard/quizzes/${quiz.id}/attempts/${attempt.id}/result`"
          class="lms-btn-primary"
        >
          View result (when graded)
        </Link>
        <Link
          href="/dashboard/my-courses"
          class="lms-btn-outline"
        >
          Back to My Courses
        </Link>
      </div>
    </div>
    </div>
  </AppLayout>
</template>
