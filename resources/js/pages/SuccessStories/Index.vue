<template>
    <PublicLayout>
        <SeoHead
            title="Success Stories"
            description="How Traitz Academy graduates have transformed their careers — real students, real outcomes, in Web Development, Mobile Apps, Data Science and more."
        />

        <!-- Hero Section -->
        <section class="relative overflow-hidden py-20">
            <div class="absolute inset-0">
                <img
                    src="/images/academy-community/classroom/students-row-laptops.jpg"
                    alt="Traitz Academy students working at their laptops"
                    class="h-full w-full object-cover"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-br from-[#000928]/95 via-[#1a1a4e]/90 to-[#381998]/85"
                ></div>
            </div>

            <div
                class="relative mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8"
            >
                <h1
                    class="mb-6 text-4xl font-bold text-white md:text-5xl lg:text-6xl"
                >
                    Student
                    <span
                        class="bg-gradient-to-r from-[#42b6c5] to-[#9de6ee] bg-clip-text text-transparent"
                    >
                        Success Stories
                    </span>
                </h1>
                <p class="mx-auto max-w-3xl text-xl text-gray-300">
                    Discover how our graduates have transformed their careers
                    and are making an impact in their communities.
                </p>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="border-b border-gray-100 bg-white py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 text-center md:grid-cols-3">
                    <div>
                        <div class="mb-2 text-4xl font-bold text-[#42b6c5]">
                            {{ successStories.length }}+
                        </div>
                        <p class="text-gray-600">Success Stories</p>
                    </div>
                    <div>
                        <div class="mb-2 text-4xl font-bold text-[#42b6c5]">
                            95%
                        </div>
                        <p class="text-gray-600">Career Advancement</p>
                    </div>
                    <div>
                        <div class="mb-2 text-4xl font-bold text-[#42b6c5]">
                            100%
                        </div>
                        <p class="text-gray-600">Recommend Us</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Success Stories Grid -->
        <section class="bg-gray-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="successStories.length > 0"
                    class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="story in successStories"
                        :key="story.id"
                        class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg transition-all duration-300 hover:border-[#42b6c5]/30 hover:shadow-2xl"
                    >
                        <!-- Story Content -->
                        <div class="p-8">
                            <!-- Quote Icon -->
                            <div class="mb-6">
                                <svg
                                    class="h-10 w-10 text-[#42b6c5]/30"
                                    fill="currentColor"
                                    viewBox="0 0 32 32"
                                >
                                    <path
                                        d="M10 8c-3.3 0-6 2.7-6 6v10h10V14H8c0-1.1.9-2 2-2V8zm14 0c-3.3 0-6 2.7-6 6v10h10V14h-6c0-1.1.9-2 2-2V8z"
                                    />
                                </svg>
                            </div>

                            <!-- Story Text -->
                            <p
                                class="mb-6 line-clamp-4 text-lg leading-relaxed text-gray-600 italic"
                            >
                                "{{ story.story }}"
                            </p>

                            <!-- Rating -->
                            <div class="mb-6 flex text-[#42b6c5]">
                                <svg
                                    v-for="i in 5"
                                    :key="i"
                                    class="h-5 w-5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    />
                                </svg>
                            </div>

                            <!-- Author -->
                            <div
                                class="flex items-center border-t border-gray-100 pt-6"
                            >
                                <img
                                    v-if="story.image_url"
                                    :src="getImageUrl(story.image_url)"
                                    :alt="story.name"
                                    class="mr-4 h-14 w-14 rounded-full object-cover ring-2 ring-[#42b6c5]/20"
                                />
                                <div
                                    v-else
                                    class="mr-4 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-[#42b6c5] to-[#381998]"
                                >
                                    <span
                                        class="text-lg font-bold text-white"
                                        >{{ story.name.charAt(0) }}</span
                                    >
                                </div>
                                <div>
                                    <p class="font-bold text-[#000928]">
                                        {{ story.name }}
                                    </p>
                                    <p
                                        v-if="story.role || story.company"
                                        class="text-sm text-gray-500"
                                    >
                                        {{ story.role
                                        }}<span
                                            v-if="story.role && story.company"
                                        >
                                            @ </span
                                        >{{ story.company }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-20 text-center">
                    <div
                        class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100"
                    >
                        <svg
                            class="h-10 w-10 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                            />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-800">
                        No Success Stories Yet
                    </h3>
                    <p class="mb-6 text-gray-500">
                        We're collecting inspiring stories from our graduates.
                        Check back soon!
                    </p>
                    <Link
                        href="/programs"
                        class="inline-flex items-center rounded-lg bg-[#42b6c5] px-6 py-3 font-semibold text-white transition-colors hover:bg-[#35919e]"
                    >
                        Explore Programs
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="bg-gradient-to-r from-[#000928] to-[#381998] py-20">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="mb-6 text-3xl font-bold text-white md:text-4xl">
                    Ready to Write Your Success Story?
                </h2>
                <p class="mb-10 text-xl text-gray-300">
                    Join our community of successful graduates and transform
                    your career today.
                </p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <Link
                        href="/programs"
                        class="inline-flex transform items-center justify-center rounded-xl bg-[#42b6c5] px-8 py-4 text-lg font-semibold text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-[#35919e] hover:shadow-xl"
                    >
                        Apply Now
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
                                d="M13 7l5 5m0 0l-5 5m5-5H6"
                            />
                        </svg>
                    </Link>
                    <Link
                        href="/contact"
                        class="inline-flex items-center justify-center rounded-xl border-2 border-white/30 bg-white/10 px-8 py-4 text-lg font-semibold text-white transition-all duration-300 hover:bg-white/20"
                    >
                        Contact Us
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import SeoHead from '@/components/SeoHead.vue';
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
