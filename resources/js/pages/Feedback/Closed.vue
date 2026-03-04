<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import PublicLayout from '@/layouts/PublicLayout.vue'

defineProps<{
  form: { title: string; description: string | null }
}>()
</script>

<template>
  <Head :title="`Closed — ${form.title}`" />

  <PublicLayout>
    <div class="min-h-screen bg-gradient-to-br from-[#f0fafc] via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center py-10 px-4">
      <div class="max-w-lg mx-auto text-center">

        <div class="w-24 h-24 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-8">
          <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>

        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">This Form is Closed</h1>
        <h2 class="text-lg font-semibold text-gray-600 dark:text-gray-400 mb-4">{{ form.title }}</h2>
        <p class="text-gray-500 dark:text-gray-400 leading-relaxed max-w-sm mx-auto">
          This feedback form is no longer accepting responses. It may have been deactivated or the deadline has passed.
        </p>

        <a
          href="/"
          class="mt-8 inline-block px-8 py-3.5 bg-[#42b6c5] text-white font-semibold rounded-xl hover:bg-[#35a3b2] transition-colors"
        >
          Return to Traitz Academy
        </a>

        <p class="text-center text-xs text-gray-400 mt-8">
          Powered by <span class="font-semibold text-[#42b6c5]">Traitz Academy</span>
        </p>
      </div>
    </div>
  </PublicLayout>
</template>
