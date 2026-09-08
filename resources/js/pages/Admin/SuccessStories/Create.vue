<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const toast = useToast();

const form = useForm({
    name: '',
    role: '',
    company: '',
    story: '',
    image: null as File | null,
    is_active: true,
    sort_order: 0,
});

const imagePreview = ref<string | null>(null);

const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.image = target.files[0];
        imagePreview.value = URL.createObjectURL(target.files[0]);
    }
};

const submit = () => {
    form.post('/admin/success-stories', {
        forceFormData: true,
        onSuccess: () => {
            // Flash message handled by global watcher (SuccessStoryController::store flashes 'success')
        },
        onError: () => {
            toast.error(
                'Failed to create story. Please check the form for errors.',
            );
        },
    });
};
</script>

<template>
    <div class="mx-auto max-w-5xl">
        <Head title="Add Success Story" />

        <!-- Header -->
        <div class="mb-8">
            <Link
                href="/admin/success-stories"
                class="mb-4 inline-flex items-center text-[#42b6c5] hover:text-[#35919e]"
            >
                <svg
                    class="mr-2 h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                Back to Success Stories
            </Link>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Add Success Story
            </h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Add a new student testimonial to display on the homepage
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <!-- Person Information -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Person Information
                </h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Full Name *</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="e.g., John Doe"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Role / Job Title</label
                        >
                        <input
                            v-model="form.role"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="e.g., Software Engineer"
                        />
                        <p
                            v-if="form.errors.role"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.role }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Company / Organization</label
                        >
                        <input
                            v-model="form.company"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="e.g., Tech Startup Lagos"
                        />
                        <p
                            v-if="form.errors.company"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.company }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Story -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Testimonial
                </h3>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Story / Quote *</label
                    >
                    <textarea
                        v-model="form.story"
                        rows="4"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        placeholder="Write the student's testimonial here..."
                    ></textarea>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Maximum 1000 characters. Current:
                        {{ form.story.length }}
                    </p>
                    <p
                        v-if="form.errors.story"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.story }}
                    </p>
                </div>
            </div>

            <!-- Image -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Photo
                </h3>
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0">
                        <div
                            v-if="imagePreview"
                            class="h-24 w-24 overflow-hidden rounded-full"
                        >
                            <img
                                :src="imagePreview"
                                alt="Preview"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div
                            v-else
                            class="flex h-24 w-24 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700"
                        >
                            <svg
                                class="h-12 w-12 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Profile Photo</label
                        >
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            @change="handleImageChange"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Recommended: Square image, max 2MB
                        </p>
                        <p
                            v-if="form.errors.image"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.image }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Settings
                </h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Display Order</label
                        >
                        <input
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Lower numbers appear first
                        </p>
                        <p
                            v-if="form.errors.sort_order"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.sort_order }}
                        </p>
                    </div>

                    <div class="flex items-center">
                        <label class="flex cursor-pointer items-center">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-5 w-5 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700"
                            />
                            <span
                                class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300"
                                >Publish on homepage</span
                            >
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4">
                <Link
                    href="/admin/success-stories"
                    class="rounded-lg border border-gray-300 px-6 py-3 font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-[#42b6c5] px-6 py-3 font-semibold text-white transition-colors hover:bg-[#35919e] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create Story' }}
                </button>
            </div>
        </form>
    </div>
</template>
