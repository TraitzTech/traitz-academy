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
          <div class="hidden lg:flex items-center space-x-8">
            <Link 
              href="/"
              :class="[
                'relative px-1 py-1 text-sm font-semibold transition-colors duration-200',
                page.url === '/'
                  ? 'text-[#42b6c5]'
                  : 'text-gray-700 hover:text-[#42b6c5]'
              ]"
            >
              Home
              <span v-if="page.url === '/'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#42b6c5] rounded-full"></span>
            </Link>

            <Link 
              href="/programs"
              :class="[
                'relative px-1 py-1 text-sm font-semibold transition-colors duration-200',
                page.url.includes('/programs')
                  ? 'text-[#42b6c5]'
                  : 'text-gray-700 hover:text-[#42b6c5]'
              ]"
            >
              Programs
              <span v-if="page.url.includes('/programs')" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#42b6c5] rounded-full"></span>
            </Link>

            <Link 
              href="/events"
              :class="[
                'relative px-1 py-1 text-sm font-semibold transition-colors duration-200',
                page.url.includes('/events')
                  ? 'text-[#42b6c5]'
                  : 'text-gray-700 hover:text-[#42b6c5]'
              ]"
            >
              Events
              <span v-if="page.url.includes('/events')" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#42b6c5] rounded-full"></span>
            </Link>

            <Link 
              href="/about"
              :class="[
                'relative px-1 py-1 text-sm font-semibold transition-colors duration-200',
                page.url === '/about'
                  ? 'text-[#42b6c5]'
                  : 'text-gray-700 hover:text-[#42b6c5]'
              ]"
            >
              About
              <span v-if="page.url === '/about'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#42b6c5] rounded-full"></span>
            </Link>

            <Link 
              href="/success-stories"
              :class="[
                'relative px-1 py-1 text-sm font-semibold transition-colors duration-200',
                page.url === '/success-stories'
                  ? 'text-[#42b6c5]'
                  : 'text-gray-700 hover:text-[#42b6c5]'
              ]"
            >
              Success Stories
              <span v-if="page.url === '/success-stories'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#42b6c5] rounded-full"></span>
            </Link>

            <Link 
              href="/contact"
              :class="[
                'relative px-1 py-1 text-sm font-semibold transition-colors duration-200',
                page.url === '/contact'
                  ? 'text-[#42b6c5]'
                  : 'text-gray-700 hover:text-[#42b6c5]'
              ]"
            >
              Contact
              <span v-if="page.url === '/contact'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#42b6c5] rounded-full"></span>
            </Link>
          </div>

          <!-- Desktop Auth & CTA Buttons -->
          <div class="hidden lg:flex items-center space-x-4">
            <template v-if="$page.props.auth.user">
              <Link
                :href="$page.props.auth.user.role === 'admin' ? '/admin/dashboard' : '/dashboard'"
                class="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-[#42b6c5] transition-colors"
              >
                Dashboard
              </Link>
              <Link
                href="/logout"
                method="post"
                as="button"
                class="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-red-600 transition-colors"
              >
                Logout
              </Link>
            </template>
            <template v-else>
              <Link
                href="/login"
                class="px-4 py-2 text-sm font-semibold text-gray-700 hover:text-[#42b6c5] transition-colors"
              >
                Login
              </Link>
              <Link
                href="/programs"
                class="inline-flex items-center px-7 py-2.5 bg-[#42b6c5] text-white rounded-lg font-semibold text-sm hover:bg-[#35919e] shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105"
              >
                Apply Now
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </template>
          </div>

          <!-- Mobile menu button -->
          <div class="lg:hidden flex items-center space-x-2">
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

        <!-- Mobile menu with slide transition -->
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
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17.25c0 2.848.992 5.541 2.581 7.65m9.419-10.4C17.5 6.253 22 10.998 22 17.25" />
                  </svg>
                  Programs
                </div>
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
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Events
                </div>
              </Link>

              <Link 
                href="/about"
                @click="mobileMenuOpen = false"
                :class="[
                  'block px-4 py-3 rounded-lg font-semibold text-sm transition-colors duration-200',
                  page.url === '/about'
                    ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
              >
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  About
                </div>
              </Link>

              <Link 
                href="/success-stories"
                @click="mobileMenuOpen = false"
                :class="[
                  'block px-4 py-3 rounded-lg font-semibold text-sm transition-colors duration-200',
                  page.url === '/success-stories'
                    ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
              >
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                  </svg>
                  Success Stories
                </div>
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
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  Contact
                </div>
              </Link>

              <!-- Mobile Auth Links -->
              <div class="border-t border-gray-100 mt-3 pt-3">
                <template v-if="$page.props.auth.user">
                  <Link
                    :href="$page.props.auth.user.role === 'admin' ? '/admin/dashboard' : '/dashboard'"
                    @click="mobileMenuOpen = false"
                    class="block px-4 py-3 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                  >
                    <div class="flex items-center">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                      </svg>
                      Dashboard
                    </div>
                  </Link>
                  <Link
                    href="/logout"
                    method="post"
                    as="button"
                    @click="mobileMenuOpen = false"
                    class="w-full block px-4 py-3 rounded-lg font-semibold text-sm text-red-600 hover:bg-red-50 transition-colors duration-200"
                  >
                    <div class="flex items-center">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                      </svg>
                      Logout
                    </div>
                  </Link>
                </template>
                <template v-else>
                  <Link
                    href="/login"
                    @click="mobileMenuOpen = false"
                    class="block px-4 py-3 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                  >
                    <div class="flex items-center">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                      </svg>
                      Login
                    </div>
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
import { ref } from 'vue';

import { Toaster } from '@/components/ui/toast';

const page = usePage();
const mobileMenuOpen = ref(false);
</script>
