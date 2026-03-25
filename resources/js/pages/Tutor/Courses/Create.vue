<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';

interface Category {
  id: number;
  name: string;
  slug: string;
}

defineProps<{
  categories: Category[];
}>();

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

    <div class="mx-auto max-w-2xl">
      <!-- Header -->
      <div class="mb-8 flex items-center gap-4">
        <Link
          href="/tutor/courses"
          class="flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition-colors hover:border-[#381998] hover:text-[#381998] dark:border-gray-700"
        >
          <ArrowLeft class="h-4 w-4" />
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-[#000928] dark:text-white">Create New Course</h1>
          <p class="mt-0.5 text-sm text-gray-500">Start with the basics — you can add content after.</p>
        </div>
      </div>

      <!-- Form card -->
      <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="mb-6 flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#381998]/10">
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
              class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
              :class="{ 'border-red-400': form.errors.title }"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
          </div>

          <!-- Category + Level row -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">Category</label>
              <select
                v-model="form.category_id"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-700 focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white"
              >
                <option value="">No category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-500">{{ form.errors.category_id }}</p>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                Level <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.level"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-700 focus:border-[#42b6c5] focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-white"
              >
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
              </select>
              <p v-if="form.errors.level" class="mt-1 text-xs text-red-500">{{ form.errors.level }}</p>
            </div>
          </div>

          <!-- Short description -->
          <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
              Short Description <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.short_description"
              rows="3"
              placeholder="A brief summary of what students will learn (max 500 characters)"
              class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
              :class="{ 'border-red-400': form.errors.short_description }"
              maxlength="500"
            />
            <div class="mt-1 flex items-center justify-between">
              <p v-if="form.errors.short_description" class="text-xs text-red-500">{{ form.errors.short_description }}</p>
              <p class="ml-auto text-xs text-gray-400">{{ form.short_description.length }}/500</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between pt-2">
            <Link
              href="/tutor/courses"
              class="rounded-xl px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:text-[#381998]"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928] disabled:opacity-60"
            >
              <span v-if="form.processing">Creating…</span>
              <span v-else>Create & Continue →</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
