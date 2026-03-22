<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface ProgramOption {
  id: number
  title: string
}

interface ApplicationData {
  id: number
  program_id: number
  first_name: string
  last_name: string
  email: string
  phone: string | null
  country: string | null
  status: 'pending' | 'accepted' | 'rejected'
  notes: string | null
  interview_id: number | null
}

const props = defineProps<{
  application: ApplicationData
  programs: ProgramOption[]
}>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const form = useForm({
  program_id: String(props.application.program_id),
  first_name: props.application.first_name,
  last_name: props.application.last_name,
  email: props.application.email,
  phone: props.application.phone || '',
  country: props.application.country || '',
  status: props.application.status,
  notes: props.application.notes || '',
})

const submit = () => {
  form.transform((data) => ({
    ...data,
    program_id: Number(data.program_id),
  })).put(`/admin/applications/${props.application.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Application updated successfully.')
    },
    onError: () => {
      toast.error('Failed to update application.')
    },
  })
}
</script>

<template>
  <div>
    <Head :title="`Edit Application - ${application.first_name} ${application.last_name}`" />

    <div class="mb-8">
      <Link :href="`/admin/applications/${application.id}`" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-2">
        ← Back to Application
      </Link>
      <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Edit Application</h2>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Update applicant details, status, and selected program.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Application Details</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program *</label>
            <select
              v-model="form.program_id"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            >
              <option value="" disabled>Select a program</option>
              <option v-for="program in programs" :key="program.id" :value="String(program.id)">
                {{ program.title }}
              </option>
            </select>
            <p v-if="application.interview_id" class="mt-1 text-xs text-amber-600 dark:text-amber-400">Changing program clears scheduled interview information.</p>
            <p v-if="form.errors.program_id" class="mt-1 text-sm text-red-600">{{ form.errors.program_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name *</label>
            <input v-model="form.first_name" type="text" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
            <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name *</label>
            <input v-model="form.last_name" type="text" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
            <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">{{ form.errors.last_name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
            <input v-model="form.email" type="email" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
            <input v-model="form.phone" type="text" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
            <input v-model="form.country" type="text" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" />
            <p v-if="form.errors.country" class="mt-1 text-sm text-red-600">{{ form.errors.country }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
            <select
              v-model="form.status"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
            >
              <option value="pending">Pending</option>
              <option value="accepted">Accepted</option>
              <option value="rejected">Rejected</option>
            </select>
            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Admin Notes</label>
            <textarea
              v-model="form.notes"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent"
              placeholder="Optional notes about this application"
            />
            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3">
        <Link
          :href="`/admin/applications/${application.id}`"
          class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          Cancel
        </Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="px-5 py-2 bg-[#42b6c5] text-white rounded-lg font-medium hover:bg-[#35919e] disabled:opacity-60"
        >
          {{ form.processing ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </form>
  </div>
</template>
