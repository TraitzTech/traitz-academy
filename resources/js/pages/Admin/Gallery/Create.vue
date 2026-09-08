<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
const toast = useToast();

const form = useForm({
    title: '',
    type: 'image',
    description: '',
    image: null as File | null,
    youtube_url: '',
    tags: '',
    sort_order: 0,
    is_active: true,
});

const submit = () => {
    form.post('/admin/gallery', {
        forceFormData: true,
        // Flash message handled by global watcher (GalleryItemController::store flashes 'success')
        onError: () => toast.error('Failed to create gallery item.'),
    });
};
</script>

<template>
    <div class="mx-auto max-w-5xl">
        <Head title="Create Gallery Item" />

        <div class="mb-8">
            <Link
                href="/admin/gallery"
                class="text-[#42b6c5] hover:text-[#35919e]"
                >← Back to Gallery</Link
            >
            <h2
                class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100"
            >
                Create Gallery Item
            </h2>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-5 rounded-xl bg-white p-6 shadow dark:bg-gray-800"
        >
            <div>
                <label class="mb-1 block text-sm font-medium dark:text-gray-200"
                    >Title *</label
                >
                <input
                    v-model="form.title"
                    type="text"
                    class="w-full rounded-lg border px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                />
                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                    {{ form.errors.title }}
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium dark:text-gray-200"
                    >Type *</label
                >
                <select
                    v-model="form.type"
                    class="w-full rounded-lg border px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                >
                    <option value="image">Image</option>
                    <option value="youtube_video">YouTube Video</option>
                </select>
            </div>

            <div v-if="form.type === 'image'">
                <label class="mb-1 block text-sm font-medium dark:text-gray-200"
                    >Image *</label
                >
                <input
                    type="file"
                    accept="image/*"
                    @change="
                        form.image =
                            ($event.target as HTMLInputElement).files?.[0] ??
                            null
                    "
                    class="w-full rounded-lg border px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                />
                <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">
                    {{ form.errors.image }}
                </p>
            </div>

            <div v-else>
                <label class="mb-1 block text-sm font-medium dark:text-gray-200"
                    >YouTube URL *</label
                >
                <input
                    v-model="form.youtube_url"
                    type="url"
                    class="w-full rounded-lg border px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    placeholder="https://www.youtube.com/watch?v=..."
                />
                <p
                    v-if="form.errors.youtube_url"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.youtube_url }}
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium dark:text-gray-200"
                    >Description</label
                >
                <textarea
                    v-model="form.description"
                    rows="4"
                    class="w-full rounded-lg border px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium dark:text-gray-200"
                    >Tags</label
                >
                <input
                    v-model="form.tags"
                    type="text"
                    class="w-full rounded-lg border px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    placeholder="campus, workshop, 2026"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-sm font-medium dark:text-gray-200"
                        >Sort Order</label
                    >
                    <input
                        v-model="form.sort_order"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                </div>
                <label class="mt-7 inline-flex items-center">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600"
                    />
                    <span class="ml-2 text-sm dark:text-gray-200"
                        >Visible on homepage</span
                    >
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <Link
                    href="/admin/gallery"
                    class="rounded-lg border px-4 py-2 dark:border-gray-600 dark:text-gray-200"
                    >Cancel</Link
                >
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-[#42b6c5] px-5 py-2 text-white disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save Item' }}
                </button>
            </div>
        </form>
    </div>
</template>
