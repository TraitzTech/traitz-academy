<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Welcome back, {{ userName }}! 👋</h1>
      <p class="text-gray-600 dark:text-gray-400 mt-1">Track your applications and event registrations</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 sm:p-6 border-l-4 border-[#42b6c5] hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Applications</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">{{ totalCount }}</p>
          </div>
          <div class="p-3 bg-[#42b6c5]/10 dark:bg-[#42b6c5]/20 rounded-lg">
            <svg class="w-6 h-6 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 sm:p-6 border-l-4 border-green-500 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Accepted</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ acceptedCount }}</p>
          </div>
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 sm:p-6 border-l-4 border-yellow-500 hover:shadow-md transition-shadow sm:col-span-2 lg:col-span-1">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Pending Review</p>
            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">{{ pendingCount }}</p>
          </div>
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Applications Section -->
    <div id="applications" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden mb-8">
      <div class="px-4 sm:px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">My Applications</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Track your program applications</p>
      </div>

      <!-- Applications List -->
      <div v-if="hasApplications" class="divide-y divide-gray-100 dark:divide-gray-700">
        <div v-for="application in applications" :key="application.id" class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
          <!-- Mobile View -->
          <div class="sm:hidden">
            <div class="flex items-start justify-between mb-3">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex-1 pr-2">{{ application.program?.title || 'Program' }}</h3>
              <span :class="[
                'px-2.5 py-1 rounded-full text-xs font-semibold uppercase whitespace-nowrap',
                application.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                application.status === 'accepted' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                application.status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
              ]">
                {{ application.status }}
              </span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{{ application.program?.description || 'No description' }}</p>
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-3">
              <span>Applied: {{ formatDate(application.created_at) }}</span>
              <span>#{{ application.id }}</span>
            </div>
            <Link
              :href="`/programs/${application.program?.slug}`"
              class="w-full inline-flex items-center justify-center px-4 py-2 bg-[#42b6c5] text-white rounded-lg text-sm font-medium hover:bg-[#35919e] transition-colors"
            >
              View Program
            </Link>
          </div>
          
          <!-- Desktop View -->
          <div class="hidden sm:block">
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ application.program?.title || 'Program' }}</h3>
                  <span :class="[
                    'px-3 py-1 rounded-full text-xs font-semibold uppercase',
                    application.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                    application.status === 'accepted' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                    application.status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                  ]">
                    {{ application.status }}
                  </span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ application.program?.description || 'No description' }}</p>
              </div>
              <Link
                :href="`/programs/${application.program?.slug}`"
                class="ml-4 px-4 py-2 bg-[#42b6c5] text-white rounded-lg text-sm font-medium hover:bg-[#35919e] transition-colors flex-shrink-0"
              >
                View Program
              </Link>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-sm">
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Applied</p>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(application.created_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Type</p>
                <p class="font-medium text-gray-900 dark:text-gray-100 capitalize">{{ application.application_type || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Updated</p>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(application.updated_at) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">ID</p>
                <p class="font-medium text-gray-900 dark:text-gray-100">#{{ application.id }}</p>
              </div>
            </div>
            
            <!-- Status Message -->
            <div v-if="application.status === 'accepted'" class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-lg">
              <p class="text-sm text-green-800 dark:text-green-300"><span class="font-semibold">🎉 Congratulations!</span> Check your email for next steps.</p>
            </div>
            <div v-else-if="application.status === 'rejected'" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-r-lg">
              <p class="text-sm text-red-800 dark:text-red-300"><span class="font-semibold">Not Selected.</span> We encourage you to apply again.</p>
            </div>
            <div v-else class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-[#42b6c5] rounded-r-lg">
              <p class="text-sm text-blue-800 dark:text-blue-300"><span class="font-semibold">Under Review.</span> You'll receive a decision within 2-4 weeks.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="px-6 py-12 text-center">
        <div class="inline-block p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
          <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <p class="text-gray-600 dark:text-gray-300 font-medium mb-2">No Applications Yet</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Start your journey by exploring our programs!</p>
        <Link href="/programs" class="inline-flex items-center px-4 py-2 bg-[#42b6c5] text-white rounded-lg text-sm font-medium hover:bg-[#35919e] transition-colors">
          Browse Programs
        </Link>
      </div>
    </div>

    <!-- Event Registrations Section -->
    <div id="registrations" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
      <div class="px-4 sm:px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">My Event Registrations</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Your upcoming and past events</p>
      </div>

      <div v-if="hasRegistrations" class="divide-y divide-gray-100 dark:divide-gray-700">
        <div v-for="reg in registrations" :key="reg.id" class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
          <!-- Mobile View -->
          <div class="sm:hidden">
            <div class="flex items-start justify-between mb-3">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex-1 pr-2">{{ reg.event?.title || 'Event' }}</h3>
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 whitespace-nowrap capitalize">
                {{ reg.status }}
              </span>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-3 space-y-1">
              <p><span class="text-gray-500 dark:text-gray-400">Date:</span> {{ formatDate(reg.event?.event_date) }}</p>
              <p><span class="text-gray-500 dark:text-gray-400">Registered:</span> {{ formatDate(reg.created_at) }}</p>
            </div>
            <Link
              :href="`/events/${reg.event?.slug}`"
              class="w-full inline-flex items-center justify-center px-4 py-2 bg-[#42b6c5] text-white rounded-lg text-sm font-medium hover:bg-[#35919e] transition-colors"
            >
              View Event
            </Link>
          </div>
          
          <!-- Desktop View -->
          <div class="hidden sm:block">
            <div class="flex items-start justify-between mb-3">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ reg.event?.title || 'Event' }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Registered: {{ formatDate(reg.created_at) }}</p>
              </div>
              <Link :href="`/events/${reg.event?.slug}`" class="ml-4 px-4 py-2 bg-[#42b6c5] text-white rounded-lg text-sm font-medium hover:bg-[#35919e] transition-colors flex-shrink-0">
                View Event
              </Link>
            </div>
            <div class="grid grid-cols-3 gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-sm">
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Event Date</p>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(reg.event?.event_date) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</p>
                <p class="font-medium text-gray-900 dark:text-gray-100 capitalize">{{ reg.status }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</p>
                <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ reg.email }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="px-6 py-12 text-center">
        <div class="inline-block p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
          <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <p class="text-gray-600 dark:text-gray-300 font-medium mb-2">No Event Registrations</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Explore our upcoming events!</p>
        <Link href="/events" class="inline-flex items-center px-4 py-2 bg-gray-900 dark:bg-gray-700 text-white rounded-lg text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-600 transition-colors">
          Browse Events
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const page = usePage()

// Get auth user and applications from props
const authUser = page.props.auth?.user
const applications = page.props.applications || []
const registrations = page.props.registrations || []

// Derived values
const userName = authUser?.name ? authUser.name.split(' ')[0] : 'Student'
const totalCount = applications.length
const acceptedCount = applications.filter(a => a.status === 'accepted').length
const pendingCount = applications.filter(a => a.status === 'pending').length
const hasApplications = applications.length > 0
const hasRegistrations = registrations.length > 0

// Format date utility
const formatDate = (date) => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch {
    return 'N/A'
  }
}
</script>