<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'

interface Program {
  id: number
  title: string
  category: string
  description: string
  overview: string | null
  who_is_for: string | null
  skills_and_tools: string | null
  duration: string
  learning_outcomes: string | null
  certification: string | null
  price: number
  image_url: string | null
  is_featured: boolean
  is_active: boolean
  capacity: number
  start_date: string | null
  end_date: string | null
  curriculum: string | null
}

interface Props {
  program: Program
  categories: Record<string, string>
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const form = useForm({
  title: props.program.title,
  category: props.program.category,
  description: props.program.description,
  overview: props.program.overview || '',
  who_is_for: props.program.who_is_for || '',
  skills_and_tools: props.program.skills_and_tools || '',
  duration: props.program.duration,
  learning_outcomes: props.program.learning_outcomes || '',
  certification: props.program.certification || '',
  price: props.program.price,
  image: null as File | null,
  is_featured: props.program.is_featured,
  is_active: props.program.is_active,
  capacity: props.program.capacity,
  start_date: props.program.start_date?.split('T')[0] || '',
  end_date: props.program.end_date?.split('T')[0] || '',
  curriculum: props.program.curriculum || '',
})

const imagePreview = ref<string | null>(
  props.program.image_url
    ? props.program.image_url.startsWith('http')
      ? props.program.image_url
      : `/storage/${props.program.image_url}`
    : null
)

const handleImageChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    form.image = target.files[0]
    imagePreview.value = URL.createObjectURL(target.files[0])
  }
}

const submit = () => {
  console.log('Submitting form with data:', form.data())
  form.put(`/admin/programs/${props.program.id}`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      console.log('Update successful!')
      toast.success('Program updated successfully!')
    },
    onError: (errors) => {
      console.error('Update failed with errors:', errors)
      toast.error('Failed to update program. Please check the form for errors.')
    },
  } as any)
}
</script>

<template>
  <div>
    <Head :title="`Edit ${program.title}`" />

    <!-- Header -->
    <div class="mb-8">
      <Link href="/admin/programs" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Programs
      </Link>
      <h2 class="text-3xl font-bold text-gray-900">Edit Program</h2>
      <p class="text-gray-600 mt-2">Update program details</p>
    </div>

    <form @submit.prevent="submit" class="space-y-8">
      <!-- Basic Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
            <select
              v-model="form.category"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            >
              <option value="">Select a category</option>
              <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
            </select>
            <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Duration *</label>
            <input
              v-model="form.duration"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.duration" class="mt-1 text-sm text-red-600">{{ form.errors.duration }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Price (XAF) *</label>
            <input
              v-model="form.price"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Capacity *</label>
            <input
              v-model="form.capacity"
              type="number"
              min="1"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.capacity" class="mt-1 text-sm text-red-600">{{ form.errors.capacity }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input
              v-model="form.start_date"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input
              v-model="form.end_date"
              type="date"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Short Description *</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
          </div>
        </div>
      </div>

      <!-- Program Image -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Program Image</h3>
        <div class="flex items-start gap-6">
          <div class="flex-shrink-0">
            <div v-if="imagePreview" class="w-40 h-28 rounded-lg overflow-hidden">
              <img :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-40 h-28 rounded-lg bg-gray-100 flex items-center justify-center">
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
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#42b6c5] file:text-white hover:file:bg-[#35919e]"
            />
            <p class="mt-2 text-sm text-gray-500">Leave empty to keep current image. Recommended: 600x400px, max 2MB</p>
            <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
          </div>
        </div>
      </div>

      <!-- Detailed Content -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Detailed Content</h3>
        <div class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Overview</label>
            <textarea
              v-model="form.overview"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Who Is This For?</label>
            <textarea
              v-model="form.who_is_for"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Skills & Tools</label>
            <input
              v-model="form.skills_and_tools"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Learning Outcomes</label>
            <textarea
              v-model="form.learning_outcomes"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Curriculum</label>
            <textarea
              v-model="form.curriculum"
              rows="6"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Certification</label>
            <input
              v-model="form.certification"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
          </div>
        </div>
      </div>

      <!-- Settings -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Settings</h3>
        <div class="space-y-4">
          <label class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
            />
            <span class="ml-2 text-sm text-gray-700">Active (visible on website)</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.is_featured"
              type="checkbox"
              class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
            />
            <span class="ml-2 text-sm text-gray-700">Featured (shown on homepage)</span>
          </label>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex items-center justify-end gap-4">
        <Link
          href="/admin/programs"
          class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
        >
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </form>
  </div>
</template>
