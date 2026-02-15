<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import RichTextEditor from '@/components/RichTextEditor.vue'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface RecipientCounts {
  all: number
  with_applications: number
  without_applications: number
  accepted_applicants: number
  pending_applicants: number
  rejected_applicants: number
  interview_scheduled: number
  interview_completed: number
  interview_passed: number
  interview_not_passed: number
}

interface EmailHistoryItem {
  id: number
  subject: string
  audience: string
  audience_label: string
  message_html: string
  action_text: string | null
  action_url: string | null
  recipient_count: number
  sent_by: string
  sent_at: string | null
  preview_recipients: string[]
}

interface Props {
  recipientCounts: RecipientCounts
  emailHistory: EmailHistoryItem[]
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
const showSendModal = ref(false)
const selectedHistory = ref<EmailHistoryItem | null>(null)

const recipientOptions = [
  { value: 'all', label: 'All Users', description: 'Send to all registered users', group: 'general' },
  { value: 'with_applications', label: 'Users with Applications', description: 'Users who have submitted at least one application', group: 'general' },
  { value: 'without_applications', label: 'Users without Applications', description: 'Users who have not submitted any applications', group: 'general' },
  { value: 'accepted_applicants', label: 'Accepted Applicants', description: 'Users with accepted applications', group: 'applications' },
  { value: 'pending_applicants', label: 'Pending Applicants', description: 'Users with pending applications', group: 'applications' },
  { value: 'rejected_applicants', label: 'Rejected Applicants', description: 'Users with rejected applications', group: 'applications' },
  { value: 'interview_scheduled', label: 'Interview Scheduled', description: 'Users with a scheduled interview awaiting completion', group: 'interviews' },
  { value: 'interview_completed', label: 'Interview Completed', description: 'Users who have completed their interview', group: 'interviews' },
  { value: 'interview_passed', label: 'Interview Passed', description: 'Users who passed their interview', group: 'interviews' },
  { value: 'interview_not_passed', label: 'Interview Not Passed', description: 'Users who did not pass their interview', group: 'interviews' },
  { value: 'custom', label: 'Custom List', description: 'Enter email addresses manually', group: 'other' },
]

const stripHtml = (html: string) => {
  const doc = new DOMParser().parseFromString(html || '', 'text/html')
  return (doc.body.textContent || '').trim()
}

const plainMessage = computed(() => stripHtml(form.message).toLowerCase())

const startsWithGreeting = computed(() => {
  return /^(hi|hello|dear)\b/.test(plainMessage.value)
})

const currentRecipientCount = computed(() => {
  if (form.recipients === 'custom') {
    const emails = form.custom_emails.split(/[\n,]/).filter(e => e.trim())
    return emails.length
  }

  return props.recipientCounts?.[form.recipients as keyof RecipientCounts] || 0
})

const sendEmails = () => {
  if (!stripHtml(form.message)) {
    toast.error('Please enter your email content before sending.')
    return
  }

  if (currentRecipientCount.value === 0) {
    toast.error('No recipients found for the selected audience.')
    return
  }

  showSendModal.value = true
}

const confirmSendEmails = () => {
  form
    .transform((data) => ({
      ...data,
      custom_emails: data.custom_emails
        ? data.custom_emails
            .split(/[\n,]/)
            .map((e: string) => e.trim())
            .filter((e: string) => e)
        : [],
    }))
    .post('/admin/emails', {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
        showSendModal.value = false
        previewMode.value = false
      },
      onError: (errors) => {
        const errorMessage = Object.values(errors)[0] || 'Failed to send emails. Please try again.'
        toast.error(errorMessage as string)
      },
    })
}

const togglePreview = () => {
  previewMode.value = !previewMode.value
}

const handleEditorUploadError = (message: string) => {
  toast.error(message)
}

const formatDateTime = (value: string | null) => {
  if (!value) return 'Unknown time'

  return new Date(value).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const openHistoryDetails = (entry: EmailHistoryItem) => {
  selectedHistory.value = entry
}

const closeHistoryDetails = () => {
  selectedHistory.value = null
}
</script>

<template>
  <div>
    <Head title="Email Notifications" />

    <div class="mb-8">
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Email Notifications</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-2">Send rich, personalized email notifications to users</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2">
        <form @submit.prevent="sendEmails" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recipients</label>

            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">General</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
              <div
                v-for="option in recipientOptions.filter(o => o.group === 'general')"
                :key="option.value"
                @click="form.recipients = option.value"
                :class="[
                  'p-4 border-2 rounded-lg cursor-pointer transition-all',
                  form.recipients === option.value ? 'border-[#42b6c5] bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                ]"
              >
                <div class="flex items-center gap-2">
                  <input type="radio" :value="option.value" v-model="form.recipients" class="text-[#42b6c5] focus:ring-[#42b6c5]" />
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ option.label }}</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-6">{{ option.description }}</p>
              </div>
            </div>

            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Applications</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
              <div
                v-for="option in recipientOptions.filter(o => o.group === 'applications')"
                :key="option.value"
                @click="form.recipients = option.value"
                :class="[
                  'p-4 border-2 rounded-lg cursor-pointer transition-all',
                  form.recipients === option.value ? 'border-[#42b6c5] bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                ]"
              >
                <div class="flex items-center gap-2">
                  <input type="radio" :value="option.value" v-model="form.recipients" class="text-[#42b6c5] focus:ring-[#42b6c5]" />
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ option.label }}</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-6">{{ option.description }}</p>
              </div>
            </div>

            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Interviews</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
              <div
                v-for="option in recipientOptions.filter(o => o.group === 'interviews')"
                :key="option.value"
                @click="form.recipients = option.value"
                :class="[
                  'p-4 border-2 rounded-lg cursor-pointer transition-all',
                  form.recipients === option.value ? 'border-[#42b6c5] bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                ]"
              >
                <div class="flex items-center gap-2">
                  <input type="radio" :value="option.value" v-model="form.recipients" class="text-[#42b6c5] focus:ring-[#42b6c5]" />
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ option.label }}</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-6">{{ option.description }}</p>
              </div>
            </div>

            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Other</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                v-for="option in recipientOptions.filter(o => o.group === 'other')"
                :key="option.value"
                @click="form.recipients = option.value"
                :class="[
                  'p-4 border-2 rounded-lg cursor-pointer transition-all',
                  form.recipients === option.value ? 'border-[#42b6c5] bg-cyan-50 dark:bg-cyan-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                ]"
              >
                <div class="flex items-center gap-2">
                  <input type="radio" :value="option.value" v-model="form.recipients" class="text-[#42b6c5] focus:ring-[#42b6c5]" />
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ option.label }}</span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-6">{{ option.description }}</p>
              </div>
            </div>

            <p v-if="form.errors.recipients" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.recipients }}</p>
          </div>

          <div v-if="form.recipients === 'custom'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Addresses</label>
            <textarea
              v-model="form.custom_emails"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100"
              placeholder="Enter email addresses separated by commas or new lines..."
            ></textarea>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ currentRecipientCount }} email(s) detected</p>
            <p v-if="form.errors.custom_emails" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.custom_emails }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject Line</label>
            <input
              v-model="form.subject"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100"
              placeholder="Enter email subject..."
            />
            <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.subject }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
            <RichTextEditor
              v-model="form.message"
              placeholder="Write your email body with formatting, links, lists, and images..."
              @upload-error="handleEditorUploadError"
            />
            <p class="mt-2 text-sm text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2">
              Do not include "Hi" or "Hello" at the beginning. The system already sends a personalized greeting with each recipient's name.
            </p>
            <p v-if="startsWithGreeting" class="mt-1 text-sm text-amber-700 dark:text-amber-300">
              Your message currently starts with a greeting. Consider removing it to avoid duplicate greetings.
            </p>
            <p v-if="form.errors.message" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.message }}</p>
          </div>

          <div class="border-t dark:border-gray-700 pt-6">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Action Button (optional)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Button Text</label>
                <input
                  v-model="form.action_text"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100"
                  placeholder="e.g., View Programs"
                />
                <p v-if="form.errors.action_text" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.action_text }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Button URL</label>
                <input
                  v-model="form.action_url"
                  type="url"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100"
                  placeholder="https://..."
                />
                <p v-if="form.errors.action_url" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.action_url }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t dark:border-gray-700">
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

      <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recipient Statistics</h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">All Users</span>
              <span class="font-medium dark:text-gray-100">{{ recipientCounts.all }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">With Applications</span>
              <span class="font-medium dark:text-gray-100">{{ recipientCounts.with_applications }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">Accepted</span>
              <span class="font-medium text-green-600 dark:text-green-400">{{ recipientCounts.accepted_applicants }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">Pending</span>
              <span class="font-medium text-yellow-600 dark:text-yellow-400">{{ recipientCounts.pending_applicants }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600 dark:text-gray-400">Rejected</span>
              <span class="font-medium text-red-600 dark:text-red-400">{{ recipientCounts.rejected_applicants }}</span>
            </div>
            <div class="border-t dark:border-gray-700 pt-3 mt-3">
              <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Interviews</p>
              <div class="space-y-2">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Scheduled</span>
                  <span class="font-medium text-blue-600 dark:text-blue-400">{{ recipientCounts.interview_scheduled }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Completed</span>
                  <span class="font-medium dark:text-gray-100">{{ recipientCounts.interview_completed }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Passed</span>
                  <span class="font-medium text-green-600 dark:text-green-400">{{ recipientCounts.interview_passed }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Not Passed</span>
                  <span class="font-medium text-red-600 dark:text-red-400">{{ recipientCounts.interview_not_passed }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="previewMode" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
          <div class="bg-gray-100 dark:bg-gray-700 px-4 py-2 border-b dark:border-gray-600">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Email Preview</span>
          </div>
          <div class="p-4">
            <div class="border dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
              <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Subject:</div>
              <div class="font-medium text-gray-900 dark:text-gray-100 mb-4">{{ form.subject || '(No subject)' }}</div>
              <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Message:</div>
              <div class="email-content" v-html="form.message || '<p>(No message)</p>'"></div>
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

        <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
          <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Tips</h4>
          <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
            <li>• Rich text and images are supported</li>
            <li>• The greeting line is automatically personalized</li>
            <li>• Emails are sent in the background using a queue</li>
            <li>• Test with a small group first before mass sending</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sent Email History</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Recent emails you sent. Click details to view full content.</p>
      </div>

      <div v-if="emailHistory.length === 0" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
        No email history yet.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700/40">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Subject</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Audience</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Recipients</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Sent By</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Sent At</th>
              <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="entry in emailHistory" :key="entry.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ entry.subject }}</td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300">
                  {{ entry.audience_label }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ entry.recipient_count }}</td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ entry.sent_by }}</td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ formatDateTime(entry.sent_at) }}</td>
              <td class="px-6 py-4 text-right">
                <button
                  type="button"
                  @click="openHistoryDetails(entry)"
                  class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#42b6c5] text-white hover:bg-[#35919e] transition-colors"
                >
                  View Details
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="selectedHistory" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" @click="closeHistoryDetails"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-3xl w-full p-6 shadow-xl">
          <div class="flex items-start justify-between gap-3 mb-4">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ selectedHistory.subject }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ selectedHistory.audience_label }} • {{ selectedHistory.recipient_count }} recipient(s) • {{ formatDateTime(selectedHistory.sent_at) }}
              </p>
            </div>
            <button
              type="button"
              @click="closeHistoryDetails"
              class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recipient Preview</p>
            <p class="text-sm text-gray-600 dark:text-gray-300">
              <span v-if="selectedHistory.preview_recipients.length > 0">
                {{ selectedHistory.preview_recipients.join(', ') }}
                <span v-if="selectedHistory.recipient_count > selectedHistory.preview_recipients.length">
                  and {{ selectedHistory.recipient_count - selectedHistory.preview_recipients.length }} more
                </span>
              </span>
              <span v-else>None</span>
            </p>
          </div>

          <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40 max-h-[55vh] overflow-y-auto">
            <div class="email-content" v-html="selectedHistory.message_html"></div>
            <div v-if="selectedHistory.action_text && selectedHistory.action_url" class="mt-4">
              <a
                :href="selectedHistory.action_url"
                target="_blank"
                class="inline-block px-4 py-2 rounded-lg bg-[#42b6c5] text-white text-sm font-semibold hover:bg-[#35919e] transition-colors"
              >
                {{ selectedHistory.action_text }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showSendModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="showSendModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Send Bulk Emails</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Are you sure you want to send this email to <strong>{{ currentRecipientCount }}</strong> recipient(s)?
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="showSendModal = false"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
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

<style scoped>
:deep(.email-content) {
  color: rgb(17 24 39);
  font-size: 0.95rem;
  line-height: 1.7;
}

:deep(.email-content h1),
:deep(.email-content h2),
:deep(.email-content h3) {
  color: inherit;
  font-weight: 700;
  line-height: 1.3;
  margin: 0.65rem 0;
}

:deep(.email-content h1) {
  font-size: 1.6rem;
}

:deep(.email-content h2) {
  font-size: 1.3rem;
}

:deep(.email-content h3) {
  font-size: 1.1rem;
}

:deep(.email-content p),
:deep(.email-content div),
:deep(.email-content span),
:deep(.email-content li) {
  color: inherit;
}

:deep(.email-content ul),
:deep(.email-content ol) {
  padding-left: 1.45rem;
  margin: 0.45rem 0 0.8rem;
}

:deep(.email-content ul) {
  list-style: disc;
}

:deep(.email-content ol) {
  list-style: decimal;
}

:deep(.email-content li) {
  margin-bottom: 0.25rem;
}

:deep(.email-content li::marker) {
  color: #42b6c5;
}

:deep(.email-content blockquote) {
  border-left: 3px solid #42b6c5;
  padding-left: 0.8rem;
  margin: 0.6rem 0 0.8rem;
  color: rgb(75 85 99);
}

:deep(.email-content pre) {
  margin: 0.6rem 0 0.9rem;
  padding: 0.7rem 0.8rem;
  border-radius: 0.5rem;
  background: rgba(17, 24, 39, 0.07);
  overflow-x: auto;
}

:deep(.email-content code) {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
}

:deep(.email-content hr) {
  border: 0;
  border-top: 1px solid rgba(107, 114, 128, 0.35);
  margin: 0.9rem 0;
}

:deep(.email-content a) {
  color: #0f94a2;
  text-decoration: underline;
}

:global(.dark) :deep(.email-content) {
  color: rgb(243 244 246);
}

:global(.dark) :deep(.email-content li::marker) {
  color: #6ad7e5;
}

:global(.dark) :deep(.email-content blockquote) {
  color: rgb(209 213 219);
}

:global(.dark) :deep(.email-content pre) {
  background: rgba(255, 255, 255, 0.09);
}

:global(.dark) :deep(.email-content hr) {
  border-top-color: rgba(209, 213, 219, 0.35);
}

:global(.dark) :deep(.email-content a) {
  color: #7fe0ec;
}
</style>
