<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useToast } from '@/composables/useToast';
import PublicLayout from '@/layouts/PublicLayout.vue';

const page = usePage();
const toast = useToast();

const form = useForm({
  name: '',
  email: '',
  subject: '',
  message: '',
});

const settings = computed(() => page.props.siteSettings);

const submitForm = () => {
  form.post('/contact', {
    onSuccess: () => {
      form.reset();
      toast.success('Message sent successfully! We\'ll get back to you soon.');
    },
    onError: (errors) => {
      toast.error('Failed to send message. Please check the form and try again.');
      console.error(errors);
    },
  });
};

// Format WhatsApp link
const getWhatsAppLink = () => {
  if (!settings.value.contact_whatsapp) return '#';
  const phone = settings.value.contact_whatsapp.replace(/[^0-9]/g, '');
  return `https://wa.me/${phone}`;
};
</script>

<template>
  <PublicLayout>
    <Head :title="`Contact Us - ${$page.props.siteSettings.site_title}`" />

    <!-- Header -->
    <section class="bg-gradient-to-r from-[#000928] to-[#381998] text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Get in Touch</h1>
        <p class="text-xl text-gray-300">Have questions? We'd love to hear from you. Contact us today.</p>
      </div>
    </section>

    <!-- Contact Methods -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
          <!-- Email -->
          <div class="bg-gray-50 rounded-lg p-8 text-center">
            <div class="w-16 h-16 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Email</h3>
            <a v-if="settings.contact_email" :href="`mailto:${settings.contact_email}`" class="text-[#42b6c5] hover:text-[#35919e] font-semibold mb-2 block">
              {{ settings.contact_email }}
            </a>
            <p class="text-gray-600 text-sm">We'll respond within 24 hours</p>
          </div>

          <!-- WhatsApp -->
          <div class="bg-gray-50 rounded-lg p-8 text-center">
            <div class="w-16 h-16 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">WhatsApp</h3>
            <a v-if="settings.contact_whatsapp" :href="getWhatsAppLink()" target="_blank" class="text-[#42b6c5] hover:text-[#35919e] font-semibold mb-2 block">
              {{ settings.contact_whatsapp }}
            </a>
            <a v-else href="#" class="text-[#42b6c5] hover:text-[#35919e] font-semibold mb-2 block">
              Chat with us
            </a>
            <p class="text-gray-600 text-sm">Quick responses to your questions</p>
          </div>

          <!-- Phone -->
          <div class="bg-gray-50 rounded-lg p-8 text-center">
            <div class="w-16 h-16 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Phone</h3>
            <a v-if="settings.contact_phone" :href="`tel:${settings.contact_phone}`" class="text-[#42b6c5] hover:text-[#35919e] font-semibold mb-2 block">
              {{ settings.contact_phone }}
            </a>
            <p class="text-gray-600 text-sm">Mon-Fri, 9AM - 5PM WAT</p>
          </div>
        </div>

        <!-- Form Section -->
        <div class="max-w-2xl mx-auto bg-gray-50 rounded-lg p-8">
          <h2 class="text-2xl font-bold text-[#000928] mb-6">Send us a Message</h2>

          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Name *</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                  placeholder="Your full name"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
              </div>

              <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                  placeholder="your@email.com"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
              </div>
            </div>

            <div>
              <label for="subject" class="block text-sm font-semibold text-gray-700 mb-2">Subject *</label>
              <input
                id="subject"
                v-model="form.subject"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                placeholder="What is this regarding?"
              />
              <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
            </div>

            <div>
              <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">Message *</label>
              <textarea
                id="message"
                v-model="form.message"
                rows="6"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent outline-none transition"
                placeholder="Tell us more about your inquiry..."
              ></textarea>
              <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
            </div>

            <button
              type="submit"
              :disabled="form.processing"
              class="w-full px-6 py-3 bg-[#42b6c5] text-white rounded-lg font-bold text-lg hover:bg-[#35919e] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ form.processing ? 'Sending...' : 'Send Message' }}
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-[#000928] text-center mb-12">Frequently Asked Questions</h2>

        <div class="space-y-4">
          <details class="bg-white rounded-lg p-6 group">
            <summary class="flex justify-between items-center font-bold text-[#000928] cursor-pointer">
              What are the prerequisites to join a program?
              <span class="transform group-open:rotate-180 transition-transform">
                <svg class="w-5 h-5 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
              </span>
            </summary>
            <p class="text-gray-600 mt-4">Most of our programs are open to anyone with a passion to learn. Some programs may require basic programming knowledge or professional experience. Check the specific program details for requirements.</p>
          </details>

          <details class="bg-white rounded-lg p-6 group">
            <summary class="flex justify-between items-center font-bold text-[#000928] cursor-pointer">
              How long do programs take?
              <span class="transform group-open:rotate-180 transition-transform">
                <svg class="w-5 h-5 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
              </span>
            </summary>
            <p class="text-gray-600 mt-4">Program durations range from 4 weeks for workshops to 6 months for comprehensive bootcamps. Each program page lists the exact duration.</p>
          </details>

          <details class="bg-white rounded-lg p-6 group">
            <summary class="flex justify-between items-center font-bold text-[#000928] cursor-pointer">
              Are the programs online or in-person?
              <span class="transform group-open:rotate-180 transition-transform">
                <svg class="w-5 h-5 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
              </span>
            </summary>
            <p class="text-gray-600 mt-4">We offer both online and hybrid options. Most core programs are in-person to maximize hands-on learning, but we provide flexibility where needed.</p>
          </details>

          <details class="bg-white rounded-lg p-6 group">
            <summary class="flex justify-between items-center font-bold text-[#000928] cursor-pointer">
              What kind of certification do I get?
              <span class="transform group-open:rotate-180 transition-transform">
                <svg class="w-5 h-5 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
              </span>
            </summary>
            <p class="text-gray-600 mt-4">All successful completions earn an industry-recognized Traitz Academy certificate. More importantly, you'll have real projects in your portfolio that demonstrate your capabilities to employers.</p>
          </details>

          <details class="bg-white rounded-lg p-6 group">
            <summary class="flex justify-between items-center font-bold text-[#000928] cursor-pointer">
              Do you help with job placement?
              <span class="transform group-open:rotate-180 transition-transform">
                <svg class="w-5 h-5 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
              </span>
            </summary>
            <p class="text-gray-600 mt-4">Yes! We provide career support including resume review, interview preparation, and connect graduates with our network of hiring partners.</p>
          </details>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-16 bg-gradient-to-r from-[#000928] to-[#381998] text-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to Take the Next Step?</h2>
        <p class="text-xl text-gray-300 mb-8">Explore our programs and start your journey today</p>
        <Link
          href="/programs"
          class="inline-flex items-center px-8 py-3 bg-[#42b6c5] text-[#000928] rounded-lg font-bold text-lg hover:bg-white transition-all duration-200 transform hover:scale-105"
        >
          View Programs
        </Link>
      </div>
    </section>
  </PublicLayout>
</template>
