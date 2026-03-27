<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'

interface Category { id: number; name: string; slug: string }
interface Course {
  id: number
  title: string
  category_id: number | null
  level: 'beginner' | 'intermediate' | 'advanced'
  short_description: string
  description: string | null
  price: string
  sale_price: string | null
  duration: string | null
  status: 'draft' | 'pending_review' | 'published' | 'archived'
  is_featured: boolean
  category: Category | null
}

const props = defineProps<{
  course: Course
  categories: Category[]
}>()

defineOptions({ layout: AppLayout })

const form = useForm({
  title: props.course.title,
  category_id: props.course.category?.id ?? '',
  level: props.course.level,
  short_description: props.course.short_description,
  description: props.course.description ?? '',
  price: props.course.price,
  sale_price: props.course.sale_price ?? '',
  duration: props.course.duration ?? '',
  status: props.course.status,
  is_featured: props.course.is_featured,
})

function save() {
  form.put(`/admin/courses/${props.course.id}`)
}
</script>

<template>
  <div>
    <Head :title="`Edit Course: ${course.title}`" />

    <div class="mb-6">
      <Link href="/admin/courses" class="mb-2 inline-flex items-center text-sm text-[#42b6c5] hover:text-[#35919e]">
        ← Back to Courses
      </Link>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Course</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update course details as admin</p>
    </div>

    <div class="max-w-3xl rounded-xl bg-white p-6 shadow dark:bg-gray-800">
      <form class="space-y-4" @submit.prevent="save">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title *</label>
          <input v-model="form.title" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
          <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
            <select v-model="form.category_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
              <option value="">No category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Level *</label>
            <select v-model="form.level" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Short description *</label>
          <textarea v-model="form.short_description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
          <p v-if="form.errors.short_description" class="mt-1 text-xs text-red-600">{{ form.errors.short_description }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
          <textarea v-model="form.description" rows="5" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Price *</label>
            <input v-model="form.price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sale price</label>
            <input v-model="form.sale_price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Duration</label>
            <input v-model="form.duration" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status *</label>
            <select v-model="form.status" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
              <option value="draft">Draft</option>
              <option value="pending_review">Pending review</option>
              <option value="published">Published</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <label class="mt-7 flex items-center gap-2">
            <input v-model="form.is_featured" type="checkbox" class="rounded border-gray-300" />
            <span class="text-sm text-gray-700 dark:text-gray-300">Featured</span>
          </label>
        </div>

        <div class="pt-2">
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#381998] px-5 py-2 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-50">
            {{ form.processing ? 'Saving…' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
