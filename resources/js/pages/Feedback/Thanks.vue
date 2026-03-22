<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import PublicLayout from '@/layouts/PublicLayout.vue'

defineProps<{
  form: { title: string; description: string | null }
}>()
</script>

<template>
  <Head :title="`Thank You — ${form.title}`" />

  <PublicLayout>
    <div class="min-h-screen bg-gradient-to-br from-[#f0fafc] via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center py-10 px-4">
      <div class="max-w-lg mx-auto text-center">

        <!-- Success icon -->
        <div class="relative inline-flex items-center justify-center mb-8">
          <div class="absolute inset-0 rounded-full bg-[#42b6c5]/20 animate-ping" />
          <div class="relative w-24 h-24 rounded-full bg-gradient-to-br from-[#42b6c5] to-blue-400 flex items-center justify-center shadow-2xl shadow-[#42b6c5]/30">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>

        <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">
          Thank You! 🎉
        </h1>
        <h2 class="text-xl font-semibold text-[#42b6c5] mb-4">{{ form.title }}</h2>
        <p class="text-gray-500 dark:text-gray-400 text-base leading-relaxed max-w-sm mx-auto">
          Your feedback has been successfully recorded. We truly appreciate you taking the time to share your thoughts — it helps us make Traitz Academy better every day!
        </p>

        <div class="mt-10 space-y-3">
          <a
            href="/"
            class="block w-full py-3.5 bg-[#42b6c5] text-white font-semibold rounded-xl hover:bg-[#35a3b2] transition-colors shadow-lg shadow-[#42b6c5]/20"
          >
            Return to Traitz Academy
          </a>
          <a
            href="/dashboard"
            class="block w-full py-3.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            Go to Dashboard
          </a>
        </div>

        <p class="text-center text-xs text-gray-400 mt-8">
          Powered by <span class="font-semibold text-[#42b6c5]">Traitz Academy</span>
        </p>
      </div>
    </div>
  </PublicLayout>
</template>
