<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import PublicLayout from '@/layouts/PublicLayout.vue';

interface Program {
  id: number;
  title: string;
  slug: string;
  category: string;
  description: string;
  overview: string | null;
  who_is_for: string | null;
  skills_and_tools: string | null;
  duration: string;
  learning_outcomes: string | null;
  certification: string | null;
  price: number;
  max_installments: number;
  image_url: string;
  is_featured: boolean;
  capacity: number | null;
  enrolled_count: number;
  start_date: string | null;
  end_date: string | null;
  curriculum: string | null;
}

interface Application {
  id: number;
  status: string;
  created_at: string;
}

interface Props {
  program: Program;
  userApplication?: Application | null;
}

interface PageProps {
  siteSettings: {
    contact_whatsapp?: string | null;
  };
}

const props = defineProps<Props>();
const page = usePage();

const formatCategory = (cat: string) => cat.replace('-', ' ').toUpperCase();
const formatDate = (date: string | null) => {
  if (!date) {
    return 'TBD';
  }

  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};
const isAcademic = (cat: string) => cat === 'academic-internship';
const isProfessional = (cat: string) => cat === 'professional-internship';
const isJobOpportunity = (cat: string) => cat === 'job-opportunity';

const isCareerRole = computed(() => ['job-opportunity', 'professional-internship'].includes(props.program.category));

const labels = computed(() => {
  if (isCareerRole.value) {
    return {
      overview: 'Role Overview',
      overviewEmpty: 'Role overview will be shared soon.',
      whoIsFor: 'Who Should Apply?',
      whoIsForEmpty: 'Open to qualified professionals ready to contribute.',
      skills: 'Skills & Tools You\'ll Use',
      skillsEmpty: 'Required skills will be updated soon.',
      outcomes: 'Key Responsibilities',
      outcomesEmpty: 'Responsibilities will be detailed soon.',
      curriculum: 'Role Scope & Work Plan',
      curriculumEmpty: 'Work plan details will be published soon.',
      certification: 'What We Offer',
      certificationEmpty: 'Benefits and perks will be shared before applications close.',
    };
  }
  return {
    overview: 'Program Overview',
    overviewEmpty: 'Program overview will be shared soon.',
    whoIsFor: 'Who Is This For?',
    whoIsForEmpty: 'Open to motivated learners ready to grow.',
    skills: 'Skills & Tools You\'ll Learn',
    skillsEmpty: 'Skills will be updated soon.',
    outcomes: 'Learning Outcomes',
    outcomesEmpty: 'Learning outcomes will be available soon.',
    curriculum: 'Curriculum',
    curriculumEmpty: 'Curriculum details will be published soon.',
    certification: 'Certification',
    certificationEmpty: 'Certificate details will be shared before cohort start.',
  };
});

const skills = computed(() => {
  return (props.program.skills_and_tools ?? '')
    .split(',')
    .map((value) => value.trim())
    .filter((value) => value.length > 0);
});

const learningOutcomes = computed(() => {
  return (props.program.learning_outcomes ?? '')
    .split('.')
    .map((value) => value.trim())
    .filter((value) => value.length > 0);
});

const enrollmentPercentage = computed(() => {
  const capacity = props.program.capacity ?? 0;
  if (capacity <= 0) {
    return 0;
  }

  return Math.round((props.program.enrolled_count / capacity) * 100);
});

const contactWhatsApp = computed(() => (page.props as unknown as PageProps).siteSettings?.contact_whatsapp ?? null);
const whatsAppLink = computed(() => {
  if (!contactWhatsApp.value) {
    return null;
  }
  const digits = contactWhatsApp.value.replace(/[^0-9]/g, '');
  if (!digits) {
    return contactWhatsApp.value;
  }
  return `https://wa.me/${digits}`;
});

const formatPrice = (price: number) => {
  if (price === 0) return 'Free'
  return new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(price)
}
</script>

<template>
  <PublicLayout>
    <Head :title="`${program.title} - Traitz Academy`" />

    <!-- Header -->
    <section class="bg-gradient-to-r from-[#000928] to-[#381998] text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <Link href="/programs" class="inline-flex items-center text-[#42b6c5] hover:text-white mb-4">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Programs
        </Link>
        <div class="flex justify-between items-start">
          <div>
            <span class="inline-block bg-[#42b6c5] text-[#000928] px-4 py-1 rounded-full text-sm font-bold mb-4">
              {{ formatCategory(program.category) }}
            </span>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ program.title }}</h1>
            <p class="text-xl text-gray-300">{{ program.description }}</p>
          </div>
          <Link
            :href="`/programs/${program.id}/apply`"
            class="hidden md:inline-flex items-center px-8 py-3 bg-[#42b6c5] text-[#000928] rounded-lg font-bold text-lg hover:bg-white transition-all duration-200 transform hover:scale-105 whitespace-nowrap ml-4"
          >
            Apply Now
          </Link>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Left Column -->
          <div class="lg:col-span-2">
            <!-- Overview -->
            <div class="mb-12">
              <h2 class="text-3xl font-bold text-[#000928] mb-4">{{ labels.overview }}</h2>
              <p class="text-gray-700 text-lg leading-relaxed">{{ program.overview || labels.overviewEmpty }}</p>
            </div>

            <!-- Who is For -->
            <div class="mb-12">
              <h2 class="text-3xl font-bold text-[#000928] mb-4">{{ labels.whoIsFor }}</h2>
              <p class="text-gray-700 text-lg leading-relaxed">{{ program.who_is_for || labels.whoIsForEmpty }}</p>
            </div>

            <!-- Skills & Tools -->
            <div class="mb-12">
              <h2 class="text-3xl font-bold text-[#000928] mb-4">{{ labels.skills }}</h2>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="skill in skills"
                  :key="skill"
                  class="bg-[#42b6c5]/10 text-[#42b6c5] px-4 py-2 rounded-lg font-semibold"
                >
                  {{ skill }}
                </span>
                <span v-if="skills.length === 0" class="text-gray-500">{{ labels.skillsEmpty }}</span>
              </div>
            </div>

            <!-- Outcomes / Responsibilities -->
            <div class="mb-12">
              <h2 class="text-3xl font-bold text-[#000928] mb-4">{{ labels.outcomes }}</h2>
              <ul class="space-y-3">
                <li v-for="(outcome, index) in learningOutcomes" :key="index" class="flex items-start">
                  <svg class="w-6 h-6 text-[#42b6c5] mr-3 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-700">{{ outcome.trim() }}</span>
                </li>
                <li v-if="learningOutcomes.length === 0" class="text-gray-500">{{ labels.outcomesEmpty }}</li>
              </ul>
            </div>

            <!-- Curriculum / Work Plan -->
            <div class="mb-12">
              <h2 class="text-3xl font-bold text-[#000928] mb-4">{{ labels.curriculum }}</h2>
              <p class="text-gray-700 text-lg leading-relaxed whitespace-pre-line">{{ program.curriculum || labels.curriculumEmpty }}</p>
            </div>

            <!-- Certification -->
            <div class="bg-[#42b6c5]/10 border border-[#42b6c5] rounded-lg p-6 mb-12">
              <h3 class="text-2xl font-bold text-[#000928] mb-2">{{ labels.certification }}</h3>
              <p class="text-gray-700">{{ program.certification || labels.certificationEmpty }}</p>
            </div>
          </div>

          <!-- Right Column - Sidebar -->
          <div>
            <!-- Program Card -->
            <div class="bg-gray-50 rounded-lg p-8 sticky top-20">
              <div class="mb-6">
                <div class="text-4xl font-bold text-[#42b6c5] mb-2">
                  <span v-if="program.price">{{ formatPrice(program.price) }}</span>
                  <span v-else class="text-[#000928]">Free</span>
                </div>
                <p class="text-gray-600">Program fee (if applicable)</p>
                <p v-if="program.price > 0" class="text-sm text-gray-500 mt-2">
                  Flexible payment: up to {{ program.max_installments || 1 }} installment(s)
                </p>
              </div>

              <div class="space-y-6 mb-8 pb-8 border-b border-gray-200">
                <div>
                  <p class="text-sm text-gray-600 font-semibold">Duration</p>
                  <p class="text-lg font-bold text-[#000928]">{{ program.duration }}</p>
                </div>

                <div>
                  <p class="text-sm text-gray-600 font-semibold">Start Date</p>
                  <p class="text-lg font-bold text-[#000928]">{{ formatDate(program.start_date) }}</p>
                </div>

                <div>
                  <p class="text-sm text-gray-600 font-semibold">End Date</p>
                  <p class="text-lg font-bold text-[#000928]">{{ formatDate(program.end_date) }}</p>
                </div>

                <div>
                  <p class="text-sm text-gray-600 font-semibold">Enrollment</p>
                  <div class="mt-2">
                    <div class="flex justify-between mb-2">
                      <span class="font-bold text-[#000928]">{{ program.enrolled_count }}/{{ program.capacity || 'N/A' }}</span>
                      <span class="text-gray-600">{{ enrollmentPercentage }}% full</span>
                    </div>
                    <div class="w-full bg-gray-300 rounded-full h-2">
                      <div class="bg-[#42b6c5] h-2 rounded-full" :style="{ width: `${enrollmentPercentage}%` }"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Already Applied Message -->
              <div v-if="userApplication" class="block w-full">
                <div class="bg-green-50 border-2 border-green-500 rounded-lg p-4 mb-3">
                  <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div>
                      <p class="font-bold text-green-900 mb-1">✓ Application Submitted</p>
                      <p class="text-sm text-green-800 mb-2">You've already applied for this program</p>
                      <div class="text-xs text-green-700 space-y-1">
                        <p><span class="font-semibold">Status:</span> <span class="inline-block px-2 py-0.5 rounded ml-1" :class="userApplication.status === 'accepted' ? 'bg-green-200 text-green-900' : userApplication.status === 'rejected' ? 'bg-red-200 text-red-900' : 'bg-yellow-200 text-yellow-900'">{{ userApplication.status.charAt(0).toUpperCase() + userApplication.status.slice(1) }}</span></p>
                        <p><span class="font-semibold">Applied:</span> {{ new Date(userApplication.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
                <Link
                  href="/dashboard"
                  class="block w-full text-center px-6 py-3 bg-[#42b6c5] text-white rounded-lg font-bold text-lg hover:bg-[#35919e] transition-colors"
                >
                  View in Dashboard
                </Link>
              </div>

              <!-- Apply Button -->
              <Link
                v-else
                :href="`/programs/${program.id}/apply`"
                class="block w-full text-center px-6 py-3 bg-[#42b6c5] text-white rounded-lg font-bold text-lg hover:bg-[#35919e] transition-colors mb-3"
              >
                Apply Now
              </Link>

              <!-- Academic Internship Info -->
              <div v-if="isAcademic(program.category)" class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm">
                <p class="font-semibold text-blue-900 mb-2">📚 For Students</p>
                <p class="text-blue-800">Perfect for university and school internship requirements. Includes institutional coordination and academic supervisor support.</p>
              </div>

              <!-- Professional Internship Info -->
              <div v-if="isProfessional(program.category)" class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm">
                <p class="font-semibold text-green-900 mb-2">💼 For Professionals</p>
                <p class="text-green-800">Career-focused internship with skill assessment, portfolio building, and industry placement opportunities.</p>
              </div>

              <!-- Job Opportunity Info -->
              <div v-if="isJobOpportunity(program.category)" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 text-sm mt-3">
                <p class="font-semibold text-indigo-900 mb-2">🧑‍💻 Job Opportunity</p>
                <p class="text-indigo-800">Submit a complete application with your CV and track progress directly from your dashboard.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-[#000928] to-[#381998] text-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Get Started?</h2>
        <p class="text-xl text-gray-300 mb-8">Apply now and take the first step in your professional journey</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            :href="`/programs/${program.id}/apply`"
            class="inline-flex items-center justify-center px-8 py-3 bg-[#42b6c5] text-[#000928] rounded-lg font-bold text-lg hover:bg-white transition-all duration-200 transform hover:scale-105"
          >
            Apply Now
          </Link>
          <a
            v-if="whatsAppLink"
            :href="whatsAppLink"
            target="_blank"
            class="inline-flex items-center justify-center px-8 py-3 border-2 border-white text-white rounded-lg font-bold text-lg hover:bg-white hover:text-[#000928] transition-all duration-200"
          >
            Ask Questions
          </a>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
