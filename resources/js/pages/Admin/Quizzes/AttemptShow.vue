<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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
    };
    attempt: {
        id: number;
        answers: Record<string, unknown>;
        score_percentage: string | null;
        passed: boolean | null;
        user: { name: string; email: string };
    };
}>();

function answerFor(id: number) {
    return props.attempt.answers?.[String(id)];
}
</script>

<template>
    <AppLayout>
        <Head :title="`Attempt #${attempt.id}`" />

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#000928] dark:text-white">
                    Attempt #{{ attempt.id }}
                </h1>
                <p class="text-sm text-gray-500">
                    {{ attempt.user.name }} · {{ quiz.title }}
                </p>
            </div>
            <Link
                :href="`/admin/quizzes/${quiz.id}/attempts`"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold"
                >Back</Link
            >
        </div>

        <div class="mb-4 rounded-xl bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm">
                Score: <strong>{{ attempt.score_percentage ?? '—' }}%</strong> ·
                {{ attempt.passed ? 'Passed' : 'Not passed' }}
            </p>
        </div>

        <div class="space-y-4">
            <div
                v-for="(q, idx) in quiz.questions"
                :key="q.id"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <h3 class="mb-2 text-sm font-semibold">
                    {{ idx + 1 }}. {{ q.question }}
                </h3>
                <p class="text-sm text-gray-600">
                    Student answer: {{ answerFor(q.id) ?? 'No answer' }}
                </p>
                <p class="text-sm text-gray-700">
                    Correct: {{ q.correct_answer }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
