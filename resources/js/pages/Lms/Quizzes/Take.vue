<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { Download } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

interface QuizQuestion {
  id: number
  question: string
  type: 'multiple_choice' | 'multiple_select' | 'true_false' | 'short_answer'
  options: string[]
  points: number
}

interface LessonAttachment {
  id: number
  name: string
  file_url: string
  file_type: string | null
  file_size: number | null
  formatted_file_size: string
}

const props = defineProps<{
  quiz: {
    id: number
    title: string
    instructions: string | null
    pass_mark_percentage: number
    max_attempts: number | null
    reveal_answers: boolean
    questions: QuizQuestion[]
    course: { id: number; title: string }
    lesson: { id: number; title: string; attachments: LessonAttachment[] } | null
  }
  attempt: { id: number; answers: Record<string, unknown>; started_at: string | null }
  attemptCount: number
}>()

function attachmentUrl(path: string): string {
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  return `/storage/${path}`
}

const answers = ref<Record<string, any>>({ ...(props.attempt.answers ?? {}) })
const submitting = ref(false)
const saving = ref(false)

const totalPoints = computed(() => props.quiz.questions.reduce((sum, q) => sum + (q.points ?? 0), 0))

function onAnswerChange(questionId: number, value: any) {
  answers.value[String(questionId)] = value
  saveProgress()
}

let saveTimer: ReturnType<typeof setTimeout> | null = null
function saveProgress() {
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(() => {
    void persistProgress()
  }, 350)
}

async function persistProgress() {
  saving.value = true
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  try {
    const res = await fetch(`/dashboard/quizzes/${props.quiz.id}/attempts/${props.attempt.id}/progress`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ answers: answers.value }),
    })
    if (!res.ok) {
      console.error('Failed to save quiz progress', await res.text())
    }
  } catch (e) {
    console.error('Quiz progress save failed', e)
  } finally {
    saving.value = false
  }
}

function toggleMulti(questionId: number, optionIndex: number, checked: boolean) {
  const key = String(questionId)
  const current = Array.isArray(answers.value[key]) ? [...answers.value[key]] : []
  const next = checked ? [...current, optionIndex] : current.filter((x: number) => x !== optionIndex)
  onAnswerChange(questionId, Array.from(new Set(next)))
}

function submitQuiz() {
  submitting.value = true
  router.post(`/dashboard/quizzes/${props.quiz.id}/attempts/${props.attempt.id}/submit`, { answers: answers.value }, {
    preserveScroll: true,
    onFinish: () => { submitting.value = false },
  })
}
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'My Courses', href: '/dashboard/my-courses' },
    { title: 'Quiz', href: `/dashboard/quizzes/${quiz.id}` },
  ]">
    <Head :title="quiz.title" />

    <div class="lms-page mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="lms-title">{{ quiz.title }}</h1>
        <p class="lms-subtitle">{{ quiz.course.title }} <span v-if="quiz.lesson">· {{ quiz.lesson.title }}</span></p>
      </div>
      <div class="text-right text-xs text-gray-500 dark:text-gray-400">
        <p>Attempt #{{ attemptCount + 1 }}<span v-if="quiz.max_attempts"> / {{ quiz.max_attempts }}</span></p>
        <p>{{ totalPoints }} total points</p>
        <p>{{ saving ? 'Saving...' : 'Progress saved automatically' }}</p>
      </div>
    </div>

    <div class="lms-panel">
      <div
        v-if="quiz.lesson?.attachments?.length"
        class="mb-6 rounded-xl border border-gray-100 bg-gray-50/90 px-4 py-4 dark:border-gray-700 dark:bg-gray-900/40 sm:px-5"
      >
        <h2 class="mb-2 text-sm font-semibold text-[#000928] dark:text-white">Resources</h2>
        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
          Files for this lesson — download before or while you take the quiz.
        </p>
        <ul class="space-y-2">
          <li v-for="file in quiz.lesson.attachments" :key="file.id">
            <a
              :href="attachmentUrl(file.file_url)"
              target="_blank"
              rel="noopener noreferrer"
              class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm shadow-sm transition-colors hover:border-[#42b6c5]/40 dark:border-gray-600 dark:bg-gray-800"
            >
              <span class="min-w-0 flex-1 font-medium text-gray-800 dark:text-gray-100">{{ file.name }}</span>
              <span class="shrink-0 text-xs text-gray-500">{{ file.formatted_file_size }}</span>
              <Download class="h-4 w-4 shrink-0 text-[#42b6c5]" aria-hidden="true" />
            </a>
          </li>
        </ul>
      </div>

      <p v-if="quiz.instructions" class="mb-6 whitespace-pre-wrap text-sm text-gray-600 dark:text-gray-300">{{ quiz.instructions }}</p>

      <div class="space-y-5">
        <div v-for="(q, index) in quiz.questions" :key="q.id" class="lms-panel-soft p-4">
          <div class="mb-3 flex items-start justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ index + 1 }}. {{ q.question }}</h3>
            <span class="text-xs text-gray-500">{{ q.points }} pt{{ q.points > 1 ? 's' : '' }}</span>
          </div>

          <div v-if="q.type === 'multiple_choice'" class="space-y-2">
            <label v-for="(opt, optIndex) in q.options" :key="optIndex" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
              <input
                type="radio"
                :name="`q_${q.id}`"
                :value="optIndex"
                :checked="Number(answers[String(q.id)]) === optIndex"
                @change="onAnswerChange(q.id, optIndex)"
              />
              <span>{{ opt }}</span>
            </label>
          </div>

          <div v-else-if="q.type === 'multiple_select'" class="space-y-2">
            <label v-for="(opt, optIndex) in q.options" :key="optIndex" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
              <input
                type="checkbox"
                :checked="Array.isArray(answers[String(q.id)]) && answers[String(q.id)].includes(optIndex)"
                @change="toggleMulti(q.id, optIndex, ($event.target as HTMLInputElement).checked)"
              />
              <span>{{ opt }}</span>
            </label>
          </div>

          <div v-else-if="q.type === 'true_false'" class="space-y-2">
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
              <input type="radio" :name="`q_${q.id}`" value="true" :checked="answers[String(q.id)] === 'true'" @change="onAnswerChange(q.id, 'true')" />
              <span>True</span>
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
              <input type="radio" :name="`q_${q.id}`" value="false" :checked="answers[String(q.id)] === 'false'" @change="onAnswerChange(q.id, 'false')" />
              <span>False</span>
            </label>
          </div>

          <div v-else>
            <input
              type="text"
              :value="answers[String(q.id)] ?? ''"
              @input="onAnswerChange(q.id, ($event.target as HTMLInputElement).value)"
              class="lms-input"
              placeholder="Type your answer"
            />
          </div>
        </div>
      </div>

      <div class="mt-6 flex items-center justify-between">
        <Link :href="'/dashboard/my-courses'" class="text-sm font-medium text-gray-500 hover:text-gray-700">Back to my courses</Link>
        <button @click="submitQuiz" :disabled="submitting" class="lms-btn-primary disabled:opacity-50">
          {{ submitting ? 'Submitting...' : 'Submit Quiz' }}
        </button>
      </div>
    </div>
    </div>
  </AppLayout>
</template>
