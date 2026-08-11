<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, BookOpen, Sparkles } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';

interface Category {
  id: number;
  name: string;
  slug: string;
}

defineProps<{
  categories: Category[];
}>();

const levels = [
  { value: 'beginner', label: 'Beginner', hint: 'No prior experience' },
  { value: 'intermediate', label: 'Intermediate', hint: 'Some fundamentals' },
  { value: 'advanced', label: 'Advanced', hint: 'Experienced learners' },
] as const;

const form = useForm({
  title:             '',
  category_id:       '',
  level:             'beginner',
  short_description: '',
});

function submit() {
  form.post('/tutor/courses');
}
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'My Courses', href: '/tutor/courses' },
    { title: 'New Course', href: '/tutor/courses/create' },
  ]">
    <Head title="Create Course" />

    <div class="mx-auto max-w-3xl">
      <!-- Header -->
      <div class="mb-6 flex items-center gap-4">
        <Link
          href="/tutor/courses"
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition-colors hover:border-[#381998] hover:text-[#381998] dark:border-gray-700"
        >
          <ArrowLeft class="h-4 w-4" />
        </Link>
        <div class="min-w-0">
          <h1 class="text-2xl font-bold text-[#000928] dark:text-white">Create New Course</h1>
          <p class="mt-0.5 text-sm text-gray-500">Start with the basics — you'll add content next.</p>
        </div>
        <span
          class="ml-auto hidden shrink-0 items-center gap-1.5 rounded-full bg-[#381998]/10 px-3 py-1 text-xs font-semibold text-[#381998] sm:inline-flex"
        >
          Step 1 of 2 · Basics
        </span>
      </div>

      <!-- Form card -->
      <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <!-- Brand accent -->
        <div class="h-1.5 bg-gradient-to-r from-[#381998] via-[#42b6c5] to-[#000928]"></div>

        <div class="p-6 sm:p-8">
          <div class="mb-6 flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#381998]/10">
              <BookOpen class="h-5 w-5 text-[#381998]" />
            </div>
            <div>
              <h2 class="font-bold text-[#000928] dark:text-white">Course Overview</h2>
              <p class="text-xs text-gray-500">This will be your course's first impression.</p>
            </div>
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <!-- Title -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                Course Title <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                placeholder="e.g. Python for Data Science"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                :class="{ 'border-red-400': form.errors.title }"
              />
              <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
            </div>

            <!-- Category -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Category</label>
              <select
                v-model="form.category_id"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-700 focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
              >
                <option value="">No category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-500">{{ form.errors.category_id }}</p>
            </div>

            <!-- Level (segmented) -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                Level <span class="text-red-500">*</span>
              </label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="lvl in levels"
                  :key="lvl.value"
                  type="button"
                  @click="form.level = lvl.value"
                  class="rounded-xl border px-3 py-2.5 text-left transition-all"
                  :class="form.level === lvl.value
                    ? 'border-[#381998] bg-[#381998]/5 ring-1 ring-[#381998]/20'
                    : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500'"
                >
                  <span
                    class="block text-sm font-semibold"
                    :class="form.level === lvl.value ? 'text-[#381998]' : 'text-gray-700 dark:text-gray-200'"
                  >
                    {{ lvl.label }}
                  </span>
                  <span class="mt-0.5 block text-[11px] leading-tight text-gray-400">{{ lvl.hint }}</span>
                </button>
              </div>
              <p v-if="form.errors.level" class="mt-1 text-xs text-red-500">{{ form.errors.level }}</p>
            </div>

            <!-- Short description -->
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                Short Description <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="form.short_description"
                rows="3"
                placeholder="A brief summary of what students will learn — shown on course cards."
                class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                :class="{ 'border-red-400': form.errors.short_description }"
                maxlength="500"
              />
              <div class="mt-1 flex items-center justify-between">
                <p v-if="form.errors.short_description" class="text-xs text-red-500">{{ form.errors.short_description }}</p>
                <p
                  class="ml-auto text-xs tabular-nums"
                  :class="form.short_description.length > 450 ? 'text-amber-500' : 'text-gray-400'"
                >
                  {{ form.short_description.length }}/500
                </p>
              </div>
            </div>

            <!-- What's next hint -->
            <div class="flex items-start gap-2.5 rounded-xl bg-[#42b6c5]/[0.07] px-4 py-3">
              <Sparkles class="mt-0.5 h-4 w-4 shrink-0 text-[#42b6c5]" />
              <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                Next, you'll add the <strong class="font-semibold text-gray-700 dark:text-gray-200">curriculum, pricing, and full description</strong>
                on the course builder. Your course stays a private draft until you publish it.
              </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between border-t border-gray-100 pt-5 dark:border-gray-700">
              <Link
                href="/tutor/courses"
                class="rounded-xl px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:text-[#381998] dark:text-gray-300"
              >
                Cancel
              </Link>
              <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#000928] disabled:opacity-60"
              >
                <span v-if="form.processing">Creating…</span>
                <template v-else>
                  Create &amp; Continue
                  <ArrowRight class="h-4 w-4" />
                </template>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
