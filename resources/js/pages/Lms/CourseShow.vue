<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
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
import { computed, ref } from 'vue'

import PublicLayout from '@/layouts/PublicLayout.vue'

interface InstalmentPlan {
  id: number
  name: string
  number_of_instalments: number
  amount_per_instalment: string
  interval_in_days: number
  is_active: boolean
}

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
  duration: string | null
  enrolled_count: number
  rating: string
  review_count: number
  is_featured: boolean
  instructor: { id: number; name: string } | null
  category: { id: number; name: string; slug: string; icon: string | null; color: string | null } | null
  instalmentPlans?: InstalmentPlan[]
  instalment_plans?: InstalmentPlan[]
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
}>()

const page = usePage()
const isLoggedIn = computed(() => !!(page.props.auth as { user?: unknown })?.user)

const expanded = ref<Set<number>>(new Set(props.course.sections.map((s) => s.id)))

function toggleSection(id: number) {
  expanded.value.has(id) ? expanded.value.delete(id) : expanded.value.add(id)
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
  if (sp != null && sp < p) return { label: `${formatMoney(sp)} XAF`, amount: sp, strike: `${formatMoney(p)} XAF` }
  return { label: `${formatMoney(p)} XAF`, amount: p, strike: null as string | null }
})

function planTotal(plan: InstalmentPlan) {
  return plan.number_of_instalments * parseFloat(plan.amount_per_instalment)
}

const enrolHref = computed(() => (isLoggedIn.value ? '/dashboard/courses' : '/login'))

const instalmentPlans = computed(() => props.course.instalment_plans ?? props.course.instalmentPlans ?? [])

const typeLabels: Record<string, string> = {
  video: 'Video',
  text: 'Text',
  quiz: 'Quiz',
}
</script>

<template>
  <PublicLayout>
    <Head :title="`${course.title} — Online Courses`" />

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
            <div v-if="course.description" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
              <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-[#000928]">
                <BookOpen class="h-5 w-5 text-[#42b6c5]" /> About this course
              </h2>
              <div class="prose prose-sm max-w-none text-gray-600 whitespace-pre-wrap">{{ course.description }}</div>
            </div>

            <!-- Curriculum -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
              <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#000928]">
                <Layers class="h-5 w-5 text-[#42b6c5]" /> Course content
              </h2>
              <p class="mb-4 text-sm text-gray-500">
                {{ course.sections.reduce((n: number, s: SectionRow) => n + s.lessons.length, 0) }} lessons in {{ course.sections.length }} sections
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
                        v-if="lesson.is_free"
                        :href="`/online-courses/${course.id}/lessons/${lesson.id}/preview`"
                        class="shrink-0 rounded-lg bg-[#42b6c5]/10 px-3 py-1 text-xs font-semibold text-[#2a8a96] transition-colors hover:bg-[#42b6c5]/20"
                      >
                        Preview
                      </Link>
                      <span v-else class="inline-flex shrink-0 items-center gap-1 text-xs text-gray-400">
                        <Lock class="h-3.5 w-3.5" /> Enrolled
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar: pricing -->
          <div class="space-y-6">
            <div class="sticky top-24 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
              <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-500">Enrolment</h3>
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
                :href="enrolHref"
                class="mb-4 flex w-full items-center justify-center rounded-xl bg-[#000928] py-3 text-sm font-semibold text-white transition-colors hover:bg-[#381998]"
              >
                {{ isLoggedIn ? 'Browse dashboard' : 'Sign in to enrol' }}
              </Link>
              <p class="text-center text-xs text-gray-400">Full access after purchase or enrolment.</p>
            </div>

            <!-- Installment plans -->
            <div
              v-if="instalmentPlans.length > 0 && priceInfo.amount > 0"
              class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
            >
              <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-500">Pay in installments</h3>
              <ul class="space-y-3">
                <li
                  v-for="plan in instalmentPlans"
                  :key="plan.id"
                  class="rounded-xl border border-gray-100 bg-gray-50/80 p-4"
                >
                  <p class="font-semibold text-[#000928]">{{ plan.name }}</p>
                  <p class="mt-1 text-sm text-gray-600">
                    {{ plan.number_of_instalments }} × {{ formatMoney(plan.amount_per_instalment) }} XAF
                    <span class="text-gray-400">· every {{ plan.interval_in_days }} days</span>
                  </p>
                  <p class="mt-2 text-xs text-gray-500">
                    Total ≈ {{ formatMoney(planTotal(plan)) }} XAF
                  </p>
                </li>
              </ul>
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
  </PublicLayout>
</template>
