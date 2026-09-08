<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface ResourceRow {
    id: number;
    title: string;
    type: 'document' | 'youtube_video' | 'writing' | 'external_link';
    description: string | null;
    youtube_url: string | null;
    external_url: string | null;
    content: string | null;
    document_url: string | null;
    program: { id: number; title: string } | null;
    created_by: string | null;
    created_at: string | null;
}

defineProps<{
    resources: ResourceRow[];
}>();

const typeLabels: Record<ResourceRow['type'], string> = {
    document: 'Document',
    youtube_video: 'YouTube Video',
    writing: 'Writing',
    external_link: 'External Link',
};

function formatDateTime(value: string | null) {
    if (!value) return '';
    return new Date(value).toLocaleString();
}
</script>

<template>
    <div>
        <Head title="Program Resources" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Program resources
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Materials your supervisor has shared with your program.
            </p>
        </div>

        <div
            v-if="resources.length === 0"
            class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800"
        >
            No resources shared yet.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="resource in resources"
                :key="resource.id"
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                            >
                                {{ resource.title }}
                            </h2>
                            <span
                                class="rounded-full bg-[#42b6c5]/10 px-2 py-0.5 text-xs font-semibold text-[#2a8a96]"
                                >{{ typeLabels[resource.type] }}</span
                            >
                        </div>
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ resource.program?.title }} • Shared by
                            {{ resource.created_by ?? 'Supervisor' }} •
                            {{ formatDateTime(resource.created_at) }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="resource.description"
                    class="mt-3 text-sm text-gray-700 dark:text-gray-300"
                >
                    {{ resource.description }}
                </p>
                <p
                    v-if="resource.type === 'writing' && resource.content"
                    class="mt-3 text-sm whitespace-pre-wrap text-gray-700 dark:text-gray-300"
                >
                    {{ resource.content }}
                </p>

                <a
                    v-if="resource.type === 'document' && resource.document_url"
                    :href="resource.document_url"
                    target="_blank"
                    class="mt-4 inline-flex rounded-lg border border-[#381998] px-3 py-1.5 text-sm font-semibold text-[#381998]"
                >
                    Open document
                </a>
                <a
                    v-else-if="
                        resource.type === 'youtube_video' &&
                        resource.youtube_url
                    "
                    :href="resource.youtube_url"
                    target="_blank"
                    class="mt-4 inline-flex rounded-lg border border-[#381998] px-3 py-1.5 text-sm font-semibold text-[#381998]"
                >
                    Watch video
                </a>
                <a
                    v-else-if="
                        resource.type === 'external_link' &&
                        resource.external_url
                    "
                    :href="resource.external_url"
                    target="_blank"
                    class="mt-4 inline-flex rounded-lg border border-[#381998] px-3 py-1.5 text-sm font-semibold text-[#381998]"
                >
                    Open link
                </a>
            </div>
        </div>
    </div>
</template>
