<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

interface NoteItem {
    id: number;
    content: string;
    timestamp: string | null;
    updated_at: string | null;
    lesson: {
        id: number;
        title: string;
    };
}

interface NotesGroup {
    course: {
        id: number;
        title: string;
    };
    notes: NoteItem[];
}

defineProps<{
    groups: NotesGroup[];
}>();

defineOptions({ layout: AppLayout });
</script>

<template>
    <div>
        <Head title="My Notes" />

        <div class="bg-gray-50 py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-[#000928]">My Notes</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Your saved lesson notes across all enrolled courses.
                    </p>
                </div>

                <div
                    v-if="!groups.length"
                    class="rounded-xl border border-dashed border-gray-200 bg-white p-10 text-center text-sm text-gray-500"
                >
                    No notes yet. Open a lesson and start taking notes.
                </div>

                <div v-else class="space-y-6">
                    <section
                        v-for="group in groups"
                        :key="group.course.id"
                        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div
                            class="mb-4 flex items-center justify-between gap-3"
                        >
                            <h2 class="text-lg font-semibold text-[#000928]">
                                {{ group.course.title }}
                            </h2>
                            <Link
                                :href="`/online-courses/${group.course.id}`"
                                class="text-xs font-semibold text-[#42b6c5] hover:text-[#35919e]"
                            >
                                Open course
                            </Link>
                        </div>

                        <ul class="space-y-3">
                            <li
                                v-for="note in group.notes"
                                :key="note.id"
                                class="rounded-lg border border-gray-200 bg-gray-50 p-3"
                            >
                                <div
                                    class="mb-1 flex flex-wrap items-center gap-2 text-xs text-gray-500"
                                >
                                    <span class="font-semibold text-gray-700">{{
                                        note.lesson.title
                                    }}</span>
                                    <span
                                        v-if="note.timestamp"
                                        class="rounded-md bg-[#381998]/10 px-2 py-0.5 font-semibold text-[#381998]"
                                    >
                                        {{ note.timestamp }}
                                    </span>
                                    <span v-if="note.updated_at">{{
                                        new Date(
                                            note.updated_at,
                                        ).toLocaleString()
                                    }}</span>
                                </div>
                                <p
                                    class="text-sm whitespace-pre-wrap text-gray-700"
                                >
                                    {{ note.content }}
                                </p>
                                <Link
                                    :href="`/dashboard/courses/${group.course.id}/lessons/${note.lesson.id}`"
                                    class="mt-2 inline-flex text-xs font-semibold text-[#42b6c5] hover:text-[#35919e]"
                                >
                                    Open lesson
                                </Link>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
