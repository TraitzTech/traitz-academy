<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    quiz: {
        id: number;
        title: string;
        course: { id: number; title: string };
        lesson: { id: number; title: string } | null;
    };
    attempts: {
        data: Array<{
            id: number;
            status: string;
            score_percentage: string | null;
            passed: boolean | null;
            created_at: string;
            user: { name: string; email: string };
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
</script>

<template>
    <AppLayout>
        <Head :title="`Attempts - ${quiz.title}`" />
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#000928] dark:text-white">
                    Quiz Attempts
                </h1>
                <p class="text-sm text-gray-500">{{ quiz.title }}</p>
            </div>
            <Link
                :href="`/admin/courses/${quiz.course.id}`"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold"
                >Back to Course</Link
            >
        </div>

        <div
            class="overflow-hidden rounded-xl bg-white shadow dark:bg-gray-800"
        >
            <table class="w-full text-sm">
                <thead
                    class="bg-gray-50 text-left text-xs text-gray-500 uppercase dark:bg-gray-900/40"
                >
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Score</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="a in attempts.data"
                        :key="a.id"
                        class="border-t border-gray-100 dark:border-gray-700"
                    >
                        <td class="px-4 py-3">{{ a.user.name }}</td>
                        <td class="px-4 py-3">{{ a.status }}</td>
                        <td class="px-4 py-3">
                            {{ a.score_percentage ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ new Date(a.created_at).toLocaleString() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="`/admin/quizzes/${quiz.id}/attempts/${a.id}`"
                                class="font-semibold text-[#381998]"
                                >View</Link
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
