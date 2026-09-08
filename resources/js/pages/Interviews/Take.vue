<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface Question {
    id: number;
    question: string;
    type: 'multiple_choice' | 'text' | 'boolean';
    options: string[] | null;
    points: number;
    sort_order: number;
}

interface Interview {
    id: number;
    title: string;
    description: string | null;
    passing_score: number;
    time_limit_minutes: number | null;
    questions: Question[];
}

interface Props {
    interview: Interview;
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const toast = useToast();

const form = useForm({
    answers: props.interview.questions.map((q) => ({
        question_id: q.id,
        answer: '',
    })),
});

const currentStep = ref(0);
const totalQuestions = computed(() => props.interview.questions.length);
const currentQuestion = computed(
    () => props.interview.questions[currentStep.value],
);
const progress = computed(
    () => ((currentStep.value + 1) / totalQuestions.value) * 100,
);
const isLastQuestion = computed(
    () => currentStep.value === totalQuestions.value - 1,
);

// Timer
const timeLeft = ref<number | null>(null);
let timerInterval: ReturnType<typeof setInterval> | null = null;

const formattedTime = computed(() => {
    if (timeLeft.value === null) return null;
    const mins = Math.floor(timeLeft.value / 60);
    const secs = timeLeft.value % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
});

const isTimeWarning = computed(
    () => timeLeft.value !== null && timeLeft.value <= 60,
);

onMounted(() => {
    if (props.interview.time_limit_minutes) {
        timeLeft.value = props.interview.time_limit_minutes * 60;
        timerInterval = setInterval(() => {
            if (timeLeft.value !== null) {
                timeLeft.value--;
                if (timeLeft.value <= 0) {
                    clearInterval(timerInterval!);
                    submitInterview();
                }
            }
        }, 1000);
    }
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});

const nextQuestion = () => {
    if (currentStep.value < totalQuestions.value - 1) {
        currentStep.value++;
    }
};

const prevQuestion = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const goToQuestion = (index: number) => {
    currentStep.value = index;
};

const submitInterview = () => {
    const unanswered = form.answers.filter((a) => !a.answer).length;
    if (unanswered > 0 && timeLeft.value !== 0) {
        if (
            !confirm(
                `You have ${unanswered} unanswered question(s). Are you sure you want to submit?`,
            )
        ) {
            return;
        }
    }

    form.post(`/interviews/${props.interview.id}/submit`, {
        onSuccess: () => {
            // Flash message handled by global watcher (InterviewController::submit flashes 'success'/'info')
        },
        onError: () => {
            toast.error('Failed to submit interview.');
        },
    });
};
</script>

<template>
    <div>
        <Head :title="`${interview.title} - Interview`" />

        <div class="mx-auto max-w-4xl">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1
                            class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                        >
                            {{ interview.title }}
                        </h1>
                        <p
                            v-if="interview.description"
                            class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                        >
                            {{ interview.description }}
                        </p>
                    </div>
                    <div v-if="formattedTime" class="flex-shrink-0">
                        <div
                            :class="
                                isTimeWarning
                                    ? 'animate-pulse bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            "
                            class="rounded-lg px-4 py-2 font-mono text-lg font-bold"
                        >
                            ⏱ {{ formattedTime }}
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-4">
                    <div
                        class="mb-2 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400"
                    >
                        <span
                            >Question {{ currentStep + 1 }} of
                            {{ totalQuestions }}</span
                        >
                        <span>{{ Math.round(progress) }}% complete</span>
                    </div>
                    <div
                        class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700"
                    >
                        <div
                            class="h-2 rounded-full bg-[#42b6c5] transition-all duration-300"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Question Navigation Dots -->
            <div class="mb-6 flex flex-wrap gap-2">
                <button
                    v-for="(q, index) in interview.questions"
                    :key="q.id"
                    @click="goToQuestion(index)"
                    :class="[
                        'h-8 w-8 rounded-full text-xs font-semibold transition-colors',
                        index === currentStep
                            ? 'bg-[#42b6c5] text-white'
                            : form.answers[index]?.answer
                              ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                              : 'bg-gray-200 text-gray-600 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600',
                    ]"
                >
                    {{ index + 1 }}
                </button>
            </div>

            <!-- Question Card -->
            <div
                class="mb-6 rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800"
            >
                <div class="mb-6 flex items-start gap-4">
                    <span
                        class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-[#42b6c5] font-bold text-white"
                    >
                        {{ currentStep + 1 }}
                    </span>
                    <div class="flex-1">
                        <p
                            class="text-lg font-medium text-gray-900 dark:text-gray-100"
                        >
                            {{ currentQuestion.question }}
                        </p>
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ currentQuestion.points }} points
                        </p>
                    </div>
                </div>

                <!-- Multiple Choice -->
                <div
                    v-if="
                        currentQuestion.type === 'multiple_choice' &&
                        currentQuestion.options
                    "
                    class="space-y-3"
                >
                    <label
                        v-for="(option, oIndex) in currentQuestion.options"
                        :key="oIndex"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border-2 p-4 transition-colors"
                        :class="
                            form.answers[currentStep].answer === option
                                ? 'border-[#42b6c5] bg-[#42b6c5]/5 dark:bg-[#42b6c5]/10'
                                : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
                        "
                    >
                        <input
                            type="radio"
                            :name="`question-${currentQuestion.id}`"
                            :value="option"
                            v-model="form.answers[currentStep].answer"
                            class="text-[#42b6c5] focus:ring-[#42b6c5]"
                        />
                        <span
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                            >{{ String.fromCharCode(65 + oIndex) }}.</span
                        >
                        <span class="text-gray-900 dark:text-gray-100">{{
                            option
                        }}</span>
                    </label>
                </div>

                <!-- Boolean -->
                <div
                    v-else-if="currentQuestion.type === 'boolean'"
                    class="space-y-3"
                >
                    <label
                        v-for="opt in ['True', 'False']"
                        :key="opt"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border-2 p-4 transition-colors"
                        :class="
                            form.answers[currentStep].answer === opt
                                ? 'border-[#42b6c5] bg-[#42b6c5]/5 dark:bg-[#42b6c5]/10'
                                : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
                        "
                    >
                        <input
                            type="radio"
                            :name="`question-${currentQuestion.id}`"
                            :value="opt"
                            v-model="form.answers[currentStep].answer"
                            class="text-[#42b6c5] focus:ring-[#42b6c5]"
                        />
                        <span class="text-gray-900 dark:text-gray-100">{{
                            opt
                        }}</span>
                    </label>
                </div>

                <!-- Text -->
                <div v-else>
                    <textarea
                        v-model="form.answers[currentStep].answer"
                        rows="6"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        placeholder="Type your answer here..."
                    ></textarea>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between">
                <button
                    @click="prevQuestion"
                    :disabled="currentStep === 0"
                    class="rounded-lg border border-gray-300 px-6 py-3 font-semibold text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    ← Previous
                </button>

                <div class="flex gap-3">
                    <button
                        v-if="!isLastQuestion"
                        @click="nextQuestion"
                        class="rounded-lg bg-[#42b6c5] px-6 py-3 font-semibold text-white transition-colors hover:bg-[#35919e]"
                    >
                        Next →
                    </button>
                    <button
                        @click="submitInterview"
                        :disabled="form.processing"
                        :class="
                            isLastQuestion
                                ? 'rounded-lg bg-green-600 px-8 py-3 font-bold text-white transition-colors hover:bg-green-700 disabled:opacity-50'
                                : 'rounded-lg bg-gray-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-gray-700 disabled:opacity-50'
                        "
                    >
                        {{
                            form.processing
                                ? 'Submitting...'
                                : 'Submit Interview'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
