<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Layers } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface Intern {
    id: number;
    name: string | null;
    email: string | null;
    program: string | null;
    status: string;
    start_date: string | null;
}

interface CohortDetail {
    id: number;
    name: string;
    description: string | null;
    start_date: string | null;
    end_date: string | null;
    intake_opens_at: string | null;
    intake_closes_at: string | null;
    status: string;
    is_intake: boolean;
    programs: string[];
}

defineProps<{ cohort: CohortDetail; interns: Intern[] }>();

const statusClass: Record<string, string> = {
    upcoming: 'bg-blue-100 text-blue-700',
    active: 'bg-green-100 text-green-700',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-700',
};
</script>

<template>
    <div class="mx-auto max-w-full">
        <Head :title="cohort.name" />

        <Link
            href="/supervisor/cohorts"
            class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-[#381998] hover:underline"
        >
            <ArrowLeft class="h-4 w-4" /> Back to cohorts
        </Link>

        <div class="mb-6 flex items-center gap-3">
            <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#381998]/10"
            >
                <Layers class="h-5 w-5 text-[#381998]" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-[#000928] dark:text-white">
                    {{ cohort.name }}
                    <span
                        v-if="cohort.is_intake"
                        class="ml-1.5 rounded-full bg-[#42b6c5]/15 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-[#2a8a96] uppercase"
                        >Intake</span
                    >
                </h1>
                <p class="text-sm text-gray-500">
                    Read-only cohort overview — reach out to an admin to change
                    cohort settings.
                </p>
            </div>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <p
                    class="mb-1 text-xs font-semibold tracking-wide text-gray-400 uppercase"
                >
                    Status
                </p>
                <span
                    :class="[
                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                        statusClass[cohort.status] ||
                            'bg-gray-100 text-gray-700',
                    ]"
                    >{{ cohort.status }}</span
                >

                <p
                    class="mt-4 mb-1 text-xs font-semibold tracking-wide text-gray-400 uppercase"
                >
                    Cohort dates
                </p>
                <p
                    class="inline-flex items-center gap-1 text-sm text-gray-700 dark:text-gray-300"
                >
                    <CalendarDays class="h-3.5 w-3.5" />{{
                        cohort.start_date
                    }}
                    → {{ cohort.end_date }}
                </p>

                <template
                    v-if="cohort.intake_opens_at || cohort.intake_closes_at"
                >
                    <p
                        class="mt-4 mb-1 text-xs font-semibold tracking-wide text-gray-400 uppercase"
                    >
                        Application window
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ cohort.intake_opens_at || '—' }} →
                        {{ cohort.intake_closes_at || '—' }}
                    </p>
                </template>
            </div>

            <div
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <p
                    class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase"
                >
                    Programs in this cohort
                </p>
                <div
                    v-if="cohort.programs.length === 0"
                    class="text-sm text-gray-400"
                >
                    —
                </div>
                <div v-else class="flex flex-wrap gap-1.5">
                    <span
                        v-for="p in cohort.programs"
                        :key="p"
                        class="inline-block rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-200"
                        >{{ p }}</span
                    >
                </div>
                <p
                    v-if="cohort.description"
                    class="mt-4 text-sm text-gray-600 dark:text-gray-300"
                >
                    {{ cohort.description }}
                </p>
            </div>
        </div>

        <h2 class="mb-3 text-lg font-semibold text-[#000928] dark:text-white">
            Your interns in this cohort
        </h2>
        <div
            class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                v-if="interns.length === 0"
                class="p-10 text-center text-sm text-gray-500"
            >
                None of your interns are in this cohort.
            </div>
            <table v-else class="w-full text-sm">
                <thead
                    class="border-b border-gray-100 text-left text-xs tracking-wide text-gray-500 uppercase dark:border-gray-700"
                >
                    <tr>
                        <th class="px-5 py-3">Intern</th>
                        <th class="px-5 py-3">Program</th>
                        <th class="px-5 py-3">Started</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    <tr
                        v-for="i in interns"
                        :key="i.id"
                        class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30"
                    >
                        <td class="px-5 py-3">
                            <p
                                class="font-semibold text-[#000928] dark:text-white"
                            >
                                {{ i.name }}
                            </p>
                            <p class="text-xs text-gray-400">{{ i.email }}</p>
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                            {{ i.program || '—' }}
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                            {{ i.start_date || '—' }}
                        </td>
                        <td
                            class="px-5 py-3 text-gray-600 capitalize dark:text-gray-300"
                        >
                            {{ i.status }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <Link
                                :href="`/supervisor/interns/${i.id}`"
                                class="text-xs font-semibold text-[#381998] hover:underline"
                                >Open</Link
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
