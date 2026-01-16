<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'

interface Props {
  recipientCounts: {
    all: number
    with_applications: number
    without_applications: number
    accepted_applicants: number
    pending_applicants: number
    rejected_applicants: number
  }
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const form = useForm({
  recipients: 'all',
  custom_emails: '',
  subject: '',
  message: '',
  action_text: '',
  action_url: '',
})

const previewMode = ref(false)

const recipientOptions = [
  { value: 'all', label: 'All Users', description: 'Send to all registered users' },
  { value: 'with_applications', label: 'Users with Applications', description: 'Users who have submitted at least one application' },
  { value: 'without_applications', label: 'Users without Applications', description: 'Users who have not submitted any applications' },
  { value: 'accepted_applicants', label: 'Accepted Applicants', description: 'Users with accepted applications' },
  { value: 'pending_applicants', label: 'Pending Applicants', description: 'Users with pending applications' },
  { value: 'rejected_applicants', label: 'Rejected Applicants', description: 'Users with rejected applications' },
  { value: 'custom', label: 'Custom List', description: 'Enter email addresses manually' },
]

const currentRecipientCount = computed(() => {
  if (form.recipients === 'custom') {
    const emails = form.custom_emails.split(/[\n,]/).filter(e => e.trim())
    return emails.length
  }
  return props.recipientCounts?.[form.recipients as keyof typeof props.recipientCounts] || 0
})

const showSendModal = ref(false)

const sendEmails = () => {
  showSendModal.value = true
}

const confirmSendEmails = () => {
  // Transform custom_emails string to array for backend validation
  form.transform((data) => ({
    ...data,
    custom_emails: data.custom_emails
      ? data.custom_emails.split(/[\n,]/).map((e: string) => e.trim()).filter((e: string) => e)
      : [],
  })).post('/admin/emails', {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Email queued for ${currentRecipientCount.value} recipient(s)!`)
      form.reset()
      showSendModal.value = false
    },
    onError: (errors) => {
      const errorMessage = Object.values(errors)[0] || 'Failed to send emails. Please try again.'
      toast.error(errorMessage as string)
    }
  })
}

const togglePreview = () => {
  previewMode.value = !previewMode.value
}
</script>

<template>
  <div>
    <Head title="Email Notifications" />

    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-gray-900">Email Notifications</h2>
      <p class="text-gray-600 mt-2">Send batch email notifications to users</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Email Form -->
      <div class="lg:col-span-2">
        <form @submit.prevent="sendEmails" class="bg-white rounded-lg shadow p-6 space-y-6">
          <!-- Recipients -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Recipients</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                v-for="option in recipientOptions"
                :key="option.value"
                @click="form.recipients = option.value"
                :class="[
                  'p-4 border-2 rounded-lg cursor-pointer transition-all',
                  form.recipients === option.value ? 'border-[#42b6c5] bg-cyan-50' : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                <div class="flex items-center gap-2">
                  <input
                    type="radio"
                    :value="option.value"
                    v-model="form.recipients"
                    class="text-[#42b6c5] focus:ring-[#42b6c5]"
                  />
                  <span class="font-medium text-gray-900">{{ option.label }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1 ml-6">{{ option.description }}</p>
              </div>
            </div>
          </div>

          <!-- Custom Emails (if custom selected) -->
          <div v-if="form.recipients === 'custom'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Addresses</label>
            <textarea
              v-model="form.custom_emails"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="Enter email addresses separated by commas or new lines..."
            ></textarea>
            <p class="mt-1 text-sm text-gray-500">{{ currentRecipientCount }} email(s) detected</p>
            <p v-if="form.errors.custom_emails" class="mt-1 text-sm text-red-600">{{ form.errors.custom_emails }}</p>
          </div>

          <!-- Subject -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subject Line</label>
            <input
              v-model="form.subject"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="Enter email subject..."
            />
            <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
          </div>

          <!-- Message -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
            <textarea
              v-model="form.message"
              rows="8"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="Enter your message..."
            ></textarea>
            <p class="mt-1 text-sm text-gray-500">You can use basic formatting. The message will be sent in a styled email template.</p>
            <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
          </div>

          <!-- Action Button (optional) -->
          <div class="border-t pt-6">
            <h4 class="text-sm font-medium text-gray-700 mb-4">Action Button (optional)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
                <input
                  v-model="form.action_text"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
                  placeholder="e.g., View Programs"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Button URL</label>
                <input
                  v-model="form.action_url"
                  type="url"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
                  placeholder="https://..."
                />
              </div>
            </div>
          </div>

          <!-- Submit -->
          <div class="flex items-center justify-between pt-4 border-t">
            <button
              type="button"
              @click="togglePreview"
              class="text-[#42b6c5] hover:text-[#35919e] font-medium"
            >
              {{ previewMode ? 'Hide Preview' : 'Show Preview' }}
            </button>
            <button
              type="submit"
              :disabled="form.processing || currentRecipientCount === 0"
              class="px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="form.processing">Sending...</span>
              <span v-else>Send to {{ currentRecipientCount }} Recipient(s)</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Sidebar / Preview -->
      <div class="space-y-6">
        <!-- Recipient Stats -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Recipient Statistics</h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">All Users</span>
              <span class="font-medium">{{ recipientCounts.all }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">With Applications</span>
              <span class="font-medium">{{ recipientCounts.with_applications }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Accepted</span>
              <span class="font-medium text-green-600">{{ recipientCounts.accepted_applicants }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Pending</span>
              <span class="font-medium text-yellow-600">{{ recipientCounts.pending_applicants }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Rejected</span>
              <span class="font-medium text-red-600">{{ recipientCounts.rejected_applicants }}</span>
            </div>
          </div>
        </div>

        <!-- Email Preview -->
        <div v-if="previewMode" class="bg-white rounded-lg shadow overflow-hidden">
          <div class="bg-gray-100 px-4 py-2 border-b">
            <span class="text-sm font-medium text-gray-600">Email Preview</span>
          </div>
          <div class="p-4">
            <div class="border rounded-lg p-4 bg-gray-50">
              <div class="text-sm text-gray-500 mb-2">Subject:</div>
              <div class="font-medium text-gray-900 mb-4">{{ form.subject || '(No subject)' }}</div>
              <div class="text-sm text-gray-500 mb-2">Message:</div>
              <div class="text-gray-900 whitespace-pre-line">{{ form.message || '(No message)' }}</div>
              <div v-if="form.action_text && form.action_url" class="mt-4">
                <a
                  :href="form.action_url"
                  target="_blank"
                  class="inline-block px-4 py-2 bg-[#42b6c5] text-white font-medium rounded-lg"
                >
                  {{ form.action_text }}
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Tips -->
        <div class="bg-blue-50 rounded-lg p-4">
          <h4 class="font-medium text-blue-900 mb-2">Tips</h4>
          <ul class="text-sm text-blue-800 space-y-1">
            <li>• Emails are sent in the background using a queue</li>
            <li>• Each recipient receives an individual email</li>
            <li>• Test with a small group first before mass sending</li>
            <li>• Action buttons are optional but improve engagement</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showSendModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="showSendModal = false"></div>
        <div class="relative bg-white rounded-lg max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Send Bulk Emails</h3>
          <p class="text-sm text-gray-600 mb-4">
            Are you sure you want to send this email to <strong>{{ currentRecipientCount }}</strong> recipient(s)?
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="showSendModal = false"
              class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="confirmSendEmails"
              :disabled="form.processing"
              class="px-4 py-2 bg-[#42b6c5] text-white font-medium rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="form.processing">Sending...</span>
              <span v-else>Confirm Send</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>