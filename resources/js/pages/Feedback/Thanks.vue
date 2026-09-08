<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PublicLayout from '@/layouts/PublicLayout.vue';

defineProps<{
    form: { title: string; description: string | null };
}>();
</script>

<template>
    <Head :title="`Thank You — ${form.title}`" />

    <PublicLayout>
        <div
            class="flex min-h-screen items-center justify-center bg-gradient-to-br from-[#f0fafc] via-white to-blue-50 px-4 py-10 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800"
        >
            <div class="mx-auto max-w-lg text-center">
                <!-- Success icon -->
                <div
                    class="relative mb-8 inline-flex items-center justify-center"
                >
                    <div
                        class="absolute inset-0 animate-ping rounded-full bg-[#42b6c5]/20"
                    />
                    <div
                        class="relative flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-[#42b6c5] to-blue-400 shadow-2xl shadow-[#42b6c5]/30"
                    >
                        <svg
                            class="h-12 w-12 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                </div>

                <h1
                    class="mb-3 text-3xl font-bold text-gray-900 lg:text-4xl dark:text-gray-100"
                >
                    Thank You! 🎉
                </h1>
                <h2 class="mb-4 text-xl font-semibold text-[#42b6c5]">
                    {{ form.title }}
                </h2>
                <p
                    class="mx-auto max-w-sm text-base leading-relaxed text-gray-500 dark:text-gray-400"
                >
                    Your feedback has been successfully recorded. We truly
                    appreciate you taking the time to share your thoughts — it
                    helps us make Traitz Academy better every day!
                </p>

                <div class="mt-10 space-y-3">
                    <a
                        href="/"
                        class="block w-full rounded-xl bg-[#42b6c5] py-3.5 font-semibold text-white shadow-lg shadow-[#42b6c5]/20 transition-colors hover:bg-[#35a3b2]"
                    >
                        Return to Traitz Academy
                    </a>
                    <a
                        href="/dashboard"
                        class="block w-full rounded-xl border border-gray-200 bg-white py-3.5 font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        Go to Dashboard
                    </a>
                </div>

                <p class="mt-8 text-center text-xs text-gray-400">
                    Powered by
                    <span class="font-semibold text-[#42b6c5]"
                        >Traitz Academy</span
                    >
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
