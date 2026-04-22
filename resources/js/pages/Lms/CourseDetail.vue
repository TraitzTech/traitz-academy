<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { BookOpen, ChevronDown, Clock, Layers, Lock, PlayCircle, User, Users } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import { courseDescriptionHtml } from '@/utils/lessonContentHtml'

interface LessonRow {
  id: number
  title: string
  type: string
  duration: string | null
  is_free: boolean
}

interface SectionRow {
  id: number
  title: string
  lessons: LessonRow[]
}

interface Course {
  id: number
  title: string
  short_description: string
  description: string | null
  cover_image: string | null
  level: string
  price: string
  sale_price: string | null
  max_installments?: number
  duration: string | null
  enrolled_count: number
  instructor: { id: number; name: string } | null
  category: { id: number; name: string; icon: string | null } | null
  sections: SectionRow[]
}

const props = defineProps<{
  course: Course
  previewLessons: Array<{ id: number; course_section_id: number; title: string; type: string }>
  isEnrolled: boolean
  requiresCheckout: boolean
}>()

const enrolling = ref(false)

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

const expanded = ref<Set<number>>(new Set(props.course.sections.map((s) => s.id)))

function toggleSection(id: number) {
  if (expanded.value.has(id)) expanded.value.delete(id)
  else expanded.value.add(id)
}

function coverUrl(url: string | null) {
  if (!url) return null
  return url.startsWith('http') ? url : `/storage/${url}`
}

function money(v: string | number) {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return n.toLocaleString()
}

const effectivePrice = computed(() => {
  const p = parseFloat(props.course.price)
  if (p <= 0) return 0
  const sp = props.course.sale_price ? parseFloat(props.course.sale_price) : null
  if (sp != null && sp > 0 && sp < p) return sp
  return p
})

const maxInstallments = computed(() => Math.max(1, props.course.max_installments ?? 1))

const perInstallment = computed(() => {
  if (effectivePrice.value <= 0) return 0
  return Math.round((effectivePrice.value / maxInstallments.value) * 100) / 100
})

const firstLessonId = computed(() => props.course.sections[0]?.lessons[0]?.id ?? null)
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'Courses', href: '/dashboard/courses' },
    { title: course.title, href: `/dashboard/courses/${course.id}` },
  ]">
    <Head :title="`${course.title} - Course`" />

    <div class="lms-page">
    <section class="mb-8 overflow-hidden rounded-2xl bg-linear-to-br from-[#000928] via-[#1a0a52] to-[#381998] p-6 text-white shadow-lg">
      <div class="grid gap-6 lg:grid-cols-5 lg:items-start">
        <div class="lg:col-span-3">
          <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
            <span v-if="course.category" class="rounded-full bg-white/15 px-3 py-1 font-semibold">{{ course.category.icon }} {{ course.category.name }}</span>
            <span class="rounded-full bg-white/15 px-3 py-1 font-semibold">{{ course.level }}</span>
          </div>
          <h1 class="text-3xl font-bold">{{ course.title }}</h1>
          <p class="mt-3 text-gray-200">{{ course.short_description }}</p>
          <div class="mt-5 flex flex-wrap items-center gap-5 text-sm text-gray-200">
            <span v-if="course.instructor" class="inline-flex items-center gap-2"><User class="h-4 w-4" /> {{ course.instructor.name }}</span>
            <span v-if="course.duration" class="inline-flex items-center gap-2"><Clock class="h-4 w-4" /> {{ course.duration }}</span>
            <span class="inline-flex items-center gap-2"><Users class="h-4 w-4" /> {{ course.enrolled_count.toLocaleString() }} learners</span>
          </div>
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl bg-white/10 lg:col-span-2">
          <img v-if="coverUrl(course.cover_image)" :src="coverUrl(course.cover_image) ?? undefined" :alt="course.title" class="h-full w-full object-cover" />
          <div v-else class="flex h-full items-center justify-center"><BookOpen class="h-12 w-12 text-white/40" /></div>
        </div>
      </div>
    </section>

    <div class="grid gap-8 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <div v-if="course.description?.trim()" class="lms-panel">
          <h2 class="mb-3 text-lg font-bold text-[#000928] dark:text-white">About this course</h2>
          <div
            class="prose prose-sm max-w-none text-gray-600 prose-headings:text-[#000928] prose-a:text-[#42b6c5] dark:prose-invert dark:text-gray-300 dark:prose-headings:text-gray-100"
            v-html="courseDescriptionHtml(course.description)"
          />
        </div>

        <div class="lms-panel">
          <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#000928] dark:text-white"><Layers class="h-5 w-5 text-[#42b6c5]" /> Curriculum</h2>
          <div class="space-y-2">
            <div v-for="section in course.sections" :key="section.id" class="overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700">
              <button type="button" class="flex w-full items-center justify-between bg-gray-50 px-4 py-3 text-left dark:bg-gray-900/40" @click="toggleSection(section.id)">
                <span class="font-semibold text-[#000928] dark:text-gray-100">{{ section.title }}</span>
                <ChevronDown :class="['h-4 w-4 transition-transform', expanded.has(section.id) ? 'rotate-180' : '']" />
              </button>
              <div v-show="expanded.has(section.id)" class="divide-y divide-gray-100 dark:divide-gray-700">
                <div v-for="lesson in section.lessons" :key="lesson.id" class="flex items-center gap-3 px-4 py-3 text-sm">
                  <PlayCircle class="h-4 w-4 shrink-0 text-[#381998]" />
                  <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-gray-800 dark:text-gray-100">{{ lesson.title }}</p>
                    <p class="text-xs text-gray-400">{{ lesson.type }}<template v-if="lesson.duration"> · {{ lesson.duration }}</template></p>
                  </div>
                  <Link
                    v-if="isEnrolled"
                    :href="`/dashboard/courses/${course.id}/lessons/${lesson.id}`"
                    class="rounded-md bg-[#381998]/10 px-2 py-1 text-xs font-semibold text-[#381998]"
                  >
                    Open
                  </Link>
                  <Link
                    v-else-if="lesson.is_free"
                    :href="`/online-courses/${course.id}/lessons/${lesson.id}/preview`"
                    class="rounded-md bg-[#42b6c5]/10 px-2 py-1 text-xs font-semibold text-[#2a8a96]"
                  >
                    Preview
                  </Link>
                  <span v-else class="inline-flex items-center gap-1 text-xs text-gray-400"><Lock class="h-3 w-3" /> Locked</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="lms-panel">
          <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-500">Pricing</h3>
          <p v-if="parseFloat(course.price) <= 0" class="text-3xl font-bold text-green-600">Free</p>
          <template v-else>
            <p class="text-3xl font-bold text-[#000928] dark:text-white">
              {{ money(parseFloat(course.sale_price) > 0 && parseFloat(course.sale_price) < parseFloat(course.price) ? course.sale_price : course.price) }} XAF
            </p>
            <p v-if="parseFloat(course.sale_price) > 0 && parseFloat(course.sale_price) < parseFloat(course.price)" class="mt-1 text-sm text-gray-400 line-through">
              {{ money(course.price) }} XAF
            </p>
            <p v-if="parseFloat(course.price) > 0 && maxInstallments > 1" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
              Flexible payment: up to {{ maxInstallments }} installment(s)
            </p>
            <p v-if="parseFloat(course.price) > 0 && maxInstallments > 1" class="lms-subtitle">
              ≈ {{ money(perInstallment) }} XAF per installment
            </p>
          </template>

          <Link
            v-if="isEnrolled && firstLessonId"
            :href="`/dashboard/courses/${course.id}/lessons/${firstLessonId}`"
            class="mt-4 flex w-full items-center justify-center rounded-xl border border-[#42b6c5] bg-[#42b6c5]/10 py-3 text-sm font-semibold text-[#2a8a96] transition-colors hover:bg-[#42b6c5]/20"
          >
            Continue learning
          </Link>
          <Link
            v-else-if="isEnrolled"
            href="/dashboard/my-courses"
            class="mt-4 flex w-full items-center justify-center rounded-xl border border-[#42b6c5] bg-[#42b6c5]/10 py-3 text-sm font-semibold text-[#2a8a96] transition-colors hover:bg-[#42b6c5]/20"
          >
            Go to My Courses
          </Link>
          <Link
            v-else-if="requiresCheckout"
            :href="`/dashboard/courses/${course.id}/checkout`"
            class="mt-4 flex w-full items-center justify-center rounded-xl bg-[#000928] py-3 text-sm font-semibold text-white transition-colors hover:bg-[#381998]"
          >
            Pay &amp; enroll
          </Link>
          <template v-else>
            <button
              type="button"
              :disabled="enrolling"
              class="mt-4 flex w-full items-center justify-center rounded-xl bg-[#000928] py-3 text-sm font-semibold text-white transition-colors hover:bg-[#381998] disabled:opacity-60"
              @click="enroll"
            >
              {{ enrolling ? 'Enrolling…' : 'Enroll in this course' }}
            </button>
          </template>
          <Link href="/dashboard/courses" class="mt-3 flex w-full items-center justify-center rounded-xl border border-gray-200 py-2.5 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700/50">
            Back to all courses
          </Link>
        </div>

      </div>
    </div>
    </div>
  </AppLayout>
</template>
