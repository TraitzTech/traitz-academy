<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Question {
  id: number
  question: string
  type: 'multiple_choice' | 'text' | 'boolean'
  options: string[] | null
  points: number
  sort_order: number
}

interface Interview {
  id: number
  title: string
  description: string | null
  passing_score: number
  time_limit_minutes: number | null
  questions: Question[]
}

interface Props {
  interview: Interview
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const form = useForm({
  answers: props.interview.questions.map(q => ({
    question_id: q.id,
    answer: '',
  })),
})

const currentStep = ref(0)
const totalQuestions = computed(() => props.interview.questions.length)
const currentQuestion = computed(() => props.interview.questions[currentStep.value])
const progress = computed(() => ((currentStep.value + 1) / totalQuestions.value) * 100)
const isLastQuestion = computed(() => currentStep.value === totalQuestions.value - 1)

// Timer
const timeLeft = ref<number | null>(null)
let timerInterval: ReturnType<typeof setInterval> | null = null

const formattedTime = computed(() => {
  if (timeLeft.value === null) return null
  const mins = Math.floor(timeLeft.value / 60)
  const secs = timeLeft.value % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
})

const isTimeWarning = computed(() => timeLeft.value !== null && timeLeft.value <= 60)

onMounted(() => {
  if (props.interview.time_limit_minutes) {
    timeLeft.value = props.interview.time_limit_minutes * 60
    timerInterval = setInterval(() => {
      if (timeLeft.value !== null) {
        timeLeft.value--
        if (timeLeft.value <= 0) {
          clearInterval(timerInterval!)
          submitInterview()
        }
      }
    }, 1000)
  }
})

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})

const nextQuestion = () => {
  if (currentStep.value < totalQuestions.value - 1) {
    currentStep.value++
  }
}

const prevQuestion = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const goToQuestion = (index: number) => {
  currentStep.value = index
}

const submitInterview = () => {
  const unanswered = form.answers.filter(a => !a.answer).length
  if (unanswered > 0 && timeLeft.value !== 0) {
    if (!confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to submit?`)) {
      return
    }
  }

  form.post(`/interviews/${props.interview.id}/submit`, {
    onSuccess: () => {
      toast.success('Interview submitted successfully!')
    },
    onError: () => {
      toast.error('Failed to submit interview.')
    },
  })
}
</script>

<template>
  <div>
    <Head :title="`${interview.title} - Interview`" />

    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ interview.title }}</h1>
            <p v-if="interview.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ interview.description }}</p>
          </div>
          <div v-if="formattedTime" class="flex-shrink-0">
            <div
              :class="isTimeWarning ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 animate-pulse' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
              class="px-4 py-2 rounded-lg text-lg font-mono font-bold"
            >
              ⏱ {{ formattedTime }}
            </div>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4">
          <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
            <span>Question {{ currentStep + 1 }} of {{ totalQuestions }}</span>
            <span>{{ Math.round(progress) }}% complete</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div
              class="bg-[#42b6c5] h-2 rounded-full transition-all duration-300"
              :style="{ width: `${progress}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Question Navigation Dots -->
      <div class="flex flex-wrap gap-2 mb-6">
        <button
          v-for="(q, index) in interview.questions"
          :key="q.id"
          @click="goToQuestion(index)"
          :class="[
            'w-8 h-8 rounded-full text-xs font-semibold transition-colors',
            index === currentStep
              ? 'bg-[#42b6c5] text-white'
              : form.answers[index]?.answer
                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600'
          ]"
        >
          {{ index + 1 }}
        </button>
      </div>

      <!-- Question Card -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 mb-6">
        <div class="flex items-start gap-4 mb-6">
          <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-[#42b6c5] text-white font-bold">
            {{ currentStep + 1 }}
          </span>
          <div class="flex-1">
            <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ currentQuestion.question }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ currentQuestion.points }} points</p>
          </div>
        </div>

        <!-- Multiple Choice -->
        <div v-if="currentQuestion.type === 'multiple_choice' && currentQuestion.options" class="space-y-3">
          <label
            v-for="(option, oIndex) in currentQuestion.options"
            :key="oIndex"
            class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-colors"
            :class="form.answers[currentStep].answer === option
              ? 'border-[#42b6c5] bg-[#42b6c5]/5 dark:bg-[#42b6c5]/10'
              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
          >
            <input
              type="radio"
              :name="`question-${currentQuestion.id}`"
              :value="option"
              v-model="form.answers[currentStep].answer"
              class="text-[#42b6c5] focus:ring-[#42b6c5]"
            />
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ String.fromCharCode(65 + oIndex) }}.</span>
            <span class="text-gray-900 dark:text-gray-100">{{ option }}</span>
          </label>
        </div>

        <!-- Boolean -->
        <div v-else-if="currentQuestion.type === 'boolean'" class="space-y-3">
          <label
            v-for="opt in ['True', 'False']"
            :key="opt"
            class="flex items-center gap-3 p-4 border-2 rounded-lg cursor-pointer transition-colors"
            :class="form.answers[currentStep].answer === opt
              ? 'border-[#42b6c5] bg-[#42b6c5]/5 dark:bg-[#42b6c5]/10'
              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
          >
            <input
              type="radio"
              :name="`question-${currentQuestion.id}`"
              :value="opt"
              v-model="form.answers[currentStep].answer"
              class="text-[#42b6c5] focus:ring-[#42b6c5]"
            />
            <span class="text-gray-900 dark:text-gray-100">{{ opt }}</span>
          </label>
        </div>

        <!-- Text -->
        <div v-else>
          <textarea
            v-model="form.answers[currentStep].answer"
            rows="6"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            placeholder="Type your answer here..."
          ></textarea>
        </div>
      </div>

      <!-- Navigation -->
      <div class="flex items-center justify-between">
        <button
          @click="prevQuestion"
          :disabled="currentStep === 0"
          class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          ← Previous
        </button>

        <div class="flex gap-3">
          <button
            v-if="!isLastQuestion"
            @click="nextQuestion"
            class="px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors"
          >
            Next →
          </button>
          <button
            @click="submitInterview"
            :disabled="form.processing"
            :class="isLastQuestion
              ? 'px-8 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50'
              : 'px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50'"
          >
            {{ form.processing ? 'Submitting...' : 'Submit Interview' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
