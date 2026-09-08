<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PublicLayout from '@/layouts/PublicLayout.vue';

defineProps<{
    form: { title: string; description: string | null };
}>();
</script>

<template>
    <Head :title="`Closed — ${form.title}`" />

    <PublicLayout>
        <div
            class="flex min-h-screen items-center justify-center bg-gradient-to-br from-[#f0fafc] via-white to-blue-50 px-4 py-10 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800"
        >
            <div class="mx-auto max-w-lg text-center">
                <div
                    class="mx-auto mb-8 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700"
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
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </div>

                <h1
                    class="mb-3 text-3xl font-bold text-gray-900 dark:text-gray-100"
                >
                    This Form is Closed
                </h1>
                <h2
                    class="mb-4 text-lg font-semibold text-gray-600 dark:text-gray-400"
                >
                    {{ form.title }}
                </h2>
                <p
                    class="mx-auto max-w-sm leading-relaxed text-gray-500 dark:text-gray-400"
                >
                    This feedback form is no longer accepting responses. It may
                    have been deactivated or the deadline has passed.
                </p>

                <a
                    href="/"
                    class="mt-8 inline-block rounded-xl bg-[#42b6c5] px-8 py-3.5 font-semibold text-white transition-colors hover:bg-[#35a3b2]"
                >
                    Return to Traitz Academy
                </a>

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
