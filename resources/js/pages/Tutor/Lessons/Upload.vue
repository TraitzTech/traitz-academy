<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { FileText, Pencil, Trash2, Upload, Video } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import LessonForm from '@/components/LessonForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Section {
    id: number;
    title: string;
}
interface Course {
    id: number;
    title: string;
    status: string;
    sections: Section[];
}
interface Lesson {
    id: number;
    title: string;
    type: string;
    duration: string | null;
    course: string | null;
    section: string | null;
    is_free: boolean;
    has_video: boolean;
    created_at: string;
}

const props = defineProps<{
    courses: Course[];
    recentLessons: Lesson[];
}>();

defineOptions({ layout: AppLayout });

// ── Form ──────────────────────────────────────────────────────────────────────
const form = useForm({
    course_id: '',
    section_id: '',
    title: '',
    type: 'video' as 'video' | 'text' | 'quiz',
    description: '',
    duration: '',
    is_free: false,
    video_file: null as File | null,
    content: '',
});

// Sections for the selected course
const sections = computed<Section[]>(() => {
    const course = props.courses.find(
        (c) => String(c.id) === String(form.course_id),
    );
    return course?.sections ?? [];
});

// Reset section when course changes
watch(
    () => form.course_id,
    () => {
        form.section_id = '';
    },
);

watch(
    () => form.type,
    (type) => {
        if (type !== 'video') {
            form.duration = '';
        }
    },
);

// ── Submit ────────────────────────────────────────────────────────────────────
function submit() {
    form.post('/tutor/lessons/upload', {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteTarget = ref<Lesson | null>(null);

function confirmDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/tutor/lessons/${deleteTarget.value.id}`, {
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const videoCount = computed(
    () => props.recentLessons.filter((l) => l.type === 'video').length,
);
const textCount = computed(
    () => props.recentLessons.filter((l) => l.type !== 'video').length,
);
</script>

<template>
    <div class="mx-auto max-w-3xl">
        <Head title="Lesson Upload" />

        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Lesson Upload
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add a new lesson to one of your courses
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- ── Left: Upload form (adapted from prototype) ── -->
            <div class="rounded-xl bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-5 text-base font-semibold text-gray-900 dark:text-gray-100"
                >
                    Upload New Lesson
                </h3>

                <form @submit.prevent="submit" class="space-y-5">
                    <LessonForm
                        :form="form"
                        :courses="courses"
                        show-course-selection
                        show-section-selection
                    >
                        <!-- Submit button slot -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#42b6c5] py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35919e] disabled:opacity-60"
                        >
                            <span
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
                            />
                            <Upload v-else class="h-4 w-4" />
                            {{ form.processing ? 'Saving…' : 'Save Lesson' }}
                        </button>
                    </LessonForm>
                </form>
            </div>

            <!-- ── Right: Recent uploads (adapted from prototype) ── -->
            <div class="rounded-xl bg-white p-6 shadow dark:bg-gray-800">
                <div class="mb-5 flex items-center justify-between">
                    <h3
                        class="text-base font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Recent Lessons
                    </h3>
                    <div
                        class="flex gap-3 text-xs text-gray-500 dark:text-gray-400"
                    >
                        <span>Videos: {{ videoCount }}</span>
                        <span>Other: {{ textCount }}</span>
                    </div>
                </div>

                <div
                    v-if="recentLessons.length === 0"
                    class="py-10 text-center text-sm text-gray-400"
                >
                    No lessons yet. Upload your first one.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="lesson in recentLessons"
                        :key="lesson.id"
                        class="group flex items-center gap-3"
                    >
                        <!-- Type icon -->
                        <div
                            :class="[
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                                lesson.type === 'video'
                                    ? 'bg-purple-100 dark:bg-purple-900/30'
                                    : 'bg-blue-100 dark:bg-blue-900/30',
                            ]"
                        >
                            <Video
                                v-if="lesson.type === 'video'"
                                class="h-4 w-4 text-purple-600 dark:text-purple-400"
                            />
                            <FileText
                                v-else
                                class="h-4 w-4 text-blue-600 dark:text-blue-400"
                            />
                        </div>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p
                                    class="truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                                >
                                    {{ lesson.title }}
                                </p>
                                <span
                                    v-if="lesson.is_free"
                                    class="shrink-0 rounded-full bg-green-100 px-1.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400"
                                    >Free</span
                                >
                            </div>
                            <p class="truncate text-xs text-gray-400">
                                {{ lesson.course }}
                                <template v-if="lesson.section">
                                    · {{ lesson.section }}</template
                                >
                                <template
                                    v-if="
                                        lesson.type === 'video' &&
                                        lesson.duration
                                    "
                                >
                                    · {{ lesson.duration }}</template
                                >
                                · {{ lesson.created_at }}
                            </p>
                        </div>

                        <!-- Actions (appear on hover like prototype) -->
                        <div
                            class="flex shrink-0 gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <a
                                :href="`/tutor/courses`"
                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-500 dark:hover:bg-blue-900/10"
                                title="Go to course editor"
                            >
                                <Pencil class="h-3 w-3" />
                            </a>
                            <button
                                @click="deleteTarget = lesson"
                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/10"
                                title="Delete lesson"
                            >
                                <Trash2 class="h-3 w-3" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete confirmation -->
        <ConfirmationModal
            :open="!!deleteTarget"
            title="Delete Lesson"
            :description="
                deleteTarget
                    ? `Delete &quot;${deleteTarget.title}&quot;? This cannot be undone.`
                    : ''
            "
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="destructive"
            @update:open="
                (val) => {
                    if (!val) deleteTarget = null;
                }
            "
            @confirm="confirmDelete"
        />
    </div>
</template>
