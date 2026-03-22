<template>
  <div class="flex flex-col min-h-screen">
    <!-- Navigation -->
    <nav class="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
          <!-- Logo -->
          <Link href="/" class="flex items-center flex-shrink-0">
            <img 
              :src="$page.props.siteSettings.logo_url || '/images/Tratz Academy-Horizontal Profile.svg'" 
              :alt="$page.props.siteSettings.logo_text" 
              class="h-11 w-auto hover:opacity-80 transition-opacity duration-200" 
            />
          </Link>

          <!-- Desktop Navigation Links -->
          <div class="hidden lg:flex items-center gap-1">
            <Link
              href="/"
              :class="[
                'relative px-3 py-2 text-sm font-semibold rounded-lg transition-colors duration-200',
                page.url === '/'
                  ? 'text-[#42b6c5] bg-[#42b6c5]/5'
                  : 'text-gray-700 hover:text-[#42b6c5] hover:bg-gray-50'
              ]"
            >
              Home
            </Link>

            <Link
              href="/programs"
              :class="[
                'relative px-3 py-2 text-sm font-semibold rounded-lg transition-colors duration-200',
                page.url.includes('/programs')
                  ? 'text-[#42b6c5] bg-[#42b6c5]/5'
                  : 'text-gray-700 hover:text-[#42b6c5] hover:bg-gray-50'
              ]"
            >
              Programs
            </Link>

            <Link
              href="/events"
              :class="[
                'relative px-3 py-2 text-sm font-semibold rounded-lg transition-colors duration-200',
                page.url.includes('/events')
                  ? 'text-[#42b6c5] bg-[#42b6c5]/5'
                  : 'text-gray-700 hover:text-[#42b6c5] hover:bg-gray-50'
              ]"
            >
              Events
            </Link>

            <!-- AI Forge -->
            <Link
              href="/ai-forge"
              :class="[
                'relative px-3 py-2 text-sm font-semibold rounded-lg transition-colors duration-200',
                page.url.includes('/ai-forge')
                  ? 'text-[#42b6c5] bg-[#42b6c5]/5'
                  : 'text-gray-700 hover:text-[#42b6c5] hover:bg-gray-50'
              ]"
            >
              <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                AI Forge
              </span>
            </Link>

            <!-- Discover Dropdown -->
            <div class="relative" @mouseenter="discoverOpen = true" @mouseleave="discoverOpen = false">
              <button
                :class="[
                  'flex items-center gap-1 px-3 py-2 text-sm font-semibold rounded-lg transition-colors duration-200',
                  ['/gallery', '/resources', '/about', '/success-stories'].some(p => page.url.includes(p))
                    ? 'text-[#42b6c5] bg-[#42b6c5]/5'
                    : 'text-gray-700 hover:text-[#42b6c5] hover:bg-gray-50'
                ]"
              >
                Discover
                <svg :class="['w-3.5 h-3.5 transition-transform duration-200', discoverOpen ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
              </button>

              <transition
                enter-active-class="transition ease-out duration-150"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
              >
                <div v-if="discoverOpen" class="absolute top-full left-1/2 -translate-x-1/2 mt-1 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50">
                  <Link href="/gallery" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5] transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    Gallery
                  </Link>
                  <Link href="/resources" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5] transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    Resources
                  </Link>
                  <Link href="/success-stories" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5] transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                    Success Stories
                  </Link>
                  <Link href="/about" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5] transition-colors">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    About Us
                  </Link>
                </div>
              </transition>
            </div>

            <Link
              href="/contact"
              :class="[
                'relative px-3 py-2 text-sm font-semibold rounded-lg transition-colors duration-200',
                page.url === '/contact'
                  ? 'text-[#42b6c5] bg-[#42b6c5]/5'
                  : 'text-gray-700 hover:text-[#42b6c5] hover:bg-gray-50'
              ]"
            >
              Contact
            </Link>
          </div>

          <!-- Desktop Right: Cart + Auth -->
          <div class="hidden lg:flex items-center gap-3">
            <!-- Cart Icon -->
            <Link
              href="/ai-forge/cart"
              class="relative p-2 rounded-lg text-gray-600 hover:text-[#42b6c5] hover:bg-gray-50 transition-colors"
              title="Shopping Cart"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
              </svg>
              <span
                v-if="cartCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center bg-[#42b6c5] text-white text-[10px] font-bold rounded-full px-1"
              >
                {{ cartCount > 99 ? '99+' : cartCount }}
              </span>
            </Link>

            <div class="w-px h-6 bg-gray-200"></div>

            <template v-if="$page.props.auth.user">
              <Link
                :href="['cto', 'ceo', 'program_coordinator', 'admin'].includes($page.props.auth.user.role) ? '/admin/dashboard' : '/dashboard'"
                class="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-[#42b6c5] transition-colors rounded-lg hover:bg-gray-50"
              >
                Dashboard
              </Link>
              <Link
                href="/logout"
                method="post"
                as="button"
                class="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-red-600 transition-colors rounded-lg hover:bg-gray-50"
              >
                Logout
              </Link>
            </template>
            <template v-else>
              <Link
                href="/login"
                class="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-[#42b6c5] transition-colors rounded-lg hover:bg-gray-50"
              >
                Login
              </Link>
              <Link
                href="/programs"
                class="inline-flex items-center px-6 py-2.5 bg-[#42b6c5] text-white rounded-lg font-semibold text-sm hover:bg-[#35919e] shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105"
              >
                Apply Now
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </template>
          </div>

          <!-- Mobile: Cart + menu button -->
          <div class="lg:hidden flex items-center gap-2">
            <!-- Mobile Cart Icon -->
            <Link
              href="/ai-forge/cart"
              class="relative p-2 rounded-lg text-gray-600 hover:text-[#42b6c5] transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
              </svg>
              <span
                v-if="cartCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center bg-[#42b6c5] text-white text-[10px] font-bold rounded-full px-1"
              >
                {{ cartCount > 99 ? '99+' : cartCount }}
              </span>
            </Link>

            <Link
              href="/programs"
              class="inline-flex items-center px-4 py-2 bg-[#42b6c5] text-white rounded-lg font-semibold text-xs hover:bg-[#35919e] transition-colors"
            >
              Apply
            </Link>
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="p-2.5 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
              :aria-expanded="mobileMenuOpen"
            >
              <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Mobile menu -->
        <transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div v-if="mobileMenuOpen" class="lg:hidden border-t border-gray-100 bg-white py-4">
            <div class="space-y-1 px-2">
              <!-- Main Links -->
              <Link
                href="/"
                @click="mobileMenuOpen = false"
                :class="[
                  'block px-4 py-3 rounded-lg font-semibold text-sm transition-colors duration-200',
                  page.url === '/'
                    ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
              >
                Home
              </Link>

              <Link
                href="/programs"
                @click="mobileMenuOpen = false"
                :class="[
                  'block px-4 py-3 rounded-lg font-semibold text-sm transition-colors duration-200',
                  page.url.includes('/programs')
                    ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
              >
                Programs
              </Link>

              <Link
                href="/events"
                @click="mobileMenuOpen = false"
                :class="[
                  'block px-4 py-3 rounded-lg font-semibold text-sm transition-colors duration-200',
                  page.url.includes('/events')
                    ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
              >
                Events
              </Link>

              <Link
                href="/ai-forge"
                @click="mobileMenuOpen = false"
                :class="[
                  'block px-4 py-3 rounded-lg font-semibold text-sm transition-colors duration-200',
                  page.url.includes('/ai-forge')
                    ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
              >
                <span class="flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                  </svg>
                  AI Forge
                </span>
              </Link>

              <Link
                href="/contact"
                @click="mobileMenuOpen = false"
                :class="[
                  'block px-4 py-3 rounded-lg font-semibold text-sm transition-colors duration-200',
                  page.url === '/contact'
                    ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
              >
                Contact
              </Link>

              <!-- Discover Section -->
              <div class="pt-2 mt-2 border-t border-gray-100">
                <p class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Discover</p>
                <Link
                  href="/gallery"
                  @click="mobileMenuOpen = false"
                  :class="[
                    'block px-4 py-2.5 rounded-lg text-sm transition-colors duration-200',
                    page.url.includes('/gallery')
                      ? 'bg-[#42b6c5]/10 text-[#42b6c5] font-semibold'
                      : 'text-gray-600 hover:bg-gray-100'
                  ]"
                >
                  Gallery
                </Link>
                <Link
                  href="/resources"
                  @click="mobileMenuOpen = false"
                  :class="[
                    'block px-4 py-2.5 rounded-lg text-sm transition-colors duration-200',
                    page.url.includes('/resources')
                      ? 'bg-[#42b6c5]/10 text-[#42b6c5] font-semibold'
                      : 'text-gray-600 hover:bg-gray-100'
                  ]"
                >
                  Resources
                </Link>
                <Link
                  href="/success-stories"
                  @click="mobileMenuOpen = false"
                  :class="[
                    'block px-4 py-2.5 rounded-lg text-sm transition-colors duration-200',
                    page.url === '/success-stories'
                      ? 'bg-[#42b6c5]/10 text-[#42b6c5] font-semibold'
                      : 'text-gray-600 hover:bg-gray-100'
                  ]"
                >
                  Success Stories
                </Link>
                <Link
                  href="/about"
                  @click="mobileMenuOpen = false"
                  :class="[
                    'block px-4 py-2.5 rounded-lg text-sm transition-colors duration-200',
                    page.url === '/about'
                      ? 'bg-[#42b6c5]/10 text-[#42b6c5] font-semibold'
                      : 'text-gray-600 hover:bg-gray-100'
                  ]"
                >
                  About Us
                </Link>
              </div>

              <!-- Mobile Auth Links -->
              <div class="border-t border-gray-100 mt-2 pt-2">
                <template v-if="$page.props.auth.user">
                  <Link
                    :href="['cto', 'ceo', 'program_coordinator', 'admin'].includes($page.props.auth.user.role) ? '/admin/dashboard' : '/dashboard'"
                    @click="mobileMenuOpen = false"
                    class="block px-4 py-3 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                  >
                    Dashboard
                  </Link>
                  <Link
                    href="/logout"
                    method="post"
                    as="button"
                    @click="mobileMenuOpen = false"
                    class="w-full block px-4 py-3 rounded-lg font-semibold text-sm text-red-600 hover:bg-red-50 transition-colors duration-200"
                  >
                    Logout
                  </Link>
                </template>
                <template v-else>
                  <Link
                    href="/login"
                    @click="mobileMenuOpen = false"
                    class="block px-4 py-3 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                  >
                    Login
                  </Link>
                </template>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </nav>

    <!-- Flash Messages -->
    <div v-if="page.props.flash?.success" class="bg-green-50 border-l-4 border-green-500 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-green-800">{{ page.props.flash?.success }}</p>
        </div>
      </div>
    </div>
    <div v-if="page.props.flash?.error" class="bg-red-50 border-l-4 border-red-500 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-red-800">{{ page.props.flash?.error }}</p>
        </div>
      </div>
    </div>
    <div v-if="page.props.flash?.info" class="bg-blue-50 border-l-4 border-blue-500 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-blue-800">{{ page.props.flash?.info }}</p>
        </div>
      </div>
    </div>

    <!-- Toast Notifications -->
    <Toaster />

    <!-- Main Content -->
    <main class="flex-grow">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-[#000928] text-white py-16 mt-24">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          <!-- Brand -->
          <div>
            <h3 class="font-bold text-lg mb-4">{{ $page.props.siteSettings.site_title }}</h3>
            <p class="text-gray-300 text-sm">{{ $page.props.siteSettings.site_description }}</p>
          </div>

          <!-- Programs -->
          <div>
            <h4 class="font-semibold mb-4">Programs</h4>
            <ul class="space-y-2 text-sm text-gray-300">
              <li><Link href="/programs" class="hover:text-[#42b6c5] transition-colors">All Programs</Link></li>
              <li><Link href="/programs" class="hover:text-[#42b6c5] transition-colors">Trainings</Link></li>
              <li><Link href="/programs" class="hover:text-[#42b6c5] transition-colors">Internships</Link></li>
            </ul>
          </div>

          <!-- Quick Links -->
          <div>
            <h4 class="font-semibold mb-4">Quick Links</h4>
            <ul class="space-y-2 text-sm text-gray-300">
              <li><Link href="/about" class="hover:text-[#42b6c5] transition-colors">About Us</Link></li>
              <li><Link href="/events" class="hover:text-[#42b6c5] transition-colors">Events</Link></li>
              <li><Link href="/success-stories" class="hover:text-[#42b6c5] transition-colors">Success Stories</Link></li>
              <li><Link href="/ai-forge" class="hover:text-[#42b6c5] transition-colors">AI Forge</Link></li>
              <li><Link href="/contact" class="hover:text-[#42b6c5] transition-colors">Contact</Link></li>
            </ul>
          </div>

          <!-- Contact -->
          <div>
            <h4 class="font-semibold mb-4">Get in Touch</h4>
            <template v-if="$page.props.siteSettings.contact_email">
              <p class="text-sm text-gray-300 mb-2">Email: <a :href="`mailto:${$page.props.siteSettings.contact_email}`" class="hover:text-[#42b6c5] transition-colors">{{ $page.props.siteSettings.contact_email }}</a></p>
            </template>
            <template v-if="$page.props.siteSettings.contact_whatsapp">
              <p class="text-sm text-gray-300 mb-4">WhatsApp: <a :href="`https://wa.me/${$page.props.siteSettings.contact_whatsapp.replace(/[^0-9]/g, '')}`" target="_blank" class="hover:text-[#42b6c5] transition-colors">{{ $page.props.siteSettings.contact_whatsapp }}</a></p>
            </template>
            <template v-if="!$page.props.siteSettings.contact_whatsapp">
              <p class="text-sm text-gray-300 mb-4">WhatsApp: <a href="https://wa.me/" class="hover:text-[#42b6c5] transition-colors">Contact us</a></p>
            </template>
            <div class="flex space-x-4 mt-4">
              <a 
                v-if="$page.props.siteSettings.social_linkedin"
                :href="$page.props.siteSettings.social_linkedin" 
                target="_blank"
                rel="noopener noreferrer"
                class="text-[#42b6c5] hover:text-white transition-colors"
              >
                LinkedIn
              </a>
              <a 
                v-if="$page.props.siteSettings.social_twitter"
                :href="$page.props.siteSettings.social_twitter" 
                target="_blank"
                rel="noopener noreferrer"
                class="text-[#42b6c5] hover:text-white transition-colors"
              >
                Twitter
              </a>
              <a 
                v-if="$page.props.siteSettings.social_facebook"
                :href="$page.props.siteSettings.social_facebook" 
                target="_blank"
                rel="noopener noreferrer"
                class="text-[#42b6c5] hover:text-white transition-colors"
              >
                Facebook
              </a>
              <a 
                v-if="$page.props.siteSettings.social_instagram"
                :href="$page.props.siteSettings.social_instagram" 
                target="_blank"
                rel="noopener noreferrer"
                class="text-[#42b6c5] hover:text-white transition-colors"
              >
                Instagram
              </a>
            </div>
          </div>
        </div>

        <div class="border-t border-gray-700 pt-8">
          <p class="text-center text-sm text-gray-400">{{ $page.props.siteSettings.footer_copyright_text }} | {{ $page.props.siteSettings.footer_powered_by }}</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import { Toaster } from '@/components/ui/toast';

const page = usePage();
const mobileMenuOpen = ref(false);
const discoverOpen = ref(false);

const cartCount = computed(() => {
  return (page.props as Record<string, unknown>).cartCount as number ?? 0;
});
</script>
