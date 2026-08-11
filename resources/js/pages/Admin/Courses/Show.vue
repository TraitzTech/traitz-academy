<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { categoryIconFor } from '@/utils/categoryIcons'
import { courseDescriptionHtml } from '@/utils/lessonContentHtml'
import { STREAMING_IFRAME_ALLOW, streamingEmbedSrc } from '@/utils/videoEmbed'

interface Lesson {
  id: number
  title: string
  type: string
  duration: string | null
  is_free: boolean
  description: string | null
  video_url?: string | null
  youtube_video_id?: string | null
  youtube_status?: 'pending' | 'processing' | 'ready' | 'failed' | null
  youtube_error?: string | null
  quiz?: { id: number; lesson_id: number } | null
}

interface Section {
  id: number
  title: string
  description: string | null
  lessons: Lesson[]
}

interface Course {
  id: number
  title: string
  slug: string
  short_description: string
  description: string | null
  cover_image: string | null
  level: string
  status: string
  price: number
  sale_price: number | null
  duration: string | null
  is_featured: boolean
  enrolled_count: number
  sections_count: number
  enrollments_count: number
  published_at: string | null
  created_at: string
  instructor: { id: number; name: string; email: string } | null
  category: { id: number; name: string; slug: string; icon: string | null; color: string | null } | null
  sections: Section[]
}

const props = defineProps<{
  course: Course
  can_manually_enroll: boolean
}>()

defineOptions({ layout: AppLayout })

const coverSrc = props.course.cover_image
  ? props.course.cover_image.startsWith('http')
    ? props.course.cover_image
    : `/storage/${props.course.cover_image}`
  : null

const statusLabels: Record<string, string> = {
  draft: 'Draft',
  pending_review: 'Pending Review',
  published: 'Published',
  archived: 'Archived',
}
const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
  pending_review: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  archived: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
}
const levelLabels: Record<string, string> = {
  beginner: 'Beginner',
  intermediate: 'Intermediate',
  advanced: 'Advanced',
}

const showApproveModal = ref(false)
const showRejectModal  = ref(false)

function confirmApprove() {
  router.post(`/admin/courses/${props.course.id}/approve`, {}, {
    onSuccess: () => { showApproveModal.value = false },
  })
}
function confirmReject() {
  router.post(`/admin/courses/${props.course.id}/reject`, {}, {
    onSuccess: () => { showRejectModal.value = false },
  })
}

const expandedSections = ref<Set<number>>(new Set(props.course.sections.map(s => s.id)))

const enrollForm = useForm({ email: '' })
const videoUploadForm = useForm({ video_file: null as File | null })

const showReplaceVideoModal = ref(false)
const pendingVideoUpload = ref<{ sectionId: number; lessonId: number; file: File; input: HTMLInputElement } | null>(null)

function submitEnrollStudent() {
  enrollForm.post(`/admin/courses/${props.course.id}/enroll-student`, { preserveScroll: true })
}

function shouldConfirmVideoReplacement(sectionId: number, lessonId: number): boolean {
  const section = props.course.sections.find((s) => s.id === sectionId)
  if (!section) return false

  const lesson = section.lessons.find((l) => l.id === lessonId)
  if (!lesson) return false

  return Boolean(lesson.video_url || lesson.youtube_video_id)
}

function onLessonVideoSelected(event: Event, sectionId: number, lessonId: number) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  if (!file) return

  // Overwriting an existing video is destructive — confirm via modal first.
  if (shouldConfirmVideoReplacement(sectionId, lessonId)) {
    pendingVideoUpload.value = { sectionId, lessonId, file, input }
    showReplaceVideoModal.value = true
    return
  }

  performVideoUpload(sectionId, lessonId, file, input)
}

function confirmReplaceVideo() {
  const pending = pendingVideoUpload.value
  showReplaceVideoModal.value = false
  pendingVideoUpload.value = null
  if (pending) {
    performVideoUpload(pending.sectionId, pending.lessonId, pending.file, pending.input)
  }
}

function cancelReplaceVideo() {
  if (pendingVideoUpload.value?.input) {
    pendingVideoUpload.value.input.value = ''
  }
  pendingVideoUpload.value = null
  showReplaceVideoModal.value = false
}

function performVideoUpload(sectionId: number, lessonId: number, file: File, input: HTMLInputElement) {
  videoUploadForm.video_file = file

  videoUploadForm.post(`/admin/courses/${props.course.id}/sections/${sectionId}/lessons/${lessonId}/video`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      videoUploadForm.reset()
    },
    onFinish: () => {
      input.value = ''
    },
  })
}

function toggleSection(id: number) {
  if (expandedSections.value.has(id)) {
    expandedSections.value.delete(id)
  } else {
    expandedSections.value.add(id)
  }
}

function lessonEmbedUrl(lesson: Lesson): string | null {
  // Prefer the YouTube id (same source of truth the learner player uses) so the
  // preview never falls back to embedding a raw, non-embeddable URL.
  if (lesson.youtube_video_id) {
    return `https://www.youtube-nocookie.com/embed/${lesson.youtube_video_id}?controls=0&rel=0&modestbranding=1&playsinline=1&iv_load_policy=3&disablekb=1&fs=0`
  }
  return streamingEmbedSrc(lesson.video_url ?? null)
}
</script>

<template>
  <div class="mx-auto max-w-5xl">
    <Head :title="`Review: ${course.title}`" />

    <!-- Back + header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <Link
          href="/admin/courses"
          class="mb-2 inline-flex items-center text-sm text-[#42b6c5] hover:text-[#35919e]"
        >
          <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Courses
        </Link>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Course Review</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Read-only preview for admin review</p>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap items-center gap-3">
        <Link
          :href="`/admin/courses/${course.id}/pricing`"
          class="rounded-lg border border-[#381998]/40 bg-[#381998]/5 px-5 py-2 text-sm font-semibold text-[#381998] transition-colors hover:bg-[#381998]/10 dark:border-[#42b6c5]/40 dark:text-[#42b6c5]"
        >
          Pricing &amp; installments
        </Link>
        <template v-if="course.status === 'pending_review'">
          <button
            @click="showRejectModal = true"
            class="rounded-lg border border-red-300 px-5 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20"
          >
            Reject
          </button>
          <button
            @click="showApproveModal = true"
            class="rounded-lg bg-green-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-700"
          >
            Approve & Publish
          </button>
        </template>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

      <!-- Left column: main content -->
      <div class="space-y-6 lg:col-span-2">

        <!-- Cover image -->
        <div class="overflow-hidden rounded-xl bg-gradient-to-br from-[#381998] to-[#42b6c5] shadow">
          <img
            v-if="coverSrc"
            :src="coverSrc"
            :alt="course.title"
            class="h-64 w-full object-cover"
          />
          <div v-else class="flex h-64 items-center justify-center">
            <div class="text-center text-white/60">
              <svg class="mx-auto mb-2 h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <p class="text-sm">No cover image uploaded</p>
            </div>
          </div>
        </div>

        <!-- Title & description -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800 p-6">
          <div class="mb-4 flex flex-wrap items-center gap-2">
            <span :class="['rounded-full px-3 py-0.5 text-xs font-semibold', statusColors[course.status]]">
              {{ statusLabels[course.status] }}
            </span>
            <span class="rounded-full border border-gray-200 px-3 py-0.5 text-xs font-medium text-gray-600 dark:border-gray-600 dark:text-gray-300">
              {{ levelLabels[course.level] }}
            </span>
            <span v-if="course.category" class="inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-xs font-semibold text-white" :style="{ backgroundColor: course.category.color || '#381998' }">
              <component :is="categoryIconFor(course.category.icon)" v-if="categoryIconFor(course.category.icon)" class="h-3.5 w-3.5" />
              {{ course.category.name }}
            </span>
          </div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ course.title }}</h1>
          <p class="mt-2 text-base text-gray-600 dark:text-gray-300">{{ course.short_description }}</p>

          <div v-if="course.description?.trim()" class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-700">
            <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Full description</h3>
            <div
              class="prose prose-sm max-w-none text-gray-600 dark:prose-invert dark:text-gray-400"
              v-html="courseDescriptionHtml(course.description)"
            />
          </div>
        </div>

        <!-- Curriculum -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800 p-6">
          <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
            Curriculum
            <span class="ml-2 text-sm font-normal text-gray-400">({{ course.sections.length }} section{{ course.sections.length !== 1 ? 's' : '' }})</span>
          </h3>

          <div v-if="course.sections.length === 0" class="rounded-lg border-2 border-dashed border-gray-200 p-8 text-center dark:border-gray-600">
            <p class="text-sm text-gray-400">No sections have been added to this course yet.</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="section in course.sections"
              :key="section.id"
              class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-600"
            >
              <!-- Section header -->
              <button
                type="button"
                @click="toggleSection(section.id)"
                class="flex w-full items-center justify-between bg-gray-50 px-4 py-3 text-left dark:bg-gray-700/50"
              >
                <div>
                  <span class="font-medium text-gray-800 dark:text-gray-100">{{ section.title }}</span>
                  <span class="ml-2 text-xs text-gray-400">{{ section.lessons.length }} lesson{{ section.lessons.length !== 1 ? 's' : '' }}</span>
                </div>
                <svg
                  :class="['h-4 w-4 text-gray-400 transition-transform', expandedSections.has(section.id) ? 'rotate-180' : '']"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Lessons -->
              <div v-if="expandedSections.has(section.id)" class="divide-y divide-gray-100 dark:divide-gray-700">
                <div
                  v-for="lesson in section.lessons"
                  :key="lesson.id"
                  class="px-4 py-3"
                >
                <div class="flex items-center gap-3">
                  <!-- Type icon -->
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#42b6c5]/10 text-[#42b6c5]">
                    <svg v-if="lesson.type === 'video'" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M8 5v14l11-7z"/>
                    </svg>
                    <svg v-else-if="lesson.type === 'quiz'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ lesson.title }}</p>
                    <p class="text-xs capitalize text-gray-400">{{ lesson.type }}{{ lesson.duration ? ' · ' + lesson.duration : '' }}</p>
                  </div>
                  <span v-if="lesson.is_free" class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    Free
                  </span>
                  <Link
                    v-if="lesson.type === 'quiz' && lesson.quiz"
                    :href="`/admin/quizzes/${lesson.quiz.id}/attempts`"
                    class="shrink-0 rounded-md border border-[#42b6c5]/40 px-2 py-1 text-xs font-semibold text-[#42b6c5] hover:bg-[#42b6c5]/10"
                  >
                    Attempts
                  </Link>
                  <label
                    v-if="lesson.type === 'video'"
                    class="cursor-pointer rounded-md border border-[#381998]/40 px-2 py-1 text-xs font-semibold text-[#381998] hover:bg-[#381998]/10"
                  >
                    {{ lesson.video_url ? 'Replace video' : 'Upload video' }}
                    <input
                      type="file"
                      class="hidden"
                      accept="video/mp4,video/mov,video/avi,video/mkv,video/webm"
                      @change="onLessonVideoSelected($event, section.id, lesson.id)"
                    />
                  </label>
                </div>
                <div
                  v-if="lesson.type === 'video'"
                  class="mt-2 flex items-center gap-2 pl-11 text-xs"
                >
                  <span class="text-gray-400">Video:</span>
                  <span
                    :class="[
                      'rounded-full px-2 py-0.5 font-medium',
                      lesson.youtube_status === 'ready'
                        ? 'bg-green-100 text-green-700'
                        : lesson.youtube_status === 'failed'
                          ? 'bg-red-100 text-red-700'
                          : lesson.youtube_status === 'processing'
                            ? 'bg-amber-100 text-amber-700'
                            : 'bg-gray-100 text-gray-600',
                    ]"
                  >
                    {{ lesson.youtube_status || 'not uploaded' }}
                  </span>
                  <span v-if="lesson.youtube_error" class="truncate text-red-500" :title="lesson.youtube_error">
                    {{ lesson.youtube_error }}
                  </span>
                </div>
                <div
                  v-if="lesson.type === 'video' && lessonEmbedUrl(lesson)"
                  class="mt-2 pl-11"
                >
                  <div class="yt-crop-shell aspect-video max-w-2xl overflow-hidden rounded-lg bg-black">
                    <iframe
                      :src="lessonEmbedUrl(lesson) ?? undefined"
                      class="h-full w-full"
                      referrerpolicy="strict-origin-when-cross-origin"
                      :allow="STREAMING_IFRAME_ALLOW"
                      allowfullscreen
                    />
                  </div>
                </div>
                </div>
                <div v-if="section.lessons.length === 0" class="px-4 py-3 text-sm italic text-gray-400">
                  No lessons in this section.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right column: meta -->
      <div class="space-y-5">

        <!-- Instructor -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800 p-5">
          <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Instructor</h3>
          <div v-if="course.instructor" class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#381998] text-sm font-bold text-white">
              {{ course.instructor.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">{{ course.instructor.name }}</p>
              <p class="text-xs text-gray-400">{{ course.instructor.email }}</p>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400">No instructor assigned.</p>
        </div>

        <!-- Course details -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800 p-5">
          <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Details</h3>
          <dl class="space-y-3">
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500 dark:text-gray-400">Price</dt>
              <dd class="font-semibold text-gray-900 dark:text-gray-100">
                <span v-if="Number(course.price) === 0">Free</span>
                <span v-else>{{ Number(course.price).toLocaleString() }} XAF</span>
              </dd>
            </div>
            <div v-if="course.sale_price" class="flex justify-between text-sm">
              <dt class="text-gray-500 dark:text-gray-400">Sale Price</dt>
              <dd class="font-semibold text-green-600">{{ Number(course.sale_price).toLocaleString() }} XAF</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500 dark:text-gray-400">Duration</dt>
              <dd class="font-medium text-gray-900 dark:text-gray-100">{{ course.duration || '—' }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500 dark:text-gray-400">Level</dt>
              <dd class="font-medium text-gray-900 dark:text-gray-100">{{ levelLabels[course.level] }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500 dark:text-gray-400">Sections</dt>
              <dd class="font-medium text-gray-900 dark:text-gray-100">{{ course.sections_count }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500 dark:text-gray-400">Enrollments</dt>
              <dd class="font-medium text-gray-900 dark:text-gray-100">{{ course.enrollments_count }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500 dark:text-gray-400">Submitted</dt>
              <dd class="font-medium text-gray-900 dark:text-gray-100">{{ new Date(course.created_at).toLocaleDateString() }}</dd>
            </div>
          </dl>
        </div>

        <!-- Manual enrollment (admin) -->
        <div v-if="can_manually_enroll" class="rounded-lg bg-white shadow dark:bg-gray-800 p-5">
          <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Enroll a learner</h3>
          <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            Grant course access by email. The person must already have a registered account with that email.
          </p>
          <form class="space-y-3" @submit.prevent="submitEnrollStudent">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Learner email</label>
              <input
                v-model="enrollForm.email"
                type="email"
                autocomplete="email"
                placeholder="student@example.com"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
              />
              <p v-if="enrollForm.errors.email" class="mt-1 text-xs text-red-600">{{ enrollForm.errors.email }}</p>
            </div>
            <button
              type="submit"
              :disabled="enrollForm.processing"
              class="w-full rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#35919e] disabled:opacity-60"
            >
              {{ enrollForm.processing ? 'Enrolling…' : 'Grant access' }}
            </button>
          </form>
        </div>

        <!-- Readiness checklist -->
        <div class="rounded-lg bg-white shadow dark:bg-gray-800 p-5">
          <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Readiness Checklist</h3>
          <ul class="space-y-2">
            <li
              v-for="item in [
                { label: 'Title & description', done: !!course.title && !!course.short_description },
                { label: 'Cover image uploaded', done: !!course.cover_image },
                { label: 'Category assigned', done: !!course.category },
                { label: 'At least one section', done: course.sections.length > 0 },
                { label: 'At least one lesson', done: course.sections.some(s => s.lessons.length > 0) },
              ]"
              :key="item.label"
              class="flex items-center gap-2 text-sm"
            >
              <span :class="item.done ? 'text-green-500' : 'text-gray-300 dark:text-gray-600'">
                <svg v-if="item.done" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
              </span>
              <span :class="item.done ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400'">{{ item.label }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Approve confirmation -->
    <ConfirmationModal
      :open="showApproveModal"
      title="Approve & Publish Course"
      :description="`Approve &quot;${course.title}&quot;? It will immediately go live and be visible to students.`"
      confirm-text="Yes, Publish"
      cancel-text="Cancel"
      variant="default"
      @update:open="showApproveModal = $event"
      @confirm="confirmApprove"
    />

    <!-- Reject confirmation -->
    <ConfirmationModal
      :open="showRejectModal"
      title="Reject Course"
      :description="`Return &quot;${course.title}&quot; to the tutor as a draft? They will need to make changes and re-submit.`"
      confirm-text="Yes, Reject"
      cancel-text="Cancel"
      variant="destructive"
      @update:open="showRejectModal = $event"
      @confirm="confirmReject"
    />

    <!-- Replace video confirmation -->
    <ConfirmationModal
      :open="showReplaceVideoModal"
      title="Replace this video?"
      description="The current video will be deleted and replaced with the new upload. This can't be undone."
      confirm-text="Replace Video"
      cancel-text="Keep Current"
      variant="destructive"
      @update:open="(v) => { if (!v) cancelReplaceVideo(); }"
      @confirm="confirmReplaceVideo"
    />
  </div>
</template>

<style scoped>
/* Overscan the embed so YouTube's top title/branding bar is cropped out of view
   (controls are already hidden via controls=0). Matches the student player. */
.yt-crop-shell :deep(iframe) {
  width: 112% !important;
  height: 112% !important;
  margin: -6% !important;
}
</style>
