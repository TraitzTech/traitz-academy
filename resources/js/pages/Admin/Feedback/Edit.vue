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

interface FeedbackFormData {
  id: number
  title: string
  description: string | null
  is_active: boolean
  allow_anonymous: boolean
  send_thank_you_email: boolean
  closes_at: string | null
  questions: Array<{
    id: number
    question: string
    type: 'text' | 'multiple_choice'
    options: string[] | null
    required: boolean
    sort_order: number
  }>
}

const props = defineProps<{ form: FeedbackFormData }>()

const editForm = useForm({
  title: props.form.title,
  description: props.form.description ?? '',
  is_active: props.form.is_active,
  allow_anonymous: props.form.allow_anonymous,
  send_thank_you_email: props.form.send_thank_you_email,
  closes_at: props.form.closes_at ?? '',
  questions: props.form.questions.map((q) => ({
    question: q.question,
    type: q.type,
    options: q.options ?? [],
    required: q.required,
  })) as Question[],
})

const expandedQuestions = ref<number[]>(props.form.questions.map((_, i) => i))

const toggleQuestion = (index: number) => {
  const pos = expandedQuestions.value.indexOf(index)
  if (pos >= 0) {
    expandedQuestions.value.splice(pos, 1)
  } else {
    expandedQuestions.value.push(index)
  }
}

const addQuestion = () => {
  editForm.questions.push({ question: '', type: 'text', options: [], required: true })
  expandedQuestions.value.push(editForm.questions.length - 1)
}

const removeQuestion = (index: number) => {
  if (editForm.questions.length <= 1) {
    toast.error('A form must have at least one question.')
    return
  }
  editForm.questions.splice(index, 1)
}

const onTypeChange = (qIndex: number) => {
  const q = editForm.questions[qIndex]
  if (q.type === 'multiple_choice') {
    q.options = ['', '', '', '']
  } else {
    q.options = []
  }
}

const addOption = (qIndex: number) => {
  editForm.questions[qIndex].options.push('')
}

const removeOption = (qIndex: number, oIndex: number) => {
  if (editForm.questions[qIndex].options.length <= 2) { return }
  editForm.questions[qIndex].options.splice(oIndex, 1)
}

const submit = () => {
  const payload = {
    ...editForm.data(),
    questions: editForm.questions.map((q) => ({
      ...q,
      options: q.type === 'multiple_choice' ? q.options : null,
    })),
  }
  editForm.transform(() => payload).put(`/admin/feedback/${props.form.id}`, {
    onSuccess: () => toast.success('Feedback form updated!'),
    onError: () => toast.error('Please fix the errors and try again.'),
  })
}
</script>

<template>
  <Head title="Edit Feedback Form" />

  <div class="p-4 lg:p-8 max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6 lg:mb-8">
      <Link
        :href="`/admin/feedback/${props.form.id}`"
        class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </Link>
      <div>
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-100">Edit Feedback Form</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ props.form.title }}</p>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
        <h2 class="text-base font-bold text-gray-800 dark:text-gray-200 mb-5">Form Details</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
              Form Title <span class="text-red-500">*</span>
            </label>
            <input
              v-model="editForm.title"
              type="text"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40"
            />
            <p v-if="editForm.errors.title" class="mt-1 text-xs text-red-500">{{ editForm.errors.title }}</p>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
            <textarea
              v-model="editForm.description"
              rows="3"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40 resize-none"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Close Date</label>
            <input
              v-model="editForm.closes_at"
              type="datetime-local"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40"
            />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <label class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer">
              <input v-model="editForm.is_active" type="checkbox" class="mt-0.5 w-4 h-4 accent-[#42b6c5]" />
              <div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Active</p>
                <p class="text-xs text-gray-400">Accept responses now</p>
              </div>
            </label>
            <label class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer">
              <input v-model="editForm.allow_anonymous" type="checkbox" class="mt-0.5 w-4 h-4 accent-[#42b6c5]" />
              <div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Allow Anonymous</p>
                <p class="text-xs text-gray-400">No name/email required</p>
              </div>
            </label>
            <label class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer">
              <input v-model="editForm.send_thank_you_email" type="checkbox" class="mt-0.5 w-4 h-4 accent-[#42b6c5]" />
              <div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Thank-You Email</p>
                <p class="text-xs text-gray-400">Send email on submit</p>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Questions -->
      <div class="space-y-3">
        <h2 class="text-base font-bold text-gray-800 dark:text-gray-200">
          Questions <span class="text-[#42b6c5]">({{ editForm.questions.length }})</span>
        </h2>

        <div
          v-for="(question, qIndex) in editForm.questions"
          :key="qIndex"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
          <div class="flex items-center gap-3 px-5 py-4 cursor-pointer select-none" @click="toggleQuestion(qIndex)">
            <GripVertical class="w-4 h-4 text-gray-300 dark:text-gray-600 flex-shrink-0" />
            <span class="flex-shrink-0 w-7 h-7 flex items-center justify-center bg-[#42b6c5]/10 text-[#42b6c5] text-sm font-bold rounded-full">
              {{ qIndex + 1 }}
            </span>
            <span class="flex-1 text-sm font-semibold text-gray-700 dark:text-gray-300 truncate">
              {{ question.question || `Question ${qIndex + 1}` }}
            </span>
            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
              {{ question.type === 'multiple_choice' ? 'Multiple Choice' : 'Text' }}
            </span>
            <button
              type="button" @click.stop="removeQuestion(qIndex)"
              class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
            >
              <Trash2 class="w-4 h-4" />
            </button>
            <ChevronDown v-if="!expandedQuestions.includes(qIndex)" class="w-4 h-4 text-gray-400" />
            <ChevronUp v-else class="w-4 h-4 text-gray-400" />
          </div>

          <div v-if="expandedQuestions.includes(qIndex)" class="px-5 pb-5 border-t border-gray-100 dark:border-gray-700 pt-4 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                Question Text <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="question.question" rows="2" placeholder="Enter your question..."
                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40 resize-none"
              />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Type</label>
                <select
                  v-model="question.type" @change="onTypeChange(qIndex)"
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
            <div v-if="question.type === 'multiple_choice'" class="space-y-2">
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                Answer Options <span class="text-red-500">*</span>
              </label>
              <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex gap-2">
                <input
                  v-model="question.options[oIndex]" type="text" :placeholder="`Option ${oIndex + 1}`"
                  class="flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40"
                />
                <button
                  type="button" @click="removeOption(qIndex, oIndex)"
                  :disabled="question.options.length <= 2"
                  class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
              <button type="button" @click="addOption(qIndex)" class="flex items-center gap-2 text-sm text-[#42b6c5] hover:text-[#35a3b2] font-semibold mt-1">
                <PlusCircle class="w-4 h-4" /> Add Option
              </button>
            </div>
          </div>
        </div>

        <button
          type="button" @click="addQuestion"
          class="w-full py-3.5 border-2 border-dashed border-[#42b6c5]/40 text-[#42b6c5] rounded-xl text-sm font-semibold hover:border-[#42b6c5] hover:bg-[#42b6c5]/5 transition-colors flex items-center justify-center gap-2"
        >
          <PlusCircle class="w-5 h-5" /> Add Question
        </button>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <Link
          :href="`/admin/feedback/${props.form.id}`"
          class="px-5 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
          Cancel
        </Link>
        <button
          type="submit" :disabled="editForm.processing"
          class="px-6 py-2.5 bg-[#42b6c5] text-white text-sm font-semibold rounded-lg hover:bg-[#35a3b2] transition-colors disabled:opacity-60 flex items-center gap-2"
        >
          <svg v-if="editForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
        </button>
      </div>
    </form>
  </div>
</template>
