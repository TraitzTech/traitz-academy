<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { ref, watch } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface ResourceItem {
    id: number;
    title: string;
    slug: string;
    type: 'document' | 'youtube_video' | 'writing' | 'external_link';
    description: string | null;
    tags: string[];
    sort_order: number;
    is_active: boolean;
}

interface Props {
    resources: {
        data: ResourceItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        type?: string;
        status?: string;
        tag?: string;
    };
}

const props = defineProps<Props>();
defineOptions({ layout: AppLayout });
const toast = useToast();

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const status = ref(props.filters.status || '');

const applyFilters = debounce(() => {
    router.get(
        '/admin/learning-resources',
        {
            search: search.value || undefined,
            type: type.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch([search, type, status], applyFilters);

const remove = (resource: ResourceItem) => {
    if (!confirm(`Delete "${resource.title}"?`)) return;

    router.delete(`/admin/learning-resources/${resource.slug}`, {
        // Flash message handled by global watcher (LearningResourceController::destroy flashes 'success')
        onError: () => toast.error('Failed to delete resource.'),
    });
};

const labelForType = (type: string) => {
    switch (type) {
        case 'document':
            return 'Document';
        case 'youtube_video':
            return 'Video';
        case 'writing':
            return 'Writing';
        case 'external_link':
            return 'Link';
        default:
            return type;
    }
};
</script>

<template>
    <div>
        <Head title="Manage Learning Resources" />

        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Learning Resources
                </h2>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Manage educational documents, videos, writings, and links
                </p>
            </div>
            <Link
                href="/admin/learning-resources/create"
                class="inline-flex items-center rounded-lg bg-[#42b6c5] px-5 py-3 font-semibold text-white transition-colors hover:bg-[#35919e]"
            >
                Add Resource
            </Link>
        </div>

        <div
            class="mb-6 grid grid-cols-1 gap-4 rounded-lg bg-white p-4 shadow md:grid-cols-3 dark:bg-gray-800"
        >
            <input
                v-model="search"
                type="text"
                placeholder="Search resources..."
                class="w-full rounded-lg border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
            />
            <select
                v-model="type"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
            >
                <option value="">All Types</option>
                <option value="document">Document</option>
                <option value="youtube_video">YouTube Video</option>
                <option value="writing">Writing</option>
                <option value="external_link">External Link</option>
            </select>
            <select
                v-model="status"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
            >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Hidden</option>
            </select>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="resource in resources.data"
                :key="resource.id"
                class="rounded-xl border border-gray-100 bg-white p-5 shadow dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="mb-2 flex items-start justify-between gap-2">
                    <h3
                        class="line-clamp-2 font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{ resource.title }}
                    </h3>
                    <span
                        class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                        >{{ labelForType(resource.type) }}</span
                    >
                </div>
                <p
                    v-if="resource.description"
                    class="mb-3 line-clamp-3 text-sm text-gray-600 dark:text-gray-400"
                >
                    {{ resource.description }}
                </p>
                <div class="mb-3 flex flex-wrap gap-2">
                    <span
                        v-for="tag in resource.tags || []"
                        :key="tag"
                        class="rounded-full bg-[#381998]/10 px-2 py-1 text-xs text-[#381998]"
                        >#{{ tag }}</span
                    >
                </div>
                <div
                    class="mb-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"
                >
                    <span>Order: {{ resource.sort_order }}</span>
                    <span
                        :class="
                            resource.is_active
                                ? 'text-green-600'
                                : 'text-red-500'
                        "
                        >{{ resource.is_active ? 'Active' : 'Hidden' }}</span
                    >
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="`/resources/${resource.slug}`"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:text-gray-200"
                        >View</Link
                    >
                    <Link
                        :href="`/admin/learning-resources/${resource.slug}/edit`"
                        class="rounded-lg bg-[#381998] px-3 py-2 text-sm text-white"
                        >Edit</Link
                    >
                    <button
                        @click="remove(resource)"
                        class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="resources.data.length === 0"
            class="mt-8 text-center text-gray-500 dark:text-gray-400"
        >
            No resources found.
        </div>

        <div class="mt-8 flex flex-wrap gap-2">
            <component
                :is="link.url ? Link : 'span'"
                v-for="(link, index) in resources.links"
                :key="index"
                :href="link.url || ''"
                v-html="link.label"
                class="rounded border px-3 py-1 text-sm"
                :class="[
                    link.active
                        ? 'border-[#42b6c5] bg-[#42b6c5] text-white'
                        : 'border-gray-300 bg-white text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200',
                    !link.url ? 'cursor-not-allowed opacity-50' : '',
                ]"
            />
        </div>
    </div>
</template>
