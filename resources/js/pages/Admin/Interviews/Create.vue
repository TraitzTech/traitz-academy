<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Program {
  id: number
  title: string
}

interface Question {
  question: string
  type: 'multiple_choice' | 'text' | 'boolean'
  options: string[]
  correct_answer: string
  points: number
}

interface Props {
  programs: Program[]
}

defineProps<Props>()
defineOptions({ layout: AppLayout })

const toast = useToast()

const form = useForm({
  title: '',
  description: '',
  program_id: '' as string | number,
  passing_score: 60,
  time_limit_minutes: null as number | null,
  is_active: true,
  questions: [
    { question: '', type: 'multiple_choice' as const, options: ['', '', '', ''], correct_answer: '', points: 10 },
  ] as Question[],
})

const addQuestion = () => {
  form.questions.push({
    question: '',
    type: 'multiple_choice',
    options: ['', '', '', ''],
    correct_answer: '',
    points: 10,
  })
}

const removeQuestion = (index: number) => {
  if (form.questions.length <= 1) {
    toast.error('An interview must have at least one question.')
    return
  }
  form.questions.splice(index, 1)
}

const addOption = (qIndex: number) => {
  form.questions[qIndex].options.push('')
}

const removeOption = (qIndex: number, oIndex: number) => {
  if (form.questions[qIndex].options.length <= 2) {
    return
  }
  form.questions[qIndex].options.splice(oIndex, 1)
}

const onTypeChange = (qIndex: number) => {
  const q = form.questions[qIndex]
  if (q.type === 'boolean') {
    q.options = ['True', 'False']
    q.correct_answer = ''
  } else if (q.type === 'text') {
    q.options = []
    q.correct_answer = ''
  } else {
    if (q.options.length < 2) {
      q.options = ['', '', '', '']
    }
    q.correct_answer = ''
  }
}

const expandedQuestions = ref<number[]>([0])

const toggleQuestion = (index: number) => {
  const pos = expandedQuestions.value.indexOf(index)
  if (pos >= 0) {
    expandedQuestions.value.splice(pos, 1)
  } else {
    expandedQuestions.value.push(index)
  }
}

const submit = () => {
  form.post('/admin/interviews', {
    onSuccess: () => {
      toast.success('Interview created successfully!')
    },
    onError: () => {
      toast.error('Failed to create interview. Please check the form.')
    },
  })
}
</script>

<template>
  <div>
    <Head title="Create Interview" />

    <!-- Header -->
    <div class="mb-8">
      <Link href="/admin/interviews" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Interviews
      </Link>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Create New Interview</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Set up interview questions for applicants</p>
    </div>

    <form @submit.prevent="submit" class="space-y-8">
      <!-- Interview Details -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Interview Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="e.g., Web Development Interview Assessment"
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="Brief description of the interview..."
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program (Optional)</label>
            <select
              v-model="form.program_id"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            >
              <option value="">No Program</option>
              <option v-for="program in programs" :key="program.id" :value="program.id">{{ program.title }}</option>
            </select>
            <p v-if="form.errors.program_id" class="mt-1 text-sm text-red-600">{{ form.errors.program_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Passing Score (%) *</label>
            <input
              v-model="form.passing_score"
              type="number"
              min="1"
              max="100"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.passing_score" class="mt-1 text-sm text-red-600">{{ form.errors.passing_score }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time Limit (minutes)</label>
            <input
              v-model="form.time_limit_minutes"
              type="number"
              min="1"
              max="480"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="Leave empty for no time limit"
            />
            <p v-if="form.errors.time_limit_minutes" class="mt-1 text-sm text-red-600">{{ form.errors.time_limit_minutes }}</p>
          </div>

          <div class="flex items-end">
            <label class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="rounded border-gray-300 dark:border-gray-600 text-[#42b6c5] focus:ring-[#42b6c5]"
              />
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active (visible to applicants)</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Questions -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Questions ({{ form.questions.length }})</h3>
          <button
            type="button"
            @click="addQuestion"
            class="px-4 py-2 bg-[#42b6c5] text-white text-sm font-medium rounded-lg hover:bg-[#35919e] transition-colors"
          >
            + Add Question
          </button>
        </div>

        <div class="space-y-4">
          <div
            v-for="(question, qIndex) in form.questions"
            :key="qIndex"
            class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
          >
            <!-- Question Header -->
            <div
              @click="toggleQuestion(qIndex)"
              class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700/50 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <div class="flex items-center gap-3">
                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Q{{ qIndex + 1 }}</span>
                <span class="text-sm text-gray-900 dark:text-gray-100 truncate max-w-md">
                  {{ question.question || 'New Question' }}
                </span>
                <span class="text-xs px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 capitalize">
                  {{ question.type.replace('_', ' ') }}
                </span>
                <span class="text-xs text-gray-500">{{ question.points }} pts</span>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click.stop="removeQuestion(qIndex)"
                  class="text-red-500 hover:text-red-700 text-sm"
                >
                  Remove
                </button>
                <svg :class="expandedQuestions.includes(qIndex) ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>

            <!-- Question Body -->
            <div v-show="expandedQuestions.includes(qIndex)" class="p-4 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Question *</label>
                <textarea
                  v-model="question.question"
                  rows="2"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
                  placeholder="Enter your question..."
                ></textarea>
                <p v-if="form.errors[`questions.${qIndex}.question`]" class="mt-1 text-sm text-red-600">{{ form.errors[`questions.${qIndex}.question`] }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                  <select
                    v-model="question.type"
                    @change="onTypeChange(qIndex)"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
                  >
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="boolean">True / False</option>
                    <option value="text">Text Answer</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Points *</label>
                  <input
                    v-model="question.points"
                    type="number"
                    min="1"
                    max="100"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
                  />
                </div>

                <div v-if="question.type !== 'text'">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correct Answer *</label>
                  <select
                    v-if="question.type === 'boolean'"
                    v-model="question.correct_answer"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
                  >
                    <option value="">Select answer</option>
                    <option value="True">True</option>
                    <option value="False">False</option>
                  </select>
                  <select
                    v-else
                    v-model="question.correct_answer"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
                  >
                    <option value="">Select correct option</option>
                    <option v-for="(opt, i) in question.options" :key="i" :value="opt" :disabled="!opt">
                      {{ opt || `Option ${i + 1} (empty)` }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Options for Multiple Choice -->
              <div v-if="question.type === 'multiple_choice'" class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Options</label>
                <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex items-center gap-2">
                  <span class="text-sm text-gray-500 dark:text-gray-400 w-6">{{ String.fromCharCode(65 + oIndex) }}.</span>
                  <input
                    v-model="question.options[oIndex]"
                    type="text"
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
                    :placeholder="`Option ${String.fromCharCode(65 + oIndex)}`"
                  />
                  <button
                    v-if="question.options.length > 2"
                    type="button"
                    @click="removeOption(qIndex, oIndex)"
                    class="p-1 text-red-500 hover:text-red-700"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                <button
                  type="button"
                  @click="addOption(qIndex)"
                  class="text-sm text-[#42b6c5] hover:text-[#35919e] font-medium"
                >
                  + Add Option
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex items-center justify-end gap-4">
        <Link
          href="/admin/interviews"
          class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-50"
        >
          {{ form.processing ? 'Creating...' : 'Create Interview' }}
        </button>
      </div>
    </form>
  </div>
</template>
