<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { CheckSquare, ChevronLeft, ChevronRight, Loader2, User } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import PublicLayout from '@/layouts/PublicLayout.vue'

interface Question {
  id: number
  question: string
  type: 'text' | 'multiple_choice'
  options: string[] | null
  required: boolean
  sort_order: number
}

interface FeedbackFormData {
  id: number
  title: string
  description: string | null
  slug: string
  allow_anonymous: boolean
  send_thank_you_email: boolean
  closes_at: string | null
  questions: Question[]
}

const props = defineProps<{
  form: FeedbackFormData
  authUser: { name: string; email: string } | null
}>()

const page = usePage()

const isAnonymous = ref(false)
const currentStep = ref<'info' | 'questions'>('info')

const submitForm = useForm({
  is_anonymous: false,
  respondent_name: props.authUser?.name ?? '',
  respondent_email: props.authUser?.email ?? '',
  answers: {} as Record<number, string>,
})

// Pre-fill answers map
props.form.questions.forEach((q) => {
  submitForm.answers[q.id] = ''
})

const canSkipInfo = computed(() => {
  if (isAnonymous.value) { return true }
  return (submitForm.respondent_name.trim().length > 0)
})

const attemptedSubmit = ref(false)

const requiredUnanswered = computed(() => {
  return props.form.questions.filter((q) => {
    return q.required && !submitForm.answers[q.id]?.trim()
  })
})

const isQuestionInvalid = (questionId: number) => {
  if (!attemptedSubmit.value) { return false }
  const question = props.form.questions.find((q) => q.id === questionId)
  return question?.required && !submitForm.answers[questionId]?.trim()
}

const proceed = () => {
  if (!canSkipInfo.value) { return }
  submitForm.is_anonymous = isAnonymous.value
  currentStep.value = 'questions'
}

const submit = () => {
  attemptedSubmit.value = true
  if (requiredUnanswered.value.length > 0) { return }
  submitForm.is_anonymous = isAnonymous.value
  submitForm.post(`/feedback/${props.form.slug}`)
}

const progressPercent = computed(() => {
  const answered = props.form.questions.filter((q) => !!submitForm.answers[q.id]?.trim()).length
  return props.form.questions.length > 0 ? Math.round((answered / props.form.questions.length) * 100) : 0
})
</script>

<template>
  <Head :title="`${form.title} — Feedback`" />

  <PublicLayout>
    <div class="min-h-screen bg-gradient-to-br from-[#f0fafc] via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 py-10 px-4">
      <div class="max-w-2xl mx-auto">

        <!-- Brand header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-14 h-14 bg-[#42b6c5] rounded-2xl shadow-lg mb-4">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
          </div>
          <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ form.title }}</h1>
          <p v-if="form.description" class="mt-2 text-gray-500 dark:text-gray-400 text-sm max-w-lg mx-auto leading-relaxed">
            {{ form.description }}
          </p>
          <p class="mt-2 text-xs text-gray-400">
            {{ form.questions.length }} {{ form.questions.length === 1 ? 'question' : 'questions' }}
            <span v-if="form.allow_anonymous"> · Anonymous option available</span>
          </p>
        </div>

        <!-- Step: Who are you? -->
        <transition name="slide-fade" mode="out-in">
          <div v-if="currentStep === 'info'" key="info">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-6 lg:p-8">
              <div class="flex items-center gap-2 mb-6">
                <div class="w-8 h-8 rounded-full bg-[#42b6c5] flex items-center justify-center text-white text-sm font-bold">1</div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">About You</h2>
              </div>

              <!-- Anonymous toggle -->
              <div
                v-if="form.allow_anonymous"
                @click="isAnonymous = !isAnonymous"
                :class="[
                  'mb-6 p-4 rounded-xl border-2 cursor-pointer transition-all select-none',
                  isAnonymous
                    ? 'border-purple-400 bg-purple-50 dark:bg-purple-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-purple-300',
                ]"
              >
                <div class="flex items-center gap-3">
                  <div
                    :class="[
                      'w-5 h-5 rounded-full border-2 flex-shrink-0 transition-colors',
                      isAnonymous ? 'border-purple-500 bg-purple-500' : 'border-gray-300',
                    ]"
                  >
                    <div v-if="isAnonymous" class="w-full h-full flex items-center justify-center">
                      <div class="w-2 h-2 bg-white rounded-full" />
                    </div>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Submit Anonymously</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">No name or email will be recorded</p>
                  </div>
                </div>
              </div>

              <!-- Name & email (only when not anonymous) -->
              <div v-if="!isAnonymous" class="space-y-4">
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Your Name <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input
                      v-model="submitForm.respondent_name"
                      type="text"
                      placeholder="Enter your name"
                      :disabled="!!authUser"
                      class="w-full pl-9 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40 disabled:opacity-60 disabled:cursor-not-allowed"
                    />
                  </div>
                  <p v-if="submitForm.errors.respondent_name" class="mt-1 text-xs text-red-500">{{ submitForm.errors.respondent_name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Email Address
                    <span v-if="form.send_thank_you_email" class="text-xs font-normal text-gray-400">(for thank-you email)</span>
                  </label>
                  <input
                    v-model="submitForm.respondent_email"
                    type="email"
                    placeholder="your@email.com"
                    :disabled="!!authUser"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/40 disabled:opacity-60 disabled:cursor-not-allowed"
                  />
                </div>
              </div>

              <button
                @click="proceed"
                :disabled="!canSkipInfo"
                class="mt-6 w-full py-3.5 bg-[#42b6c5] text-white font-semibold rounded-xl hover:bg-[#35a3b2] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                Continue to Questions
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Step: Questions -->
          <div v-else-if="currentStep === 'questions'" key="questions">
            <!-- Progress bar -->
            <div class="mb-4">
              <div class="flex justify-between text-xs text-gray-400 mb-1.5">
                <span>{{ progressPercent }}% answered</span>
                <span>{{ props.form.questions.filter(q => !!submitForm.answers[q.id]?.trim()).length }} / {{ props.form.questions.length }}</span>
              </div>
              <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div
                  class="h-full bg-[#42b6c5] rounded-full transition-all duration-500"
                  :style="{ width: `${progressPercent}%` }"
                />
              </div>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
              <div
                v-for="(question, index) in form.questions"
                :key="question.id"
                :class="[
                  'bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 border-2 transition-colors',
                  isQuestionInvalid(question.id)
                    ? 'border-red-400 dark:border-red-500'
                    : 'border-gray-100 dark:border-gray-700',
                ]"
              >
                <div class="flex items-start gap-3 mb-4">
                  <span class="flex-shrink-0 w-7 h-7 flex items-center justify-center bg-[#42b6c5]/10 text-[#42b6c5] text-sm font-bold rounded-full mt-0.5">
                    {{ index + 1 }}
                  </span>
                  <div>
                    <p class="font-semibold text-gray-800 dark:text-gray-200 leading-snug">
                      {{ question.question }}
                      <span v-if="question.required" class="text-red-500 ml-0.5">*</span>
                    </p>
                    <p v-if="!question.required" class="text-xs text-gray-400 mt-0.5">Optional</p>
                  </div>
                </div>

                <!-- Text response -->
                <textarea
                  v-if="question.type === 'text'"
                  v-model="submitForm.answers[question.id]"
                  :placeholder="question.required ? 'Your answer is required…' : 'Your answer (optional)…'"
                  rows="4"
                  :class="[
                    'w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 resize-none border',
                    isQuestionInvalid(question.id)
                      ? 'border-red-400 dark:border-red-500 focus:ring-red-400/40'
                      : 'border-gray-200 dark:border-gray-600 focus:ring-[#42b6c5]/40',
                  ]"
                />
                <p v-if="question.type === 'text' && isQuestionInvalid(question.id)" class="mt-1.5 text-xs text-red-500 dark:text-red-400">
                  This question requires an answer.
                </p>

                <!-- Multiple choice -->
                <div v-else-if="question.type === 'multiple_choice' && question.options" class="space-y-2.5">
                  <label
                    v-for="option in question.options"
                    :key="option"
                    :class="[
                      'flex items-center gap-3 px-4 py-3 rounded-xl border-2 cursor-pointer transition-all',
                      submitForm.answers[question.id] === option
                        ? 'border-[#42b6c5] bg-[#42b6c5]/5 dark:bg-[#42b6c5]/10'
                        : isQuestionInvalid(question.id)
                          ? 'border-red-300 dark:border-red-600 hover:border-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
                          : 'border-gray-200 dark:border-gray-600 hover:border-[#42b6c5]/50 hover:bg-gray-50 dark:hover:bg-gray-700/50',
                    ]"
                  >
                    <div
                      :class="[
                        'w-5 h-5 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition-colors',
                        submitForm.answers[question.id] === option
                          ? 'border-[#42b6c5] bg-[#42b6c5]'
                          : 'border-gray-300 dark:border-gray-500',
                      ]"
                      @click="submitForm.answers[question.id] = option"
                    >
                      <div v-if="submitForm.answers[question.id] === option" class="w-2 h-2 bg-white rounded-full" />
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ option }}</span>
                    <input
                      type="radio"
                      :name="`q_${question.id}`"
                      :value="option"
                      v-model="submitForm.answers[question.id]"
                      class="sr-only"
                    />
                  </label>
                  <p v-if="isQuestionInvalid(question.id)" class="mt-1.5 text-xs text-red-500 dark:text-red-400">
                    Please select an option.
                  </p>
                </div>
              </div>

              <!-- Required validation hint -->
              <div v-if="requiredUnanswered.length > 0 && attemptedSubmit" class="px-4 py-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-400">
                Please answer all required questions ({{ requiredUnanswered.length }} remaining) before submitting.
              </div>

              <!-- Action buttons -->
              <div class="flex gap-3 pt-2">
                <button
                  type="button"
                  @click="currentStep = 'info'"
                  class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                  <ChevronLeft class="w-4 h-4" />
                  Back
                </button>
                <button
                  type="submit"
                  :disabled="submitForm.processing"
                  class="flex-1 py-3.5 bg-[#42b6c5] text-white font-semibold rounded-xl hover:bg-[#35a3b2] transition-colors disabled:opacity-60 flex items-center justify-center gap-2 shadow-lg shadow-[#42b6c5]/20"
                >
                  <Loader2 v-if="submitForm.processing" class="w-4 h-4 animate-spin" />
                  <CheckSquare v-else class="w-4 h-4" />
                  {{ submitForm.processing ? 'Submitting…' : 'Submit Feedback' }}
                </button>
              </div>
            </form>
          </div>
        </transition>

        <p class="text-center text-xs text-gray-400 mt-8">
          Powered by <span class="font-semibold text-[#42b6c5]">Traitz Academy</span>
        </p>
      </div>
    </div>
  </PublicLayout>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.25s ease;
}
.slide-fade-enter-from {
  opacity: 0;
  transform: translateX(16px);
}
.slide-fade-leave-to {
  opacity: 0;
  transform: translateX(-16px);
}
</style>
