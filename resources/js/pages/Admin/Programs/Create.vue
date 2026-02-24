<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Props {
  categories: Record<string, string>
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const form = useForm({
  title: '',
  category: '',
  description: '',
  overview: '',
  who_is_for: '',
  skills_and_tools: '',
  duration: '',
  learning_outcomes: '',
  certification: 'Industry-recognized certificate of completion',
  price: 0,
  max_installments: 1,
  is_cv_required: false,
  image: null as File | null,
  is_featured: false,
  is_active: true,
  capacity: 30,
  start_date: '',
  end_date: '',
  curriculum: '',
})

const imagePreview = ref<string | null>(null)

const isCareerRole = computed(() => ['job-opportunity', 'professional-internship'].includes(form.category))

const contentLabels = computed(() => {
  if (isCareerRole.value) {
    return {
      overview: 'Role Overview',
      overviewPlaceholder: 'Detailed role overview...',
      whoIsFor: 'Who Should Apply?',
      whoIsForPlaceholder: 'Describe the ideal candidate for this role...',
      skills: 'Required Skills & Tools',
      skillsPlaceholder: 'Laravel, Vue.js, Git, Docker, etc.',
      outcomes: 'Key Responsibilities',
      outcomesPlaceholder: 'What the role involves and what the hire will deliver...',
      curriculum: 'Role Scope & Work Plan',
      curriculumPlaceholder: 'Month 1: Onboarding & orientation...\nMonth 2: Project assignments...',
      certification: 'What We Offer',
    };
  }
  return {
    overview: 'Overview',
    overviewPlaceholder: 'Detailed program overview...',
    whoIsFor: 'Who Is This For?',
    whoIsForPlaceholder: 'Describe the ideal candidate...',
    skills: 'Skills & Tools',
    skillsPlaceholder: 'React, Node.js, MongoDB, Git, etc.',
    outcomes: 'Learning Outcomes',
    outcomesPlaceholder: 'What students will learn...',
    curriculum: 'Curriculum',
    curriculumPlaceholder: 'Week 1: Introduction...\nWeek 2: Fundamentals...',
    certification: 'Certification',
  };
})

const handleImageChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    form.image = target.files[0]
    imagePreview.value = URL.createObjectURL(target.files[0])
  }
}

const submit = () => {
  form.post('/admin/programs', {
    forceFormData: true,
    onSuccess: () => {
      toast.success('Program created successfully!')
    },
    onError: () => {
      toast.error('Failed to create program. Please check the form for errors.')
    }
  })
}
</script>

<template>
  <div>
    <Head title="Create Program" />

    <!-- Header -->
    <div class="mb-8">
      <Link href="/admin/programs" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Programs
      </Link>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Create New Program</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Add a new training program to the academy</p>
    </div>

    <form @submit.prevent="submit" class="space-y-8">
      <!-- Duplicate Program Alert -->
      <div v-if="form.errors.title && form.errors.title.includes('already exists')" class="bg-amber-50 dark:bg-amber-950/30 border-2 border-amber-400 dark:border-amber-600 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <svg class="h-6 w-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.27 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <div>
            <h3 class="font-semibold text-amber-800 dark:text-amber-300">Duplicate Program Detected</h3>
            <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">{{ form.errors.title }}</p>
            <p class="text-sm text-amber-600 dark:text-amber-500 mt-2">Tip: Programs can share the same title only if they belong to different categories (e.g., Academic Internship vs Professional Internship).</p>
          </div>
        </div>
      </div>

      <!-- Basic Information -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="e.g., Full-Stack Web Development Bootcamp"
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
            <select
              v-model="form.category"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            >
              <option value="">Select a category</option>
              <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Duration *</label>
            <input
              v-model="form.duration"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="e.g., 12 weeks"
            />
            <p v-if="form.errors.duration" class="mt-1 text-sm text-red-600">{{ form.errors.duration }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Price (XAF) *</label>
            <input
              v-model="form.price"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Capacity *</label>
            <input
              v-model="form.capacity"
              type="number"
              min="1"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.capacity" class="mt-1 text-sm text-red-600">{{ form.errors.capacity }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Installments *</label>
            <input
              v-model="form.max_installments"
              type="number"
              min="1"
              max="12"
              :disabled="Number(form.price) <= 0"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent disabled:opacity-60"
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Set to 1 for one-time payment. Free programs always use 1.</p>
            <p v-if="form.errors.max_installments" class="mt-1 text-sm text-red-600">{{ form.errors.max_installments }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
            <input
              v-model="form.start_date"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
            <input
              v-model="form.end_date"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Short Description *</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="Brief description for program cards"
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
          </div>
        </div>
      </div>

      <!-- Program Image -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Program Image</h3>
        <div class="flex items-start gap-6">
          <div class="flex-shrink-0">
            <div v-if="imagePreview" class="w-40 h-28 rounded-lg overflow-hidden">
              <img :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-40 h-28 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <div class="flex-1">
            <input
              type="file"
              accept="image/*"
              @change="handleImageChange"
              class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#42b6c5] file:text-white hover:file:bg-[#35919e]"
            />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Recommended: 600x400px, max 2MB</p>
            <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
          </div>
        </div>
      </div>

      <!-- Detailed Content -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Detailed Content</h3>
        <div class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ contentLabels.overview }}</label>
            <textarea
              v-model="form.overview"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              :placeholder="contentLabels.overviewPlaceholder"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ contentLabels.whoIsFor }}</label>
            <textarea
              v-model="form.who_is_for"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              :placeholder="contentLabels.whoIsForPlaceholder"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ contentLabels.skills }}</label>
            <input
              v-model="form.skills_and_tools"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              :placeholder="contentLabels.skillsPlaceholder"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ contentLabels.outcomes }}</label>
            <textarea
              v-model="form.learning_outcomes"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              :placeholder="contentLabels.outcomesPlaceholder"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ contentLabels.curriculum }}</label>
            <textarea
              v-model="form.curriculum"
              rows="6"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              :placeholder="contentLabels.curriculumPlaceholder"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ contentLabels.certification }}</label>
            <input
              v-model="form.certification"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
          </div>
        </div>
      </div>

      <!-- Settings -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Settings</h3>
        <div class="space-y-4">
          <label class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              class="rounded border-gray-300 dark:border-gray-600 text-[#42b6c5] focus:ring-[#42b6c5]"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active (visible on website)</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.is_featured"
              type="checkbox"
              class="rounded border-gray-300 dark:border-gray-600 text-[#42b6c5] focus:ring-[#42b6c5]"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Featured (shown on homepage)</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.is_cv_required"
              type="checkbox"
              class="rounded border-gray-300 dark:border-gray-600 text-[#42b6c5] focus:ring-[#42b6c5]"
            />
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Require CV for applications</span>
          </label>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex items-center justify-end gap-4">
        <Link
          href="/admin/programs"
          class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-50"
        >
          {{ form.processing ? 'Creating...' : 'Create Program' }}
        </button>
      </div>
    </form>
  </div>
</template>
