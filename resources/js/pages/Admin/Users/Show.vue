<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

interface Application {
  id: number
  status: 'pending' | 'accepted' | 'rejected'
  created_at: string
  program: {
    id: number
    title: string
    category: string
  }
}

interface User {
  id: number
  name: string
  email: string
  phone: string | null
  role: 'user' | 'admin'
  email_verified_at: string | null
  created_at: string
  applications: Application[]
}

interface Props {
  user: User
  stats: {
    total_applications: number
    accepted_applications: number
    pending_applications: number
  }
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'accepted': return 'bg-green-100 text-green-800'
    case 'rejected': return 'bg-red-100 text-red-800'
    default: return 'bg-yellow-100 text-yellow-800'
  }
}

const formatCategory = (cat: string) => cat.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
</script>

<template>
  <div>
    <Head :title="`User - ${user.name}`" />

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <Link href="/admin/users" class="text-[#42b6c5] hover:text-[#35919e] text-sm mb-2 inline-block">
          ← Back to Users
        </Link>
        <h2 class="text-3xl font-bold text-gray-900">{{ user.name }}</h2>
        <p class="text-gray-600 mt-1">Member since {{ formatDate(user.created_at) }}</p>
      </div>
      <div class="flex items-center gap-3">
        <span :class="[
          'px-3 py-1 text-sm font-medium rounded-full',
          user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'
        ]">
          {{ user.role.charAt(0).toUpperCase() + user.role.slice(1) }}
        </span>
        <Link
          :href="`/admin/users/${user.id}/edit`"
          class="px-4 py-2 bg-[#42b6c5] text-white font-medium rounded-lg hover:bg-[#35919e] transition-colors"
        >
          Edit User
        </Link>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- User Information -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">User Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-500">Full Name</label>
              <p class="mt-1 text-gray-900">{{ user.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500">Email Address</label>
              <p class="mt-1">
                <a :href="`mailto:${user.email}`" class="text-[#42b6c5] hover:underline">{{ user.email }}</a>
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500">Phone Number</label>
              <p class="mt-1 text-gray-900">{{ user.phone || 'Not provided' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500">Email Verified</label>
              <p class="mt-1">
                <span v-if="user.email_verified_at" class="text-green-600 font-medium">
                  ✓ Verified on {{ formatDate(user.email_verified_at) }}
                </span>
                <span v-else class="text-yellow-600 font-medium">
                  ⚠ Not verified
                </span>
              </p>
            </div>
          </div>
        </div>

        <!-- Recent Applications -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Recent Applications</h3>
          <div v-if="user.applications.length > 0" class="space-y-4">
            <div
              v-for="application in user.applications"
              :key="application.id"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
            >
              <div>
                <Link
                  :href="`/admin/applications/${application.id}`"
                  class="font-medium text-[#42b6c5] hover:underline"
                >
                  {{ application.program?.title || 'Unknown Program' }}
                </Link>
                <p class="text-sm text-gray-600">
                  {{ formatCategory(application.program?.category || '') }} • Applied {{ formatDate(application.created_at) }}
                </p>
              </div>
              <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(application.status)]">
                {{ application.status.charAt(0).toUpperCase() + application.status.slice(1) }}
              </span>
            </div>
          </div>
          <p v-else class="text-gray-500 text-center py-8">No applications yet</p>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Stats -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Application Stats</h3>
          <div class="space-y-4">
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Total Applications</span>
              <span class="font-bold text-gray-900">{{ stats.total_applications }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Accepted</span>
              <span class="font-bold text-green-600">{{ stats.accepted_applications }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Pending</span>
              <span class="font-bold text-yellow-600">{{ stats.pending_applications }}</span>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Quick Actions</h3>
          <div class="space-y-3">
            <Link
              :href="`/admin/users/${user.id}/edit`"
              class="block w-full text-center px-4 py-2 bg-[#42b6c5] text-white font-medium rounded-lg hover:bg-[#35919e] transition-colors"
            >
              Edit User
            </Link>
            <a
              :href="`mailto:${user.email}`"
              class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
            >
              Send Email
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
