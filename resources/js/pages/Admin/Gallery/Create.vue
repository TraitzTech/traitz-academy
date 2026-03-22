<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
const toast = useToast()

const form = useForm({
  title: '',
  type: 'image',
  description: '',
  image: null as File | null,
  youtube_url: '',
  tags: '',
  sort_order: 0,
  is_active: true,
})

const submit = () => {
  form.post('/admin/gallery', {
    forceFormData: true,
    onSuccess: () => toast.success('Gallery item created successfully!'),
    onError: () => toast.error('Failed to create gallery item.'),
  })
}
</script>

<template>
  <div>
    <Head title="Create Gallery Item" />

    <div class="mb-8">
      <Link href="/admin/gallery" class="text-[#42b6c5] hover:text-[#35919e]">← Back to Gallery</Link>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">Create Gallery Item</h2>
    </div>

    <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-5">
      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Title *</label>
        <input v-model="form.title" type="text" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
        <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Type *</label>
        <select v-model="form.type" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
          <option value="image">Image</option>
          <option value="youtube_video">YouTube Video</option>
        </select>
      </div>

      <div v-if="form.type === 'image'">
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Image *</label>
        <input type="file" accept="image/*" @change="form.image = ($event.target as HTMLInputElement).files?.[0] ?? null" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
        <p v-if="form.errors.image" class="text-sm text-red-600 mt-1">{{ form.errors.image }}</p>
      </div>

      <div v-else>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">YouTube URL *</label>
        <input v-model="form.youtube_url" type="url" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" placeholder="https://www.youtube.com/watch?v=...">
        <p v-if="form.errors.youtube_url" class="text-sm text-red-600 mt-1">{{ form.errors.youtube_url }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Description</label>
        <textarea v-model="form.description" rows="4" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Tags</label>
        <input v-model="form.tags" type="text" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" placeholder="campus, workshop, 2026">
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
        <Link href="/admin/gallery" class="px-4 py-2 border rounded-lg dark:border-gray-600 dark:text-gray-200">Cancel</Link>
        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-[#42b6c5] text-white rounded-lg disabled:opacity-50">{{ form.processing ? 'Saving...' : 'Save Item' }}</button>
      </div>
    </form>
  </div>
</template>
