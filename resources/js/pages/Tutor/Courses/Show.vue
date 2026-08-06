<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import {
  BookOpen,
  ChevronDown,
  Clock,
  ExternalLink,
  GraduationCap,
  Layers,
  Lock,
  Pencil,
  PlayCircle,
  Sparkles,
  Star,
  User,
  Users,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import { categoryIconFor } from '@/utils/categoryIcons'
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
  status: 'draft' | 'pending_review' | 'published' | 'archived'
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

const props = defineProps<{
  course: Course
  previewLessons: PreviewLesson[]
  publicCatalogueUrl: string | null
}>()

const expanded = ref<Set<number>>(new Set(props.course.sections.map((s: SectionRow) => s.id)))

function toggleSection(id: number) {
  if (expanded.value.has(id)) expanded.value.delete(id)
  else expanded.value.add(id)
}

const levelLabels: Record<string, string> = {
  beginner: 'Beginner',
  intermediate: 'Intermediate',
  advanced: 'Advanced',
}

const statusLabels: Record<string, string> = {
  draft: 'Draft',
  pending_review: 'Pending review',
  published: 'Published',
  archived: 'Archived',
}

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  pending_review: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
  published: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
  archived: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
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

const maxInstallments = computed(() => Math.max(1, props.course.max_installments ?? 1))

const perInstallmentApprox = computed(() => {
  if (priceInfo.value.amount <= 0) return 0
  return Math.round((priceInfo.value.amount / maxInstallments.value) * 100) / 100
})

const typeLabels: Record<string, string> = {
  video: 'Video',
  text: 'Text',
  quiz: 'Quiz',
}

const canLinkFreePreviews = computed(() => props.publicCatalogueUrl !== null)

const totalLessons = computed(() =>
  props.course.sections.reduce((n: number, s: SectionRow) => n + s.lessons.length, 0),
)
</script>

<template>
  <AppLayout
    :breadcrumbs="[
      { title: 'My Courses', href: '/tutor/courses' },
      { title: course.title, href: `/tutor/courses/${course.id}` },
    ]"
  >
    <Head :title="`${course.title} — View`" />
    <div class="mx-auto max-w-6xl">
      <!-- Hero -->
      <section
        class="relative mb-8 overflow-hidden rounded-2xl bg-linear-to-br from-[#000928] via-[#1a0a52] to-[#381998] p-6 text-white shadow-lg sm:p-8"
      >
        <div class="absolute right-0 top-0 h-48 w-48 rounded-full bg-[#42b6c5]/15 blur-3xl" />
        <div class="relative grid gap-6 lg:grid-cols-5 lg:items-start">
          <div class="lg:col-span-3">
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <span :class="['rounded-full px-3 py-1 text-xs font-bold', statusColors[course.status]]">
                {{ statusLabels[course.status] }}
              </span>
              <span
                v-if="course.category"
                class="inline-flex items-center gap-1 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold"
              >
                <component :is="categoryIconFor(course.category.icon)" v-if="categoryIconFor(course.category.icon)" class="h-3.5 w-3.5" />
                {{ course.category.name }}
              </span>
              <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold">
                {{ levelLabels[course.level] ?? course.level }}
              </span>
              <span
                v-if="course.is_featured && course.status === 'published'"
                class="inline-flex items-center gap-1 rounded-full bg-[#42b6c5]/30 px-3 py-1 text-xs font-bold text-[#7ee8f9]"
              >
                <Sparkles class="h-3 w-3" /> Featured
              </span>
            </div>
            <h1 class="text-2xl font-bold leading-tight sm:text-3xl">{{ course.title }}</h1>
            <p class="mt-3 text-sm text-gray-200 sm:text-base">{{ course.short_description }}</p>
            <div class="mt-5 flex flex-wrap items-center gap-5 text-sm text-gray-200">
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

            <div class="mt-6 flex flex-wrap gap-3">
              <Link
                :href="`/tutor/courses/${course.id}/edit`"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-[#000928] transition-colors hover:bg-gray-100"
              >
                <Pencil class="h-4 w-4" /> Edit course
              </Link>
              <a
                v-if="publicCatalogueUrl"
                :href="publicCatalogueUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition-colors hover:bg-white/20"
              >
                <ExternalLink class="h-4 w-4" /> View on catalogue
              </a>
            </div>
          </div>
          <div class="relative aspect-video overflow-hidden rounded-xl border border-white/10 shadow-xl lg:col-span-2">
            <img
              v-if="coverUrl(course.cover_image)"
              :src="coverUrl(course.cover_image) ?? undefined"
              :alt="course.title"
              class="h-full w-full object-cover"
            />
            <div v-else class="flex h-full min-h-[160px] items-center justify-center bg-white/5">
              <BookOpen class="h-16 w-16 text-white/30" />
            </div>
          </div>
        </div>
      </section>

      <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
          <div
            v-if="course.description?.trim()"
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
          >
            <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-[#000928] dark:text-white">
              <BookOpen class="h-5 w-5 text-[#42b6c5]" /> About this course
            </h2>
            <div
              class="prose prose-sm max-w-none text-gray-600 prose-headings:text-[#000928] prose-a:text-[#42b6c5] dark:prose-invert dark:text-gray-300 dark:prose-headings:text-gray-100"
              v-html="courseDescriptionHtml(course.description)"
            />
          </div>

          <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#000928] dark:text-white">
              <Layers class="h-5 w-5 text-[#42b6c5]" /> Course content
            </h2>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
              {{ totalLessons }} lessons in
              {{ course.sections.length }} sections
            </p>

            <div v-if="course.sections.length === 0" class="rounded-xl border border-dashed border-gray-200 py-12 text-center text-sm text-gray-400">
              No sections yet. Add curriculum in the editor.
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="section in course.sections"
                :key="section.id"
                class="overflow-hidden rounded-xl border border-gray-100 dark:border-gray-600"
              >
                <button
                  type="button"
                  class="flex w-full items-center justify-between gap-3 bg-gray-50 px-4 py-3 text-left transition-colors hover:bg-gray-100 dark:bg-gray-700/50 dark:hover:bg-gray-700"
                  @click="toggleSection(section.id)"
                >
                  <span class="font-semibold text-[#000928] dark:text-gray-100">{{ section.title }}</span>
                  <ChevronDown
                    :class="['h-5 w-5 shrink-0 text-gray-400 transition-transform', expanded.has(section.id) ? 'rotate-180' : '']"
                  />
                </button>
                <div v-show="expanded.has(section.id)" class="divide-y divide-gray-100 border-t border-gray-100 bg-white dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-800">
                  <div
                    v-for="lesson in section.lessons"
                    :key="lesson.id"
                    class="flex items-center gap-3 px-4 py-3 text-sm"
                  >
                    <PlayCircle v-if="lesson.type === 'video'" class="h-4 w-4 shrink-0 text-[#381998]" />
                    <BookOpen v-else-if="lesson.type === 'text'" class="h-4 w-4 shrink-0 text-[#42b6c5]" />
                    <GraduationCap v-else class="h-4 w-4 shrink-0 text-gray-400" />
                    <div class="min-w-0 flex-1">
                      <p class="truncate font-medium text-gray-800 dark:text-gray-200">{{ lesson.title }}</p>
                      <p class="text-xs text-gray-400">
                        {{ typeLabels[lesson.type] ?? lesson.type }}
                        <template v-if="lesson.duration"> · {{ lesson.duration }}</template>
                      </p>
                    </div>
                    <Link
                      v-if="canLinkFreePreviews && lesson.is_free"
                      :href="`/online-courses/${course.id}/lessons/${lesson.id}/preview`"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="shrink-0 rounded-lg bg-[#42b6c5]/10 px-3 py-1 text-xs font-semibold text-[#2a8a96] transition-colors hover:bg-[#42b6c5]/20"
                    >
                      Preview
                    </Link>
                    <span v-else-if="lesson.is_free && !canLinkFreePreviews" class="shrink-0 text-xs text-amber-600 dark:text-amber-400">
                      Preview when published
                    </span>
                    <span v-else class="inline-flex shrink-0 items-center gap-1 text-xs text-gray-400">
                      <Lock class="h-3.5 w-3.5" /> Locked
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="sticky top-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pricing</h3>
            <div class="mb-4">
              <template v-if="priceInfo.amount <= 0">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">Free</p>
              </template>
              <template v-else>
                <p class="text-2xl font-bold text-[#000928] dark:text-white">{{ priceInfo.label }}</p>
                <p v-if="priceInfo.strike" class="mt-1 text-sm text-gray-400 line-through">{{ priceInfo.strike }}</p>
              </template>
            </div>
            <p
              v-if="!publicCatalogueUrl && course.status !== 'published'"
              class="mb-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-900 dark:bg-amber-900/20 dark:text-amber-200"
            >
              This course is not on the public catalogue until it is published.
            </p>
            <Link
              :href="`/tutor/courses/${course.id}/edit`"
              class="mb-3 flex w-full items-center justify-center gap-2 rounded-xl bg-[#381998] py-3 text-sm font-semibold text-white transition-colors hover:bg-[#000928]"
            >
              <Pencil class="h-4 w-4" /> Edit course
            </Link>
            <a
              v-if="publicCatalogueUrl"
              :href="publicCatalogueUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
            >
              <ExternalLink class="h-4 w-4" /> Open catalogue page
            </a>
          </div>

          <div
            v-if="priceInfo.amount > 0 && maxInstallments > 1"
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
          >
            <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment options</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300">Up to {{ maxInstallments }} installment(s)</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              ≈ {{ formatMoney(perInstallmentApprox) }} XAF per installment
            </p>
          </div>

          <div
            v-if="previewLessons.length > 0 && canLinkFreePreviews"
            class="rounded-xl border border-[#42b6c5]/30 bg-[#42b6c5]/5 p-6 dark:border-[#42b6c5]/40 dark:bg-[#42b6c5]/10"
          >
            <h3 class="mb-3 text-sm font-bold text-[#000928] dark:text-gray-100">Free preview lessons</h3>
            <ul class="space-y-2">
              <li v-for="pl in previewLessons" :key="pl.id">
                <a
                  :href="`/online-courses/${course.id}/lessons/${pl.id}/preview`"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-[#381998] hover:bg-white/80 dark:hover:bg-gray-800/80"
                >
                  <span class="line-clamp-1">{{ pl.title }}</span>
                  <span class="shrink-0 text-xs text-gray-500">{{ typeLabels[pl.type] ?? pl.type }}</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
