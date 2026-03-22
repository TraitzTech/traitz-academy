<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
const toast = useToast()

const form = useForm({
  title: '',
  type: 'document',
  description: '',
  document: null as File | null,
  youtube_url: '',
  external_url: '',
  content: '',
  tags: '',
  sort_order: 0,
  is_active: true,
})

const submit = () => {
  form.post('/admin/learning-resources', {
    forceFormData: true,
    onSuccess: () => toast.success('Resource created successfully!'),
    onError: () => toast.error('Failed to create resource.'),
  })
}
</script>

<template>
  <div>
    <Head title="Create Learning Resource" />

    <div class="mb-8">
      <Link href="/admin/learning-resources" class="text-[#42b6c5] hover:text-[#35919e]">← Back to Resources</Link>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">Create Learning Resource</h2>
    </div>

    <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-5">
      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Title *</label>
        <input v-model="form.title" type="text" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Type *</label>
        <select v-model="form.type" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
          <option value="document">Document</option>
          <option value="youtube_video">YouTube Video</option>
          <option value="writing">Writing</option>
          <option value="external_link">External Link</option>
        </select>
      </div>

      <div v-if="form.type === 'document'">
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Document *</label>
        <input type="file" @change="form.document = ($event.target as HTMLInputElement).files?.[0] ?? null" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
        <p class="text-xs text-gray-500 mt-1">Accepted: PDF, DOC, DOCX, PPT, XLS, ZIP, TXT (max 10MB)</p>
      </div>

      <div v-if="form.type === 'youtube_video'">
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">YouTube URL *</label>
        <input v-model="form.youtube_url" type="url" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
      </div>

      <div v-if="form.type === 'external_link'">
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">External URL *</label>
        <input v-model="form.external_url" type="url" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
      </div>

      <div v-if="form.type === 'writing'">
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Writing Content *</label>
        <textarea v-model="form.content" rows="8" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Description</label>
        <textarea v-model="form.description" rows="4" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Tags</label>
        <input v-model="form.tags" type="text" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" placeholder="laravel, frontend, design">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1 dark:text-gray-200">Sort Order</label>
          <input v-model="form.sort_order" type="number" min="0" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
        </div>
        <label class="inline-flex items-center mt-7">
          <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-[#42b6c5] focus:ring-[#42b6c5]">
          <span class="ml-2 text-sm dark:text-gray-200">Visible on homepage</span>
        </label>
      </div>

      <div class="pt-2 flex justify-end gap-3">
        <Link href="/admin/learning-resources" class="px-4 py-2 border rounded-lg dark:border-gray-600 dark:text-gray-200">Cancel</Link>
        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-[#42b6c5] text-white rounded-lg disabled:opacity-50">{{ form.processing ? 'Saving...' : 'Save Resource' }}</button>
      </div>
    </form>
  </div>
</template>
