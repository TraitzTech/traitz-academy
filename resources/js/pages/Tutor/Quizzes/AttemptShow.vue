<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    quiz: {
        id: number;
        title: string;
        questions: Array<{
            id: number;
            question: string;
            type: string;
            options: string[];
            correct_answer: Array<string | number>;
            points: number;
        }>;
        course: { id: number; title: string };
        lesson: { id: number; title: string } | null;
    };
    attempt: {
        id: number;
        answers: Record<string, unknown>;
        status: string;
        score_percentage: string | number | null;
        passed: boolean | null;
        instructor_feedback: string | null;
        submitted_at: string | null;
        graded_at: string | null;
        user: { name: string; email: string };
    };
}>();

const page = usePage();
const flashSuccess = computed(
    () => (page.props as { flash?: { success?: string } }).flash?.success,
);

const gradeForm = useForm({
    score_percentage:
        props.attempt.score_percentage != null &&
        props.attempt.score_percentage !== ''
            ? String(props.attempt.score_percentage)
            : '',
    passed: props.attempt.passed === true,
    instructor_feedback: props.attempt.instructor_feedback ?? '',
});

function answerFor(id: number) {
    return props.attempt.answers?.[String(id)];
}

function formatStudentAnswer(q: (typeof props.quiz.questions)[0]) {
    const submitted = answerFor(q.id);
    if (q.type === 'multiple_choice') {
        return q.options?.[Number(submitted)] ?? '—';
    }
    if (q.type === 'multiple_select') {
        if (!Array.isArray(submitted) || submitted.length === 0) return '—';
        return submitted.map((i: number) => q.options?.[i] ?? '').join(', ');
    }
    if (q.type === 'true_false') {
        return String(submitted ?? '—');
    }
    return String(submitted ?? '—');
}

function formatCorrect(q: (typeof props.quiz.questions)[0]) {
    const c = q.correct_answer;
    if (q.type === 'multiple_choice') {
        return q.options?.[Number(c[0])] ?? String(c[0] ?? '');
    }
    if (q.type === 'multiple_select') {
        return Array.isArray(c)
            ? c.map((i) => q.options?.[Number(i)] ?? i).join(', ')
            : '';
    }
    return String(c[0] ?? '');
}

function submitGrade() {
    gradeForm.put(
        `/tutor/quizzes/${props.quiz.id}/attempts/${props.attempt.id}/grade`,
        {
            preserveScroll: true,
        },
    );
}

const statusLabel = computed(() => {
    if (props.attempt.status === 'submitted') return 'Awaiting your review';
    if (props.attempt.status === 'graded') return 'Graded';
    return props.attempt.status;
});
</script>

<template>
    <AppLayout>
        <Head :title="`Attempt #${attempt.id}`" />

        <div class="mx-auto max-w-3xl">
            <div
                v-if="flashSuccess"
                class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-200"
            >
                {{ flashSuccess }}
            </div>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1
                        class="text-2xl font-bold text-[#000928] dark:text-white"
                    >
                        Attempt #{{ attempt.id }}
                    </h1>
                    <p class="text-sm text-gray-500">
                        {{ attempt.user.name }} · {{ quiz.title }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-[#381998]">
                        {{ statusLabel }}
                    </p>
                </div>
                <Link
                    :href="`/tutor/quizzes/${quiz.id}/attempts`"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold dark:border-gray-600"
                    >Back</Link
                >
            </div>

            <div
                v-if="attempt.status === 'graded'"
                class="mb-6 rounded-xl bg-white p-4 shadow dark:bg-gray-800"
            >
                <p class="text-sm">
                    Score:
                    <strong>{{ attempt.score_percentage ?? '—' }}%</strong>
                    ·
                    {{ attempt.passed ? 'Passed' : 'Not passed' }}
                    <span v-if="attempt.graded_at" class="text-gray-500">
                        · Graded
                        {{ new Date(attempt.graded_at).toLocaleString() }}</span
                    >
                </p>
                <p
                    v-if="attempt.instructor_feedback"
                    class="mt-2 text-sm whitespace-pre-wrap text-gray-600 dark:text-gray-300"
                >
                    <span class="font-medium">Feedback sent to student:</span
                    ><br />
                    {{ attempt.instructor_feedback }}
                </p>
            </div>

            <div
                v-if="attempt.status === 'submitted'"
                class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-900/20"
            >
                <h2
                    class="text-sm font-semibold text-amber-900 dark:text-amber-100"
                >
                    Grade this attempt
                </h2>
                <p
                    class="mt-1 text-xs text-amber-800/90 dark:text-amber-200/90"
                >
                    Pass mark on this quiz is a reference only — you decide the
                    final score and pass/fail.
                </p>
                <form
                    class="mt-4 grid gap-4 sm:grid-cols-2"
                    @submit.prevent="submitGrade"
                >
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300"
                            >Score (%)</label
                        >
                        <input
                            v-model="gradeForm.score_percentage"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        />
                        <p
                            v-if="gradeForm.errors.score_percentage"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ gradeForm.errors.score_percentage }}
                        </p>
                    </div>
                    <div class="flex flex-col justify-end">
                        <label
                            class="mb-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"
                        >
                            <input
                                v-model="gradeForm.passed"
                                type="checkbox"
                                class="rounded border-gray-300"
                            />
                            Passed
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <label
                            class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300"
                            >Feedback for student (optional)</label
                        >
                        <textarea
                            v-model="gradeForm.instructor_feedback"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            placeholder="Comments the student will see on their result page…"
                        />
                        <p
                            v-if="gradeForm.errors.instructor_feedback"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ gradeForm.errors.instructor_feedback }}
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <button
                            type="submit"
                            :disabled="gradeForm.processing"
                            class="rounded-lg bg-[#381998] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-50"
                        >
                            {{
                                gradeForm.processing ? 'Saving…' : 'Save grade'
                            }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(q, idx) in quiz.questions"
                    :key="q.id"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <h3
                        class="mb-2 text-sm font-semibold text-[#000928] dark:text-white"
                    >
                        {{ idx + 1 }}. {{ q.question }}
                    </h3>
                    <p class="text-xs text-gray-500">
                        {{ q.points }} pt · {{ q.type.replace('_', ' ') }}
                    </p>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                        <span class="font-medium">Student:</span>
                        {{ formatStudentAnswer(q) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-medium">Answer key:</span>
                        {{ formatCorrect(q) }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
