<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface Answer {
    id: number;
    answer: string | null;
    is_correct: boolean;
    points_earned: number;
    question: {
        id: number;
        question: string;
        type: string;
        options: string[] | null;
        correct_answer: string | null;
        points: number;
    };
}

interface InterviewResponse {
    id: number;
    score: number;
    total_points: number;
    percentage: number;
    passed: boolean;
    status: string;
    requires_manual_review: boolean;
    reviewed_at: string | null;
    reviewed_by: number | null;
    started_at: string;
    completed_at: string;
    user: { id: number; name: string; email: string };
    application: { id: number; program: { title: string } } | null;
    answers: Answer[];
}

interface Interview {
    id: number;
    title: string;
    passing_score: number;
}

interface Props {
    interview: Interview;
    response: InterviewResponse;
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const toast = useToast();

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

const isPendingReview = computed(
    () => props.response.requires_manual_review && !props.response.reviewed_at,
);
const textAnswers = computed(() =>
    props.response.answers.filter((a) => a.question.type === 'text'),
);

// Manual grading state
const scores = ref<Record<string, number>>({});

// Initialize scores from existing values
props.response.answers.forEach((answer) => {
    if (answer.question.type === 'text') {
        scores.value[String(answer.id)] = answer.points_earned;
    }
});

const showSubmitReviewModal = ref(false);

const previewTotal = computed(() => {
    let total = 0;
    props.response.answers.forEach((answer) => {
        if (answer.question.type === 'text') {
            total += Math.min(
                Math.max(scores.value[String(answer.id)] ?? 0, 0),
                answer.question.points,
            );
        } else {
            total += answer.points_earned;
        }
    });
    return total;
});

const previewPercentage = computed(() => {
    if (props.response.total_points === 0) return 0;
    return (
        Math.round(
            (previewTotal.value / props.response.total_points) * 100 * 100,
        ) / 100
    );
});

const previewPassed = computed(
    () => previewPercentage.value >= props.interview.passing_score,
);

const submitReview = () => {
    const form = useForm({ scores: scores.value });
    form.post(
        `/admin/interviews/${props.interview.id}/responses/${props.response.id}/review`,
        {
            preserveScroll: true,
            onSuccess: () => {
                showSubmitReviewModal.value = false;
                // Flash message handled by global watcher (InterviewController::reviewResponse flashes 'success')
            },
            onError: () => {
                toast.error('Failed to submit review.');
            },
        },
    );
};
</script>

<template>
    <div class="mx-auto max-w-5xl">
        <Head :title="`${response.user.name} — ${interview.title}`" />

        <!-- Header -->
        <div class="mb-8">
            <Link
                :href="`/admin/interviews/${interview.id}/responses`"
                class="mb-4 inline-flex items-center text-[#42b6c5] hover:text-[#35919e]"
            >
                <svg
                    class="mr-2 h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                Back to Responses
            </Link>
            <div class="flex items-center gap-3">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Response Details
                </h2>
                <span
                    v-if="isPendingReview"
                    class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                >
                    ⏳ Pending Review
                </span>
                <span
                    v-else-if="
                        response.requires_manual_review && response.reviewed_at
                    "
                    class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                >
                    ✓ Reviewed
                </span>
            </div>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {{ interview.title }} — {{ response.user.name }}
            </p>
        </div>

        <!-- Pending Review Banner -->
        <div
            v-if="isPendingReview"
            class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20"
        >
            <div class="flex items-center gap-3">
                <svg
                    class="h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                    />
                </svg>
                <div>
                    <p
                        class="text-sm font-semibold text-amber-800 dark:text-amber-300"
                    >
                        Manual Review Required
                    </p>
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        This interview contains
                        {{ textAnswers.length }} open-ended question(s) that
                        need to be scored manually. Assign points below and
                        submit your review.
                    </p>
                </div>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="mb-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Candidate
                    </p>
                    <p
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        {{ response.user.name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ response.user.email }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Score
                    </p>
                    <p
                        v-if="isPendingReview"
                        class="text-lg font-semibold text-amber-600 dark:text-amber-400"
                    >
                        Preview: {{ previewTotal }} /
                        {{ response.total_points }}
                    </p>
                    <p
                        v-else
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        {{ response.score }} / {{ response.total_points }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Percentage
                    </p>
                    <p
                        v-if="isPendingReview"
                        class="text-lg font-semibold text-amber-600 dark:text-amber-400"
                    >
                        {{ previewPercentage }}%
                    </p>
                    <p
                        v-else
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        {{ response.percentage }}%
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Result
                    </p>
                    <template v-if="isPendingReview">
                        <span
                            class="mt-1 inline-block rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                        >
                            ⏳ Awaiting Review
                        </span>
                    </template>
                    <template v-else>
                        <span
                            :class="
                                response.passed
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                            "
                            class="mt-1 inline-block rounded-full px-3 py-1 text-sm font-semibold"
                        >
                            {{ response.passed ? '✓ Passed' : '✗ Failed' }}
                        </span>
                    </template>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Completed
                    </p>
                    <p
                        class="text-sm font-medium text-gray-900 dark:text-gray-100"
                    >
                        {{ formatDate(response.completed_at) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Answers -->
        <div
            class="mb-8 overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800"
        >
            <div
                class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/50"
            >
                <h3
                    class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Answers ({{ response.answers.length }})
                </h3>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <div
                    v-for="(answer, index) in response.answers"
                    :key="answer.id"
                    class="p-6"
                >
                    <div class="mb-3 flex items-start justify-between">
                        <div class="flex flex-1 items-start gap-3">
                            <span
                                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                                :class="
                                    isPendingReview &&
                                    answer.question.type === 'text'
                                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
                                        : answer.is_correct
                                          ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                          : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                "
                            >
                                {{ index + 1 }}
                            </span>
                            <div class="flex-1">
                                <p
                                    class="mb-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                                >
                                    {{ answer.question.question }}
                                </p>
                                <div
                                    class="mb-3 flex gap-4 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    <span class="capitalize">{{
                                        answer.question.type.replace('_', ' ')
                                    }}</span>
                                    <span
                                        v-if="
                                            !(
                                                isPendingReview &&
                                                answer.question.type === 'text'
                                            )
                                        "
                                        >{{ answer.points_earned }} /
                                        {{ answer.question.points }} pts</span
                                    >
                                    <span
                                        v-else
                                        class="font-medium text-amber-600 dark:text-amber-400"
                                        >Max:
                                        {{ answer.question.points }} pts</span
                                    >
                                </div>

                                <!-- Answer Display -->
                                <div class="space-y-2">
                                    <div class="flex items-start gap-2">
                                        <span
                                            class="min-w-[80px] text-xs font-medium text-gray-500 dark:text-gray-400"
                                            >Given:</span
                                        >
                                        <span
                                            class="text-sm"
                                            :class="
                                                isPendingReview &&
                                                answer.question.type === 'text'
                                                    ? 'text-gray-900 dark:text-gray-100'
                                                    : answer.is_correct
                                                      ? 'text-green-700 dark:text-green-400'
                                                      : 'text-red-700 dark:text-red-400'
                                            "
                                        >
                                            {{ answer.answer || '(no answer)' }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="
                                            answer.question.correct_answer &&
                                            !answer.is_correct &&
                                            answer.question.type !== 'text'
                                        "
                                        class="flex items-start gap-2"
                                    >
                                        <span
                                            class="min-w-[80px] text-xs font-medium text-gray-500 dark:text-gray-400"
                                            >Correct:</span
                                        >
                                        <span
                                            class="text-sm text-green-700 dark:text-green-400"
                                            >{{
                                                answer.question.correct_answer
                                            }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Manual Scoring Input for Text Questions -->
                                <div
                                    v-if="
                                        isPendingReview &&
                                        answer.question.type === 'text'
                                    "
                                    class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/10"
                                >
                                    <label
                                        class="mb-2 block text-xs font-semibold text-amber-800 dark:text-amber-300"
                                    >
                                        Award Points (0 –
                                        {{ answer.question.points }})
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input
                                            v-model.number="
                                                scores[String(answer.id)]
                                            "
                                            type="number"
                                            :min="0"
                                            :max="answer.question.points"
                                            class="w-24 rounded-lg border border-amber-300 px-3 py-1.5 text-sm focus:border-transparent focus:ring-2 focus:ring-amber-400 dark:border-amber-700 dark:bg-gray-800 dark:text-gray-100"
                                        />
                                        <span
                                            class="text-sm text-gray-500 dark:text-gray-400"
                                            >/
                                            {{
                                                answer.question.points
                                            }}
                                            pts</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span
                            v-if="
                                !(
                                    isPendingReview &&
                                    answer.question.type === 'text'
                                )
                            "
                            :class="
                                answer.is_correct
                                    ? 'text-green-600 dark:text-green-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                            class="ml-4 flex-shrink-0 text-lg"
                        >
                            {{ answer.is_correct ? '✓' : '✗' }}
                        </span>
                        <span
                            v-else
                            class="ml-4 flex-shrink-0 text-lg text-amber-500"
                        >
                            ✎
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Review Button -->
        <div
            v-if="isPendingReview"
            class="mb-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Submit Review
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Preview: {{ previewTotal }}/{{
                            response.total_points
                        }}
                        ({{ previewPercentage }}%) —
                        <span
                            :class="
                                previewPassed
                                    ? 'font-semibold text-green-600 dark:text-green-400'
                                    : 'font-semibold text-red-600 dark:text-red-400'
                            "
                        >
                            {{ previewPassed ? 'Will Pass' : 'Will Not Pass' }}
                        </span>
                        (Passing: {{ interview.passing_score }}%)
                    </p>
                </div>
                <Button
                    @click="showSubmitReviewModal = true"
                    class="bg-[#42b6c5] hover:bg-[#35919e]"
                >
                    Submit Review
                </Button>
            </div>
        </div>

        <!-- Submit Review Confirmation Modal -->
        <ConfirmationModal
            :open="showSubmitReviewModal"
            title="Submit Review"
            :description="`Are you sure you want to submit this review? The candidate will receive a score of ${previewTotal}/${response.total_points} (${previewPercentage}%) and ${previewPassed ? 'will PASS' : 'will NOT PASS'}. This action cannot be undone.`"
            confirm-text="Submit Review"
            variant="default"
            @update:open="showSubmitReviewModal = $event"
            @confirm="submitReview"
        />
    </div>
</template>
