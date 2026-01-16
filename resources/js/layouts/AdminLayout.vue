<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Mobile sidebar backdrop -->
    <div 
      v-if="sidebarOpen" 
      class="fixed inset-0 bg-black/50 z-40 lg:hidden"
      @click="sidebarOpen = false"
    ></div>

    <!-- Sidebar -->
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-[#000928] text-white shadow-lg transform transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <div class="flex items-center justify-between p-4 lg:p-6">
        <Link href="/" class="inline-block">
          <img 
            :src="$page.props.siteSettings.logo_url || '/images/Tratz Academy-Horizontal Profile.svg'" 
            :alt="$page.props.siteSettings.logo_text" 
            class="h-8 lg:h-10 w-auto filter brightness-0 invert" 
          />
        </Link>
        <!-- Close button for mobile -->
        <button 
          @click="sidebarOpen = false"
          class="lg:hidden p-2 rounded-lg text-gray-300 hover:bg-gray-800 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Navigation Menu -->
      <nav class="mt-4 lg:mt-6 space-y-1 px-3 pb-6 overflow-y-auto max-h-[calc(100vh-80px)]">
        <Link
          href="/admin/dashboard"
          @click="closeSidebarOnMobile"
          :class="[
            'flex items-center px-4 py-3 rounded-lg font-semibold transition-colors duration-200',
            page.url === '/admin/dashboard'
              ? 'bg-[#42b6c5] text-white'
              : 'text-gray-300 hover:bg-gray-800'
          ]"
        >
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 11l4-4m0 0l4 4m-4-4v4" />
          </svg>
          Dashboard
        </Link>

        <Link
          href="/admin/programs"
          @click="closeSidebarOnMobile"
          :class="[
            'flex items-center px-4 py-3 rounded-lg font-semibold transition-colors duration-200',
            page.url.includes('/admin/programs')
              ? 'bg-[#42b6c5] text-white'
              : 'text-gray-300 hover:bg-gray-800'
          ]"
        >
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747c5.5 0 10-4.992 10-11.747S17.5 6.253 12 6.253z" />
          </svg>
          Programs
        </Link>

        <Link
          href="/admin/events"
          @click="closeSidebarOnMobile"
          :class="[
            'flex items-center px-4 py-3 rounded-lg font-semibold transition-colors duration-200',
            page.url.includes('/admin/events')
              ? 'bg-[#42b6c5] text-white'
              : 'text-gray-300 hover:bg-gray-800'
          ]"
        >
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Events
        </Link>

        <Link
          href="/admin/applications"
          @click="closeSidebarOnMobile"
          :class="[
            'flex items-center px-4 py-3 rounded-lg font-semibold transition-colors duration-200',
            page.url.includes('/admin/applications')
              ? 'bg-[#42b6c5] text-white'
              : 'text-gray-300 hover:bg-gray-800'
          ]"
        >
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Applications
        </Link>

        <Link
          href="/admin/users"
          @click="closeSidebarOnMobile"
          :class="[
            'flex items-center px-4 py-3 rounded-lg font-semibold transition-colors duration-200',
            page.url.includes('/admin/users')
              ? 'bg-[#42b6c5] text-white'
              : 'text-gray-300 hover:bg-gray-800'
          ]"
        >
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.646 4 4 0 010-8.646M9 9H3v10a6 6 0 006 6h6a6 6 0 006-6V9h-6a4 4 0 00-4 4v2" />
          </svg>
          Users
        </Link>

        <Link
          href="/admin/emails"
          @click="closeSidebarOnMobile"
          :class="[
            'flex items-center px-4 py-3 rounded-lg font-semibold transition-colors duration-200',
            page.url.includes('/admin/emails')
              ? 'bg-[#42b6c5] text-white'
              : 'text-gray-300 hover:bg-gray-800'
          ]"
        >
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          Emails
        </Link>

        <Link
          href="/admin/settings"
          @click="closeSidebarOnMobile"
          :class="[
            'flex items-center px-4 py-3 rounded-lg font-semibold transition-colors duration-200',
            page.url.includes('/admin/settings')
              ? 'bg-[#42b6c5] text-white'
              : 'text-gray-300 hover:bg-gray-800'
          ]"
        >
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Settings
        </Link>

        <div class="my-4 border-t border-gray-700"></div>

        <Link
          href="/admin/account"
          @click="closeSidebarOnMobile"
          :class="[
            'flex items-center px-4 py-3 rounded-lg font-semibold transition-colors duration-200',
            page.url.includes('/admin/account')
              ? 'bg-[#42b6c5] text-white'
              : 'text-gray-300 hover:bg-gray-800'
          ]"
        >
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12a4 4 0 100-8 4 4 0 000 8zm0 2c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4z" />
          </svg>
          Account
        </Link>

        <!-- Mobile logout button -->
        <Link
          href="/logout"
          method="post"
          as="button"
          class="lg:hidden w-full flex items-center px-4 py-3 rounded-lg font-semibold transition-colors duration-200 text-red-400 hover:bg-red-900/20 mt-4"
        >
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Logout
        </Link>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden w-full lg:w-auto">
      <!-- Top Bar -->
      <header class="bg-white shadow-sm border-b border-gray-200 px-4 lg:px-6 py-3 lg:py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Hamburger menu button -->
          <button 
            @click="sidebarOpen = true"
            class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <h1 class="text-lg lg:text-2xl font-bold text-[#000928]">Admin Panel</h1>
        </div>
        <div class="flex items-center gap-2 lg:gap-6">
          <div class="text-right hidden sm:block">
            <p class="text-sm font-semibold text-gray-900 truncate max-w-[120px] lg:max-w-none">{{ $page.props.auth.user.name }}</p>
            <p class="text-xs text-gray-500">Administrator</p>
          </div>
          <!-- Mobile: Avatar only -->
          <div class="sm:hidden flex-shrink-0 w-8 h-8 bg-[#42b6c5] rounded-full flex items-center justify-center">
            <span class="text-white text-sm font-semibold">{{ $page.props.auth.user.name.charAt(0).toUpperCase() }}</span>
          </div>
          <Link
            href="/logout"
            method="post"
            as="button"
            class="hidden lg:block px-4 py-2 bg-red-50 text-red-600 rounded-lg font-semibold hover:bg-red-100 transition-colors text-sm"
          >
            Logout
          </Link>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-auto">
        <div class="p-4 lg:p-6">
          <slot />
        </div>
      </main>
    </div>

    <!-- Toast Notifications -->
    <Toaster />
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Toaster, useToast } from '@/components/ui/toast'

const page = usePage()
const toast = useToast()
const sidebarOpen = ref(false)

const closeSidebarOnMobile = () => {
  if (window.innerWidth < 1024) {
    sidebarOpen.value = false
  }
}

// Watch for flash messages from the server
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      toast.success(flash.success)
    }
    if (flash?.error) {
      toast.error(flash.error)
    }
    if (flash?.warning) {
      toast.warning(flash.warning)
    }
    if (flash?.info) {
      toast.info(flash.info)
    }
  },
  { immediate: true }
)
</script>
