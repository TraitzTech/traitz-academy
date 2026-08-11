<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'

defineProps<{
  quiz: { id: number; title: string; course: { id: number; title: string }; lesson: { id: number; title: string } | null }
  attempts: { data: Array<{ id: number; status: string; score_percentage: string | null; passed: boolean | null; created_at: string; user: { name: string; email: string } }>; links: Array<{ url: string | null; label: string; active: boolean }> }
}>()
</script>

<template>
  <AppLayout>
    <Head :title="`Attempts - ${quiz.title}`" />
    <div class="mx-auto max-w-4xl">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-[#000928] dark:text-white">Quiz Attempts</h1>
        <p class="text-sm text-gray-500">{{ quiz.title }}</p>
      </div>
      <Link :href="`/tutor/courses/${quiz.course.id}/lessons/${quiz.lesson?.id}/quiz`" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold">Back to Builder</Link>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow dark:bg-gray-800">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-900/40">
          <tr><th class="px-4 py-3">Student</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Score</th><th class="px-4 py-3">Date</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody>
          <tr v-for="a in attempts.data" :key="a.id" class="border-t border-gray-100 dark:border-gray-700">
            <td class="px-4 py-3">{{ a.user.name }}</td>
            <td class="px-4 py-3">
              <span
                v-if="a.status === 'submitted'"
                class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
              >Awaiting review</span>
              <span v-else-if="a.status === 'graded'" class="text-gray-700 dark:text-gray-200">Graded</span>
              <span v-else>{{ a.status }}</span>
            </td>
            <td class="px-4 py-3">{{ a.score_percentage ?? '—' }}</td>
            <td class="px-4 py-3">{{ new Date(a.created_at).toLocaleString() }}</td>
            <td class="px-4 py-3 text-right"><Link :href="`/tutor/quizzes/${quiz.id}/attempts/${a.id}`" class="font-semibold text-[#381998]">View</Link></td>
          </tr>
        </tbody>
      </table>
    </div>
    </div>
  </AppLayout>
</template>
