<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { computed } from 'vue';

import PublicLayout from '@/layouts/PublicLayout.vue';
import { lessonBodyHtml } from '@/utils/lessonContentHtml';
import { STREAMING_IFRAME_ALLOW, streamingEmbedSrc } from '@/utils/videoEmbed';

interface LessonAttachment {
    id: number;
    name: string;
    file_url: string;
    file_type: string | null;
    file_size: number | null;
    formatted_file_size: string;
}

const props = defineProps<{
    course: { id: number; title: string; slug: string };
    lesson: {
        id: number;
        title: string;
        type: string;
        description: string | null;
        content: string | null;
        video_url: string | null;
        duration: string | null;
        attachments: LessonAttachment[];
    };
}>();

const previewTextHtml = computed(() =>
    lessonBodyHtml(props.lesson.content, 'No content yet.'),
);

function videoSrc(url: string | null): string | null {
    if (!url) return null;
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    return `/storage/${url}`;
}

function attachmentUrl(path: string): string {
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return `/storage/${path}`;
}
</script>

<template>
    <PublicLayout>
        <Head :title="`${lesson.title} — Preview`" />

        <div class="lms-page">
            <div class="border-b border-gray-100 bg-gray-50/80">
                <div class="mx-auto max-w-4xl px-4 py-3 text-sm">
                    <nav
                        class="flex flex-wrap items-center gap-2 text-gray-500"
                    >
                        <Link href="/" class="hover:text-[#42b6c5]">Home</Link>
                        <span>/</span>
                        <Link
                            href="/online-courses"
                            class="hover:text-[#42b6c5]"
                            >Online Courses</Link
                        >
                        <span>/</span>
                        <Link
                            :href="`/online-courses/${course.id}`"
                            class="line-clamp-1 hover:text-[#42b6c5]"
                            >{{ course.title }}</Link
                        >
                        <span>/</span>
                        <span class="line-clamp-1 font-medium text-[#000928]"
                            >Preview</span
                        >
                    </nav>
                </div>
            </div>

            <section class="bg-gray-50 py-10">
                <div class="mx-auto max-w-4xl px-4 sm:px-6">
                    <p
                        class="mb-2 text-xs font-bold tracking-wide text-[#42b6c5] uppercase"
                    >
                        Free preview
                    </p>
                    <h1 class="text-2xl font-bold text-[#000928] sm:text-3xl">
                        {{ lesson.title }}
                    </h1>
                    <p
                        v-if="lesson.duration"
                        class="mt-2 text-sm text-gray-500"
                    >
                        Duration: {{ lesson.duration }}
                    </p>

                    <div class="lms-panel mt-8">
                        <p v-if="lesson.description" class="mb-6 text-gray-600">
                            {{ lesson.description }}
                        </p>

                        <!-- Video -->
                        <div v-if="lesson.type === 'video'" class="space-y-4">
                            <div
                                v-if="streamingEmbedSrc(lesson.video_url)"
                                class="aspect-video overflow-hidden rounded-xl bg-black"
                            >
                                <iframe
                                    :src="
                                        streamingEmbedSrc(lesson.video_url) ??
                                        undefined
                                    "
                                    class="h-full w-full"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    :allow="STREAMING_IFRAME_ALLOW"
                                    allowfullscreen
                                />
                            </div>
                            <video
                                v-else-if="videoSrc(lesson.video_url)"
                                :src="videoSrc(lesson.video_url) ?? undefined"
                                controls
                                class="w-full rounded-xl"
                            />
                            <p v-else class="text-sm text-gray-500">
                                Video for this lesson is not available for
                                preview yet.
                            </p>
                        </div>

                        <!-- Text -->
                        <div
                            v-else-if="lesson.type === 'text'"
                            class="prose prose-lg w-full max-w-3xl leading-relaxed text-gray-800 prose-slate dark:prose-invert prose-headings:font-semibold prose-headings:text-[#000928] prose-p:leading-relaxed prose-a:text-[#381998] prose-code:before:content-none prose-code:after:content-none prose-pre:overflow-x-auto prose-pre:rounded-xl prose-pre:bg-[#0d1117] prose-pre:px-4 prose-pre:py-3 prose-pre:text-gray-100 prose-ol:my-4 prose-ul:my-4 prose-li:my-1 prose-img:max-w-full prose-img:rounded-lg [&_pre_code]:bg-transparent [&_pre_code]:p-0"
                            v-html="previewTextHtml"
                        />

                        <!-- Quiz placeholder -->
                        <div
                            v-else
                            class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500"
                        >
                            Quiz previews are available after enrolment.
                        </div>

                        <div
                            v-if="lesson.attachments?.length"
                            class="mt-8 rounded-xl border border-gray-100 bg-white px-4 py-5 shadow-sm sm:px-6"
                        >
                            <h2
                                class="mb-2 text-sm font-semibold text-[#000928]"
                            >
                                Resources
                            </h2>
                            <p class="mb-4 text-xs text-gray-500">
                                Download files shared for this preview lesson.
                            </p>
                            <ul class="space-y-2">
                                <li
                                    v-for="file in lesson.attachments"
                                    :key="file.id"
                                >
                                    <a
                                        :href="attachmentUrl(file.file_url)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm transition-colors hover:border-[#42b6c5]/40 hover:bg-gray-50"
                                    >
                                        <span
                                            class="min-w-0 flex-1 font-medium text-gray-800"
                                            >{{ file.name }}</span
                                        >
                                        <span
                                            class="shrink-0 text-xs text-gray-500"
                                            >{{
                                                file.formatted_file_size
                                            }}</span
                                        >
                                        <Download
                                            class="h-4 w-4 shrink-0 text-[#42b6c5]"
                                            aria-hidden="true"
                                        />
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-8 text-center">
                        <Link
                            :href="`/online-courses/${course.id}`"
                            class="lms-btn-primary inline-flex items-center"
                        >
                            Back to course
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
