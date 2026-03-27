<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

import PublicLayout from '@/layouts/PublicLayout.vue'

const props = defineProps<{
  course: { id: number; title: string; slug: string }
  lesson: {
    id: number
    title: string
    type: string
    description: string | null
    content: string | null
    video_url: string | null
    duration: string | null
  }
}>()

function embedUrl(url: string | null): string | null {
  if (!url) return null
  const u = url.trim()
  if (u.includes('youtube.com/watch') || u.includes('youtu.be/')) {
    try {
      const parsed = new URL(u.startsWith('http') ? u : `https://${u}`)
      let id = parsed.searchParams.get('v')
      if (!id && parsed.hostname.includes('youtu.be')) {
        id = parsed.pathname.replace('/', '')
      }
      if (id) return `https://www.youtube.com/embed/${id}`
    } catch {
      return null
    }
  }
  if (u.includes('vimeo.com')) {
    const m = u.match(/vimeo\.com\/(\d+)/)
    if (m) return `https://player.vimeo.com/video/${m[1]}`
  }
  return null
}

function videoSrc(url: string | null): string | null {
  if (!url) return null
  if (url.startsWith('http://') || url.startsWith('https://')) return url
  return `/storage/${url}`
}
</script>

<template>
  <PublicLayout>
    <Head :title="`${lesson.title} — Preview`" />

    <div class="border-b border-gray-100 bg-gray-50/80">
      <div class="mx-auto max-w-4xl px-4 py-3 text-sm">
        <nav class="flex flex-wrap items-center gap-2 text-gray-500">
          <Link href="/" class="hover:text-[#42b6c5]">Home</Link>
          <span>/</span>
          <Link href="/online-courses" class="hover:text-[#42b6c5]">Online Courses</Link>
          <span>/</span>
          <Link :href="`/online-courses/${course.id}`" class="hover:text-[#42b6c5] line-clamp-1">{{ course.title }}</Link>
          <span>/</span>
          <span class="line-clamp-1 font-medium text-[#000928]">Preview</span>
        </nav>
      </div>
    </div>

    <section class="bg-gray-50 py-10">
      <div class="mx-auto max-w-4xl px-4 sm:px-6">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-[#42b6c5]">Free preview</p>
        <h1 class="text-2xl font-bold text-[#000928] sm:text-3xl">{{ lesson.title }}</h1>
        <p v-if="lesson.duration" class="mt-2 text-sm text-gray-500">Duration: {{ lesson.duration }}</p>

        <div class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
          <p v-if="lesson.description" class="mb-6 text-gray-600">{{ lesson.description }}</p>

          <!-- Video -->
          <div v-if="lesson.type === 'video'" class="space-y-4">
            <div v-if="embedUrl(lesson.video_url)" class="aspect-video overflow-hidden rounded-xl bg-black">
              <iframe
                :src="embedUrl(lesson.video_url) ?? undefined"
                class="h-full w-full"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
              />
            </div>
            <video
              v-else-if="videoSrc(lesson.video_url)"
              :src="videoSrc(lesson.video_url) ?? undefined"
              controls
              class="w-full rounded-xl"
            />
            <p v-else class="text-sm text-gray-500">Video for this lesson is not available for preview yet.</p>
          </div>

          <!-- Text -->
          <div v-else-if="lesson.type === 'text'" class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">
            {{ lesson.content || 'No content yet.' }}
          </div>

          <!-- Quiz placeholder -->
          <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500">
            Quiz previews are available after enrolment.
          </div>
        </div>

        <div class="mt-8 text-center">
          <Link
            :href="`/online-courses/${course.id}`"
            class="inline-flex items-center rounded-xl bg-[#000928] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#381998]"
          >
            Back to course
          </Link>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
