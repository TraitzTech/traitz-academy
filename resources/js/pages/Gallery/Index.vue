<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import { useToast } from '@/composables/useToast'
import PublicLayout from '@/layouts/PublicLayout.vue'

interface GalleryItem {
  id: number
  title: string
  slug: string
  type: 'image' | 'youtube_video'
  image_path: string | null
  youtube_url: string | null
  description: string | null
  tags: string[]
}

interface PaginatedItems {
  data: GalleryItem[]
}

interface Props {
  items: PaginatedItems
}

const props = defineProps<Props>()
const toast = useToast()

const activeTag = ref('all')
const lightboxImage = ref<GalleryItem | null>(null)

const mediaUrl = (path: string | null) => {
  if (!path) {
    return null
  }

  return path.startsWith('http') ? path : `/storage/${path}`
}

const normalizeYouTubeUrl = (url: string | null) => {
  if (!url) {
    return null
  }

  if (url.includes('embed/')) {
    return url
  }

  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/
  const match = url.match(regExp)

  if (match && match[2].length === 11) {
    return `https://www.youtube.com/embed/${match[2]}?autoplay=0&modestbranding=1&rel=0`
  }

  return url
}

const tags = computed(() => {
  const allTags = new Set<string>()

  props.items.data.forEach((item) => {
    ;(item.tags || []).forEach((tag) => allTags.add(tag))
  })

  return ['all', ...Array.from(allTags)]
})

const filteredItems = computed(() => {
  if (activeTag.value === 'all') {
    return props.items.data
  }

  return props.items.data.filter((item) => (item.tags || []).includes(activeTag.value))
})

const copyShareLink = async (path: string) => {
  try {
    const absoluteUrl = `${window.location.origin}${path}`
    await navigator.clipboard.writeText(absoluteUrl)
    toast.success('Share link copied successfully!')
  } catch {
    toast.error('Unable to copy link. Please copy it manually from the address bar.')
  }
}
</script>

<template>
  <PublicLayout>
    <Head title="Gallery - Traitz Academy" />

    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h1 class="text-4xl md:text-5xl font-bold text-[#000928] mb-4">Gallery</h1>
          <p class="text-gray-600 text-lg max-w-3xl mx-auto">Explore our moments through images and videos from trainings, workshops, and events.</p>
        </div>

        <div v-if="tags.length > 1" class="flex flex-wrap justify-center gap-2 mb-8">
          <button
            v-for="tag in tags"
            :key="tag"
            @click="activeTag = tag"
            class="px-3 py-1.5 text-sm rounded-full border transition-colors"
            :class="activeTag === tag ? 'bg-[#42b6c5] border-[#42b6c5] text-white' : 'bg-white border-gray-300 text-gray-700 hover:border-[#42b6c5] hover:text-[#42b6c5]'"
          >
            {{ tag === 'all' ? 'All' : `#${tag}` }}
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <article
            v-for="item in filteredItems"
            :key="item.id"
            class="group rounded-2xl overflow-hidden border border-gray-200 shadow-sm hover:shadow-xl transition-all duration-300 bg-white"
          >
            <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
              <img
                v-if="item.type === 'image'"
                :src="mediaUrl(item.image_path) || ''"
                :alt="item.title"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
              >
              <iframe
                v-else
                :src="normalizeYouTubeUrl(item.youtube_url) || ''"
                class="w-full h-full"
                allowfullscreen
              />
              <button
                v-if="item.type === 'image'"
                @click="lightboxImage = item"
                class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"
                aria-label="View full image"
              />
            </div>
            <div class="p-4">
              <div class="flex items-start justify-between gap-3">
                <h3 class="font-bold text-[#000928] line-clamp-2">{{ item.title }}</h3>
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700">{{ item.type === 'image' ? 'Image' : 'Video' }}</span>
              </div>
              <p v-if="item.description" class="mt-2 text-sm text-gray-600 line-clamp-2">{{ item.description }}</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="tag in item.tags || []" :key="tag" class="text-xs px-2 py-1 rounded-full bg-[#42b6c5]/10 text-[#42b6c5]">#{{ tag }}</span>
              </div>
              <div class="mt-4 flex items-center justify-between">
                <Link :href="`/gallery/${item.slug}`" class="text-sm font-semibold text-[#381998] hover:text-[#42b6c5]">View details</Link>
                <button @click="copyShareLink(`/gallery/${item.slug}`)" class="text-xs text-gray-600 hover:text-[#42b6c5]">Share</button>
              </div>
            </div>
          </article>
        </div>

        <p v-if="filteredItems.length === 0" class="text-center text-gray-500 mt-8">No gallery items found for this tag yet.</p>
      </div>
    </section>

    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="lightboxImage" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4" @click="lightboxImage = null">
        <div class="relative max-w-6xl w-full" @click.stop>
          <button class="absolute -top-12 right-0 text-white text-sm hover:text-[#42b6c5]" @click="lightboxImage = null">Close ✕</button>
          <img :src="mediaUrl(lightboxImage.image_path) || ''" :alt="lightboxImage.title" class="w-full max-h-[85vh] object-contain rounded-xl shadow-2xl">
          <div class="mt-3 text-white">
            <h3 class="font-semibold text-lg">{{ lightboxImage.title }}</h3>
            <p v-if="lightboxImage.description" class="text-sm text-gray-300">{{ lightboxImage.description }}</p>
          </div>
        </div>
      </div>
    </transition>
  </PublicLayout>
</template>
