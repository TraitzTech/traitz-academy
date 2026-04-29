<script setup lang="ts">
interface Recording {
  id: number
  file_path: string | null
  youtube_url: string | null
  status: 'processing' | 'uploaded' | 'failed'
}

defineProps<{ recordings: Recording[] }>()

function source(path: string | null): string | null {
  if (!path) return null
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  return `/storage/${path}`
}
</script>

<template>
  <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <h3 class="text-sm font-semibold text-[#000928] dark:text-white">Recordings</h3>
    <div v-if="recordings.length === 0" class="mt-3 rounded-lg border border-dashed border-gray-200 p-3 text-xs text-gray-500 dark:border-gray-600">
      No recordings yet.
    </div>
    <div v-else class="mt-3 space-y-4">
      <div v-for="recording in recordings" :key="recording.id" class="rounded-lg border border-gray-100 p-3 dark:border-gray-700">
        <div class="mb-2 flex items-center justify-between text-xs">
          <span class="font-semibold text-gray-700 dark:text-gray-200">Recording #{{ recording.id }}</span>
          <span :class="[
            'rounded-full px-2 py-0.5',
            recording.status === 'uploaded' ? 'bg-green-100 text-green-700' :
            recording.status === 'failed' ? 'bg-red-100 text-red-700' :
            'bg-amber-100 text-amber-700',
          ]">
            {{ recording.status }}
          </span>
        </div>
        <div v-if="recording.youtube_url" class="aspect-video overflow-hidden rounded-lg bg-black">
          <iframe :src="recording.youtube_url" class="h-full w-full" allowfullscreen />
        </div>
        <a
          v-else-if="source(recording.file_path)"
          :href="source(recording.file_path) ?? undefined"
          target="_blank"
          class="text-xs font-medium text-[#381998] hover:underline"
        >
          Open uploaded recording file
        </a>
      </div>
    </div>
  </section>
</template>
