<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import ProgramSearch from '@/components/ProgramSearch.vue';
import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { STREAMING_IFRAME_ALLOW, streamingEmbedSrc } from '@/utils/videoEmbed';

interface SuccessStory {
    id: number;
    name: string;
    role: string | null;
    company: string | null;
    story: string;
    image_url: string | null;
}

interface CohortSummary {
    id: number;
    name: string;
    slug: string;
    start_date: string | null;
    end_date: string | null;
    status?: string;
    description?: string | null;
    programs?: { id: number; title: string; slug: string }[];
}

interface GalleryHighlight {
    id: number;
    title: string;
    slug: string;
    image_path: string | null;
    description: string | null;
}

interface Props {
    stats: {
        students_trained: number;
        programs_count: number;
        events_count: number;
        cohorts_run: number;
    };
    featuredPrograms: any[];
    careerOpenings: any[];
    upcomingEvents: any[];
    successStories: SuccessStory[];
    currentCohort: CohortSummary | null;
    pastCohorts: CohortSummary[];
    galleryHighlights: GalleryHighlight[];
    aiForgeEvent: {
        id: number;
        title: string;
        tagline: string | null;
        short_description: string | null;
        start_date: string | null;
        end_date: string | null;
        location: string | null;
        hero_image: string | null;
        registration_open: boolean;
        registration_fee: number;
        early_bird_fee: number | null;
        early_bird_deadline: string | null;
        currency: string;
        registrations_count: number;
    } | null;
    siteSettings: {
        youtube_video_url: string | null;
        hero_title: string;
        hero_subtitle: string;
        contact_whatsapp: string | null;
    };
}

const props = defineProps<Props>();

const openingCategoryLabels: Record<string, string> = {
    'professional-internship': 'Professional Internship',
    'job-opportunity': 'Job Opportunity',
};

// Hero title
const heroTitle = computed(
    () => props.siteSettings.hero_title || 'World-Class Tech Education',
);

const youtubeEmbedUrl = computed(() =>
    streamingEmbedSrc(props.siteSettings.youtube_video_url),
);

// Get image URL helper
const getImageUrl = (imageUrl: string | null) => {
    if (!imageUrl) return undefined;
    if (imageUrl.startsWith('http')) return imageUrl;
    return `/storage/${imageUrl}`;
};

const openingBadge = (category: string) =>
    openingCategoryLabels[category] || 'Career Opening';

const formatEventDate = (dateString: string | null) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatMoney = (amount: number, currency: string = 'XAF') => {
    return new Intl.NumberFormat('en-CM', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const aiForgeCurrentPrice = computed(() => {
    const event = props.aiForgeEvent;
    if (!event || event.registration_fee <= 0) return null;
    const isEarlyBird =
        event.early_bird_fee !== null &&
        event.early_bird_deadline !== null &&
        new Date() <= new Date(event.early_bird_deadline);
    return {
        amount: isEarlyBird ? event.early_bird_fee! : event.registration_fee,
        isEarlyBird,
        regularFee: event.registration_fee,
        currency: event.currency || 'XAF',
    };
});
</script>

<template>
    <PublicLayout>
        <SeoHead
            title="World-Class Tech Education in Cameroon"
            description="Traitz Academy trains students, interns and professionals in Web Development, Mobile Apps, AI, Cybersecurity, Data Science and more through hands-on programs, mentorship and a year-round community based at ENS Street, Bambili."
        />

        <!-- Hero Section -->
        <section
            class="relative flex min-h-[90vh] items-center overflow-hidden text-white"
        >
            <!-- Photo background -->
            <div class="absolute inset-0">
                <img
                    src="/images/academy-community/classroom/cohort-classroom-wide.jpg"
                    alt="Students learning together at Traitz Academy"
                    class="h-full w-full object-cover"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-br from-[#000928]/95 via-[#1a0a52]/90 to-[#381998]/80"
                ></div>
            </div>

            <div
                class="relative mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8"
            >
                <div class="animate-fade-in relative z-10 mb-12 text-center">
                    <h1
                        class="mb-6 text-5xl leading-tight font-bold md:text-7xl"
                    >
                        <span
                            class="bg-gradient-to-r from-white to-[#42b6c5] bg-clip-text text-transparent"
                            >{{ heroTitle }}</span
                        >
                    </h1>
                    <p
                        class="mx-auto mb-8 max-w-3xl text-xl leading-relaxed text-gray-300 md:text-2xl"
                    >
                        {{
                            siteSettings.hero_subtitle ||
                            'Bridging the gap between academic learning and real-world industry needs. Join 300+ professionals transformed through structured learning and mentorship.'
                        }}
                    </p>
                </div>

                <!-- Search Component -->
                <div class="relative z-20 mx-auto max-w-4xl">
                    <ProgramSearch />
                </div>
            </div>
        </section>

        <!-- Trust Metrics -->
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="text-center">
                        <div class="mb-2 text-5xl font-bold text-[#42b6c5]">
                            {{ stats.students_trained }}+
                        </div>
                        <p class="text-lg text-gray-600">
                            Students Trained & Interned
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="mb-2 text-5xl font-bold text-[#381998]">
                            {{ stats.programs_count }}
                        </div>
                        <p class="text-lg text-gray-600">Active Programs</p>
                    </div>
                    <div class="text-center">
                        <div class="mb-2 text-5xl font-bold text-[#000928]">
                            {{ stats.events_count }}+
                        </div>
                        <p class="text-lg text-gray-600">Upcoming Events</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Current Cohort -->
        <section v-if="currentCohort" class="border-y border-gray-100 bg-gray-50 py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="flex flex-col items-start justify-between gap-6 rounded-2xl border border-gray-200 bg-white p-8 shadow-sm md:flex-row md:items-center"
                >
                    <div>
                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-[#42b6c5]/10 px-3 py-1 text-sm font-semibold text-[#0f8a99]"
                        >
                            <span class="h-2 w-2 rounded-full bg-[#42b6c5]"></span>
                            Currently Running
                        </span>
                        <h2 class="mt-3 text-2xl font-bold text-[#000928] md:text-3xl">
                            {{ currentCohort.name }}
                        </h2>
                        <p class="mt-2 max-w-2xl text-gray-600">
                            {{
                                currentCohort.description ||
                                'A live cohort of learners working through structured curriculum, mentorship, and hands-on projects at Traitz Academy.'
                            }}
                            <span v-if="currentCohort.start_date">
                                Running from {{ formatEventDate(currentCohort.start_date) }}<span v-if="currentCohort.end_date"> to {{ formatEventDate(currentCohort.end_date) }}</span>.
                            </span>
                        </p>
                        <p v-if="stats.cohorts_run > 1" class="mt-2 text-sm text-gray-500">
                            {{ stats.cohorts_run - 1 }} cohort{{ stats.cohorts_run - 1 === 1 ? '' : 's' }} completed before this one.
                        </p>
                    </div>
                    <Link
                        href="/programs"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-[#000928] px-6 py-3 font-semibold text-white transition hover:bg-[#1a0a52]"
                    >
                        See Open Programs
                    </Link>
                </div>
            </div>
        </section>

        <!-- AI Forge Banner -->
        <section v-if="aiForgeEvent" class="relative overflow-hidden py-16">
            <div
                class="absolute inset-0 bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052]"
            >
                <div class="absolute inset-0 opacity-20">
                    <div
                        class="absolute top-1/4 left-1/4 h-72 w-72 rounded-full bg-[#42b6c5] blur-[100px]"
                    ></div>
                    <div
                        class="absolute right-1/4 bottom-1/4 h-64 w-64 rounded-full bg-[#381998] blur-[100px]"
                    ></div>
                </div>
                <div
                    class="absolute inset-0 opacity-5"
                    style="
                        background-image:
                            linear-gradient(
                                rgba(255, 255, 255, 0.1) 1px,
                                transparent 1px
                            ),
                            linear-gradient(
                                90deg,
                                rgba(255, 255, 255, 0.1) 1px,
                                transparent 1px
                            );
                        background-size: 50px 50px;
                    "
                ></div>
            </div>

            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center gap-10 lg:flex-row">
                    <!-- Left: Info -->
                    <div class="flex-1 text-center lg:text-left">
                        <div
                            class="mb-4 inline-flex items-center gap-2 rounded-full border border-[#42b6c5]/30 bg-[#42b6c5]/20 px-4 py-1.5"
                        >
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#42b6c5] opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex h-2 w-2 rounded-full bg-[#42b6c5]"
                                ></span>
                            </span>
                            <span
                                class="text-sm font-semibold text-[#42b6c5]"
                                >{{
                                    aiForgeEvent.registration_open
                                        ? 'Registration Open'
                                        : 'Coming Soon'
                                }}</span
                            >
                        </div>

                        <h2
                            class="mb-3 text-3xl font-black text-white sm:text-4xl lg:text-5xl"
                        >
                            <span
                                class="bg-gradient-to-r from-[#42b6c5] via-white to-[#42b6c5] bg-clip-text text-transparent"
                                >{{ aiForgeEvent.title }}</span
                            >
                        </h2>

                        <p
                            v-if="aiForgeEvent.tagline"
                            class="mb-3 text-lg font-semibold text-[#42b6c5]"
                        >
                            {{ aiForgeEvent.tagline }}
                        </p>

                        <p
                            v-if="aiForgeEvent.short_description"
                            class="mb-5 max-w-xl leading-relaxed text-gray-300"
                        >
                            {{ aiForgeEvent.short_description }}
                        </p>

                        <div
                            class="mb-6 flex flex-wrap items-center justify-center gap-4 text-sm text-gray-400 lg:justify-start"
                        >
                            <div
                                v-if="aiForgeEvent.start_date"
                                class="flex items-center gap-1.5"
                            >
                                <svg
                                    class="h-4 w-4 text-[#42b6c5]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                <span
                                    >{{
                                        formatEventDate(aiForgeEvent.start_date)
                                    }}
                                    —
                                    {{
                                        formatEventDate(aiForgeEvent.end_date)
                                    }}</span
                                >
                            </div>
                            <div
                                v-if="aiForgeEvent.location"
                                class="flex items-center gap-1.5"
                            >
                                <svg
                                    class="h-4 w-4 text-[#42b6c5]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                <span>{{ aiForgeEvent.location }}</span>
                            </div>
                        </div>

                        <div
                            class="flex flex-col justify-center gap-3 sm:flex-row lg:justify-start"
                        >
                            <Link
                                href="/ai-forge"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] px-7 py-3.5 font-bold text-white shadow-lg shadow-[#42b6c5]/25 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[#42b6c5]/40"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"
                                    />
                                </svg>
                                Learn More & Register
                            </Link>
                        </div>
                    </div>

                    <!-- Right: Stats / Price card -->
                    <div class="w-full shrink-0 lg:w-auto">
                        <div
                            class="mx-auto max-w-xs rounded-2xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur-sm"
                        >
                            <!-- Pricing -->
                            <div v-if="aiForgeCurrentPrice" class="mb-6">
                                <div
                                    v-if="aiForgeCurrentPrice.isEarlyBird"
                                    class="mb-1 text-sm text-gray-400 line-through"
                                >
                                    {{
                                        formatMoney(
                                            aiForgeCurrentPrice.regularFee,
                                            aiForgeCurrentPrice.currency,
                                        )
                                    }}
                                </div>
                                <div class="text-4xl font-black text-[#42b6c5]">
                                    {{
                                        formatMoney(
                                            aiForgeCurrentPrice.amount,
                                            aiForgeCurrentPrice.currency,
                                        )
                                    }}
                                </div>
                                <div class="mt-1 text-sm text-gray-400">
                                    {{
                                        aiForgeCurrentPrice.isEarlyBird
                                            ? 'Early Bird Price'
                                            : 'Registration Fee'
                                    }}
                                </div>
                            </div>
                            <div v-else class="mb-6">
                                <div class="text-4xl font-black text-[#42b6c5]">
                                    FREE
                                </div>
                                <div class="mt-1 text-sm text-gray-400">
                                    Registration
                                </div>
                            </div>

                            <!-- Mini stats -->
                            <div
                                class="grid grid-cols-2 gap-4 border-t border-white/10 pt-6"
                            >
                                <div>
                                    <div class="text-2xl font-bold text-white">
                                        {{
                                            aiForgeEvent.registrations_count ??
                                            0
                                        }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Registered
                                    </div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-white">
                                        10+
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Weeks
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Internship Opportunity -->
        <section class="border-y border-gray-200 bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-10 max-w-4xl text-center">
                    <h2
                        class="mb-3 text-3xl font-bold text-[#000928] md:text-4xl"
                    >
                        Internship Opportunity – Traitz Tech
                    </h2>
                    <p class="text-lg text-gray-600">
                        We are recruiting interns for a 6-month professional
                        internship with mentorship, real-world projects, and
                        stipends.
                    </p>
                </div>

                <div
                    class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4"
                >
                    <div
                        class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-center font-semibold text-[#000928]"
                    >
                        Laravel Development
                    </div>
                    <div
                        class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-center font-semibold text-[#000928]"
                    >
                        Flutter Development
                    </div>
                    <div
                        class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-center font-semibold text-[#000928]"
                    >
                        UI/UX Design
                    </div>
                    <div
                        class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-center font-semibold text-[#000928]"
                    >
                        Frontend Web Development
                    </div>
                </div>

                <div
                    v-if="careerOpenings.length > 0"
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="opening in careerOpenings"
                        :key="opening.id"
                        class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <span
                            class="mb-3 inline-block rounded-full bg-[#42b6c5]/10 px-3 py-1 text-xs font-semibold text-[#42b6c5]"
                            >{{ openingBadge(opening.category) }}</span
                        >
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            {{ opening.title }}
                        </h3>
                        <p class="mb-4 line-clamp-2 text-sm text-gray-600">
                            {{ opening.description }}
                        </p>
                        <Link
                            :href="`/programs/${opening.slug}`"
                            class="inline-flex items-center font-semibold text-[#42b6c5] hover:text-[#35919e]"
                        >
                            View & Apply
                            <svg
                                class="ml-2 h-4 w-4"
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
                </div>
                <div v-else class="text-center">
                    <Link
                        href="/programs?category=professional-internship"
                        class="inline-flex items-center rounded-lg bg-[#42b6c5] px-6 py-3 font-semibold text-white transition-colors hover:bg-[#35919e]"
                    >
                        Explore Internship Openings
                    </Link>
                </div>
            </div>
        </section>

        <!-- Video Section -->
        <section v-if="youtubeEmbedUrl" class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2
                        class="mb-4 text-4xl font-bold text-[#000928] md:text-5xl"
                    >
                        See Our Academy in Action
                    </h2>
                    <p class="mx-auto max-w-2xl text-lg text-gray-600">
                        Discover what makes Traitz Academy the leading tech
                        education platform
                    </p>
                </div>

                <div class="relative overflow-hidden rounded-2xl shadow-2xl">
                    <!-- Video container with aspect ratio -->
                    <div
                        class="relative w-full bg-black"
                        style="padding-bottom: 56.25%"
                    >
                        <iframe
                            class="absolute inset-0 h-full w-full"
                            :src="youtubeEmbedUrl"
                            title="Traitz Academy Overview"
                            frameborder="0"
                            :allow="STREAMING_IFRAME_ALLOW"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                        ></iframe>
                    </div>
                </div>

                <!-- Video features -->
                <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div class="text-center">
                        <div
                            class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/10"
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
                                    d="M14.828 14.828a4 4 0 01-5.656 0M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-bold text-[#000928]">
                            Industry Experts
                        </h3>
                        <p class="text-sm text-gray-600">
                            Learn from professionals with decades of experience
                        </p>
                    </div>
                    <div class="text-center">
                        <div
                            class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-lg bg-[#381998]/10"
                        >
                            <svg
                                class="h-6 w-6 text-[#381998]"
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
                        <h3 class="mb-2 font-bold text-[#000928]">
                            Hands-On Training
                        </h3>
                        <p class="text-sm text-gray-600">
                            Build real projects with practical, applicable
                            skills
                        </p>
                    </div>
                    <div class="text-center">
                        <div
                            class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/10"
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
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-bold text-[#000928]">
                            Proven Results
                        </h3>
                        <p class="text-sm text-gray-600">
                            300+ graduates successfully placed in top companies
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Programs -->
        <section id="featured" class="bg-gray-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2
                        class="mb-4 text-4xl font-bold text-[#000928] md:text-5xl"
                    >
                        Featured Programs
                    </h2>
                    <p class="mx-auto max-w-2xl text-lg text-gray-600">
                        Discover our most popular and highly-rated training
                        programs
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div
                        v-for="program in featuredPrograms"
                        :key="program.id"
                        class="transform overflow-hidden rounded-lg bg-white shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl"
                    >
                        <div
                            class="relative h-48 overflow-hidden bg-gradient-to-br from-[#381998] to-[#42b6c5]"
                        >
                            <img
                                :src="'/storage/' + program.image_url"
                                :alt="program.title"
                                class="h-full w-full object-cover opacity-80"
                            />
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"
                            ></div>
                        </div>
                        <div class="p-6">
                            <div
                                class="mb-2 inline-block rounded-full bg-[#42b6c5]/10 px-3 py-1 text-sm font-semibold text-[#42b6c5]"
                            >
                                {{
                                    program.category
                                        .replace('-', ' ')
                                        .toUpperCase()
                                }}
                            </div>
                            <h3 class="mb-2 text-xl font-bold text-[#000928]">
                                {{ program.title }}
                            </h3>
                            <p class="mb-4 line-clamp-2 text-gray-600">
                                {{ program.description }}
                            </p>
                            <div class="mb-6 space-y-2 text-sm text-gray-600">
                                <p>
                                    <span class="font-semibold">Duration:</span>
                                    {{ program.duration }}
                                </p>
                                <p>
                                    <span class="font-semibold">Capacity:</span>
                                    {{ program.capacity }} participants
                                </p>
                            </div>
                            <Link
                                :href="`/programs/${program.slug}`"
                                class="inline-block w-full rounded-lg bg-[#000928] px-4 py-2 text-center font-semibold text-white transition-colors hover:bg-[#381998]"
                            >
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="mt-12 text-center">
                    <Link
                        href="/programs"
                        class="inline-flex items-center rounded-lg bg-[#42b6c5] px-8 py-3 font-bold text-white transition-colors hover:bg-[#381998]"
                    >
                        View All Programs
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
            </div>
        </section>

        <!-- Life at Traitz Academy -->
        <section v-if="galleryHighlights.length" class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2
                        class="mb-4 text-4xl font-bold text-[#000928] md:text-5xl"
                    >
                        Life at Traitz Academy
                    </h2>
                    <p class="mx-auto max-w-2xl text-lg text-gray-600">
                        Workshops, classroom sessions, and community moments
                        from our office on ENS Street, Bambili.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <Link
                        v-for="(item, index) in galleryHighlights.slice(0, 8)"
                        :key="item.id"
                        href="/gallery"
                        :class="[
                            'group relative block overflow-hidden rounded-xl bg-gray-100',
                            index === 0 ? 'col-span-2 row-span-2' : '',
                        ]"
                    >
                        <img
                            :src="getImageUrl(item.image_path)"
                            :alt="item.title"
                            class="h-full min-h-[9rem] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            loading="lazy"
                        />
                        <div
                            class="absolute inset-0 flex items-end bg-gradient-to-t from-black/60 via-black/0 to-black/0 p-3 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <span class="text-sm font-medium text-white">{{ item.title }}</span>
                        </div>
                    </Link>
                </div>

                <div class="mt-8 text-center">
                    <Link
                        href="/gallery"
                        class="inline-flex items-center gap-2 font-semibold text-[#42b6c5] hover:text-[#35919e]"
                    >
                        View Full Gallery →
                    </Link>
                </div>
            </div>
        </section>

        <!-- Resources -->
        <section class="bg-gray-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center">
                    <h2
                        class="mb-4 text-4xl font-bold text-[#000928] md:text-5xl"
                    >
                        Explore More
                    </h2>
                    <p class="mx-auto max-w-2xl text-lg text-gray-600">
                        Browse our dedicated pages for media highlights and
                        learning materials.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-8 transition-shadow hover:shadow-lg"
                    >
                        <h3 class="mb-2 text-2xl font-bold text-[#000928]">
                            Gallery
                        </h3>
                        <p class="mb-6 text-gray-600">
                            View images and videos from events, classes, and
                            community activities.
                        </p>
                        <Link
                            href="/gallery"
                            class="inline-flex items-center rounded-lg bg-[#42b6c5] px-5 py-2.5 font-semibold text-white transition-colors hover:bg-[#35919e]"
                        >
                            Open Gallery
                        </Link>
                    </div>

                    <div
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-8 transition-shadow hover:shadow-lg"
                    >
                        <h3 class="mb-2 text-2xl font-bold text-[#000928]">
                            Learning Resources
                        </h3>
                        <p class="mb-6 text-gray-600">
                            Access documents, videos, writings, and curated
                            external resources.
                        </p>
                        <Link
                            href="/resources"
                            class="inline-flex items-center rounded-lg bg-[#381998] px-5 py-2.5 font-semibold text-white transition-colors hover:bg-[#2d1377]"
                        >
                            Open Resources
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Teaching Model -->
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2
                        class="mb-4 text-4xl font-bold text-[#000928] md:text-5xl"
                    >
                        How We Teach
                    </h2>
                    <p class="mx-auto max-w-2xl text-lg text-gray-600">
                        A unique approach combining mentorship, real-world
                        projects, and industry alignment
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4"
                >
                    <div class="text-center">
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-8 w-8 text-[#42b6c5]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Project-Based
                        </h3>
                        <p class="text-gray-600">
                            Learn by building real projects with industry
                            relevance and practical application
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-[#381998]/10"
                        >
                            <svg
                                class="h-8 w-8 text-[#381998]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0H9m6 0H9m6 0H9m6 0H9M9 8h6"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Expert Mentorship
                        </h3>
                        <p class="text-gray-600">
                            Get guided by industry professionals with real-world
                            experience
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-8 w-8 text-[#42b6c5]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Performance Evaluation
                        </h3>
                        <p class="text-gray-600">
                            Regular feedback and assessments to track progress
                            and growth
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-[#381998]/10"
                        >
                            <svg
                                class="h-8 w-8 text-[#381998]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Industry Aligned
                        </h3>
                        <p class="text-gray-600">
                            Curriculum designed with input from leading tech
                            companies
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How to Apply Section -->
        <section
            class="bg-gradient-to-br from-[#000928] via-[#1a0a52] to-[#381998] py-20 text-white"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-4xl font-bold md:text-5xl">
                        How to Apply
                    </h2>
                    <p class="mx-auto max-w-2xl text-lg text-gray-300">
                        Getting started is easy! Follow these simple steps to
                        begin your learning journey
                    </p>
                </div>

                <div class="relative">
                    <!-- Connection Line (Desktop) -->
                    <div
                        class="absolute top-24 left-1/2 hidden h-1 w-3/4 -translate-x-1/2 transform rounded-full bg-gradient-to-r from-[#42b6c5]/30 via-[#42b6c5] to-[#42b6c5]/30 lg:block"
                    ></div>

                    <div
                        class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4"
                    >
                        <!-- Step 1 -->
                        <div class="group relative text-center">
                            <div
                                class="relative z-10 mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-[#42b6c5] to-[#35919e] shadow-lg shadow-[#42b6c5]/30 transition-transform duration-300 group-hover:scale-110"
                            >
                                <span class="text-3xl font-bold text-white"
                                    >1</span
                                >
                            </div>
                            <div
                                class="rounded-xl bg-white/10 p-6 backdrop-blur-sm transition-colors hover:bg-white/15"
                            >
                                <div
                                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/20"
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
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <h3 class="mb-3 text-xl font-bold">
                                    Browse Programs
                                </h3>
                                <p class="text-sm text-gray-300">
                                    Explore our range of training programs and
                                    internships to find the perfect fit for your
                                    career goals.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="group relative text-center">
                            <div
                                class="relative z-10 mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-[#42b6c5] to-[#35919e] shadow-lg shadow-[#42b6c5]/30 transition-transform duration-300 group-hover:scale-110"
                            >
                                <span class="text-3xl font-bold text-white"
                                    >2</span
                                >
                            </div>
                            <div
                                class="rounded-xl bg-white/10 p-6 backdrop-blur-sm transition-colors hover:bg-white/15"
                            >
                                <div
                                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/20"
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
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                </div>
                                <h3 class="mb-3 text-xl font-bold">
                                    Create Account
                                </h3>
                                <p class="text-sm text-gray-300">
                                    Sign up for a free account to access the
                                    application portal and track your progress.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="group relative text-center">
                            <div
                                class="relative z-10 mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-[#42b6c5] to-[#35919e] shadow-lg shadow-[#42b6c5]/30 transition-transform duration-300 group-hover:scale-110"
                            >
                                <span class="text-3xl font-bold text-white"
                                    >3</span
                                >
                            </div>
                            <div
                                class="rounded-xl bg-white/10 p-6 backdrop-blur-sm transition-colors hover:bg-white/15"
                            >
                                <div
                                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/20"
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
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                </div>
                                <h3 class="mb-3 text-xl font-bold">
                                    Submit Application
                                </h3>
                                <p class="text-sm text-gray-300">
                                    Fill out the application form with your
                                    details and submit it for review by our
                                    team.
                                </p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="group relative text-center">
                            <div
                                class="relative z-10 mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-[#42b6c5] to-[#35919e] shadow-lg shadow-[#42b6c5]/30 transition-transform duration-300 group-hover:scale-110"
                            >
                                <span class="text-3xl font-bold text-white"
                                    >4</span
                                >
                            </div>
                            <div
                                class="rounded-xl bg-white/10 p-6 backdrop-blur-sm transition-colors hover:bg-white/15"
                            >
                                <div
                                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-[#42b6c5]/20"
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
                                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                                        />
                                    </svg>
                                </div>
                                <h3 class="mb-3 text-xl font-bold">
                                    Start Learning
                                </h3>
                                <p class="text-sm text-gray-300">
                                    Once accepted, begin your journey with
                                    access to world-class training and
                                    mentorship.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12 text-center">
                    <Link
                        href="/programs"
                        class="inline-flex transform items-center rounded-lg bg-[#42b6c5] px-8 py-4 text-lg font-bold text-[#000928] shadow-lg shadow-[#42b6c5]/30 transition-all duration-200 hover:scale-105 hover:bg-white"
                    >
                        Get Started Today
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
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section v-if="successStories.length > 0" class="bg-gray-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2
                        class="mb-4 text-4xl font-bold text-[#000928] md:text-5xl"
                    >
                        Student Success Stories
                    </h2>
                    <p class="text-lg text-gray-600">
                        Real stories from graduates transforming their careers
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div
                        v-for="story in successStories"
                        :key="story.id"
                        class="rounded-lg bg-white p-8 shadow-lg transition-shadow hover:shadow-xl"
                    >
                        <div class="mb-4 flex items-center">
                            <img
                                v-if="story.image_url"
                                :src="getImageUrl(story.image_url)"
                                :alt="story.name"
                                class="mr-4 h-14 w-14 rounded-full object-cover"
                            />
                            <div
                                v-else
                                class="mr-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-200"
                            >
                                <svg
                                    class="h-7 w-7 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-[#000928]">
                                    {{ story.name }}
                                </p>
                                <p
                                    v-if="story.role || story.company"
                                    class="text-sm text-gray-600"
                                >
                                    {{ story.role
                                    }}<span v-if="story.role && story.company">
                                        @ </span
                                    >{{ story.company }}
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 flex text-[#42b6c5]">
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
                        <p class="text-gray-600 italic">"{{ story.story }}"</p>
                    </div>
                </div>

                <!-- View More Link -->
                <div class="mt-12 text-center">
                    <Link
                        href="/success-stories"
                        class="inline-flex items-center rounded-xl border-2 border-[#42b6c5] bg-white px-8 py-4 text-lg font-semibold text-[#42b6c5] shadow-lg transition-all duration-300 hover:bg-[#42b6c5] hover:text-white hover:shadow-xl"
                    >
                        View All Success Stories
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
                </div>
            </div>
        </section>

        <!-- Upcoming Events -->
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2
                        class="mb-4 text-4xl font-bold text-[#000928] md:text-5xl"
                    >
                        Upcoming Events
                    </h2>
                    <p class="text-lg text-gray-600">
                        Join our community for webinars, workshops, and
                        networking
                    </p>
                </div>

                <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div
                        v-for="event in upcomingEvents"
                        :key="event.id"
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white transition-all hover:border-[#42b6c5] hover:shadow-lg"
                    >
                        <div
                            class="bg-gradient-to-r from-[#381998] to-[#42b6c5] p-6 text-white"
                        >
                            <div class="mb-2 text-4xl font-bold">
                                {{ new Date(event.event_date).getDate() }}
                            </div>
                            <p class="text-white/80">
                                {{
                                    new Date(
                                        event.event_date,
                                    ).toLocaleDateString('en-US', {
                                        month: 'long',
                                        year: 'numeric',
                                    })
                                }}
                            </p>
                        </div>
                        <div class="p-6">
                            <h3 class="mb-2 text-xl font-bold text-[#000928]">
                                {{ event.title }}
                            </h3>
                            <p class="mb-4 text-sm text-gray-600">
                                {{ event.description.substring(0, 100) }}...
                            </p>
                            <div
                                v-if="event.location || event.is_online"
                                class="mb-4 text-sm text-gray-600"
                            >
                                <span
                                    v-if="event.is_online"
                                    class="inline-block rounded bg-[#42b6c5]/10 px-2 py-1 text-[#42b6c5]"
                                    >Online</span
                                >
                                <span
                                    v-if="event.location"
                                    class="text-gray-600"
                                    >📍 {{ event.location }}</span
                                >
                            </div>
                            <Link
                                :href="`/events/${event.slug}`"
                                class="inline-block rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#381998]"
                            >
                                Learn More
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <Link
                        href="/events"
                        class="inline-flex items-center rounded-lg bg-[#000928] px-8 py-3 font-bold text-white transition-colors hover:bg-[#381998]"
                    >
                        See All Events
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
            </div>
        </section>

        <!-- Final CTA -->
        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] py-20 text-white"
        >
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="mb-6 text-4xl font-bold md:text-5xl">
                    Ready to Transform Your Career?
                </h2>
                <p class="mb-8 text-xl text-gray-300">
                    Join hundreds of professionals who have achieved their goals
                    through our programs
                </p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <Link
                        href="/programs"
                        class="inline-flex transform items-center justify-center rounded-lg bg-[#42b6c5] px-8 py-3 text-lg font-bold text-[#000928] transition-all duration-200 hover:scale-105 hover:bg-white"
                    >
                        Start Your Journey
                    </Link>
                    <a
                        :href="`https://wa.me/${(siteSettings.contact_whatsapp || '').replace(/[^0-9]/g, '')}`"
                        target="_blank"
                        class="inline-flex items-center justify-center rounded-lg border-2 border-white px-8 py-3 text-lg font-bold text-white transition-all duration-200 hover:bg-white hover:text-[#000928]"
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
</style>
