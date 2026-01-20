<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'

interface Event {
  id: number
  title: string
  description: string
  event_date: string
  location: string
  is_online: boolean
  event_url: string | null
  capacity: number
  category: string
  image_url: string | null
  is_active: boolean
  agenda: string | null
}

interface Props {
  event: Event
  categories: Record<string, string>
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const formatDateForInput = (date: string) => {
  const d = new Date(date)
  return d.toISOString().slice(0, 16)
}

const form = useForm({
  title: props.event.title,
  description: props.event.description,
  event_date: formatDateForInput(props.event.event_date),
  location: props.event.location,
  is_online: props.event.is_online,
  event_url: props.event.event_url || '',
  capacity: props.event.capacity,
  category: props.event.category,
  image: null as File | null,
  is_active: props.event.is_active,
  agenda: props.event.agenda || '',
})

const imagePreview = ref<string | null>(
  props.event.image_url
    ? props.event.image_url.startsWith('http')
      ? props.event.image_url
      : `/storage/${props.event.image_url}`
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
  form.put(`/admin/events/${props.event.id}`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Event updated successfully!')
    },
    onError: () => {
      toast.error('Failed to update event. Please check the form for errors.')
    },
  } as any)
}
</script>

<template>
  <div>
    <Head :title="`Edit ${event.title}`" />

    <!-- Header -->
    <div class="mb-8">
      <Link href="/admin/events" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Events
      </Link>
      <h2 class="text-3xl font-bold text-gray-900">Edit Event</h2>
      <p class="text-gray-600 mt-2">Update event details</p>
    </div>

    <form @submit.prevent="submit" class="space-y-8">
      <!-- Basic Information -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Event Details</h3>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Event Date & Time *</label>
            <input
              v-model="form.event_date"
              type="datetime-local"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.event_date" class="mt-1 text-sm text-red-600">{{ form.errors.event_date }}</p>
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
            <label class="flex items-center mb-2">
              <input
                v-model="form.is_online"
                type="checkbox"
                class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
              />
              <span class="ml-2 text-sm text-gray-700">This is an online event</span>
            </label>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Location *</label>
            <input
              v-model="form.location"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.location" class="mt-1 text-sm text-red-600">{{ form.errors.location }}</p>
          </div>

          <div v-if="form.is_online" class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Event URL *</label>
            <input
              v-model="form.event_url"
              type="url"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            />
            <p v-if="form.errors.event_url" class="mt-1 text-sm text-red-600">{{ form.errors.event_url }}</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
            <textarea
              v-model="form.description"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Agenda</label>
            <textarea
              v-model="form.agenda"
              rows="5"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Event Image -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Event Image</h3>
        <div class="flex items-start gap-6">
          <div class="flex-shrink-0">
            <div v-if="imagePreview" class="w-40 h-28 rounded-lg overflow-hidden">
              <img :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-40 h-28 rounded-lg bg-gray-100 flex items-center justify-center">
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
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
            <p class="mt-2 text-sm text-gray-500">Leave empty to keep current image</p>
          </div>
        </div>
      </div>

      <!-- Settings -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Settings</h3>
        <label class="flex items-center">
          <input
            v-model="form.is_active"
            type="checkbox"
            class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
          />
          <span class="ml-2 text-sm text-gray-700">Active (visible on website)</span>
        </label>
      </div>

      <!-- Submit -->
      <div class="flex items-center justify-end gap-4">
        <Link
          href="/admin/events"
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
