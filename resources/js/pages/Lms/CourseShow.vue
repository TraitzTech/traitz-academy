<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import {
  BookOpen,
  Check,
  ChevronDown,
  Clock,
  GraduationCap,
  Layers,
  Lock,
  PlayCircle,
  Sparkles,
  Star,
  User,
  Users,
} from 'lucide-vue-next'
import { computed, nextTick, ref } from 'vue'

import PublicLayout from '@/layouts/PublicLayout.vue'
import { courseDescriptionHtml } from '@/utils/lessonContentHtml'

interface LessonRow {
  id: number
  title: string
  type: string
  duration: string | null
  is_free: boolean
  description: string | null
}

interface SectionRow {
  id: number
  title: string
  description: string | null
  lessons: LessonRow[]
}

interface Course {
  id: number
  title: string
  slug: string
  short_description: string
  description: string | null
  cover_image: string | null
  level: string
  price: string
  sale_price: string | null
  max_installments?: number
  duration: string | null
  enrolled_count: number
  rating: string
  review_count: number
  is_featured: boolean
  instructor: { id: number; name: string } | null
  category: { id: number; name: string; slug: string; icon: string | null; color: string | null } | null
  sections: SectionRow[]
}

interface PreviewLesson {
  id: number
  course_section_id: number
  title: string
  type: string
}

interface CourseNote {
  id: number
  content: string
  timestamp: string | null
  timestamp_seconds: number | null
  updated_at: string | null
  lesson: {
    id: number
    title: string
    section_title: string | null
  }
}

const props = defineProps<{
  course: Course
  previewLessons: PreviewLesson[]
  isEnrolled: boolean
  requiresCheckout: boolean
  courseNotes: CourseNote[]
}>()

const page = usePage()
const isLoggedIn = computed(() => !!(page.props.auth as { user?: unknown })?.user)

const expanded = ref<Set<number>>(new Set(props.course.sections.map((s) => s.id)))

function toggleSection(id: number) {
  if (expanded.value.has(id)) expanded.value.delete(id)
  else expanded.value.add(id)
}

const levelLabels: Record<string, string> = {
  beginner: 'Beginner',
  intermediate: 'Intermediate',
  advanced: 'Advanced',
}

function coverUrl(url: string | null) {
  if (!url) return null
  return url.startsWith('http') ? url : `/storage/${url}`
}

function formatMoney(n: string | number) {
  const v = typeof n === 'string' ? parseFloat(n) : n
  return v.toLocaleString()
}

const priceInfo = computed(() => {
  const p = parseFloat(props.course.price)
  const sp = props.course.sale_price ? parseFloat(props.course.sale_price) : null
  if (p <= 0) return { label: 'Free', amount: 0, strike: null as string | null }
  if (sp != null && sp > 0 && sp < p) return { label: `${formatMoney(sp)} XAF`, amount: sp, strike: `${formatMoney(p)} XAF` }
  return { label: `${formatMoney(p)} XAF`, amount: p, strike: null as string | null }
})

const enrolling = ref(false)
const activeTab = ref<'overview' | 'notes'>('overview')
const courseNotesSectionRef = ref<HTMLElement | null>(null)

function enroll() {
  enrolling.value = true
  router.post(
    `/online-courses/${props.course.id}/enroll`,
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        enrolling.value = false
      },
    },
  )
}

const maxInstallments = computed(() => Math.max(1, props.course.max_installments ?? 1))

const perInstallmentApprox = computed(() => {
  if (priceInfo.value.amount <= 0) return 0
  return Math.round((priceInfo.value.amount / maxInstallments.value) * 100) / 100
})
const totalLessons = computed(() => props.course.sections.reduce((count, section) => count + section.lessons.length, 0))

const typeLabels: Record<string, string> = {
  video: 'Video',
  text: 'Text',
  quiz: 'Quiz',
}

async function openCourseNotesTab() {
  activeTab.value = 'notes'
  await nextTick()
  courseNotesSectionRef.value?.scrollIntoView({
    behavior: 'smooth',
    block: 'start',
  })
}
</script>

<template>
  <PublicLayout>
    <Head :title="`${course.title} — Online Courses`" />

    <div class="lms-page">
    <!-- Breadcrumb -->
    <div class="border-b border-gray-100 bg-gray-50/80">
      <div class="mx-auto max-w-7xl px-4 py-3 text-sm sm:px-6 lg:px-8">
        <nav class="flex flex-wrap items-center gap-2 text-gray-500">
          <Link href="/" class="hover:text-[#42b6c5]">Home</Link>
          <span>/</span>
          <Link href="/online-courses" class="hover:text-[#42b6c5]">Online Courses</Link>
          <span>/</span>
          <span class="line-clamp-1 font-medium text-[#000928]">{{ course.title }}</span>
        </nav>
      </div>
    </div>

    <!-- Hero -->
    <section class="relative overflow-hidden bg-linear-to-br from-[#000928] via-[#1a0a52] to-[#381998] text-white">
      <div class="absolute right-0 top-0 h-72 w-72 rounded-full bg-[#42b6c5]/15 blur-3xl" />
      <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="grid gap-10 lg:grid-cols-5 lg:items-start">
          <div class="lg:col-span-3">
            <div class="mb-4 flex flex-wrap items-center gap-2">
              <span
                v-if="course.category"
                class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold"
              >
                <span v-if="course.category.icon">{{ course.category.icon }}</span>
                {{ course.category.name }}
              </span>
              <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold">
                {{ levelLabels[course.level] ?? course.level }}
              </span>
              <span v-if="course.is_featured" class="inline-flex items-center gap-1 rounded-full bg-[#42b6c5]/30 px-3 py-1 text-xs font-bold text-[#7ee8f9]">
                <Sparkles class="h-3 w-3" /> Featured
              </span>
            </div>
            <h1 class="text-3xl font-bold leading-tight sm:text-4xl">{{ course.title }}</h1>
            <p class="mt-4 max-w-2xl text-base text-gray-300">{{ course.short_description }}</p>
            <div class="mt-6 flex flex-wrap items-center gap-6 text-sm text-gray-300">
              <span v-if="course.instructor" class="inline-flex items-center gap-2">
                <User class="h-4 w-4 text-[#42b6c5]" />
                {{ course.instructor.name }}
              </span>
              <span v-if="course.duration" class="inline-flex items-center gap-2">
                <Clock class="h-4 w-4 text-[#42b6c5]" /> {{ course.duration }}
              </span>
              <span class="inline-flex items-center gap-2">
                <Users class="h-4 w-4 text-[#42b6c5]" /> {{ course.enrolled_count.toLocaleString() }} learners
              </span>
              <span v-if="parseFloat(course.rating) > 0" class="inline-flex items-center gap-2">
                <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
                {{ parseFloat(course.rating).toFixed(1) }} ({{ course.review_count }} reviews)
              </span>
            </div>
          </div>
          <div class="relative aspect-video overflow-hidden rounded-2xl border border-white/10 shadow-2xl lg:col-span-2">
            <img
              v-if="coverUrl(course.cover_image)"
              :src="coverUrl(course.cover_image) ?? undefined"
              :alt="course.title"
              class="h-full w-full object-cover"
            />
            <div v-else class="flex h-full min-h-[200px] items-center justify-center bg-white/5">
              <BookOpen class="h-20 w-20 text-white/30" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="bg-gray-50 py-12">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
          <!-- Main -->
          <div class="space-y-8 lg:col-span-2">
            <div
              v-if="isEnrolled"
              class="inline-flex rounded-xl border border-gray-200 bg-white p-1 text-sm shadow-sm"
            >
              <button
                type="button"
                class="rounded-lg px-4 py-2 font-semibold transition-colors"
                :class="activeTab === 'overview' ? 'bg-[#381998] text-white' : 'text-gray-600 hover:bg-gray-100'"
                @click="activeTab = 'overview'"
              >
                Overview
              </button>
              <button
                type="button"
                class="rounded-lg px-4 py-2 font-semibold transition-colors"
                :class="activeTab === 'notes' ? 'bg-[#381998] text-white' : 'text-gray-600 hover:bg-gray-100'"
                @click="openCourseNotesTab"
              >
                Notes
              </button>
            </div>

            <template v-if="!isEnrolled || activeTab === 'overview'">
            <div v-if="course.description?.trim()" class="lms-panel">
              <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-[#000928]">
                <BookOpen class="h-5 w-5 text-[#42b6c5]" /> About this course
              </h2>
              <div
                class="prose prose-sm max-w-none text-gray-600 prose-headings:text-[#000928] prose-a:text-[#42b6c5] dark:prose-invert dark:text-gray-300 dark:prose-headings:text-gray-100"
                v-html="courseDescriptionHtml(course.description)"
              />
            </div>

            <!-- Curriculum -->
            <div class="lms-panel">
              <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#000928]">
                <Layers class="h-5 w-5 text-[#42b6c5]" /> Course content
              </h2>
              <p class="mb-4 text-sm text-gray-500">
                {{ totalLessons }} lessons in {{ course.sections.length }} sections
              </p>

              <div v-if="course.sections.length === 0" class="rounded-xl border border-dashed border-gray-200 py-12 text-center text-sm text-gray-400">
                Curriculum will appear here once published.
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="section in course.sections"
                  :key="section.id"
                  class="overflow-hidden rounded-xl border border-gray-100"
                >
                  <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 bg-gray-50 px-4 py-3 text-left transition-colors hover:bg-gray-100"
                    @click="toggleSection(section.id)"
                  >
                    <span class="font-semibold text-[#000928]">{{ section.title }}</span>
                    <ChevronDown
                      :class="['h-5 w-5 shrink-0 text-gray-400 transition-transform', expanded.has(section.id) ? 'rotate-180' : '']"
                    />
                  </button>
                  <div v-show="expanded.has(section.id)" class="divide-y divide-gray-100 border-t border-gray-100 bg-white">
                    <div
                      v-for="lesson in section.lessons"
                      :key="lesson.id"
                      class="flex items-center gap-3 px-4 py-3 text-sm"
                    >
                      <PlayCircle v-if="lesson.type === 'video'" class="h-4 w-4 shrink-0 text-[#381998]" />
                      <BookOpen v-else-if="lesson.type === 'text'" class="h-4 w-4 shrink-0 text-[#42b6c5]" />
                      <GraduationCap v-else class="h-4 w-4 shrink-0 text-gray-400" />
                      <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-gray-800">{{ lesson.title }}</p>
                        <p class="text-xs text-gray-400">
                          {{ typeLabels[lesson.type] ?? lesson.type }}
                          <template v-if="lesson.duration"> · {{ lesson.duration }}</template>
                        </p>
                      </div>
                      <Link
                        v-if="isEnrolled"
                        :href="`/dashboard/courses/${course.id}/lessons/${lesson.id}`"
                        class="shrink-0 rounded-lg bg-[#381998]/10 px-3 py-1 text-xs font-semibold text-[#381998] transition-colors hover:bg-[#381998]/20"
                      >
                        Open
                      </Link>
                      <Link
                        v-else-if="lesson.is_free"
                        :href="`/online-courses/${course.id}/lessons/${lesson.id}/preview`"
                        class="shrink-0 rounded-lg bg-[#42b6c5]/10 px-3 py-1 text-xs font-semibold text-[#2a8a96] transition-colors hover:bg-[#42b6c5]/20"
                      >
                        Preview
                      </Link>
                      <span v-else class="inline-flex shrink-0 items-center gap-1 text-xs text-gray-400">
                        <Lock class="h-3.5 w-3.5" /> Locked
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </template>

            <div
              ref="courseNotesSectionRef"
              v-else
              class="lms-panel"
            >
              <h2 class="mb-2 text-lg font-bold text-[#000928]">Course notes</h2>
              <p class="mb-5 text-sm text-gray-500">
                All notes you wrote across lessons, listed in curriculum order.
              </p>

              <div v-if="!courseNotes.length" class="rounded-xl border border-dashed border-gray-200 px-4 py-10 text-center text-sm text-gray-500">
                No notes yet. Open any lesson and start writing notes.
              </div>

              <ul v-else class="space-y-3">
                <li
                  v-for="note in courseNotes"
                  :key="note.id"
                  class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs"
                >
                  <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                    <span class="rounded-md bg-gray-100 px-2 py-0.5 font-semibold text-gray-700">{{ note.lesson.title }}</span>
                    <span v-if="note.timestamp" class="rounded-md bg-[#381998]/10 px-2 py-0.5 font-semibold text-[#381998]">
                      {{ note.timestamp }}
                    </span>
                    <span v-if="note.updated_at">{{ new Date(note.updated_at).toLocaleString() }}</span>
                  </div>
                  <p class="whitespace-pre-wrap text-sm text-gray-700">{{ note.content }}</p>
                  <Link
                    :href="`/dashboard/courses/${course.id}/lessons/${note.lesson.id}`"
                    class="mt-3 inline-flex text-xs font-semibold text-[#42b6c5] hover:text-[#35919e]"
                  >
                    Open lesson
                  </Link>
                </li>
              </ul>
            </div>
          </div>

          <!-- Sidebar: pricing -->
          <div class="space-y-6">
            <div class="sticky top-24 lms-panel">
              <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-500">Enrollment</h3>
              <div class="mb-4">
                <template v-if="priceInfo.amount <= 0">
                  <p class="text-3xl font-bold text-green-600">Free</p>
                </template>
                <template v-else>
                  <p class="text-3xl font-bold text-[#000928]">{{ priceInfo.label }}</p>
                  <p v-if="priceInfo.strike" class="mt-1 text-sm text-gray-400 line-through">{{ priceInfo.strike }}</p>
                </template>
              </div>

              <Link
                v-if="!isLoggedIn"
                href="/login"
                class="mb-4 flex w-full items-center justify-center rounded-xl bg-[#000928] py-3 text-sm font-semibold text-white transition-colors hover:bg-[#381998]"
              >
                Sign in to enroll
              </Link>
              <Link
                v-else-if="isEnrolled"
                href="/dashboard/my-courses"
                class="mb-4 flex w-full items-center justify-center rounded-xl border border-[#42b6c5] bg-[#42b6c5]/10 py-3 text-sm font-semibold text-[#2a8a96] transition-colors hover:bg-[#42b6c5]/20"
              >
                Go to My Courses
              </Link>
              <Link
                v-else-if="requiresCheckout"
                :href="`/dashboard/courses/${course.id}/checkout`"
                class="mb-4 flex w-full items-center justify-center rounded-xl bg-[#000928] py-3 text-sm font-semibold text-white transition-colors hover:bg-[#381998]"
              >
                Pay &amp; enroll
              </Link>
              <button
                v-else
                type="button"
                :disabled="enrolling"
                class="mb-4 flex w-full items-center justify-center rounded-xl bg-[#000928] py-3 text-sm font-semibold text-white transition-colors hover:bg-[#381998] disabled:opacity-60"
                @click="enroll"
              >
                {{ enrolling ? 'Enrolling…' : 'Enroll in this course' }}
              </button>
              <p class="text-center text-xs text-gray-400">Full access after you enroll.</p>
            </div>

            <div
              v-if="priceInfo.amount > 0 && maxInstallments > 1"
              class="lms-panel"
            >
              <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-gray-500">Payment options</h3>
              <p class="text-sm text-gray-600">
                Flexible payment: up to {{ maxInstallments }} installment(s)
              </p>
              <p class="mt-1 text-sm text-gray-500">
                ≈ {{ formatMoney(perInstallmentApprox) }} XAF per installment
              </p>
            </div>

            <!-- Free previews list -->
            <div v-if="previewLessons.length > 0" class="rounded-2xl border border-[#42b6c5]/30 bg-[#42b6c5]/5 p-6">
              <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-[#000928]">
                <Check class="h-4 w-4 text-[#42b6c5]" /> Free preview lessons
              </h3>
              <ul class="space-y-2">
                <li v-for="pl in previewLessons" :key="pl.id">
                  <Link
                    :href="`/online-courses/${course.id}/lessons/${pl.id}/preview`"
                    class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-[#381998] hover:bg-white/80"
                  >
                    <span class="line-clamp-1">{{ pl.title }}</span>
                    <span class="shrink-0 text-xs text-gray-500">{{ typeLabels[pl.type] ?? pl.type }}</span>
                  </Link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
    </div>
  </PublicLayout>
</template>
