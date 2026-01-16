<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-gray-900">Dashboard</h2>
      <p class="text-gray-600 mt-2">Welcome back! Here's an overview of your system.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total Programs -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-[#42b6c5]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 font-semibold">Total Programs</p>
            <p class="text-3xl font-bold text-[#000928] mt-2">{{ stats.total_programs }}</p>
          </div>
          <div class="p-3 bg-blue-100 rounded-lg">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747c5.5 0 10-4.992 10-11.747S17.5 6.253 12 6.253z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Total Events -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-[#381998]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 font-semibold">Total Events</p>
            <p class="text-3xl font-bold text-[#000928] mt-2">{{ stats.total_events }}</p>
          </div>
          <div class="p-3 bg-purple-100 rounded-lg">
            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Pending Applications -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 font-semibold">Pending Applications</p>
            <p class="text-3xl font-bold text-[#000928] mt-2">{{ stats.pending_applications }}</p>
          </div>
          <div class="p-3 bg-yellow-100 rounded-lg">
            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Total Users -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 font-semibold">Total Users</p>
            <p class="text-3xl font-bold text-[#000928] mt-2">{{ stats.total_users }}</p>
          </div>
          <div class="p-3 bg-green-100 rounded-lg">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.646 4 4 0 010-8.646M9 9H3v10a6 6 0 006 6h6a6 6 0 006-6V9h-6a4 4 0 00-4 4v2" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Applications Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-bold text-gray-900">Recent Applications</h3>
      </div>
      
      <div v-if="recentApplications.length > 0" class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Program</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="app in recentApplications" :key="app.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ app.first_name }} {{ app.last_name }}</td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ app.program.title }}</td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ app.email }}</td>
              <td class="px-6 py-4 text-sm">
                <span :class="[
                  'px-3 py-1 rounded-full text-xs font-semibold',
                  app.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  app.status === 'accepted' ? 'bg-green-100 text-green-800' :
                  'bg-red-100 text-red-800'
                ]">
                  {{ app.status.charAt(0).toUpperCase() + app.status.slice(1) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(app.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="px-6 py-10 text-center">
        <p class="text-gray-500">No applications yet</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue'

defineProps({
  stats: {
    type: Object,
    required: true
  },
  recentApplications: {
    type: Array,
    required: true
  }
})

defineOptions({
  layout: AdminLayout
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>
