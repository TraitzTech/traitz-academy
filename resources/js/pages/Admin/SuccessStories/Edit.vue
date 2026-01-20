<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'

interface SuccessStory {
  id: number
  name: string
  role: string | null
  company: string | null
  story: string
  image_url: string | null
  is_active: boolean
  sort_order: number
}

interface Props {
  story: SuccessStory
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const form = useForm({
  name: props.story.name,
  role: props.story.role || '',
  company: props.story.company || '',
  story: props.story.story,
  image: null as File | null,
  is_active: props.story.is_active,
  sort_order: props.story.sort_order,
})

const imagePreview = ref<string | null>(
  props.story.image_url 
    ? (props.story.image_url.startsWith('http') ? props.story.image_url : `/storage/${props.story.image_url}`)
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
  form.transform((data) => ({
    ...data,
    _method: 'PUT',
  })).post(`/admin/success-stories/${props.story.id}`, {
    forceFormData: true,
    onSuccess: () => {
      toast.success('Success story updated successfully!')
    },
    onError: () => {
      toast.error('Failed to update story. Please check the form for errors.')
    }
  })
}
</script>

<template>
  <div>
    <Head title="Edit Success Story" />

    <!-- Header -->
    <div class="mb-8">
      <Link href="/admin/success-stories" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Success Stories
      </Link>
      <h2 class="text-3xl font-bold text-gray-900">Edit Success Story</h2>
      <p class="text-gray-600 mt-2">Update the testimonial from {{ props.story.name }}</p>
    </div>

    <form @submit.prevent="submit" class="space-y-8">
      <!-- Person Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Person Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="e.g., John Doe"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Role / Job Title</label>
            <input
              v-model="form.role"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="e.g., Software Engineer"
            />
            <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company / Organization</label>
            <input
              v-model="form.company"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="e.g., Tech Startup Lagos"
            />
            <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">{{ form.errors.company }}</p>
          </div>
        </div>
      </div>

      <!-- Story -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Testimonial</h3>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Story / Quote *</label>
          <textarea
            v-model="form.story"
            rows="4"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            placeholder="Write the student's testimonial here..."
          ></textarea>
          <p class="mt-1 text-sm text-gray-500">Maximum 1000 characters. Current: {{ form.story.length }}</p>
          <p v-if="form.errors.story" class="mt-1 text-sm text-red-600">{{ form.errors.story }}</p>
        </div>
      </div>

      <!-- Image -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Photo</h3>
        <div class="flex items-start gap-6">
          <div class="flex-shrink-0">
            <div v-if="imagePreview" class="w-24 h-24 rounded-full overflow-hidden">
              <img :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center">
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
            <input
              type="file"
              accept="image/jpeg,image/png,image/jpg,image/webp"
              @change="handleImageChange"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p class="mt-1 text-sm text-gray-500">Recommended: Square image, max 2MB. Leave empty to keep current image.</p>
            <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
          </div>
        </div>
      </div>

      <!-- Settings -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
            <input
              v-model="form.sort_order"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p class="mt-1 text-sm text-gray-500">Lower numbers appear first</p>
            <p v-if="form.errors.sort_order" class="mt-1 text-sm text-red-600">{{ form.errors.sort_order }}</p>
          </div>

          <div class="flex items-center">
            <label class="flex items-center cursor-pointer">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="h-5 w-5 text-[#42b6c5] focus:ring-[#42b6c5] border-gray-300 rounded"
              />
              <span class="ml-3 text-sm font-medium text-gray-700">Publish on homepage</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex items-center justify-end gap-4">
        <Link
          href="/admin/success-stories"
          class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
        >
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ form.processing ? 'Updating...' : 'Update Story' }}
        </button>
      </div>
    </form>
  </div>
</template>
