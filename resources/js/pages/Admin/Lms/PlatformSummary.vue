<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps<{
    stats: {
        total_registered_users: number;
        total_published_courses: number;
        total_enrollments: number;
        total_completions: number;
        total_certificates_issued: number;
        total_reviews_submitted: number;
    };
}>();
</script>

<template>
    <div>
        <Head title="LMS Platform Summary" />
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                LMS platform summary
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Top-level LMS health and outcomes.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <p
                    class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >
                    Registered users
                </p>
                <p
                    class="mt-2 text-3xl font-bold text-[#000928] dark:text-gray-100"
                >
                    {{ stats.total_registered_users }}
                </p>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <p
                    class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >
                    Published courses
                </p>
                <p
                    class="mt-2 text-3xl font-bold text-[#000928] dark:text-gray-100"
                >
                    {{ stats.total_published_courses }}
                </p>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <p
                    class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >
                    Total enrollments
                </p>
                <p
                    class="mt-2 text-3xl font-bold text-[#000928] dark:text-gray-100"
                >
                    {{ stats.total_enrollments }}
                </p>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <p
                    class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >
                    Total completions
                </p>
                <p
                    class="mt-2 text-3xl font-bold text-[#000928] dark:text-gray-100"
                >
                    {{ stats.total_completions }}
                </p>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <p
                    class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >
                    Certificates issued
                </p>
                <p
                    class="mt-2 text-3xl font-bold text-[#000928] dark:text-gray-100"
                >
                    {{ stats.total_certificates_issued }}
                </p>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <p
                    class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >
                    Reviews submitted
                </p>
                <p
                    class="mt-2 text-3xl font-bold text-[#000928] dark:text-gray-100"
                >
                    {{ stats.total_reviews_submitted }}
                </p>
            </div>
        </div>
    </div>
</template>
