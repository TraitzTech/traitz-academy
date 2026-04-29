<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Edit2, GripVertical, PlusCircle, Save, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

interface QuizQuestion {
  id: number
  question: string
  type: 'multiple_choice' | 'multiple_select' | 'true_false' | 'short_answer'
  options: string[] | null
  correct_answer: Array<number | string> | null
  explanation: string | null
  points: number
  sort_order: number
}

interface Quiz {
  id: number
  title: string
  instructions: string | null
  pass_mark_percentage: string
  max_attempts: number | null
  is_required: boolean
  reveal_answers: boolean
  questions: QuizQuestion[]
}

const props = defineProps<{
  course: { id: number; title: string }
  lesson: { id: number; title: string; type: string }
  quiz: Quiz | null
}>()

const settingsForm = useForm({
  title: props.quiz?.title ?? `${props.lesson.title} Quiz`,
  instructions: props.quiz?.instructions ?? '',
  pass_mark_percentage: Number(props.quiz?.pass_mark_percentage ?? 60),
  max_attempts: props.quiz?.max_attempts ?? 3,
  is_required: props.quiz?.is_required ?? true,
  reveal_answers: props.quiz?.reveal_answers ?? true,
})

const questionForm = useForm({
  question: '',
  type: 'multiple_choice' as QuizQuestion['type'],
  options: ['', '', '', ''],
  correct_answer: 0 as number | number[] | string,
  explanation: '',
  points: 1,
})

const editingQuestionId = ref<number | null>(null)

function saveSettings() {
  settingsForm.put(`/tutor/courses/${props.course.id}/lessons/${props.lesson.id}/quiz`, { preserveScroll: true })
}

function resetQuestionForm() {
  questionForm.question = ''
  questionForm.type = 'multiple_choice'
  questionForm.options = ['', '', '', '']
  questionForm.correct_answer = 0
  questionForm.explanation = ''
  questionForm.points = 1
  editingQuestionId.value = null
}

function loadQuestionForEdit(q: QuizQuestion) {
  editingQuestionId.value = q.id
  questionForm.question = q.question
  questionForm.type = q.type
  questionForm.options = q.options && q.options.length ? [...q.options] : ['', '']
  if (q.type === 'multiple_select') {
    questionForm.correct_answer = (q.correct_answer ?? []).map((v) => Number(v))
  } else if (q.type === 'short_answer') {
    questionForm.correct_answer = String((q.correct_answer ?? [''])[0] ?? '')
  } else if (q.type === 'true_false') {
    questionForm.correct_answer = String((q.correct_answer ?? ['false'])[0] ?? 'false')
  } else {
    questionForm.correct_answer = Number((q.correct_answer ?? [0])[0] ?? 0)
  }
  questionForm.explanation = q.explanation ?? ''
  questionForm.points = q.points
}

function submitQuestion() {
  if (!props.quiz) return

  const payload = {
    question: questionForm.question,
    type: questionForm.type,
    options: questionForm.type === 'true_false' || questionForm.type === 'short_answer' ? [] : questionForm.options,
    correct_answer: questionForm.correct_answer,
    explanation: questionForm.explanation,
    points: questionForm.points,
  }

  if (editingQuestionId.value) {
    questionForm.put(`/tutor/quizzes/${props.quiz.id}/questions/${editingQuestionId.value}`, {
      data: payload,
      preserveScroll: true,
      onSuccess: () => resetQuestionForm(),
    })
    return
  }

  questionForm.post(`/tutor/quizzes/${props.quiz.id}/questions`, {
    data: payload,
    preserveScroll: true,
    onSuccess: () => resetQuestionForm(),
  })
}

function deleteQuestion(id: number) {
  if (!props.quiz) return
  router.delete(`/tutor/quizzes/${props.quiz.id}/questions/${id}`, { preserveScroll: true })
}

function moveQuestion(from: number, to: number) {
  if (!props.quiz || to < 0 || to >= props.quiz.questions.length) return
  const ids = props.quiz.questions.map((q) => q.id)
  ;[ids[from], ids[to]] = [ids[to], ids[from]]
  router.post(`/tutor/quizzes/${props.quiz.id}/questions/reorder`, { order: ids }, { preserveScroll: true })
}

function toggleMultiSelectOption(index: number, checked: boolean) {
  const current = Array.isArray(questionForm.correct_answer) ? [...questionForm.correct_answer] : []
  const next = checked ? [...current, index] : current.filter((v) => v !== index)
  questionForm.correct_answer = Array.from(new Set(next))
}
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'My Courses', href: '/tutor/courses' },
    { title: course.title, href: `/tutor/courses/${course.id}/edit` },
    { title: 'Quiz Builder', href: `/tutor/courses/${course.id}/lessons/${lesson.id}/quiz` },
  ]">
    <Head :title="`Quiz Builder - ${lesson.title}`" />

    <div class="mb-6 flex items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-[#000928] dark:text-white">Quiz Builder</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ lesson.title }}</p>
      </div>
      <div class="flex items-center gap-2">
        <Link v-if="quiz" :href="`/tutor/quizzes/${quiz.id}/attempts`" class="rounded-lg border border-[#42b6c5] px-4 py-2 text-sm font-semibold text-[#42b6c5] hover:bg-[#42b6c5]/10">
          View Attempts
        </Link>
        <Link :href="`/tutor/courses/${course.id}/edit`" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">
          Back to Course
        </Link>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <div class="rounded-xl bg-white p-6 shadow dark:bg-gray-800">
          <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Quiz Settings</h2>
          <form class="grid gap-4 md:grid-cols-2" @submit.prevent="saveSettings">
            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
              <input v-model="settingsForm.title" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>
            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Instructions</label>
              <textarea v-model="settingsForm.instructions" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Pass mark (%)</label>
              <input v-model.number="settingsForm.pass_mark_percentage" type="number" min="0" max="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Max attempts</label>
              <input v-model.number="settingsForm.max_attempts" type="number" min="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"><input v-model="settingsForm.is_required" type="checkbox" class="rounded" /> Required to complete lesson</label>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"><input v-model="settingsForm.reveal_answers" type="checkbox" class="rounded" /> Reveal answers after submission</label>
            <div class="md:col-span-2 flex justify-end">
              <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white hover:bg-[#000928]">
                <Save class="h-4 w-4" /> Save Quiz
              </button>
            </div>
          </form>
        </div>

        <div v-if="quiz" class="rounded-xl bg-white p-6 shadow dark:bg-gray-800">
          <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Questions</h2>

          <div class="mb-6 space-y-3">
            <div v-for="(q, idx) in quiz.questions" :key="q.id" class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
              <div class="mb-2 flex items-start justify-between gap-2">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                  <GripVertical class="h-4 w-4" />
                  <span>Q{{ idx + 1 }} · {{ q.type.replace('_', ' ') }} · {{ q.points }} pt{{ q.points > 1 ? 's' : '' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <button type="button" @click="moveQuestion(idx, idx - 1)" class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-gray-100">Up</button>
                  <button type="button" @click="moveQuestion(idx, idx + 1)" class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-gray-100">Down</button>
                  <button type="button" @click="loadQuestionForEdit(q)" class="rounded p-1.5 text-gray-500 hover:bg-gray-100"><Edit2 class="h-4 w-4" /></button>
                  <button type="button" @click="deleteQuestion(q.id)" class="rounded p-1.5 text-red-500 hover:bg-red-50"><Trash2 class="h-4 w-4" /></button>
                </div>
              </div>
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ q.question }}</p>
            </div>
          </div>

          <form class="space-y-3 rounded-lg border border-dashed border-gray-300 p-4 dark:border-gray-600" @submit.prevent="submitQuestion">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ editingQuestionId ? 'Edit question' : 'Add question' }}</h3>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Question</label>
              <textarea v-model="questionForm.question" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>
            <div class="grid gap-3 md:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Type</label>
                <select v-model="questionForm.type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  <option value="multiple_choice">Multiple choice</option>
                  <option value="multiple_select">Multiple select</option>
                  <option value="true_false">True / False</option>
                  <option value="short_answer">Short answer</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Points</label>
                <input v-model.number="questionForm.points" type="number" min="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
              </div>
            </div>

            <div v-if="questionForm.type === 'multiple_choice' || questionForm.type === 'multiple_select'" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Options</label>
              <div v-for="(_, i) in questionForm.options" :key="i" class="flex items-center gap-2">
                <input v-model="questionForm.options[i]" type="text" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" :placeholder="`Option ${i + 1}`" />
                <input
                  v-if="questionForm.type === 'multiple_choice'"
                  v-model.number="questionForm.correct_answer"
                  :value="i"
                  type="radio"
                  name="correct_single"
                />
                <input
                  v-else
                  type="checkbox"
                  :checked="Array.isArray(questionForm.correct_answer) && questionForm.correct_answer.includes(i)"
                  @change="toggleMultiSelectOption(i, ($event.target as HTMLInputElement).checked)"
                />
              </div>
              <button type="button" class="text-xs font-medium text-[#381998]" @click="questionForm.options.push('')">+ Add option</button>
            </div>

            <div v-else-if="questionForm.type === 'true_false'">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Correct answer</label>
              <select v-model="questionForm.correct_answer" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="true">True</option>
                <option value="false">False</option>
              </select>
            </div>

            <div v-else>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Expected answer</label>
              <input v-model="questionForm.correct_answer" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Explanation (optional)</label>
              <textarea v-model="questionForm.explanation" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>

            <div class="flex gap-2">
              <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white hover:bg-[#35919e]">
                <PlusCircle class="h-4 w-4" /> {{ editingQuestionId ? 'Update Question' : 'Add Question' }}
              </button>
              <button v-if="editingQuestionId" type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700" @click="resetQuestionForm">Cancel</button>
            </div>
          </form>
        </div>

        <div v-else class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-600 shadow dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
          Save quiz settings first, then start adding questions.
        </div>
      </div>

      <div class="space-y-4">
        <div class="rounded-xl bg-white p-5 shadow dark:bg-gray-800">
          <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Question Types</h3>
          <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Multiple choice (single correct option)</li>
            <li>Multiple select (more than one correct option)</li>
            <li>True/False</li>
            <li>Short answer (exact match grading)</li>
          </ul>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
