<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <SeoHead
            title="About Us"
            description="Traitz Academy is a technology education and talent development institution based at ENS Street, Bambili — training students and professionals through hands-on programs, mentorship and industry-aligned curricula."
        />

        <!-- Header -->
        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] py-16 text-white"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">
                    About Traitz Academy
                </h1>
                <p class="text-xl text-gray-300">
                    Bridging the gap between education and industry since 2020
                </p>
            </div>
        </section>

        <!-- Mission & Vision -->
        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-12 md:grid-cols-2">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold text-[#000928]">
                            Our Mission
                        </h2>
                        <p class="mb-4 text-lg leading-relaxed text-gray-700">
                            To develop capable, ethical, and industry-ready tech
                            professionals through structured learning,
                            mentorship, and hands-on experience.
                        </p>
                        <p class="text-lg leading-relaxed text-gray-700">
                            We are committed to closing the skills gap in
                            Africa's technology sector by providing world-class
                            education that emphasizes practical competence over
                            certificates.
                        </p>
                    </div>

                    <div>
                        <h2 class="mb-6 text-3xl font-bold text-[#000928]">
                            Our Vision
                        </h2>
                        <p class="mb-4 text-lg leading-relaxed text-gray-700">
                            To be Africa's leading institution for tech talent
                            development, recognized for producing professionals
                            who drive innovation and digital transformation
                            across industries.
                        </p>
                        <p class="text-lg leading-relaxed text-gray-700">
                            We envision a future where every aspiring tech
                            professional has access to quality education and a
                            clear pathway into meaningful careers in the digital
                            economy.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Traitz Academy -->
        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-16 text-center text-4xl font-bold text-[#000928]">
                    Why Choose Traitz Academy?
                </h2>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="rounded-lg bg-white p-8 shadow-lg">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-6 w-6 text-[#42b6c5]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Industry Alignment
                        </h3>
                        <p class="text-gray-600">
                            Our curriculum is designed with input from leading
                            tech companies, ensuring you learn what the industry
                            actually needs.
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-8 shadow-lg">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-6 w-6 text-[#42b6c5]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0H9m6 0H9m6 0H9M9 8h6"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Expert Mentors
                        </h3>
                        <p class="text-gray-600">
                            Learn from experienced professionals who bring
                            real-world insights and proven expertise from their
                            careers.
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-8 shadow-lg">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-6 w-6 text-[#42b6c5]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m7 7l-9 9-7-7 7-7"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Proven Results
                        </h3>
                        <p class="text-gray-600">
                            With 300+ successful graduates working at top
                            companies, our track record speaks for itself.
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-8 shadow-lg">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-6 w-6 text-[#42b6c5]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.402-9-1.145M16 6h.01M4 12a8 8 0 0116 0m-16 0a8 8 0 1016 0m-6-2a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Real Projects
                        </h3>
                        <p class="text-gray-600">
                            Build a professional portfolio with real-world
                            projects that demonstrate your capabilities to
                            employers.
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-8 shadow-lg">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-6 w-6 text-[#42b6c5]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Career Support
                        </h3>
                        <p class="text-gray-600">
                            We don't just teach - we help you land your dream
                            job with placement assistance and networking
                            opportunities.
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-8 shadow-lg">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-6 w-6 text-[#42b6c5]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Flexibility
                        </h3>
                        <p class="text-gray-600">
                            We offer multiple program formats - academic
                            internships, professional programs, and short
                            courses to fit your schedule.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Relationship with Traitz Tech -->
        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="rounded-lg bg-gradient-to-r from-[#000928] to-[#381998] p-12 text-white"
                >
                    <h2 class="mb-6 text-3xl font-bold">Part of Traitz Tech</h2>
                    <p class="mb-4 text-lg text-gray-300">
                        Traitz Academy is the education and talent development
                        arm of Traitz Tech, a leading technology consulting and
                        software development company. This connection ensures
                        our curriculum stays aligned with real industry needs
                        and provides our graduates with internship and
                        employment opportunities.
                    </p>
                    <p class="text-lg text-gray-300">
                        Our unique position allows us to offer practical,
                        industry-relevant training backed by a team of
                        experienced professionals working on cutting-edge
                        technology projects.
                    </p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="mb-6 text-4xl font-bold text-[#000928]">
                    Ready to Join Traitz Academy?
                </h2>
                <p class="mb-8 text-xl text-gray-600">
                    Start your journey towards a world-class tech career today
                </p>
                <Link
                    href="/programs"
                    class="inline-flex items-center rounded-lg bg-[#42b6c5] px-8 py-3 text-lg font-bold text-white transition-colors hover:bg-[#35919e]"
                >
                    Explore Programs
                    <svg
                        class="ml-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
