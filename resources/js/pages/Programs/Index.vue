<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { computed, ref } from 'vue';

interface Program {
  id: number;
  title: string;
  slug: string;
  category: string;
  description: string;
  duration: string;
  capacity: number;
  enrolled_count: number;
  image_url: string;
  is_featured: boolean;
}

interface Props {
  programs: Program[];
}

const props = defineProps<Props>();

const categories = ['all', 'professional-training', 'bootcamp', 'workshop', 'academic-internship', 'professional-internship'];
const selectedCategory = ref('all');

const filteredPrograms = computed(() => {
  if (selectedCategory.value === 'all') return props.programs;
  return props.programs.filter(p => p.category === selectedCategory.value);
});

const formatCategory = (cat: string) => cat.replace('-', ' ').toUpperCase();
</script>

<template>
  <PublicLayout>
    <Head title="Programs - Traitz Academy" />

    <!-- Page Header -->
    <section class="bg-gradient-to-r from-[#000928] to-[#381998] text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Our Programs</h1>
        <p class="text-xl text-gray-300">Choose from our comprehensive range of training, internships, and professional development programs</p>
      </div>
    </section>

    <!-- Category Filter -->
    <section class="py-12 bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap gap-3">
          <button
            v-for="category in categories"
            :key="category"
            @click="selectedCategory = category"
            :class="[
              'px-6 py-2 rounded-full font-semibold transition-all',
              selectedCategory === category
                ? 'bg-[#42b6c5] text-white'
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
            ]"
          >
            {{ formatCategory(category) }}
          </button>
        </div>
      </div>
    </section>

    <!-- Programs Grid -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div v-if="filteredPrograms.length === 0" class="text-center py-16">
          <p class="text-gray-600 text-lg">No programs found in this category.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div
            v-for="program in filteredPrograms"
            :key="program.id"
            class="bg-white rounded-lg overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 flex flex-col"
          >
            <!-- Image -->
            <div class="h-48 relative overflow-hidden bg-gradient-to-br from-[#381998] to-[#42b6c5]">
              <img :src="'/storage/' + program.image_url" :alt="program.title" class="w-full h-full object-cover opacity-80" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
              <div v-if="program.is_featured" class="absolute top-4 right-4 bg-yellow-400 text-gray-900 px-3 py-1 rounded-full text-sm font-bold">Featured</div>
            </div>

            <!-- Content -->
            <div class="p-6 flex-grow flex flex-col">
              <div class="inline-block bg-[#42b6c5]/10 text-[#42b6c5] px-3 py-1 rounded-full text-sm font-semibold mb-2 w-fit">
                {{ formatCategory(program.category) }}
              </div>

              <h3 class="text-xl font-bold text-[#000928] mb-2 line-clamp-2">{{ program.title }}</h3>
              <p class="text-gray-600 mb-4 flex-grow line-clamp-3">{{ program.description }}</p>

              <div class="space-y-2 text-sm text-gray-600 mb-6">
                <div class="flex justify-between">
                  <span class="font-semibold">Duration:</span>
                  <span>{{ program.duration }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-semibold">Enrolled:</span>
                  <span>{{ program.enrolled_count }}/{{ program.capacity }} participants</span>
                </div>
                <div v-if="program.capacity - program.enrolled_count <= 5" class="text-orange-600 font-semibold">
                  ⚠ Limited spots remaining
                </div>
              </div>

              <div class="space-y-2">
                <Link
                  :href="`/programs/${program.slug}`"
                  class="block w-full text-center px-4 py-2 bg-[#000928] text-white rounded-lg font-semibold hover:bg-[#381998] transition-colors"
                >
                  View Details
                </Link>
                <Link
                  :href="`/programs/${program.id}/apply`"
                  class="block w-full text-center px-4 py-2 bg-[#42b6c5] text-white rounded-lg font-semibold hover:bg-[#35919e] transition-colors"
                >
                  Apply Now
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
