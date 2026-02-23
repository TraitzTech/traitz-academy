<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

import { useToast } from '@/composables/useToast';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Program {
  id: number;
  title: string;
  slug: string;
  category: string;
  description: string;
}

interface Props {
  program: Program;
}

const props = defineProps<Props>();
const page = usePage();
const toast = useToast();

const isAcademic = computed(() => props.program.category === 'academic-internship');
const isJobOpportunity = computed(() => props.program.category === 'job-opportunity');
const requiresCv = computed(() => ['professional-internship', 'job-opportunity'].includes(props.program.category));

const form = useForm({
  program_id: props.program.id,
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  country: '',
  bio: '',
  education_level: isAcademic.value ? undefined : '',
  institution_name: isAcademic.value ? '' : undefined,
  academic_duration: isAcademic.value ? '' : undefined,
  motivation: '',
  experience: '',
  cv: null as File | null,
  internship_letter: null as File | null,
});

// Pre-populate form fields if user is authenticated
onMounted(() => {
  const authUser = page.props.auth?.user;
  if (authUser) {
    form.first_name = authUser.first_name || '';
    form.last_name = authUser.last_name || '';
    form.email = authUser.email || '';
    form.phone = authUser.phone || '';
  }
});

const submit = () => {
  form.post('/applications', {
    forceFormData: true,
    onSuccess: () => {
      toast.success('Thank you for applying! Check your email for confirmation.');
    },
    onError: () => {
      toast.error('Failed to submit your application. Please try again.');
    },
  });
};
</script>

<template>
  <PublicLayout>
    <Head :title="`Apply for ${program.title} - Traitz Academy`" />

    <!-- Header -->
    <section class="bg-gradient-to-r from-[#000928] to-[#381998] text-white py-12">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <Link href="/programs" class="inline-flex items-center text-[#42b6c5] hover:text-white mb-4">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Programs
        </Link>
        <h1 class="text-4xl font-bold mb-2">Apply for {{ program.title }}</h1>
        <p class="text-xl text-gray-300">Complete the form below to submit your application</p>
      </div>
    </section>

    <!-- Form Section -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
          <form @submit.prevent="submit" class="space-y-8">
            <!-- Personal Information -->
            <div>
              <h2 class="text-2xl font-bold text-[#000928] mb-6">Personal Information</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-2">First Name *</label>
                  <input
                    id="first_name"
                    v-model="form.first_name"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                    :class="{ 'border-red-500': form.errors.first_name }"
                  />
                  <p v-if="form.errors.first_name" class="text-red-500 text-sm mt-1">{{ form.errors.first_name }}</p>
                </div>

                <div>
                  <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-2">Last Name *</label>
                  <input
                    id="last_name"
                    v-model="form.last_name"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                    :class="{ 'border-red-500': form.errors.last_name }"
                  />
                  <p v-if="form.errors.last_name" class="text-red-500 text-sm mt-1">{{ form.errors.last_name }}</p>
                </div>

                <div>
                  <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address *</label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                    :class="{ 'border-red-500': form.errors.email }"
                  />
                  <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                  <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number *</label>
                  <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                    :class="{ 'border-red-500': form.errors.phone }"
                  />
                  <p v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</p>
                </div>

                <div>
                  <label for="country" class="block text-sm font-semibold text-gray-700 mb-2">Country *</label>
                  <input
                    id="country"
                    v-model="form.country"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                    :class="{ 'border-red-500': form.errors.country }"
                  />
                  <p v-if="form.errors.country" class="text-red-500 text-sm mt-1">{{ form.errors.country }}</p>
                </div>

                <div>
                  <label for="bio" class="block text-sm font-semibold text-gray-700 mb-2">Bio</label>
                  <input
                    id="bio"
                    v-model="form.bio"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                  />
                </div>
              </div>
            </div>

            <!-- Education Information -->
            <div v-if="!isAcademic">
              <h2 class="text-2xl font-bold text-[#000928] mb-6">Education Background</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="education_level" class="block text-sm font-semibold text-gray-700 mb-2">Education Level</label>
                  <select
                    id="education_level"
                    v-model="form.education_level"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                  >
                    <option value="">Select Level</option>
                    <option value="High School">High School</option>
                    <option value="Bachelor">Bachelor Degree</option>
                    <option value="Master">Master Degree</option>
                    <option value="Doctorate">Doctorate</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Academic Internship Fields -->
            <div v-if="isAcademic">
              <h2 class="text-2xl font-bold text-[#000928] mb-6">Academic Information</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="institution_name" class="block text-sm font-semibold text-gray-700 mb-2">Institution Name *</label>
                  <input
                    id="institution_name"
                    v-model="form.institution_name"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                  />
                </div>

                <div>
                  <label for="academic_duration" class="block text-sm font-semibold text-gray-700 mb-2">Academic Duration *</label>
                  <select
                    id="academic_duration"
                    v-model="form.academic_duration"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                  >
                    <option value="">Select Duration</option>
                    <option value="1 semester">1 Semester</option>
                    <option value="2 semesters">2 Semesters</option>
                    <option value="1 year">1 Year</option>
                    <option value="2 years">2 Years</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Motivation & Experience -->
            <div>
              <h2 class="text-2xl font-bold text-[#000928] mb-6">Why Do You Want to Join?</h2>
              <div class="space-y-6">
                <div>
                  <label for="motivation" class="block text-sm font-semibold text-gray-700 mb-2">Tell us your motivation * (minimum 20 characters)</label>
                  <textarea
                    id="motivation"
                    v-model="form.motivation"
                    required
                    rows="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                    :class="{ 'border-red-500': form.errors.motivation }"
                    placeholder="Share your goals, aspirations, and what you hope to achieve..."
                  ></textarea>
                  <p v-if="form.errors.motivation" class="text-red-500 text-sm mt-1">{{ form.errors.motivation }}</p>
                </div>

                <div>
                  <label for="experience" class="block text-sm font-semibold text-gray-700 mb-2">Relevant Experience (Optional)</label>
                  <textarea
                    id="experience"
                    v-model="form.experience"
                    rows="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                    placeholder="Tell us about any relevant work experience, projects, or skills..."
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- Application Documents -->
            <div>
              <h2 class="text-2xl font-bold text-[#000928] mb-6">Application Documents</h2>
              <div class="space-y-4">
                <div>
                  <label for="cv" class="block text-sm font-semibold text-gray-700 mb-2">
                    CV / Resume <span v-if="requiresCv">*</span><span v-else>(Optional)</span>
                  </label>
                  <p class="text-sm text-gray-500 mb-3">
                    Upload your most recent CV in PDF, DOC, or DOCX format.
                  </p>
                  <input
                    id="cv"
                    type="file"
                    :required="requiresCv"
                    accept=".pdf,.doc,.docx"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition file:mr-4 file:py-1 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-[#42b6c5] file:text-white hover:file:bg-[#35919e]"
                    :class="{ 'border-red-500': form.errors.cv }"
                    @input="form.cv = ($event.target as HTMLInputElement).files?.[0] ?? null"
                  />
                  <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, DOC, DOCX. Max 5MB.</p>
                  <p v-if="form.errors.cv" class="text-red-500 text-sm mt-1">{{ form.errors.cv }}</p>
                </div>

                <div>
                  <label for="internship_letter" class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ isAcademic ? 'Upload Internship Letter (Optional)' : 'Additional Supporting Document (Optional)' }}
                  </label>
                  <p class="text-sm text-gray-500 mb-3">
                    {{ isAcademic
                      ? 'If your school has issued an internship letter, upload it here so we can keep it on file.'
                      : 'You may upload any supporting document relevant to your application.' }}
                  </p>
                  <input
                    id="internship_letter"
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition file:mr-4 file:py-1 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-[#42b6c5] file:text-white hover:file:bg-[#35919e]"
                    :class="{ 'border-red-500': form.errors.internship_letter }"
                    @input="form.internship_letter = ($event.target as HTMLInputElement).files?.[0] ?? null"
                  />
                  <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG, DOC, DOCX. Max 5MB.</p>
                  <p v-if="form.errors.internship_letter" class="text-red-500 text-sm mt-1">{{ form.errors.internship_letter }}</p>
                </div>

                <div v-if="isJobOpportunity" class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                  Job applications are reviewed based on profile fit, interview performance, and role requirements.
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4">
              <button
                type="submit"
                :disabled="form.processing"
                class="flex-1 px-6 py-3 bg-[#42b6c5] text-white rounded-lg font-bold text-lg hover:bg-[#35919e] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <span v-if="form.processing" class="inline-flex items-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Submitting...
                </span>
                <span v-else>Submit Application</span>
              </button>
              <Link
                href="/programs"
                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-bold text-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </Link>
            </div>

            <!-- Privacy Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
              By submitting this application, you agree to our privacy policy and terms of service. We'll use your information to process your application and contact you about your enrollment.
            </div>
          </form>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
