<template>
  <PublicLayout>
    <Head title="Success Stories" />

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-[#000928] via-[#1a1a4e] to-[#381998] py-20 overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-96 h-96 bg-[#42b6c5] rounded-full blur-3xl transform -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-[#381998] rounded-full blur-3xl transform translate-x-1/2 translate-y-1/2"></div>
      </div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
          Student 
          <span class="bg-gradient-to-r from-[#42b6c5] to-[#9de6ee] bg-clip-text text-transparent">
            Success Stories
          </span>
        </h1>
        <p class="text-xl text-gray-300 max-w-3xl mx-auto">
          Discover how our graduates have transformed their careers and are making an impact in their communities.
        </p>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-white py-12 border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
          <div>
            <div class="text-4xl font-bold text-[#42b6c5] mb-2">{{ successStories.length }}+</div>
            <p class="text-gray-600">Success Stories</p>
          </div>
          <div>
            <div class="text-4xl font-bold text-[#42b6c5] mb-2">95%</div>
            <p class="text-gray-600">Career Advancement</p>
          </div>
          <div>
            <div class="text-4xl font-bold text-[#42b6c5] mb-2">100%</div>
            <p class="text-gray-600">Recommend Us</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Success Stories Grid -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div v-if="successStories.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div
            v-for="story in successStories"
            :key="story.id"
            class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-[#42b6c5]/30"
          >
            <!-- Story Content -->
            <div class="p-8">
              <!-- Quote Icon -->
              <div class="mb-6">
                <svg class="w-10 h-10 text-[#42b6c5]/30" fill="currentColor" viewBox="0 0 32 32">
                  <path d="M10 8c-3.3 0-6 2.7-6 6v10h10V14H8c0-1.1.9-2 2-2V8zm14 0c-3.3 0-6 2.7-6 6v10h10V14h-6c0-1.1.9-2 2-2V8z"/>
                </svg>
              </div>

              <!-- Story Text -->
              <p class="text-gray-600 mb-6 leading-relaxed italic text-lg line-clamp-4">
                "{{ story.story }}"
              </p>

              <!-- Rating -->
              <div class="flex text-[#42b6c5] mb-6">
                <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>

              <!-- Author -->
              <div class="flex items-center pt-6 border-t border-gray-100">
                <img 
                  v-if="story.image_url" 
                  :src="getImageUrl(story.image_url)" 
                  :alt="story.name" 
                  class="w-14 h-14 rounded-full object-cover mr-4 ring-2 ring-[#42b6c5]/20" 
                />
                <div v-else class="w-14 h-14 rounded-full bg-gradient-to-br from-[#42b6c5] to-[#381998] flex items-center justify-center mr-4">
                  <span class="text-white font-bold text-lg">{{ story.name.charAt(0) }}</span>
                </div>
                <div>
                  <p class="font-bold text-[#000928]">{{ story.name }}</p>
                  <p v-if="story.role || story.company" class="text-sm text-gray-500">
                    {{ story.role }}<span v-if="story.role && story.company"> @ </span>{{ story.company }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-20">
          <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-800 mb-2">No Success Stories Yet</h3>
          <p class="text-gray-500 mb-6">We're collecting inspiring stories from our graduates. Check back soon!</p>
          <Link href="/programs" class="inline-flex items-center px-6 py-3 bg-[#42b6c5] text-white rounded-lg font-semibold hover:bg-[#35919e] transition-colors">
            Explore Programs
          </Link>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-[#000928] to-[#381998]">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
          Ready to Write Your Success Story?
        </h2>
        <p class="text-xl text-gray-300 mb-10">
          Join our community of successful graduates and transform your career today.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link 
            href="/programs" 
            class="inline-flex items-center justify-center px-8 py-4 bg-[#42b6c5] text-white rounded-xl font-semibold text-lg hover:bg-[#35919e] shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105"
          >
            Apply Now
            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </Link>
          <Link 
            href="/contact" 
            class="inline-flex items-center justify-center px-8 py-4 bg-white/10 text-white border-2 border-white/30 rounded-xl font-semibold text-lg hover:bg-white/20 transition-all duration-300"
          >
            Contact Us
          </Link>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface SuccessStory {
  id: number;
  name: string;
  role: string | null;
  company: string | null;
  story: string;
  image_url: string | null;
  is_active: boolean;
  display_order: number;
}

defineProps<{
  successStories: SuccessStory[];
}>();

const getImageUrl = (path: string): string => {
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path;
  }
  return `/storage/${path}`;
};
</script>

<style scoped>
.line-clamp-4 {
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
