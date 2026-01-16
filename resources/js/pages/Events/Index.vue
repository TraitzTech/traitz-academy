<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Event {
  id: number;
  title: string;
  slug: string;
  description: string;
  event_date: string;
  location: string;
  is_online: boolean;
  event_url: string;
  category: string;
  image_url: string;
}

interface Props {
  events: Event[];
}

defineProps<Props>();

const formatDate = (date: string) => new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
const isUpcoming = (date: string) => new Date(date) > new Date();
</script>

<template>
  <PublicLayout>
    <Head title="Events - Traitz Academy" />

    <!-- Page Header -->
    <section class="bg-gradient-to-r from-[#000928] to-[#381998] text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Our Events</h1>
        <p class="text-xl text-gray-300">Join us for webinars, workshops, networking events, and more</p>
      </div>
    </section>

    <!-- Events Grid -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div v-if="events.length === 0" class="text-center py-16">
          <p class="text-gray-600 text-lg">No events available at the moment. Check back soon!</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div
            v-for="event in events"
            :key="event.id"
            class="bg-white rounded-lg overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 flex flex-col"
          >
            <!-- Date Header -->
            <div class="bg-gradient-to-r from-[#381998] to-[#42b6c5] p-6 text-white">
              <div class="text-5xl font-bold">{{ new Date(event.event_date).getDate() }}</div>
              <p class="text-white/90">{{ new Date(event.event_date).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) }}</p>
              <p class="text-sm text-white/80 mt-2">{{ new Date(event.event_date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) }}</p>
            </div>

            <!-- Content -->
            <div class="p-6 flex-grow flex flex-col">
              <div class="inline-block bg-[#42b6c5]/10 text-[#42b6c5] px-3 py-1 rounded-full text-sm font-semibold mb-2 w-fit">
                {{ event.category || 'Event' }}
              </div>

              <h3 class="text-xl font-bold text-[#000928] mb-2 line-clamp-2">{{ event.title }}</h3>
              <p class="text-gray-600 mb-4 flex-grow line-clamp-3">{{ event.description }}</p>

              <!-- Location/Online Info -->
              <div v-if="event.location || event.is_online" class="flex flex-wrap gap-2 mb-6">
                <span v-if="event.is_online" class="inline-flex items-center text-[#42b6c5] text-sm font-semibold bg-[#42b6c5]/10 px-3 py-1 rounded-full">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Online
                </span>
                <span v-if="event.location" class="inline-flex items-center text-gray-600 text-sm font-semibold">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ event.location }}
                </span>
              </div>

              <div class="space-y-2">
                <Link
                  :href="`/events/${event.slug}`"
                  class="block w-full text-center px-4 py-2 bg-[#000928] text-white rounded-lg font-semibold hover:bg-[#381998] transition-colors"
                >
                  View Details
                </Link>
                <Link
                  v-if="isUpcoming(event.event_date)"
                  :href="`/events/${event.slug}`"
                  class="block w-full text-center px-4 py-2 bg-[#42b6c5] text-white rounded-lg font-semibold hover:bg-[#35919e] transition-colors"
                >
                  Register Now
                </Link>
                <button
                  v-else
                  disabled
                  class="block w-full text-center px-4 py-2 bg-gray-300 text-gray-600 rounded-lg font-semibold cursor-not-allowed"
                >
                  Event Ended
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
