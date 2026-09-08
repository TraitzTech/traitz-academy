<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface Learner {
    id: number;
    name: string | null;
    email: string | null;
    status: string;
    progress: number;
}

interface CourseReport {
    id: number;
    title: string;
    instructor_name: string | null;
    average_rating: number;
    review_count: number;
    total_enrollments: number;
    total_completions: number;
    revenue_collected: number;
    learners: Learner[];
}

const props = defineProps<{
    courses: CourseReport[];
    filters?: { search?: string | null; instructor?: string | null };
}>();
const expandedCourseId = ref<number | null>(null);
const search = ref(props.filters?.search ?? '');
const instructor = ref(props.filters?.instructor ?? '');

const applyFilters = debounce(() => {
    router.get(
        '/admin/lms/course-reports',
        {
            search: search.value.trim() || undefined,
            instructor: instructor.value.trim() || undefined,
        },
        { preserveState: true, replace: true },
    );
}, 300);

const money = (value: number) =>
    new Intl.NumberFormat('en-CM', {
        style: 'currency',
        currency: 'XAF',
        maximumFractionDigits: 0,
    }).format(value || 0);
</script>

<template>
    <div>
        <Head title="LMS Per-course Reports" />
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Per-course report
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Enrollment, completion, rating, revenue, and learner breakdown.
            </p>
        </div>

        <div
            class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <div class="flex flex-col gap-2 sm:flex-row">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Filter by course title..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:w-72 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    @input="applyFilters"
                />
                <input
                    v-model="instructor"
                    type="search"
                    placeholder="Filter by tutor name..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:w-72 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    @input="applyFilters"
                />
            </div>
            <a
                :href="`/admin/lms/course-reports?search=${encodeURIComponent(search)}&instructor=${encodeURIComponent(instructor)}&export=csv`"
                class="inline-flex items-center justify-center rounded-lg bg-[#381998] px-3 py-2 text-xs font-semibold text-white hover:bg-[#2b126f]"
            >
                Export CSV
            </a>
        </div>

        <div
            class="overflow-hidden rounded-xl bg-white shadow dark:bg-gray-800"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead
                        class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50"
                    >
                        <tr>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Course
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Tutor
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Revenue
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Avg rating
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Enrolled
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Completed
                            </th>
                            <th
                                class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Learners
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-100 dark:divide-gray-700"
                    >
                        <template v-for="row in props.courses" :key="row.id">
                            <tr
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/40"
                            >
                                <td
                                    class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100"
                                >
                                    {{ row.title }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 dark:text-gray-300"
                                >
                                    {{ row.instructor_name || '—' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-700 dark:text-gray-200"
                                >
                                    {{ money(row.revenue_collected) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-700 dark:text-gray-200"
                                >
                                    {{ row.average_rating.toFixed(1) }} ({{
                                        row.review_count
                                    }})
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-700 dark:text-gray-200"
                                >
                                    {{ row.total_enrollments }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-700 dark:text-gray-200"
                                >
                                    {{ row.total_completions }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        class="rounded-md border border-gray-200 px-2.5 py-1 text-xs font-semibold text-[#381998] hover:bg-[#381998]/5 dark:border-gray-600"
                                        @click="
                                            expandedCourseId =
                                                expandedCourseId === row.id
                                                    ? null
                                                    : row.id
                                        "
                                    >
                                        {{
                                            expandedCourseId === row.id
                                                ? 'Hide list'
                                                : 'View list'
                                        }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expandedCourseId === row.id">
                                <td
                                    colspan="7"
                                    class="bg-gray-50 px-4 py-3 dark:bg-gray-900/30"
                                >
                                    <div
                                        v-if="row.learners.length === 0"
                                        class="text-xs text-gray-500"
                                    >
                                        No enrolled learners yet.
                                    </div>
                                    <div v-else class="space-y-2">
                                        <div
                                            v-for="learner in row.learners"
                                            :key="learner.id"
                                            class="flex items-center justify-between rounded border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-800"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate font-semibold text-gray-900 dark:text-gray-100"
                                                >
                                                    {{
                                                        learner.name ||
                                                        'Unknown learner'
                                                    }}
                                                </p>
                                                <p
                                                    class="truncate text-gray-500"
                                                >
                                                    {{ learner.email || '—' }}
                                                </p>
                                            </div>
                                            <div
                                                class="ml-2 flex items-center gap-3"
                                            >
                                                <span class="text-gray-500">{{
                                                    learner.status
                                                }}</span>
                                                <span
                                                    class="font-semibold text-[#381998]"
                                                    >{{
                                                        learner.progress
                                                    }}%</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
