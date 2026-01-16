<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
  stats: {
    students_trained: number;
    programs_count: number;
    events_count: number;
  };
  featuredPrograms: any[];
  upcomingEvents: any[];
  siteSettings: {
    youtube_video_url: string | null;
    hero_title: string;
    hero_subtitle: string;
  };
}

const props = defineProps<Props>();

// Typing effect
const displayText = ref('');
const typingSpeed = 100;

onMounted(() => {
  let index = 0;
  const fullText = props.siteSettings.hero_title || 'World-Class Tech Education';
  const typeNextCharacter = () => {
    if (index < fullText.length) {
      displayText.value += fullText.charAt(index);
      index++;
      setTimeout(typeNextCharacter, typingSpeed);
    }
  };
  typeNextCharacter();
});

// Convert YouTube URL to embed URL
const youtubeEmbedUrl = computed(() => {
  const url = props.siteSettings.youtube_video_url;
  if (!url) return null;
  
  // Handle various YouTube URL formats
  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
  const match = url.match(regExp);
  
  if (match && match[2].length === 11) {
    return `https://www.youtube.com/embed/${match[2]}?autoplay=0&modestbranding=1&rel=0`;
  }
  
  // If already an embed URL, return as is
  if (url.includes('embed/')) {
    return url;
  }
  
  return null;
});

const testimonials = [
  {
    name: 'Chioma Okoro',
    role: 'Software Engineer',
    company: 'Tech Startup Lagos',
    image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop',
    quote: 'Traitz Academy transformed my career. The hands-on approach and mentorship made all the difference.'
  },
  {
    name: 'Tunde Adebayo',
    role: 'Product Designer',
    company: 'Global Tech Company',
    image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop',
    quote: 'The real-world projects and exposure to industry standards were invaluable.'
  },
  {
    name: 'Zainab Mohammed',
    role: 'Data Analyst',
    company: 'Finance Company',
    image: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=200&h=200&fit=crop',
    quote: 'I went from zero to confident professional. The academy\'s structure is world-class.'
  }
];
</script>

<template>
  <PublicLayout>
    <Head title="Home - Traitz Academy" />

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-[#000928] via-[#1a0a52] to-[#381998] text-white min-h-[90vh] flex items-center">
      <!-- Background pattern -->
      <div class="absolute inset-0 opacity-10">
        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1200 1200">
          <defs>
            <pattern id="dots" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
              <circle cx="50" cy="50" r="2" fill="currentColor" />
            </pattern>
          </defs>
          <rect width="1200" height="1200" fill="url(#dots)" />
        </svg>
      </div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
        <div class="animate-fade-in">
          <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
            <span class="typing-text">{{ displayText }}<span class="typing-cursor"></span></span>
          </h1>
          <p class="text-xl md:text-2xl text-gray-300 mb-8 max-w-3xl mx-auto leading-relaxed">
            {{ siteSettings.hero_subtitle || 'Bridging the gap between academic learning and real-world industry needs. Join 300+ professionals transformed through structured learning and mentorship.' }}
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/programs"
              class="inline-flex items-center justify-center px-8 py-3 bg-[#42b6c5] text-[#000928] rounded-lg font-bold text-lg hover:bg-white transition-all duration-200 transform hover:scale-105"
            >
              Explore Programs
              <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </Link>
            <a
              href="#featured"
              class="inline-flex items-center justify-center px-8 py-3 border-2 border-white text-white rounded-lg font-bold text-lg hover:bg-white hover:text-[#000928] transition-all duration-200"
            >
              Learn More
            </a>
          </div>
        </div>

        <!-- Floating accent shapes -->
        <div class="absolute top-20 right-0 w-96 h-96 bg-[#42b6c5] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-32 left-0 w-96 h-96 bg-[#381998] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
      </div>
    </section>

    <!-- Trust Metrics -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="text-center">
            <div class="text-5xl font-bold text-[#42b6c5] mb-2">{{ stats.students_trained }}+</div>
            <p class="text-gray-600 text-lg">Students Trained & Interned</p>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-[#381998] mb-2">{{ stats.programs_count }}</div>
            <p class="text-gray-600 text-lg">Active Programs</p>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-[#000928] mb-2">{{ stats.events_count }}+</div>
            <p class="text-gray-600 text-lg">Upcoming Events</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Video Section -->
    <section v-if="youtubeEmbedUrl" class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-4xl md:text-5xl font-bold text-[#000928] mb-4">See Our Academy in Action</h2>
          <p class="text-gray-600 text-lg max-w-2xl mx-auto">Discover what makes Traitz Academy the leading tech education platform</p>
        </div>

        <div class="relative overflow-hidden rounded-2xl shadow-2xl group">
          <!-- Video container with aspect ratio -->
          <div class="relative w-full bg-black" style="padding-bottom: 56.25%">
            <iframe
              class="absolute inset-0 w-full h-full transition-opacity duration-300 group-hover:opacity-95"
              :src="youtubeEmbedUrl"
              title="Traitz Academy Overview"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
            ></iframe>

            <!-- Overlay gradient -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
          </div>

          <!-- Play button hint -->
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="w-20 h-20 bg-[#42b6c5] rounded-full flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
              <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Video features -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
          <div class="text-center">
            <div class="w-12 h-12 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mx-auto mb-3">
              <svg class="w-6 h-6 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="font-bold text-[#000928] mb-2">Industry Experts</h3>
            <p class="text-gray-600 text-sm">Learn from professionals with decades of experience</p>
          </div>
          <div class="text-center">
            <div class="w-12 h-12 bg-[#381998]/10 rounded-lg flex items-center justify-center mx-auto mb-3">
              <svg class="w-6 h-6 text-[#381998]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="font-bold text-[#000928] mb-2">Hands-On Training</h3>
            <p class="text-gray-600 text-sm">Build real projects with practical, applicable skills</p>
          </div>
          <div class="text-center">
            <div class="w-12 h-12 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mx-auto mb-3">
              <svg class="w-6 h-6 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="font-bold text-[#000928] mb-2">Proven Results</h3>
            <p class="text-gray-600 text-sm">300+ graduates successfully placed in top companies</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Programs -->
    <section id="featured" class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-[#000928] mb-4">Featured Programs</h2>
          <p class="text-gray-600 text-lg max-w-2xl mx-auto">Discover our most popular and highly-rated training programs</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="program in featuredPrograms"
            :key="program.id"
            class="bg-white rounded-lg overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2"
          >
            <div class="h-48 bg-gradient-to-br from-[#381998] to-[#42b6c5] relative overflow-hidden">
              <img :src="'/storage/' + program.image_url" :alt="program.title" class="w-full h-full object-cover opacity-80" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
            </div>
            <div class="p-6">
              <div class="inline-block bg-[#42b6c5]/10 text-[#42b6c5] px-3 py-1 rounded-full text-sm font-semibold mb-2">
                {{ program.category.replace('-', ' ').toUpperCase() }}
              </div>
              <h3 class="text-xl font-bold text-[#000928] mb-2">{{ program.title }}</h3>
              <p class="text-gray-600 mb-4 line-clamp-2">{{ program.description }}</p>
              <div class="space-y-2 text-sm text-gray-600 mb-6">
                <p><span class="font-semibold">Duration:</span> {{ program.duration }}</p>
                <p><span class="font-semibold">Capacity:</span> {{ program.capacity }} participants</p>
              </div>
              <Link
                :href="`/programs/${program.slug}`"
                class="inline-block w-full text-center px-4 py-2 bg-[#000928] text-white rounded-lg font-semibold hover:bg-[#381998] transition-colors"
              >
                View Details
              </Link>
            </div>
          </div>
        </div>

        <div class="text-center mt-12">
          <Link
            href="/programs"
            class="inline-flex items-center px-8 py-3 bg-[#42b6c5] text-white rounded-lg font-bold hover:bg-[#381998] transition-colors"
          >
            View All Programs
            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>
    </section>

    <!-- Teaching Model -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-[#000928] mb-4">How We Teach</h2>
          <p class="text-gray-600 text-lg max-w-2xl mx-auto">A unique approach combining mentorship, real-world projects, and industry alignment</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="w-16 h-16 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Project-Based</h3>
            <p class="text-gray-600">Learn by building real projects with industry relevance and practical application</p>
          </div>

          <div class="text-center">
            <div class="w-16 h-16 bg-[#381998]/10 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-[#381998]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0H9m6 0H9m6 0H9m6 0H9M9 8h6" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Expert Mentorship</h3>
            <p class="text-gray-600">Get guided by industry professionals with real-world experience</p>
          </div>

          <div class="text-center">
            <div class="w-16 h-16 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Performance Evaluation</h3>
            <p class="text-gray-600">Regular feedback and assessments to track progress and growth</p>
          </div>

          <div class="text-center">
            <div class="w-16 h-16 bg-[#381998]/10 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-[#381998]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Industry Aligned</h3>
            <p class="text-gray-600">Curriculum designed with input from leading tech companies</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-[#000928] mb-4">Student Success Stories</h2>
          <p class="text-gray-600 text-lg">Real stories from graduates transforming their careers</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="(testimonial, index) in testimonials"
            :key="index"
            class="bg-white rounded-lg p-8 shadow-lg hover:shadow-xl transition-shadow"
          >
            <div class="flex items-center mb-4">
              <img :src="testimonial.image" :alt="testimonial.name" class="w-14 h-14 rounded-full object-cover mr-4" />
              <div>
                <p class="font-bold text-[#000928]">{{ testimonial.name }}</p>
                <p class="text-sm text-gray-600">{{ testimonial.role }} @ {{ testimonial.company }}</p>
              </div>
            </div>
            <div class="flex text-[#42b6c5] mb-3">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <p class="text-gray-600 italic">{{ testimonial.quote }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Upcoming Events -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-[#000928] mb-4">Upcoming Events</h2>
          <p class="text-gray-600 text-lg">Join our community for webinars, workshops, and networking</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
          <div
            v-for="event in upcomingEvents"
            :key="event.id"
            class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:border-[#42b6c5] hover:shadow-lg transition-all"
          >
            <div class="bg-gradient-to-r from-[#381998] to-[#42b6c5] p-6 text-white">
              <div class="text-4xl font-bold mb-2">{{ new Date(event.event_date).getDate() }}</div>
              <p class="text-white/80">{{ new Date(event.event_date).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) }}</p>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-bold text-[#000928] mb-2">{{ event.title }}</h3>
              <p class="text-gray-600 text-sm mb-4">{{ event.description.substring(0, 100) }}...</p>
              <div v-if="event.location || event.is_online" class="text-sm text-gray-600 mb-4">
                <span v-if="event.is_online" class="inline-block bg-[#42b6c5]/10 text-[#42b6c5] px-2 py-1 rounded">Online</span>
                <span v-if="event.location" class="text-gray-600">📍 {{ event.location }}</span>
              </div>
              <Link
                :href="`/events/${event.slug}`"
                class="inline-block px-4 py-2 bg-[#42b6c5] text-white rounded-lg font-semibold hover:bg-[#381998] transition-colors text-sm"
              >
                Learn More
              </Link>
            </div>
          </div>
        </div>

        <div class="text-center">
          <Link
            href="/events"
            class="inline-flex items-center px-8 py-3 bg-[#000928] text-white rounded-lg font-bold hover:bg-[#381998] transition-colors"
          >
            See All Events
            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="py-20 bg-gradient-to-r from-[#000928] to-[#381998] text-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">Ready to Transform Your Career?</h2>
        <p class="text-xl text-gray-300 mb-8">Join hundreds of professionals who have achieved their goals through our programs</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            href="/programs"
            class="inline-flex items-center justify-center px-8 py-3 bg-[#42b6c5] text-[#000928] rounded-lg font-bold text-lg hover:bg-white transition-all duration-200 transform hover:scale-105"
          >
            Start Your Journey
          </Link>
          <a
            href="https://wa.me/234xxxxxxxxxx"
            target="_blank"
            class="inline-flex items-center justify-center px-8 py-3 border-2 border-white text-white rounded-lg font-bold text-lg hover:bg-white hover:text-[#000928] transition-all duration-200"
          >
            Chat with Us
          </a>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.8s ease-out;
}

/* Typing effect styles */
@keyframes typing-cursor {
  0%, 49% {
    border-right-color: rgba(255, 255, 255, 0.8);
  }
  50%, 100% {
    border-right-color: transparent;
  }
}

.typing-text {
  display: inline-block;
  border-right: 3px solid rgba(255, 255, 255, 0.8);
}

.typing-cursor {
  animation: typing-cursor 1s infinite;
}
</style>
