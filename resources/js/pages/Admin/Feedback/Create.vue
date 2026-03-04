<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ChevronDown, ChevronUp, GripVertical, PlusCircle, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
const toast = useToast()

interface Question {
  question: string
  type: 'text' | 'multiple_choice'
  options: string[]
  required: boolean
}

const form = useForm({
  title: '',
  description: '',
  is_active: true,
  allow_anonymous: true,
  send_thank_you_email: true,
  closes_at: '' as string,
  questions: [
    { question: '', type: 'text' as const, options: [] as string[], required: true },
  ] as Question[],
})

const expandedQuestions = ref<number[]>([0])

const toggleQuestion = (index: number) => {
  const pos = expandedQuestions.value.indexOf(index)
  if (pos >= 0) {
    expandedQuestions.value.splice(pos, 1)
  } else {
    expandedQuestions.value.push(index)
  }
}

const addQuestion = () => {
  form.questions.push({ question: '', type: 'text', options: [], required: true })
  expandedQuestions.value.push(form.questions.length - 1)
}

const removeQuestion = (index: number) => {
  if (form.questions.length <= 1) {
    toast.error('A form must have at least one question.')
    return
  }
  form.questions.splice(index, 1)
}

const onTypeChange = (qIndex: number) => {
  const q = form.questions[qIndex]
  if (q.type === 'multiple_choice') {
    q.options = ['', '', '', '']
  } else {
    q.options = []
  }
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

const submit = () => {
  form.post('/admin/feedback', {
    onSuccess: () => toast.success('Feedback form created!'),
    onError: () => toast.error('Please fix the errors and try again.'),
  })
}
</script>

<template>
  <Head title="Create Feedback Form" />

  <div class="p-4 lg:p-8 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6 lg:mb-8">
      <Link
        href="/admin/feedback"
        class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </Link>
      <div>
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-100">Create Feedback Form</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Design questions for interns to answer</p>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- Basic Info Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <h2 class="text-base font-bold text-gray-800 dark:text-gray-200 mb-5">Form Details</h2>

        <div class="space-y-4">
          <!-- Title -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
              Form Title <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              placeholder="e.g. Internship Experience Q1 2026"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
            <textarea
              v-model="form.description"
              placeholder="Briefly describe what this feedback form is about..."
              rows="3"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40 resize-none"
            />
          </div>

          <!-- Closes At -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Close Date <span class="text-xs font-normal text-gray-400">(optional)</span></label>
            <input
              v-model="form.closes_at"
              type="datetime-local"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40"
            />
            <p v-if="form.errors.closes_at" class="mt-1 text-xs text-red-500">{{ form.errors.closes_at }}</p>
          </div>

          <!-- Toggles -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <label
              v-for="(item, key) in [
                { field: 'is_active', label: 'Active', desc: 'Accept responses now' },
                { field: 'allow_anonymous', label: 'Allow Anonymous', desc: 'No name/email required' },
                { field: 'send_thank_you_email', label: 'Thank-You Email', desc: 'Send email on submit' },
              ]"
              :key="key"
              class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer"
            >
              <input
                v-if="item.field === 'is_active'" v-model="form.is_active" type="checkbox"
                class="mt-0.5 w-4 h-4 accent-[#42b6c5]"
              />
              <input
                v-else-if="item.field === 'allow_anonymous'" v-model="form.allow_anonymous" type="checkbox"
                class="mt-0.5 w-4 h-4 accent-[#42b6c5]"
              />
              <input
                v-else v-model="form.send_thank_you_email" type="checkbox"
                class="mt-0.5 w-4 h-4 accent-[#42b6c5]"
              />
              <div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ item.label }}</p>
                <p class="text-xs text-gray-400">{{ item.desc }}</p>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Questions -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-bold text-gray-800 dark:text-gray-200">
            Questions <span class="text-[#42b6c5]">({{ form.questions.length }})</span>
          </h2>
        </div>

        <p v-if="form.errors.questions" class="text-sm text-red-500">{{ form.errors.questions }}</p>

        <div
          v-for="(question, qIndex) in form.questions"
          :key="qIndex"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
          <!-- Question header -->
          <div
            class="flex items-center gap-3 px-5 py-4 cursor-pointer select-none"
            @click="toggleQuestion(qIndex)"
          >
            <GripVertical class="w-4 h-4 text-gray-300 dark:text-gray-600 flex-shrink-0" />
            <span class="flex-shrink-0 w-7 h-7 flex items-center justify-center bg-[#42b6c5]/10 text-[#42b6c5] text-sm font-bold rounded-full">
              {{ qIndex + 1 }}
            </span>
            <span class="flex-1 text-sm font-semibold text-gray-700 dark:text-gray-300 truncate">
              {{ question.question || `Question ${qIndex + 1}` }}
            </span>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 capitalize">
              {{ question.type === 'multiple_choice' ? 'Multiple Choice' : 'Text' }}
            </span>
            <button
              type="button"
              @click.stop="removeQuestion(qIndex)"
              class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
            >
              <Trash2 class="w-4 h-4" />
            </button>
            <ChevronDown v-if="!expandedQuestions.includes(qIndex)" class="w-4 h-4 text-gray-400" />
            <ChevronUp v-else class="w-4 h-4 text-gray-400" />
          </div>

          <!-- Question body -->
          <div v-if="expandedQuestions.includes(qIndex)" class="px-5 pb-5 border-t border-gray-100 dark:border-gray-700 pt-4 space-y-4">
            <!-- Question text -->
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                Question Text <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="question.question"
                placeholder="Enter your question..."
                rows="2"
                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40 resize-none"
              />
              <p v-if="form.errors[`questions.${qIndex}.question`]" class="mt-1 text-xs text-red-500">
                {{ form.errors[`questions.${qIndex}.question`] }}
              </p>
            </div>

            <!-- Type & Required -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Type</label>
                <select
                  v-model="question.type"
                  @change="onTypeChange(qIndex)"
                  class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40"
                >
                  <option value="text">Text Response</option>
                  <option value="multiple_choice">Multiple Choice</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Required</label>
                <label class="flex items-center gap-2 px-3 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer">
                  <input v-model="question.required" type="checkbox" class="w-4 h-4 accent-[#42b6c5]" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">{{ question.required ? 'Yes' : 'No' }}</span>
                </label>
              </div>
            </div>

            <!-- Multiple choice options -->
            <div v-if="question.type === 'multiple_choice'" class="space-y-2">
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                Answer Options <span class="text-red-500">*</span>
              </label>
              <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex gap-2">
                <input
                  v-model="question.options[oIndex]"
                  type="text"
                  :placeholder="`Option ${oIndex + 1}`"
                  class="flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40"
                />
                <button
                  type="button"
                  @click="removeOption(qIndex, oIndex)"
                  :disabled="question.options.length <= 2"
                  class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
              <button
                type="button"
                @click="addOption(qIndex)"
                class="flex items-center gap-2 text-sm text-[#42b6c5] hover:text-[#35a3b2] font-semibold mt-1"
              >
                <PlusCircle class="w-4 h-4" />
                Add Option
              </button>
              <p v-if="form.errors[`questions.${qIndex}.options`]" class="text-xs text-red-500">
                {{ form.errors[`questions.${qIndex}.options`] }}
              </p>
            </div>
          </div>
        </div>

        <!-- Add question button -->
        <button
          type="button"
          @click="addQuestion"
          class="w-full py-3.5 border-2 border-dashed border-[#42b6c5]/40 text-[#42b6c5] rounded-xl text-sm font-semibold hover:border-[#42b6c5] hover:bg-[#42b6c5]/5 transition-colors flex items-center justify-center gap-2"
        >
          <PlusCircle class="w-5 h-5" />
          Add Question
        </button>
      </div>

      <!-- Submit -->
      <div class="flex justify-end gap-3 pt-2">
        <Link
          href="/admin/feedback"
          class="px-5 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2.5 bg-[#42b6c5] text-white text-sm font-semibold rounded-lg hover:bg-[#35a3b2] transition-colors disabled:opacity-60 flex items-center gap-2"
        >
          <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ form.processing ? 'Creating…' : 'Create Form' }}
        </button>
      </div>
    </form>
  </div>
</template>
