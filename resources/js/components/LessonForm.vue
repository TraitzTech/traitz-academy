<script setup lang="ts">
import { FileText, Video, BookOpen, Upload } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import RichTextEditor from './RichTextEditor.vue'

interface Section { id: number; title: string }
interface Course { id: number; title: string; sections: Section[] }

interface Props {
  form: any // The Inertia form object
  courses?: Course[] // For upload form (course selection)
  showCourseSelection?: boolean
  showSectionSelection?: boolean
  showVideoUpload?: boolean
  errors?: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
  showCourseSelection: false,
  showSectionSelection: false,
  showVideoUpload: true,
  courses: () => [],
  errors: () => ({}),
})

const emit = defineEmits<{
  'update:videoFile': [file: File | null]
  'update:lessonType': [type: 'video' | 'text' | 'quiz']
}>()

// File handling
const dragActive = ref(false)
const videoInputRef = ref<HTMLInputElement | null>(null)

function onDrop(e: DragEvent | Event) {
  dragActive.value = false
  const file = (e as DragEvent).dataTransfer?.files[0]
    ?? (e.target as HTMLInputElement).files?.[0]
  if (file) {
    props.form.video_file = file
    emit('update:videoFile', file)
  }
}

function clearFile() {
  props.form.video_file = null
  if (videoInputRef.value) {
    videoInputRef.value.value = ''
  }
  emit('update:videoFile', null)
}

function setLessonType(type: 'video' | 'text' | 'quiz') {
  props.form.type = type
  emit('update:lessonType', type)
}

// Sections computed
const availableSections = computed<Section[]>(() => {
  if (!props.showCourseSelection) return []
  const course = props.courses?.find(c => String(c.id) === String(props.form.course_id))
  return course?.sections ?? []
})

// Reset section when course changes
watch(() => props.form.course_id, () => {
  props.form.section_id = ''
})

// Reset duration and video when type changes
watch(() => props.form.type, (type) => {
  if (type !== 'video') {
    props.form.duration = ''
    clearFile()
  }
})

const lessonTypeLabels: Record<string, string> = {
  video: 'Video',
  text: 'Text / Article',
  quiz: 'Quiz',
}

const lessonTypeDescriptions: Record<string, string> = {
  video: 'MP4, MOV, AVI',
  text: 'Written content',
  quiz: 'Questions',
}
</script>

<template>
  <div class="space-y-5">
    <!-- Course select (optional) -->
    <div v-if="showCourseSelection">
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

    <!-- Section select (optional) -->
    <div v-if="showSectionSelection">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section *</label>
      <select
        v-model="form.section_id"
        :disabled="!form.course_id || availableSections.length === 0"
        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent disabled:opacity-50"
        :class="{ 'border-red-400': form.errors.section_id }"
      >
        <option value="">{{ form.course_id ? (availableSections.length ? 'Select section…' : 'No sections — add one in course editor') : 'Select a course first' }}</option>
        <option v-for="s in availableSections" :key="s.id" :value="s.id">{{ s.title }}</option>
      </select>
      <p v-if="form.errors.section_id" class="mt-1 text-sm text-red-600">{{ form.errors.section_id }}</p>
    </div>

    <!-- Lesson type -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lesson Type *</label>
      <div class="grid grid-cols-3 gap-3">
        <button
          v-for="type in ['video', 'text', 'quiz'] as const"
          :key="type"
          type="button"
          @click="setLessonType(type)"
          :class="[
            'flex items-center gap-2 rounded-xl border-2 p-3 text-left transition-colors',
            form.type === type
              ? 'border-[#42b6c5] bg-[#42b6c5]/5 dark:border-[#42b6c5] dark:bg-[#42b6c5]/10'
              : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500',
          ]"
        >
          <component
            :is="type === 'video' ? Video : type === 'text' ? FileText : BookOpen"
            class="h-4 w-4 shrink-0"
            :class="form.type === type ? 'text-[#42b6c5]' : 'text-gray-400'"
          />
          <div>
            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ lessonTypeLabels[type] }}</p>
            <p class="text-xs text-gray-400">{{ lessonTypeDescriptions[type] }}</p>
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

    <!-- Video file upload (only for video type) -->
    <div v-if="form.type === 'video' && showVideoUpload">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Video file</label>
      <div>
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
            ref="videoInputRef"
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
      <p class="mt-1 text-xs text-gray-400">File is uploaded and attached to this lesson for in-platform playback.</p>
    </div>

    <!-- Text content (only for text type) -->
    <div v-if="form.type === 'text'" class="max-w-4xl">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label>
      <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
        Rich text editor: headings, lists, code blocks, images, and links—same formatting learners see in the course player.
      </p>
      <RichTextEditor
        v-model="form.content"
        placeholder="Write the lesson content here…"
        upload-url="/lesson-content/media"
        body-class="min-h-[260px] max-h-[min(70vh,720px)]"
      />
    </div>

    <!-- Description -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
      <textarea
        v-model="form.description"
        rows="2"
        placeholder="Short description of this lesson"
        class="w-full resize-none px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent text-sm"
      />
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

    <!-- Slot for submit buttons -->
    <slot />
  </div>
</template>
