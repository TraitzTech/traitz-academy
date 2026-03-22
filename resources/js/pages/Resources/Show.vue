<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

import { useToast } from '@/composables/useToast'
import PublicLayout from '@/layouts/PublicLayout.vue'

interface LearningResource {
  id: number
  title: string
  slug: string
  type: 'document' | 'youtube_video' | 'writing' | 'external_link'
  description: string | null
  document_path: string | null
  youtube_url: string | null
  external_url: string | null
  content: string | null
  tags: string[]
}

interface Props {
  resource: LearningResource
  relatedResources: LearningResource[]
}

const props = defineProps<Props>()
const toast = useToast()

const documentUrl = (path: string | null) => {
  if (!path) return null
  return path.startsWith('http') ? path : `/storage/${path}`
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(window.location.href)
    toast.success('Share link copied successfully!')
  } catch {
    toast.error('Unable to copy link. Please copy it manually from the address bar.')
  }
}

const typeLabel = (type: string) => {
  switch (type) {
    case 'document': return 'Document'
    case 'youtube_video': return 'YouTube Video'
    case 'writing': return 'Writing'
    case 'external_link': return 'External Link'
    default: return type
  }
}
</script>

<template>
  <PublicLayout>
    <Head :title="`${resource.title} - Learning Resource`" />

    <section class="py-16 bg-white">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <Link href="/resources" class="text-[#42b6c5] hover:text-[#35919e]">← Back to Resources</Link>

        <div class="mt-4 flex items-start justify-between gap-4">
          <div>
            <h1 class="text-4xl font-bold text-[#000928]">{{ resource.title }}</h1>
            <p class="text-sm text-gray-500 mt-2">{{ typeLabel(resource.type) }}</p>
            <div class="flex flex-wrap gap-2 mt-3">
              <span v-for="tag in resource.tags || []" :key="tag" class="text-xs px-2 py-1 rounded-full bg-[#381998]/10 text-[#381998]">#{{ tag }}</span>
            </div>
          </div>
          <button @click="copyLink" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">Share</button>
        </div>

        <p v-if="resource.description" class="mt-6 text-gray-700 leading-relaxed">{{ resource.description }}</p>

        <div class="mt-8 rounded-xl border border-gray-200 p-6">
          <a v-if="resource.type === 'document' && resource.document_path" :href="documentUrl(resource.document_path) || ''" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#42b6c5] text-white hover:bg-[#35919e]">Open / Download Document</a>

          <div v-else-if="resource.type === 'youtube_video' && resource.youtube_url" class="aspect-video bg-black rounded-lg overflow-hidden">
            <iframe :src="resource.youtube_url" class="w-full h-full" allowfullscreen />
          </div>

          <a v-else-if="resource.type === 'external_link' && resource.external_url" :href="resource.external_url" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#381998] text-white hover:bg-[#2d1377]">Visit External Resource</a>

          <article v-else-if="resource.type === 'writing'" class="prose prose-slate max-w-none" v-html="resource.content || ''" />
        </div>

        <div v-if="relatedResources.length > 0" class="mt-14">
          <h2 class="text-2xl font-bold text-[#000928] mb-4">Related Resources</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Link v-for="related in relatedResources" :key="related.id" :href="`/resources/${related.slug}`" class="rounded-xl border border-gray-200 p-4 hover:shadow-lg transition-shadow">
              <p class="font-semibold text-[#000928] line-clamp-2">{{ related.title }}</p>
              <p class="text-sm text-gray-500 mt-1">{{ typeLabel(related.type) }}</p>
            </Link>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
