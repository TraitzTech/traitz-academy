<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { ref, watch } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface GalleryItem {
    id: number;
    title: string;
    slug: string;
    type: 'image' | 'youtube_video';
    image_path: string | null;
    youtube_url: string | null;
    description: string | null;
    tags: string[];
    sort_order: number;
    is_active: boolean;
}

interface Props {
    items: {
        data: GalleryItem[];
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
        '/admin/gallery',
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

const remove = (item: GalleryItem) => {
    if (!confirm(`Delete "${item.title}"?`)) return;

    router.delete(`/admin/gallery/${item.slug}`, {
        // Flash message handled by global watcher (GalleryItemController::destroy flashes 'success')
        onError: () => toast.error('Failed to delete gallery item.'),
    });
};

const mediaThumbnail = (item: GalleryItem) => {
    if (item.type === 'youtube_video') return null;
    if (!item.image_path) return null;
    return item.image_path.startsWith('http')
        ? item.image_path
        : `/storage/${item.image_path}`;
};
</script>

<template>
    <div>
        <Head title="Manage Gallery" />

        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Gallery
                </h2>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Manage homepage gallery images and videos
                </p>
            </div>
            <Link
                href="/admin/gallery/create"
                class="inline-flex items-center rounded-lg bg-[#42b6c5] px-5 py-3 font-semibold text-white transition-colors hover:bg-[#35919e]"
            >
                Add Gallery Item
            </Link>
        </div>

        <div
            class="mb-6 grid grid-cols-1 gap-4 rounded-lg bg-white p-4 shadow md:grid-cols-3 dark:bg-gray-800"
        >
            <input
                v-model="search"
                type="text"
                placeholder="Search title..."
                class="w-full rounded-lg border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
            />
            <select
                v-model="type"
                class="w-full rounded-lg border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
            >
                <option value="">All Types</option>
                <option value="image">Image</option>
                <option value="youtube_video">YouTube Video</option>
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
                v-for="item in items.data"
                :key="item.id"
                class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow dark:border-gray-700 dark:bg-gray-800"
            >
                <div
                    class="flex h-44 items-center justify-center bg-gray-100 dark:bg-gray-700"
                >
                    <img
                        v-if="mediaThumbnail(item)"
                        :src="mediaThumbnail(item) || ''"
                        :alt="item.title"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="px-4 text-center text-sm font-semibold text-gray-500 dark:text-gray-300"
                    >
                        YouTube Video
                    </div>
                </div>
                <div class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <h3
                            class="line-clamp-2 font-bold text-gray-900 dark:text-gray-100"
                        >
                            {{ item.title }}
                        </h3>
                        <span
                            class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                            >{{
                                item.type === 'image' ? 'Image' : 'Video'
                            }}</span
                        >
                    </div>
                    <p
                        v-if="item.description"
                        class="line-clamp-2 text-sm text-gray-600 dark:text-gray-400"
                    >
                        {{ item.description }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="tag in item.tags || []"
                            :key="tag"
                            class="rounded-full bg-[#42b6c5]/10 px-2 py-1 text-xs text-[#42b6c5]"
                            >#{{ tag }}</span
                        >
                    </div>
                    <div
                        class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"
                    >
                        <span>Order: {{ item.sort_order }}</span>
                        <span
                            :class="
                                item.is_active
                                    ? 'text-green-600'
                                    : 'text-red-500'
                            "
                            >{{ item.is_active ? 'Active' : 'Hidden' }}</span
                        >
                    </div>
                    <div class="flex items-center gap-2 pt-2">
                        <Link
                            :href="`/gallery/${item.slug}`"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:text-gray-200"
                            >View</Link
                        >
                        <Link
                            :href="`/admin/gallery/${item.slug}/edit`"
                            class="rounded-lg bg-[#381998] px-3 py-2 text-sm text-white"
                            >Edit</Link
                        >
                        <button
                            @click="remove(item)"
                            class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="items.data.length === 0"
            class="mt-8 text-center text-gray-500 dark:text-gray-400"
        >
            No gallery items found.
        </div>

        <div class="mt-8 flex flex-wrap gap-2">
            <component
                :is="link.url ? Link : 'span'"
                v-for="(link, index) in items.links"
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
