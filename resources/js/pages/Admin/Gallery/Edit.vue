<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Item {
  id: number
  title: string
  slug: string
  type: 'image' | 'youtube_video'
  description: string | null
  youtube_url: string | null
  tags: string[]
  sort_order: number
  is_active: boolean
}

interface Props {
  item: Item
  tagsText: string
}

const props = defineProps<Props>()
defineOptions({ layout: AppLayout })
const toast = useToast()

const form = useForm({
  title: props.item.title,
  type: props.item.type,
  description: props.item.description || '',
  image: null as File | null,
  youtube_url: props.item.youtube_url || '',
  tags: props.tagsText || '',
  sort_order: props.item.sort_order,
  is_active: props.item.is_active,
  _method: 'PUT',
})

const submit = () => {
  form.post(`/admin/gallery/${props.item.slug}`, {
    forceFormData: true,
    onSuccess: () => toast.success('Gallery item updated successfully!'),
    onError: () => toast.error('Failed to update gallery item.'),
  })
}
</script>

<template>
  <div>
    <Head :title="`Edit ${item.title}`" />

    <div class="mb-8">
      <Link href="/admin/gallery" class="text-[#42b6c5] hover:text-[#35919e]">← Back to Gallery</Link>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">Edit Gallery Item</h2>
    </div>

    <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-5">
      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Title *</label>
        <input v-model="form.title" type="text" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Type *</label>
        <select v-model="form.type" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
          <option value="image">Image</option>
          <option value="youtube_video">YouTube Video</option>
        </select>
      </div>

      <div v-if="form.type === 'image'">
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Replace Image (optional)</label>
        <input type="file" accept="image/*" @change="form.image = ($event.target as HTMLInputElement).files?.[0] ?? null" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
      </div>

      <div v-else>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">YouTube URL *</label>
        <input v-model="form.youtube_url" type="url" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Description</label>
        <textarea v-model="form.description" rows="4" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1 dark:text-gray-200">Tags</label>
        <input v-model="form.tags" type="text" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
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
        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-[#42b6c5] text-white rounded-lg disabled:opacity-50">{{ form.processing ? 'Saving...' : 'Save Changes' }}</button>
      </div>
    </form>
  </div>
</template>
