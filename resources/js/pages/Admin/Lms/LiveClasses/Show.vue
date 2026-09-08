<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted } from 'vue';

import RecordingsList from '@/components/live-classes/RecordingsList.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{ liveClass: any }>();
let autoJoinTimer: number | null = null;

const recordingForm = useForm({
    recording_file: null as File | null,
});

function onFilePicked(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    recordingForm.recording_file = file;
}

function uploadRecording() {
    if (!recordingForm.recording_file) return;
    recordingForm.post(
        `/admin/lms/live-classes/${props.liveClass.id}/recordings`,
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => recordingForm.reset(),
        },
    );
}

const when = (value: string | null) =>
    value ? new Date(value).toLocaleString() : '—';

const roomUrl = computed(() => `/dashboard/live-classes/${props.liveClass.id}`);
const startsAtMs = computed(() =>
    new Date(props.liveClass.start_time).getTime(),
);
const joinOpensAtMs = computed(() => startsAtMs.value - 5 * 60 * 1000);
const endsAtMs = computed(
    () => startsAtMs.value + Number(props.liveClass.duration || 0) * 60 * 1000,
);

function canAutoJoinNow(): boolean {
    const now = Date.now();
    return now >= joinOpensAtMs.value && now <= endsAtMs.value;
}

function openRoom() {
    window.location.href = roomUrl.value;
}

onMounted(() => {
    if (canAutoJoinNow()) {
        openRoom();
        return;
    }

    const msUntilOpen = joinOpensAtMs.value - Date.now();
    if (msUntilOpen > 0) {
        autoJoinTimer = window.setTimeout(() => {
            openRoom();
        }, msUntilOpen);
    }
});

onBeforeUnmount(() => {
    if (autoJoinTimer) {
        window.clearTimeout(autoJoinTimer);
        autoJoinTimer = null;
    }
});
</script>

<template>
    <div>
        <Head :title="liveClass.title" />
        <div class="mb-6 flex items-center justify-between">
            <div>
                <Link
                    href="/admin/lms/live-classes"
                    class="mb-2 inline-flex rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Back to all live classes
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ liveClass.title }}
                </h1>
                <p class="text-sm text-gray-500">
                    {{ liveClass.description || 'No description' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Link
                    :href="roomUrl"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-[#381998] dark:border-gray-600 dark:text-gray-200"
                    >Open room</Link
                >
                <Link
                    :href="`/admin/lms/live-classes/${liveClass.id}/edit`"
                    class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white"
                    >Edit</Link
                >
            </div>
        </div>

        <div class="mb-4 grid gap-3 md:grid-cols-3">
            <div
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
            >
                <p class="text-xs tracking-wide text-gray-500 uppercase">
                    Tutor
                </p>
                <p
                    class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100"
                >
                    {{ liveClass.tutor?.name }}
                </p>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
            >
                <p class="text-xs tracking-wide text-gray-500 uppercase">
                    Start time
                </p>
                <p
                    class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100"
                >
                    {{ when(liveClass.start_time) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
            >
                <p class="text-xs tracking-wide text-gray-500 uppercase">
                    Duration
                </p>
                <p
                    class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100"
                >
                    {{ liveClass.duration }} minutes
                </p>
            </div>
        </div>

        <div
            class="mb-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
        >
            <p
                class="mb-2 text-sm font-semibold text-[#000928] dark:text-white"
            >
                Upload recording
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <input
                    type="file"
                    accept="video/mp4,video/quicktime,video/x-matroska,video/webm"
                    @change="onFilePicked"
                />
                <button
                    :disabled="
                        recordingForm.processing ||
                        !recordingForm.recording_file
                    "
                    class="rounded-lg bg-[#42b6c5] px-3 py-2 text-xs font-semibold text-white disabled:opacity-50"
                    @click="uploadRecording"
                >
                    {{
                        recordingForm.processing
                            ? 'Uploading...'
                            : 'Upload & send to YouTube'
                    }}
                </button>
            </div>
        </div>

        <div class="mb-4">
            <RecordingsList :recordings="liveClass.recordings || []" />
        </div>

        <div
            class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                class="border-b border-gray-100 px-4 py-3 dark:border-gray-700"
            >
                <h2
                    class="text-sm font-semibold text-[#000928] dark:text-white"
                >
                    Attendance
                </h2>
            </div>
            <div
                v-if="(liveClass.attendance || []).length === 0"
                class="px-4 py-8 text-sm text-gray-500"
            >
                No attendance records yet.
            </div>
            <table v-else class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Joined</th>
                        <th class="px-4 py-3">Left</th>
                        <th class="px-4 py-3">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr v-for="row in liveClass.attendance" :key="row.id">
                        <td class="px-4 py-3">
                            {{ row.student?.name || row.student_name || '—' }}
                        </td>
                        <td class="px-4 py-3">{{ when(row.joined_at) }}</td>
                        <td class="px-4 py-3">{{ when(row.left_at) }}</td>
                        <td class="px-4 py-3">
                            {{ row.duration_minutes ?? 0 }} min
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
