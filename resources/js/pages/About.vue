<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
</script>

<template>
  <PublicLayout>
    <Head title="About Us - Traitz Academy" />

    <!-- Header -->
    <section class="bg-gradient-to-r from-[#000928] to-[#381998] text-white py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">About Traitz Academy</h1>
        <p class="text-xl text-gray-300">Bridging the gap between education and industry since 2020</p>
      </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
          <div>
            <h2 class="text-3xl font-bold text-[#000928] mb-6">Our Mission</h2>
            <p class="text-gray-700 text-lg leading-relaxed mb-4">
              To develop capable, ethical, and industry-ready tech professionals through structured learning, mentorship, and hands-on experience.
            </p>
            <p class="text-gray-700 text-lg leading-relaxed">
              We are committed to closing the skills gap in Africa's technology sector by providing world-class education that emphasizes practical competence over certificates.
            </p>
          </div>

          <div>
            <h2 class="text-3xl font-bold text-[#000928] mb-6">Our Vision</h2>
            <p class="text-gray-700 text-lg leading-relaxed mb-4">
              To be Africa's leading institution for tech talent development, recognized for producing professionals who drive innovation and digital transformation across industries.
            </p>
            <p class="text-gray-700 text-lg leading-relaxed">
              We envision a future where every aspiring tech professional has access to quality education and a clear pathway into meaningful careers in the digital economy.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Why Traitz Academy -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-[#000928] text-center mb-16">Why Choose Traitz Academy?</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-white rounded-lg p-8 shadow-lg">
            <div class="w-12 h-12 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Industry Alignment</h3>
            <p class="text-gray-600">Our curriculum is designed with input from leading tech companies, ensuring you learn what the industry actually needs.</p>
          </div>

          <div class="bg-white rounded-lg p-8 shadow-lg">
            <div class="w-12 h-12 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0H9m6 0H9m6 0H9M9 8h6" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Expert Mentors</h3>
            <p class="text-gray-600">Learn from experienced professionals who bring real-world insights and proven expertise from their careers.</p>
          </div>

          <div class="bg-white rounded-lg p-8 shadow-lg">
            <div class="w-12 h-12 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 7l-9 9-7-7 7-7" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Proven Results</h3>
            <p class="text-gray-600">With 300+ successful graduates working at top companies, our track record speaks for itself.</p>
          </div>

          <div class="bg-white rounded-lg p-8 shadow-lg">
            <div class="w-12 h-12 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.402-9-1.145M16 6h.01M4 12a8 8 0 0116 0m-16 0a8 8 0 1016 0m-6-2a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Real Projects</h3>
            <p class="text-gray-600">Build a professional portfolio with real-world projects that demonstrate your capabilities to employers.</p>
          </div>

          <div class="bg-white rounded-lg p-8 shadow-lg">
            <div class="w-12 h-12 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Career Support</h3>
            <p class="text-gray-600">We don't just teach - we help you land your dream job with placement assistance and networking opportunities.</p>
          </div>

          <div class="bg-white rounded-lg p-8 shadow-lg">
            <div class="w-12 h-12 bg-[#42b6c5]/10 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-[#000928] mb-2">Flexibility</h3>
            <p class="text-gray-600">We offer multiple program formats - academic internships, professional programs, and short courses to fit your schedule.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Relationship with Traitz Tech -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-[#000928] to-[#381998] text-white rounded-lg p-12">
          <h2 class="text-3xl font-bold mb-6">Part of Traitz Tech</h2>
          <p class="text-lg text-gray-300 mb-4">
            Traitz Academy is the education and talent development arm of Traitz Tech, a leading technology consulting and software development company. This connection ensures our curriculum stays aligned with real industry needs and provides our graduates with internship and employment opportunities.
          </p>
          <p class="text-lg text-gray-300">
            Our unique position allows us to offer practical, industry-relevant training backed by a team of experienced professionals working on cutting-edge technology projects.
          </p>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-[#000928] mb-6">Ready to Join Traitz Academy?</h2>
        <p class="text-xl text-gray-600 mb-8">Start your journey towards a world-class tech career today</p>
        <Link
          href="/programs"
          class="inline-flex items-center px-8 py-3 bg-[#42b6c5] text-white rounded-lg font-bold text-lg hover:bg-[#35919e] transition-colors"
        >
          Explore Programs
          <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </Link>
      </div>
    </section>
  </PublicLayout>
</template>
