<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { ref, watch } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface GalleryItem {
  id: number
  title: string
  slug: string
  type: 'image' | 'youtube_video'
  image_path: string | null
  youtube_url: string | null
  description: string | null
  tags: string[]
  sort_order: number
  is_active: boolean
}

interface Props {
  items: {
    data: GalleryItem[]
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  filters: {
    search?: string
    type?: string
    status?: string
    tag?: string
  }
}

const props = defineProps<Props>()
defineOptions({ layout: AppLayout })
const toast = useToast()

const search = ref(props.filters.search || '')
const type = ref(props.filters.type || '')
const status = ref(props.filters.status || '')

const applyFilters = debounce(() => {
  router.get('/admin/gallery', {
    search: search.value || undefined,
    type: type.value || undefined,
    status: status.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}, 300)

watch([search, type, status], applyFilters)

const remove = (item: GalleryItem) => {
  if (!confirm(`Delete "${item.title}"?`)) return

  router.delete(`/admin/gallery/${item.slug}`, {
    onSuccess: () => toast.success('Gallery item deleted successfully!'),
    onError: () => toast.error('Failed to delete gallery item.'),
  })
}

const mediaThumbnail = (item: GalleryItem) => {
  if (item.type === 'youtube_video') return null
  if (!item.image_path) return null
  return item.image_path.startsWith('http') ? item.image_path : `/storage/${item.image_path}`
}
</script>

<template>
  <div>
    <Head title="Manage Gallery" />

    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Gallery</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Manage homepage gallery images and videos</p>
      </div>
      <Link
        href="/admin/gallery/create"
        class="inline-flex items-center px-5 py-3 bg-[#42b6c5] text-white rounded-lg font-semibold hover:bg-[#35919e] transition-colors"
      >
        Add Gallery Item
      </Link>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <input
        v-model="search"
        type="text"
        placeholder="Search title..."
        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100"
      >
      <select
        v-model="type"
        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100"
      >
        <option value="">All Types</option>
        <option value="image">Image</option>
        <option value="youtube_video">YouTube Video</option>
      </select>
      <select
        v-model="status"
        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100"
      >
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Hidden</option>
      </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="item in items.data"
        :key="item.id"
        class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden border border-gray-100 dark:border-gray-700"
      >
        <div class="h-44 bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
          <img
            v-if="mediaThumbnail(item)"
            :src="mediaThumbnail(item) || ''"
            :alt="item.title"
            class="w-full h-full object-cover"
          >
          <div v-else class="text-gray-500 dark:text-gray-300 text-sm font-semibold px-4 text-center">
            YouTube Video
          </div>
        </div>
        <div class="p-4 space-y-3">
          <div class="flex items-start justify-between gap-2">
            <h3 class="font-bold text-gray-900 dark:text-gray-100 line-clamp-2">{{ item.title }}</h3>
            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">{{ item.type === 'image' ? 'Image' : 'Video' }}</span>
          </div>
          <p v-if="item.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ item.description }}</p>
          <div class="flex flex-wrap gap-2">
            <span v-for="tag in item.tags || []" :key="tag" class="text-xs bg-[#42b6c5]/10 text-[#42b6c5] px-2 py-1 rounded-full">#{{ tag }}</span>
          </div>
          <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>Order: {{ item.sort_order }}</span>
            <span :class="item.is_active ? 'text-green-600' : 'text-red-500'">{{ item.is_active ? 'Active' : 'Hidden' }}</span>
          </div>
          <div class="pt-2 flex items-center gap-2">
            <Link :href="`/gallery/${item.slug}`" class="text-sm px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:text-gray-200">View</Link>
            <Link :href="`/admin/gallery/${item.slug}/edit`" class="text-sm px-3 py-2 rounded-lg bg-[#381998] text-white">Edit</Link>
            <button @click="remove(item)" class="text-sm px-3 py-2 rounded-lg bg-red-600 text-white">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="items.data.length === 0" class="mt-8 text-center text-gray-500 dark:text-gray-400">No gallery items found.</div>

    <div class="mt-8 flex flex-wrap gap-2">
      <component
        :is="link.url ? Link : 'span'"
        v-for="(link, index) in items.links"
        :key="index"
        :href="link.url || ''"
        v-html="link.label"
        class="px-3 py-1 rounded border text-sm"
        :class="[
          link.active ? 'bg-[#42b6c5] text-white border-[#42b6c5]' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600',
          !link.url ? 'opacity-50 cursor-not-allowed' : ''
        ]"
      />
    </div>
  </div>
</template>
