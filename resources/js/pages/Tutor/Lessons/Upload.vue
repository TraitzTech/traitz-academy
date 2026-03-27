<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { FileText, Link2, Pencil, Trash2, Upload, Video } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import AppLayout from '@/layouts/AppLayout.vue'

interface Section { id: number; title: string }
interface Course  { id: number; title: string; status: string; sections: Section[] }
interface Lesson  {
  id: number; title: string; type: string
  duration: string | null; course: string | null
  section: string | null; is_free: boolean
  has_video: boolean; created_at: string
}

const props = defineProps<{
  courses: Course[]
  recentLessons: Lesson[]
}>()

defineOptions({ layout: AppLayout })

// ── Form ──────────────────────────────────────────────────────────────────────
const form = useForm({
  course_id:   '',
  section_id:  '',
  title:       '',
  type:        'video' as 'video' | 'text' | 'quiz',
  description: '',
  duration:    '',
  is_free:     false,
  video_file:  null as File | null,
  video_url:   '',
  content:     '',
})

// Sections for the selected course
const sections = computed<Section[]>(() => {
  const course = props.courses.find(c => String(c.id) === String(form.course_id))
  return course?.sections ?? []
})

// Reset section when course changes
watch(() => form.course_id, () => { form.section_id = '' })

// ── File handling ──────────────────────────────────────────────────────────────
const dragActive  = ref(false)
const videoSrcTab = ref<'file' | 'url'>('file')  // toggle between file upload and URL

function onDrop(e: DragEvent | Event) {
  dragActive.value = false
  const file = (e as DragEvent).dataTransfer?.files[0]
    ?? (e.target as HTMLInputElement).files?.[0]
  if (file) form.video_file = file
}

function clearFile() {
  form.video_file = null
}

// ── Submit ────────────────────────────────────────────────────────────────────
function submit() {
  form.post('/tutor/lessons/upload', {
    forceFormData: true,
    onSuccess: () => {
      form.reset()
      videoSrcTab.value = 'file'
    },
  })
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteTarget = ref<Lesson | null>(null)

function confirmDelete() {
  if (!deleteTarget.value) return
  router.delete(`/tutor/lessons/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
  })
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const videoCount = computed(() => props.recentLessons.filter(l => l.type === 'video').length)
const textCount  = computed(() => props.recentLessons.filter(l => l.type !== 'video').length)
</script>

<template>
  <div>
    <Head title="Lesson Upload" />

    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Lesson Upload</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a new lesson to one of your courses</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">

      <!-- ── Left: Upload form (adapted from prototype) ── -->
      <div class="rounded-xl bg-white shadow dark:bg-gray-800 p-6">
        <h3 class="mb-5 text-base font-semibold text-gray-900 dark:text-gray-100">Upload New Lesson</h3>

        <form @submit.prevent="submit" class="space-y-5">

          <!-- Course select -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Course *</label>
            <select
              v-model="form.course_id"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              :class="{ 'border-red-400': form.errors.course_id }"
            >
              <option value="">Select course…</option>
              <option v-for="course in courses" :key="course.id" :value="course.id">
                {{ course.title }}
              </option>
            </select>
            <p v-if="form.errors.course_id" class="mt-1 text-sm text-red-600">{{ form.errors.course_id }}</p>
          </div>

          <!-- Section select (populated after course is chosen) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section *</label>
            <select
              v-model="form.section_id"
              :disabled="!form.course_id || sections.length === 0"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent disabled:opacity-50"
              :class="{ 'border-red-400': form.errors.section_id }"
            >
              <option value="">{{ form.course_id ? (sections.length ? 'Select section…' : 'No sections — add one in course editor') : 'Select a course first' }}</option>
              <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.title }}</option>
            </select>
            <p v-if="form.errors.section_id" class="mt-1 text-sm text-red-600">{{ form.errors.section_id }}</p>
          </div>

          <!-- Lesson type (adapted from prototype cards) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lesson Type *</label>
            <div class="grid grid-cols-3 gap-3">
              <button
                v-for="[val, icon, label, sub] in [['video', 'video', 'Video', 'MP4, MOV, AVI'], ['text', 'text', 'Text', 'Written content'], ['quiz', 'quiz', 'Quiz', 'Questions']]"
                :key="val"
                type="button"
                @click="form.type = val as 'video' | 'text' | 'quiz'"
                :class="[
                  'flex items-center gap-2 rounded-xl border-2 p-3 text-left transition-colors',
                  form.type === val
                    ? 'border-[#42b6c5] bg-[#42b6c5]/5 dark:border-[#42b6c5] dark:bg-[#42b6c5]/10'
                    : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500',
                ]"
              >
                <component
                  :is="val === 'video' ? Video : FileText"
                  class="h-4 w-4 shrink-0"
                  :class="form.type === val ? 'text-[#42b6c5]' : 'text-gray-400'"
                />
                <div>
                  <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ label }}</p>
                  <p class="text-xs text-gray-400">{{ sub }}</p>
                </div>
              </button>
            </div>
          </div>

          <!-- Lesson title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lesson Title *</label>
            <input
              v-model="form.title"
              type="text"
              placeholder="e.g. Introduction to HTML"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              :class="{ 'border-red-400': form.errors.title }"
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <!-- Video source (file or URL) — only shown for video type -->
          <div v-if="form.type === 'video'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Video Source</label>

            <!-- Tab toggle: file vs URL -->
            <div class="mb-3 flex w-fit gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-700">
              <button
                type="button"
                @click="videoSrcTab = 'file'"
                :class="['rounded-md px-3 py-1 text-xs font-medium transition-colors', videoSrcTab === 'file' ? 'bg-white shadow dark:bg-gray-600 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400']"
              >
                <Upload class="mr-1 inline h-3 w-3" /> Upload File
              </button>
              <button
                type="button"
                @click="videoSrcTab = 'url'"
                :class="['rounded-md px-3 py-1 text-xs font-medium transition-colors', videoSrcTab === 'url' ? 'bg-white shadow dark:bg-gray-600 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400']"
              >
                <Link2 class="mr-1 inline h-3 w-3" /> External URL
              </button>
            </div>

            <!-- Drag-drop file upload (adapted directly from prototype) -->
            <div v-if="videoSrcTab === 'file'">
              <div
                @dragover.prevent="dragActive = true"
                @dragleave="dragActive = false"
                @drop.prevent="onDrop"
                :class="[
                  'rounded-xl border-2 border-dashed p-8 text-center transition-colors cursor-pointer',
                  dragActive
                    ? 'border-[#42b6c5] bg-[#42b6c5]/5'
                    : 'border-gray-200 hover:border-[#42b6c5]/50 dark:border-gray-600',
                ]"
              >
                <input
                  type="file"
                  id="video-upload"
                  class="hidden"
                  accept="video/mp4,video/mov,video/avi,video/mkv,video/webm"
                  @change="onDrop"
                />
                <label for="video-upload" class="cursor-pointer">
                  <Upload
                    class="mx-auto mb-3 h-8 w-8"
                    :class="dragActive ? 'text-[#42b6c5]' : 'text-gray-400'"
                  />
                  <p v-if="form.video_file" class="text-sm font-medium text-[#42b6c5]">
                    {{ form.video_file.name }}
                    <button type="button" @click.stop="clearFile" class="ml-2 text-xs text-red-400 hover:text-red-600">✕ Remove</button>
                  </p>
                  <template v-else>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Drag & drop video here</p>
                    <p class="mt-1 text-xs text-gray-400">or click to browse · MP4, MOV, AVI, MKV, WebM · max 500 MB</p>
                  </template>
                </label>
              </div>
              <p v-if="form.errors.video_file" class="mt-1 text-sm text-red-600">{{ form.errors.video_file }}</p>
            </div>

            <!-- External URL (YouTube / Vimeo / direct link) -->
            <div v-else>
              <input
                v-model="form.video_url"
                type="url"
                placeholder="https://youtube.com/watch?v=… or direct video URL"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              />
              <p class="mt-1 text-xs text-gray-400">Supports YouTube, Vimeo, or any direct video URL.</p>
            </div>
          </div>

          <!-- Text content (only for text type) -->
          <div v-if="form.type === 'text'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label>
            <textarea
              v-model="form.content"
              rows="5"
              placeholder="Write the lesson content here…"
              class="w-full resize-y px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
          </div>

          <!-- Description + Duration row -->
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 sm:col-span-1">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="Short description of this lesson"
                class="w-full resize-none px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Duration</label>
              <input
                v-model="form.duration"
                type="text"
                placeholder="e.g. 12:30"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              />
            </div>
          </div>

          <!-- Free preview toggle -->
          <label class="flex cursor-pointer items-center gap-3">
            <div
              @click="form.is_free = !form.is_free"
              :class="['relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors', form.is_free ? 'bg-[#42b6c5]' : 'bg-gray-300 dark:bg-gray-600']"
            >
              <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform', form.is_free ? 'translate-x-6' : 'translate-x-1']" />
            </div>
            <span class="text-sm text-gray-700 dark:text-gray-300">Free preview (visible without enrollment)</span>
          </label>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="form.processing"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#42b6c5] py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35919e] disabled:opacity-60"
          >
            <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
            <Upload v-else class="h-4 w-4" />
            {{ form.processing ? 'Saving…' : 'Save Lesson' }}
          </button>
        </form>
      </div>

      <!-- ── Right: Recent uploads (adapted from prototype) ── -->
      <div class="rounded-xl bg-white shadow dark:bg-gray-800 p-6">
        <div class="mb-5 flex items-center justify-between">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Recent Lessons</h3>
          <div class="flex gap-3 text-xs text-gray-500 dark:text-gray-400">
            <span>Videos: {{ videoCount }}</span>
            <span>Other: {{ textCount }}</span>
          </div>
        </div>

        <div v-if="recentLessons.length === 0" class="py-10 text-center text-sm text-gray-400">
          No lessons yet. Upload your first one.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="lesson in recentLessons"
            :key="lesson.id"
            class="group flex items-center gap-3"
          >
            <!-- Type icon -->
            <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', lesson.type === 'video' ? 'bg-purple-100 dark:bg-purple-900/30' : 'bg-blue-100 dark:bg-blue-900/30']">
              <Video v-if="lesson.type === 'video'" class="h-4 w-4 text-purple-600 dark:text-purple-400" />
              <FileText v-else class="h-4 w-4 text-blue-600 dark:text-blue-400" />
            </div>

            <!-- Info -->
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ lesson.title }}</p>
                <span v-if="lesson.is_free" class="shrink-0 rounded-full bg-green-100 px-1.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">Free</span>
              </div>
              <p class="truncate text-xs text-gray-400">
                {{ lesson.course }}
                <template v-if="lesson.section"> · {{ lesson.section }}</template>
                <template v-if="lesson.duration"> · {{ lesson.duration }}</template>
                · {{ lesson.created_at }}
              </p>
            </div>

            <!-- Actions (appear on hover like prototype) -->
            <div class="flex shrink-0 gap-1 opacity-0 transition-opacity group-hover:opacity-100">
              <a
                :href="`/tutor/courses`"
                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-500 dark:hover:bg-blue-900/10"
                title="Go to course editor"
              >
                <Pencil class="h-3 w-3" />
              </a>
              <button
                @click="deleteTarget = lesson"
                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/10"
                title="Delete lesson"
              >
                <Trash2 class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete confirmation -->
    <ConfirmationModal
      :open="!!deleteTarget"
      title="Delete Lesson"
      :description="deleteTarget ? `Delete &quot;${deleteTarget.title}&quot;? This cannot be undone.` : ''"
      confirm-text="Delete"
      cancel-text="Cancel"
      variant="destructive"
      @update:open="if (!$event) deleteTarget = null"
      @confirm="confirmDelete"
    />
  </div>
</template>
