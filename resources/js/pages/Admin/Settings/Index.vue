<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { CreditCard, FileText, Image as ImageIcon, Link2, Loader2, Mail, MapPin, SlidersHorizontal, Trash2, Upload, X } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'
import { STREAMING_IFRAME_ALLOW, streamingEmbedSrc } from '@/utils/videoEmbed'

interface Setting {
  id: number
  key: string
  value: string | null
  type: 'text' | 'textarea' | 'url' | 'email' | 'image'
  group: string
  label: string
  description: string | null
}

interface Props {
  settings: {
    branding: Setting[]
    content: Setting[]
    contact: Setting[]
    social: Setting[]
    payments: Setting[]
  }
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()
const activeTab = ref('branding')
const processing = ref(false)
const uploadingKey = ref<string | null>(null)

const tabs = [
  { key: 'branding', label: 'Branding', icon: ImageIcon },
  { key: 'content', label: 'Content', icon: FileText },
  { key: 'contact', label: 'Contact', icon: Mail },
  { key: 'social', label: 'Social Media', icon: Link2 },
  { key: 'payments', label: 'Payments', icon: CreditCard },
  { key: 'internship', label: 'Internship', icon: MapPin },
]

const locating = ref(false)

// Capture the admin's live location (they run this at the office) and store it
// as the office coordinates. Server verifies distance from these on clock-in.
const useMyLocation = () => {
  if (!navigator.geolocation) {
    toast.error('Your browser does not support location.')
    return
  }
  locating.value = true
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      formData.value['office_latitude'] = pos.coords.latitude.toFixed(7)
      formData.value['office_longitude'] = pos.coords.longitude.toFixed(7)
      locating.value = false
      toast.success('Location captured. Remember to Save.')
    },
    () => {
      locating.value = false
      toast.error('Could not get your location. Allow location access and ensure you are on HTTPS.')
    },
    { enableHighAccuracy: true, timeout: 10000 },
  )
}

const currentSettings = computed(() => {
  const settings = props.settings?.[activeTab.value as keyof typeof props.settings]
  return Array.isArray(settings) ? settings : []
})

// Create a reactive form data object
const formData = ref<Record<string, string | null>>({})

// Initialize form data from settings
const initFormData = () => {
  const groups = ['branding', 'content', 'contact', 'social', 'payments', 'internship'] as const
  groups.forEach(group => {
    const settings = props.settings?.[group]
    if (Array.isArray(settings)) {
      settings.forEach(setting => {
        formData.value[setting.key] = setting.value
      })
    }
  })
}
initFormData()

// Re-initialize when props change
watch(() => props.settings, initFormData, { deep: true })

const saveSettings = async () => {
  processing.value = true
  router.put('/admin/settings', { settings: formData.value }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Settings saved successfully!')
    },
    onError: () => {
      toast.error('Failed to save settings. Please try again.')
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

const uploadImage = async (event: Event, setting: Setting) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  uploadingKey.value = setting.key
  const formDataObj = new FormData()
  formDataObj.append('image', file)
  formDataObj.append('key', setting.key)

  router.post('/admin/settings/upload', formDataObj, {
    preserveScroll: true,
    onSuccess: () => {
      // Update local form data
      const reader = new FileReader()
      reader.onload = (e) => {
        formData.value[setting.key] = e.target?.result as string
      }
      reader.readAsDataURL(file)
      toast.success('Image uploaded successfully!')
    },
    onError: () => {
      toast.error('Failed to upload image. Please try again.')
    },
    onFinish: () => {
      uploadingKey.value = null
    }
  })
}

const deleteImage = (setting: Setting) => {
  if (!confirm('Are you sure you want to delete this image?')) return

  router.delete(`/admin/settings/image/${setting.key}`, {
    preserveScroll: true,
    onSuccess: () => {
      formData.value[setting.key] = null
      toast.success('Image deleted successfully!')
    },
    onError: () => {
      toast.error('Failed to delete image. Please try again.')
    }
  })
}

const getImageUrl = (value: string | null) => {
  if (!value) return null
  if (value.startsWith('http') || value.startsWith('data:')) return value
  return `/storage/${value}`
}
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <Head title="Site Settings" />

    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-[#000928] dark:text-white lg:text-3xl">Site Settings</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your site branding, content, and configuration</p>
      </div>
      <button
        @click="saveSettings"
        :disabled="processing"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#35919e] disabled:opacity-50"
      >
        <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
        {{ processing ? 'Saving…' : 'Save Changes' }}
      </button>
    </div>

    <!-- Card -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="h-1.5 bg-gradient-to-r from-[#381998] via-[#42b6c5] to-[#000928]"></div>

      <!-- Tabs: segmented-control style, not plain underlined links -->
      <div class="border-b border-gray-100 p-3 dark:border-gray-700">
        <nav class="flex flex-wrap gap-1.5">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              'inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold whitespace-nowrap transition-colors',
              activeTab === tab.key
                ? 'bg-[#381998]/10 text-[#381998] dark:bg-[#42b6c5]/15 dark:text-[#7ee8f9]'
                : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700/50 dark:hover:text-gray-200'
            ]"
          >
            <component :is="tab.icon" class="h-4 w-4" />
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Settings Content -->
      <div class="p-6 sm:p-8">
        <!-- Internship: capture office location -->
        <div v-if="activeTab === 'internship'" class="mb-6 rounded-xl border border-dashed border-[#42b6c5]/50 bg-[#42b6c5]/[0.05] p-4">
          <p class="text-sm font-semibold text-[#000928] dark:text-white">Office location (attendance geofence)</p>
          <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-300">
            Interns can only clock in from the office. Stand at the office and tap the button to capture its coordinates, then Save. A ~100m safety buffer is added automatically so interns aren't rejected by GPS drift.
          </p>
          <button
            type="button"
            :disabled="locating"
            class="mt-3 inline-flex items-center gap-2 rounded-xl bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#35919e] disabled:opacity-60"
            @click="useMyLocation"
          >
            <Loader2 v-if="locating" class="h-4 w-4 animate-spin" />
            {{ locating ? 'Getting location…' : 'Use my current location' }}
          </button>
        </div>

        <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="setting in currentSettings"
            :key="setting.key"
            :class="['text', 'email', 'image'].includes(setting.type) ? '' : 'sm:col-span-2 lg:col-span-3'"
          >
            <!-- Text Input -->
            <div v-if="setting.type === 'text'">
              <label :for="setting.key" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ setting.label }}
              </label>
              <input
                :id="setting.key"
                v-model="formData[setting.key]"
                type="text"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
              />
              <p v-if="setting.description" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ setting.description }}</p>
            </div>

            <!-- Textarea -->
            <div v-else-if="setting.type === 'textarea'">
              <label :for="setting.key" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ setting.label }}
              </label>
              <textarea
                :id="setting.key"
                v-model="formData[setting.key]"
                rows="4"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
              ></textarea>
              <p v-if="setting.description" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ setting.description }}</p>
            </div>

            <!-- URL Input -->
            <div v-else-if="setting.type === 'url'">
              <label :for="setting.key" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ setting.label }}
              </label>
              <input
                :id="setting.key"
                v-model="formData[setting.key]"
                type="url"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                placeholder="https://..."
              />
              <p v-if="setting.description" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ setting.description }}</p>

              <!-- YouTube Preview -->
              <div v-if="setting.key === 'youtube_video_url' && formData[setting.key]" class="mt-4">
                <div class="aspect-video overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-700">
                  <iframe
                    :src="streamingEmbedSrc(formData[setting.key] ?? null) ?? ''"
                    class="h-full w-full"
                    frameborder="0"
                    referrerpolicy="strict-origin-when-cross-origin"
                    :allow="STREAMING_IFRAME_ALLOW"
                    allowfullscreen
                  ></iframe>
                </div>
              </div>
            </div>

            <!-- Email Input -->
            <div v-else-if="setting.type === 'email'">
              <label :for="setting.key" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ setting.label }}
              </label>
              <input
                :id="setting.key"
                v-model="formData[setting.key]"
                type="email"
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
              />
              <p v-if="setting.description" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ setting.description }}</p>
            </div>

            <!-- Image Upload -->
            <div v-else-if="setting.type === 'image'">
              <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ setting.label }}
              </label>
              <div class="flex items-start gap-4">
                <!-- Image Preview -->
                <div v-if="formData[setting.key]" class="relative shrink-0">
                  <img
                    :src="getImageUrl(formData[setting.key])"
                    :alt="setting.label"
                    class="h-20 w-auto rounded-xl border border-gray-200 bg-gray-50 object-contain p-2 dark:border-gray-600 dark:bg-gray-700"
                  />
                  <button
                    @click="deleteImage(setting)"
                    class="absolute -top-2 -right-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                    type="button"
                  >
                    <X class="h-4 w-4" />
                  </button>
                </div>
                <!-- Upload Button -->
                <div>
                  <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-gray-300 px-4 py-2.5 text-sm text-gray-600 transition-colors hover:border-[#42b6c5] hover:bg-[#42b6c5]/[0.04] hover:text-[#42b6c5] dark:border-gray-600 dark:text-gray-300">
                    <Loader2 v-if="uploadingKey === setting.key" class="h-4 w-4 animate-spin" />
                    <Upload v-else class="h-4 w-4" />
                    <span>{{ formData[setting.key] ? 'Change Image' : 'Upload Image' }}</span>
                    <input
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="uploadImage($event, setting)"
                    />
                  </label>
                  <p v-if="setting.description" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ setting.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="currentSettings.length === 0" class="py-12 text-center">
            <SlidersHorizontal class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" />
            <p class="text-sm text-gray-500 dark:text-gray-400">No settings found in this category.</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Settings will be added automatically when needed.</p>
          </div>
        </div>
      </div>

      <!-- Sticky footer save (mirrors the top action so long forms don't lose it) -->
      <div class="flex justify-end border-t border-gray-100 bg-gray-50/60 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/30 sm:px-8">
        <button
          @click="saveSettings"
          :disabled="processing"
          class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#000928] disabled:opacity-60"
        >
          <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
          {{ processing ? 'Saving…' : 'Save Changes' }}
        </button>
      </div>
    </div>
  </div>
</template>
