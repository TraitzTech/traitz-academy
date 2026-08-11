<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

import RichTextEditor from '@/components/RichTextEditor.vue'
import AppLayout from '@/layouts/AppLayout.vue'

interface Category { id: number; name: string; slug: string }
interface Course {
  id: number
  title: string
  category_id: number | null
  level: 'beginner' | 'intermediate' | 'advanced'
  short_description: string
  description: string | null
  duration: string | null
  status: 'draft' | 'pending_review' | 'published' | 'archived'
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

form.transform((data) => ({
  ...data,
  category_id: data.category_id === '' || data.category_id === null ? null : data.category_id,
  sale_price: data.sale_price === '' || data.sale_price === null ? null : data.sale_price,
}))

function save() {
  form.put(`/admin/courses/${props.course.id}`, { preserveScroll: true })
}
</script>

<template>
  <div class="mx-auto max-w-5xl">
    <Head :title="`Edit Course: ${course.title}`" />

    <div class="mb-6">
      <Link href="/admin/courses" class="mb-2 inline-flex items-center text-sm text-[#42b6c5] hover:text-[#35919e]">
        ← Back to Courses
      </Link>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Course</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update course details as admin</p>
    </div>

    <div class="max-w-4xl rounded-xl bg-white p-6 shadow dark:bg-gray-800">
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
            <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Level *</label>
            <select v-model="form.level" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
            <p v-if="form.errors.level" class="mt-1 text-xs text-red-600">{{ form.errors.level }}</p>
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Short description *</label>
          <textarea v-model="form.short_description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
          <p v-if="form.errors.short_description" class="mt-1 text-xs text-red-600">{{ form.errors.short_description }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Full description</label>
          <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Rich text — same formatting learners see on the course page.</p>
          <RichTextEditor
            v-model="form.description"
            placeholder="Detailed course overview, prerequisites, outcomes…"
            upload-url="/lesson-content/media"
            body-class="min-h-[200px] max-h-[min(55vh,520px)]"
          />
          <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Duration</label>
          <input v-model="form.duration" type="text" class="w-full max-w-md rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" />
          <p v-if="form.errors.duration" class="mt-1 text-xs text-red-600">{{ form.errors.duration }}</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-400">
          <p class="font-medium text-gray-800 dark:text-gray-200">Pricing &amp; featured</p>
          <p class="mt-1">
            Set price, sale price, installments, and featured on
            <Link :href="`/admin/courses/${course.id}/pricing`" class="font-medium text-[#381998] hover:underline dark:text-[#42b6c5]">Admin → Courses → Pricing</Link>.
          </p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status *</label>
          <select v-model="form.status" class="w-full max-w-md rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
            <option value="draft">Draft</option>
            <option value="pending_review">Pending review</option>
            <option value="published">Published</option>
            <option value="archived">Archived</option>
          </select>
          <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
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
