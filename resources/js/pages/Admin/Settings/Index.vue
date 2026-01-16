<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { useToast } from '@/composables/useToast'

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
  }
}

const props = defineProps<Props>()

defineOptions({ layout: AdminLayout })

const toast = useToast()
const activeTab = ref('branding')
const processing = ref(false)
const uploadingKey = ref<string | null>(null)

const tabs = [
  { key: 'branding', label: 'Branding', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
  { key: 'content', label: 'Content', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { key: 'contact', label: 'Contact', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
  { key: 'social', label: 'Social Media', icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
]

const currentSettings = computed(() => {
  const settings = props.settings?.[activeTab.value as keyof typeof props.settings]
  return Array.isArray(settings) ? settings : []
})

// Create a reactive form data object
const formData = ref<Record<string, string | null>>({})

// Initialize form data from settings
const initFormData = () => {
  const groups = ['branding', 'content', 'contact', 'social'] as const
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
    onError: (errors) => {
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
  <div>
    <Head title="Site Settings" />

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold text-gray-900">Site Settings</h2>
        <p class="text-gray-600 mt-2">Manage your site branding, content, and configuration</p>
      </div>
      <button
        @click="saveSettings"
        :disabled="processing"
        class="inline-flex items-center px-4 py-2 bg-[#42b6c5] text-white font-medium rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-50"
      >
        <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ processing ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow">
      <div class="border-b border-gray-200">
        <nav class="flex -mb-px overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              'group inline-flex items-center px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap',
              activeTab === tab.key
                ? 'border-[#42b6c5] text-[#42b6c5]'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <svg
              :class="[
                'mr-2 h-5 w-5',
                activeTab === tab.key ? 'text-[#42b6c5]' : 'text-gray-400 group-hover:text-gray-500'
              ]"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
            </svg>
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Settings Content -->
      <div class="p-6">
        <div class="space-y-6">
          <div v-for="setting in currentSettings" :key="setting.key">
            <!-- Text Input -->
            <div v-if="setting.type === 'text'">
              <label :for="setting.key" class="block text-sm font-medium text-gray-700 mb-1">
                {{ setting.label }}
              </label>
              <input
                :id="setting.key"
                v-model="formData[setting.key]"
                type="text"
                class="w-full max-w-xl px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              />
              <p v-if="setting.description" class="mt-1 text-sm text-gray-500">{{ setting.description }}</p>
            </div>

            <!-- Textarea -->
            <div v-else-if="setting.type === 'textarea'">
              <label :for="setting.key" class="block text-sm font-medium text-gray-700 mb-1">
                {{ setting.label }}
              </label>
              <textarea
                :id="setting.key"
                v-model="formData[setting.key]"
                rows="4"
                class="w-full max-w-xl px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              ></textarea>
              <p v-if="setting.description" class="mt-1 text-sm text-gray-500">{{ setting.description }}</p>
            </div>

            <!-- URL Input -->
            <div v-else-if="setting.type === 'url'">
              <label :for="setting.key" class="block text-sm font-medium text-gray-700 mb-1">
                {{ setting.label }}
              </label>
              <input
                :id="setting.key"
                v-model="formData[setting.key]"
                type="url"
                class="w-full max-w-xl px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
                placeholder="https://..."
              />
              <p v-if="setting.description" class="mt-1 text-sm text-gray-500">{{ setting.description }}</p>
              
              <!-- YouTube Preview -->
              <div v-if="setting.key === 'youtube_video_url' && formData[setting.key]" class="mt-4 max-w-xl">
                <div class="aspect-video rounded-lg overflow-hidden bg-gray-100">
                  <iframe
                    :src="formData[setting.key]?.replace('watch?v=', 'embed/').replace('youtu.be/', 'youtube.com/embed/')"
                    class="w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                  ></iframe>
                </div>
              </div>
            </div>

            <!-- Email Input -->
            <div v-else-if="setting.type === 'email'">
              <label :for="setting.key" class="block text-sm font-medium text-gray-700 mb-1">
                {{ setting.label }}
              </label>
              <input
                :id="setting.key"
                v-model="formData[setting.key]"
                type="email"
                class="w-full max-w-xl px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              />
              <p v-if="setting.description" class="mt-1 text-sm text-gray-500">{{ setting.description }}</p>
            </div>

            <!-- Image Upload -->
            <div v-else-if="setting.type === 'image'">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ setting.label }}
              </label>
              <div class="flex items-start gap-4">
                <!-- Image Preview -->
                <div v-if="formData[setting.key]" class="relative">
                  <img
                    :src="getImageUrl(formData[setting.key])"
                    :alt="setting.label"
                    class="h-20 w-auto object-contain border border-gray-200 rounded-lg bg-gray-50 p-2"
                  />
                  <button
                    @click="deleteImage(setting)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                    type="button"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                <!-- Upload Button -->
                <div>
                  <label class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                    <svg v-if="uploadingKey === setting.key" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ formData[setting.key] ? 'Change Image' : 'Upload Image' }}</span>
                    <input
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="uploadImage($event, setting)"
                    />
                  </label>
                  <p v-if="setting.description" class="mt-2 text-sm text-gray-500">{{ setting.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="currentSettings.length === 0" class="text-center py-12 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p>No settings found in this category.</p>
            <p class="text-sm mt-1">Settings will be added automatically when needed.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
