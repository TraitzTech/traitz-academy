<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import RecordingsList from '@/components/live-classes/RecordingsList.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface Recording {
    id: number;
    file_path: string | null;
    youtube_url: string | null;
    status: 'processing' | 'uploaded' | 'failed';
    created_at?: string | null;
}

interface RecordedClass {
    id: number;
    title: string;
    start_time: string;
    duration: number;
    tutor?: { id: number; name: string } | null;
    recordings: Recording[];
}

const props = defineProps<{ classes: RecordedClass[] }>();
</script>

<template>
    <div>
        <Head title="Recorded Live Classes" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Recorded live classes
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Watch recordings from your completed live sessions.
                </p>
            </div>
            <Link
                href="/dashboard/live-classes"
                class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                Back to live classes
            </Link>
        </div>

        <div
            v-if="classes.length === 0"
            class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-600"
        >
            No recorded live classes available yet.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="row in classes"
                :key="row.id"
                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="mb-3">
                    <p class="font-semibold text-[#000928] dark:text-white">
                        {{ row.title }}
                    </p>
                    <p class="text-xs text-gray-500">
                        Tutor: {{ row.tutor?.name || '—' }} ·
                        {{ new Date(row.start_time).toLocaleString() }} ·
                        {{ row.duration }} min
                    </p>
                </div>
                <RecordingsList :recordings="row.recordings" />
            </div>
        </div>
    </div>
</template>
