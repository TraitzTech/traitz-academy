<template>
  <PublicLayout>
    <!-- Header Section -->
    <section class="bg-gradient-to-r from-[#000928] via-[#1a0a52] to-[#381998] text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-4xl md:text-5xl font-bold mb-2">Your Dashboard</h1>
            <p class="text-lg text-gray-300">Track your applications and event registrations</p>
          </div>
          <div class="hidden md:flex items-center gap-6 bg-white/10 px-8 py-4 rounded-lg backdrop-blur-sm">
            <div class="text-center border-r border-white/20 pr-6">
              <div class="text-3xl font-bold text-[#42b6c5]">{{ totalCount }}</div>
              <div class="text-sm text-white/80">Total Applications</div>
            </div>
            <div class="text-center border-r border-white/20 pr-6">
              <div class="text-3xl font-bold text-green-400">{{ acceptedCount }}</div>
              <div class="text-sm text-white/80">Accepted</div>
            </div>
            <div class="text-center">
              <div class="text-3xl font-bold text-yellow-400">{{ pendingCount }}</div>
              <div class="text-sm text-white/80">Pending</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Welcome Section -->
      <div class="mb-8 md:hidden">
        <h2 class="text-2xl font-bold text-[#000928] mb-4">Welcome, {{ userName }}! 👋</h2>
        <p class="text-gray-600">Track your applications and stay updated on your progress</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-[#42b6c5] hover:shadow-lg transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-semibold">Total Applications</p>
              <p class="text-4xl font-bold text-[#000928] mt-3">{{ totalCount }}</p>
            </div>
            <div class="p-4 bg-[#42b6c5]/10 rounded-lg">
              <svg class="w-8 h-8 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-semibold">Accepted</p>
              <p class="text-4xl font-bold text-green-600 mt-3">{{ acceptedCount }}</p>
            </div>
            <div class="p-4 bg-green-100 rounded-lg">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500 hover:shadow-lg transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 font-semibold">Pending Review</p>
              <p class="text-4xl font-bold text-yellow-600 mt-3">{{ pendingCount }}</p>
            </div>
            <div class="p-4 bg-yellow-100 rounded-lg">
              <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Applications List -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-8 bg-gradient-to-r from-[#000928] to-[#381998] text-white">
          <h2 class="text-3xl font-bold mb-2">My Applications</h2>
          <p class="text-gray-300 text-lg">Manage and track all your program applications</p>
        </div>

        <!-- Applications Grid -->
        <div v-if="hasApplications" class="divide-y divide-gray-200">
          <div v-for="application in applications" :key="application.id" class="p-8 hover:bg-gray-50 transition-colors duration-200 border-b border-gray-100 last:border-b-0">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-3">
                  <h3 class="text-2xl font-bold text-[#000928]">{{ application.program?.title || 'Program' }}</h3>
                  <span :class="[
                    'px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wider inline-flex items-center gap-1',
                    application.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    application.status === 'accepted' ? 'bg-green-100 text-green-800' :
                    application.status === 'rejected' ? 'bg-red-100 text-red-800' :
                    'bg-gray-100 text-gray-800'
                  ]">
                    <span v-if="application.status === 'accepted'">✓</span>
                    <span v-else-if="application.status === 'rejected'">✕</span>
                    <span v-else>⏳</span>
                    {{ application.status }}
                  </span>
                </div>
                <p class="text-gray-600 mb-4 text-base leading-relaxed">{{ application.program?.description || 'No description' }}</p>

                <!-- Application Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 py-4 bg-gray-50 rounded-lg px-4 mb-4">
                  <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Applied On</p>
                    <p class="text-lg font-semibold text-[#000928]">{{ formatDate(application.created_at) }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Application Type</p>
                    <p class="text-lg font-semibold text-[#000928] capitalize">{{ application.application_type || 'N/A' }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Last Updated</p>
                    <p class="text-lg font-semibold text-[#000928]">{{ formatDate(application.updated_at) }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Application ID</p>
                    <p class="text-lg font-semibold text-[#000928]">#{{ application.id }}</p>
                  </div>
                </div>

                <!-- Status Messages -->
                <div v-if="application.status === 'accepted'" class="mt-4 p-5 bg-green-50 border-l-4 border-green-500 rounded-r-lg">
                  <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div>
                      <p class="font-bold text-green-900 text-lg">🎉 Congratulations!</p>
                      <p class="text-green-800 text-sm mt-1">You've been accepted to this program. Check your email for next steps and onboarding information.</p>
                    </div>
                  </div>
                </div>
                <div v-else-if="application.status === 'rejected'" class="mt-4 p-5 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                  <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <div>
                      <p class="font-bold text-red-900 text-lg">Application Not Selected</p>
                      <p class="text-red-800 text-sm mt-1">Unfortunately, your application was not selected this time. We encourage you to apply again in future cycles.</p>
                    </div>
                  </div>
                </div>
                <div v-else class="mt-4 p-5 bg-blue-50 border-l-4 border-[#42b6c5] rounded-r-lg">
                  <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-[#42b6c5] flex-shrink-0 mt-0.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                      <p class="font-bold text-blue-900 text-lg">Under Review</p>
                      <p class="text-blue-800 text-sm mt-1">Your application is being reviewed by our admissions team. You'll receive a decision notification within 2-4 weeks.</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- View Program Button -->
              <Link
                :href="`/programs/${application.program?.slug}`"
                class="ml-4 px-6 py-3 bg-[#42b6c5] text-white rounded-lg font-bold hover:bg-[#35919e] transition-colors flex-shrink-0 h-fit"
              >
                View Program
              </Link>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="px-8 py-20 text-center">
          <div class="inline-block p-6 bg-gray-100 rounded-full mb-6">
            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
          </div>
          <p class="text-gray-600 text-xl font-semibold mb-2">No Applications Yet</p>
          <p class="text-gray-500 mb-8 text-lg">Start your journey by exploring our programs and submitting your first application!</p>
          <Link
            href="/programs"
            class="inline-flex items-center px-8 py-3 bg-[#42b6c5] text-white rounded-lg font-bold text-lg hover:bg-[#35919e] transition-colors"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            Browse Programs
          </Link>
        </div>
      </div>

      <!-- Event Registrations List -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden mt-12">
        <div class="px-6 py-8 bg-gradient-to-r from-[#000928] to-[#381998] text-white">
          <h2 class="text-3xl font-bold mb-2">My Event Registrations</h2>
          <p class="text-gray-300 text-lg">Your upcoming and past events</p>
        </div>

        <div v-if="hasRegistrations" class="divide-y divide-gray-200">
          <div v-for="reg in registrations" :key="reg.id" class="p-8 hover:bg-gray-50 transition-colors duration-200 border-b border-gray-100 last:border-b-0">
            <div class="flex items-start justify-between mb-2">
              <div>
                <h3 class="text-2xl font-bold text-[#000928]">{{ reg.event?.title || 'Event' }}</h3>
                <p class="text-gray-600 mt-1">Registered: {{ formatDate(reg.created_at) }}</p>
              </div>
              <Link :href="`/events/${reg.event?.slug}`" class="ml-4 px-6 py-3 bg-[#42b6c5] text-white rounded-lg font-bold hover:bg-[#35919e] transition-colors flex-shrink-0 h-fit">
                View Event
              </Link>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 py-4 bg-gray-50 rounded-lg px-4">
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Event Date</p>
                <p class="text-lg font-semibold text-[#000928]">{{ formatDate(reg.event?.event_date) }}</p>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Status</p>
                <p class="text-lg font-semibold text-[#000928] capitalize">{{ reg.status }}</p>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Email</p>
                <p class="text-lg font-semibold text-[#000928]">{{ reg.email }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="px-8 py-12 text-center">
          <p class="text-gray-600 text-lg">No event registrations yet.</p>
          <Link href="/events" class="inline-flex items-center mt-4 px-6 py-3 bg-[#000928] text-white rounded-lg font-bold hover:bg-[#381998] transition-colors">
            Browse Events
          </Link>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'

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