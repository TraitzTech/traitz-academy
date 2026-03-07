<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import { useToast } from '@/composables/useToast'
import { ref, computed } from 'vue'

interface Benefit {
    title: string
    description: string
    icon: string
}

interface ScheduleItem {
    week: string
    title: string
    description: string
}

interface Faq {
    question: string
    answer: string
}

interface Swag {
    id: number
    name: string
    slug: string
    price: number
    image: string | null
    category: string
    currency: string
}

interface AiForgeEvent {
    id: number
    title: string
    slug: string
    tagline: string | null
    description: string | null
    short_description: string | null
    start_date: string | null
    end_date: string | null
    location: string | null
    is_online: boolean
    event_url: string | null
    capacity: number | null
    hero_image: string | null
    logo_image: string | null
    benefits: Benefit[] | null
    schedule: ScheduleItem[] | null
    faqs: Faq[] | null
    stats: { total_registered?: number; total_swags_sold?: number } | null
    registration_open: boolean
    swag_store_active: boolean
    registrations_count?: number
    registration_note: string | null
}

interface CurrentFee {
    amount: number
    is_early_bird: boolean
    label: string
    currency: string
    regular_fee?: number
    early_bird_fee?: number
    early_bird_deadline?: string | null
}

interface Props {
    event: AiForgeEvent
    featuredSwags: Swag[]
    isRegistered: boolean
    currentFee: CurrentFee
}

const props = defineProps<Props>()
const page = usePage()
const toast = useToast()
const openFaqIndex = ref<number | null>(null)

const user = computed(() => page.props.auth?.user)

const form = useForm({
    first_name: user.value?.name?.split(' ')[0] ?? '',
    last_name: user.value?.name?.split(' ').slice(1).join(' ') ?? '',
    email: user.value?.email ?? '',
    phone: (user.value as any)?.phone ?? '',
    country: '',
    organization: '',
    motivation: '',
})

const formatDate = (dateString: string | null) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

const formatMoney = (amount: number, currency: string = 'XAF') => {
    return new Intl.NumberFormat('en-CM', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount)
}

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}

const submitRegistration = () => {
    form.post('/ai-forge/register', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Registration successful!')
            form.reset()
        },
        onError: () => {
            toast.error('Please fix the errors and try again.')
        },
    })
}

const toggleFaq = (index: number) => {
    openFaqIndex.value = openFaqIndex.value === index ? null : index
}

const iconComponents: Record<string, string> = {
    sparkles: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z',
    users: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
    award: 'M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0016.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.003 6.003 0 01-5.54 0',
    briefcase: 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0',
    rocket: 'M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
}
</script>

<template>
    <PublicLayout>
        <Head :title="event.title + ' - ' + (event.tagline ?? 'AI Event')" />

        <!-- Hero Section -->
        <section class="relative min-h-[90vh] flex items-center overflow-hidden">
            <!-- Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052]">
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-[#42b6c5] rounded-full blur-[128px]"></div>
                    <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-[#381998] rounded-full blur-[128px]"></div>
                    <div class="absolute top-1/2 right-1/3 w-64 h-64 bg-purple-600 rounded-full blur-[100px]"></div>
                </div>
                <!-- Grid overlay -->
                <div class="absolute inset-0 opacity-5" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 50px 50px;"></div>
            </div>

            <!-- Hero image overlay if set -->
            <div v-if="event.hero_image" class="absolute inset-0">
                <img :src="getImageUrl(event.hero_image)" :alt="event.title" class="w-full h-full object-cover opacity-20" />
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
                <!-- Logo -->
                <div v-if="event.logo_image" class="mb-8 flex justify-center">
                    <img :src="getImageUrl(event.logo_image)" :alt="event.title" class="h-20 sm:h-28 w-auto" />
                </div>

                <!-- Date badge -->
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-5 py-2 mb-6">
                    <svg class="w-4 h-4 text-[#42b6c5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-white/90 text-sm font-medium">{{ formatDate(event.start_date) }} — {{ formatDate(event.end_date) }}</span>
                </div>

                <h1 class="text-5xl sm:text-7xl lg:text-8xl font-black text-white mb-4 tracking-tight">
                    <span class="bg-gradient-to-r from-[#42b6c5] via-white to-[#42b6c5] bg-clip-text text-transparent">{{ event.title }}</span>
                </h1>

                <p v-if="event.tagline" class="text-xl sm:text-2xl text-[#42b6c5] font-semibold mb-6">{{ event.tagline }}</p>

                <p v-if="event.short_description" class="text-lg text-gray-300 max-w-3xl mx-auto mb-10 leading-relaxed">{{ event.short_description }}</p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="#register" class="group relative inline-flex items-center gap-2 bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] text-white font-bold text-lg px-8 py-4 rounded-xl shadow-lg shadow-[#42b6c5]/25 hover:shadow-[#42b6c5]/40 transition-all duration-300 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                        </svg>
                        {{ currentFee.amount === 0 ? 'Register for Free' : `Register Now` }}
                    </a>
                    <Link href="/ai-forge/swags" v-if="event.swag_store_active" class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/30 text-white font-bold text-lg px-8 py-4 rounded-xl hover:bg-white/20 transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Browse Swag Store
                    </Link>
                </div>

                <!-- Stats -->
                <div v-if="event.stats" class="mt-16 grid grid-cols-2 sm:grid-cols-4 gap-6 max-w-3xl mx-auto">
                    <div class="text-center">
                        <div class="text-3xl sm:text-4xl font-black text-white">{{ event.stats.total_registered ?? event.registrations_count ?? 0 }}</div>
                        <div class="text-sm text-gray-400 mt-1">Registered</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl sm:text-4xl font-black text-white">10+</div>
                        <div class="text-sm text-gray-400 mt-1">Weeks</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl sm:text-4xl font-black text-white">4</div>
                        <div class="text-sm text-gray-400 mt-1">Months Gemini Pro</div>
                    </div>
                    <div class="text-center">
                        <div v-if="currentFee.amount === 0" class="text-3xl sm:text-4xl font-black text-[#42b6c5]">FREE</div>
                        <template v-else>
                            <div v-if="currentFee.is_early_bird" class="text-3xl sm:text-4xl font-black text-[#42b6c5]">{{ formatMoney(currentFee.amount, currentFee.currency) }}</div>
                            <div v-else class="text-3xl sm:text-4xl font-black text-[#42b6c5]">{{ formatMoney(currentFee.amount, currentFee.currency) }}</div>
                        </template>
                        <div class="text-sm text-gray-400 mt-1">
                            {{ currentFee.is_early_bird ? 'Early Bird Price' : 'Registration' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
                <svg class="w-6 h-6 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </div>
        </section>

        <!-- Benefits Section -->
        <section v-if="event.benefits?.length" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-[#000928] mb-4">Why Join AI Forge?</h2>
                    <p class="text-gray-600 text-lg max-w-2xl mx-auto">One-of-a-kind program covering everything from AI fundamentals to launching your own AI-powered business</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="(benefit, index) in event.benefits" :key="index" class="group relative p-6 rounded-2xl bg-gradient-to-br from-gray-50 to-white border border-gray-100 hover:border-[#42b6c5]/30 hover:shadow-xl hover:shadow-[#42b6c5]/5 transition-all duration-300">
                        <div class="w-14 h-14 bg-gradient-to-br from-[#42b6c5] to-[#2d9aa8] rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconComponents[benefit.icon] || iconComponents.sparkles" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-[#000928] mb-2">{{ benefit.title }}</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">{{ benefit.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Schedule Section -->
        <section v-if="event.schedule?.length" class="py-20 bg-gradient-to-br from-[#000928] to-[#0f0635]">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Program Schedule</h2>
                    <p class="text-gray-400 text-lg">{{ formatDate(event.start_date) }} — {{ formatDate(event.end_date) }}</p>
                </div>

                <div class="space-y-6">
                    <div v-for="(item, index) in event.schedule" :key="index" class="relative pl-8 sm:pl-12">
                        <!-- Timeline line -->
                        <div v-if="index < (event.schedule?.length ?? 0) - 1" class="absolute left-3 sm:left-5 top-12 bottom-0 w-0.5 bg-gradient-to-b from-[#42b6c5] to-transparent"></div>
                        <!-- Timeline dot -->
                        <div class="absolute left-0 sm:left-2 top-2 w-6 h-6 bg-[#42b6c5] rounded-full flex items-center justify-center ring-4 ring-[#42b6c5]/20">
                            <span class="text-xs font-bold text-white">{{ index + 1 }}</span>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6 hover:bg-white/10 transition-colors duration-300">
                            <div class="text-sm font-semibold text-[#42b6c5] mb-1">{{ item.week }}</div>
                            <h3 class="text-xl font-bold text-white mb-2">{{ item.title }}</h3>
                            <p class="text-gray-400">{{ item.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Swags -->
        <section v-if="featuredSwags.length && event.swag_store_active" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold text-[#000928] mb-4">Exclusive AI Forge Swag</h2>
                    <p class="text-gray-600 text-lg">Free for registrants, or grab yours from the store!</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Link v-for="swag in featuredSwags" :key="swag.id" :href="`/ai-forge/swags/${swag.slug}`" class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-50 overflow-hidden">
                            <img v-if="swag.image" :src="getImageUrl(swag.image)" :alt="swag.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-[#000928] group-hover:text-[#42b6c5] transition-colors">{{ swag.name }}</h3>
                            <p class="text-[#42b6c5] font-bold mt-1">{{ formatMoney(swag.price, swag.currency) }}</p>
                        </div>
                    </Link>
                </div>

                <div class="text-center mt-10">
                    <Link href="/ai-forge/swags" class="inline-flex items-center gap-2 bg-[#000928] text-white font-semibold px-8 py-3 rounded-xl hover:bg-[#0f0635] transition-colors">
                        View All Swag
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- FAQs Section -->
        <section v-if="event.faqs?.length" class="py-20 bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold text-[#000928] mb-4">Frequently Asked Questions</h2>
                </div>

                <div class="space-y-4">
                    <div v-for="(faq, index) in event.faqs" :key="index" class="border border-gray-200 rounded-xl overflow-hidden">
                        <button @click="toggleFaq(index)" class="w-full flex items-center justify-between p-5 text-left hover:bg-gray-50 transition-colors">
                            <span class="font-semibold text-[#000928]">{{ faq.question }}</span>
                            <svg :class="['w-5 h-5 text-gray-500 transition-transform duration-200', openFaqIndex === index ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="openFaqIndex === index" class="px-5 pb-5 text-gray-600">
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Registration Section -->
        <section id="register" class="py-20 bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Register for AI Forge</h2>
                    <p v-if="currentFee.amount === 0" class="text-gray-300 text-lg">Registration is completely free. Spots are limited!</p>
                    <template v-else>
                        <p class="text-gray-300 text-lg">Secure your spot now. Limited capacity!</p>
                        <!-- Pricing card -->
                        <div class="mt-6 inline-flex flex-col sm:flex-row gap-4 items-center justify-center">
                            <div v-if="currentFee.is_early_bird && currentFee.regular_fee" class="bg-white/5 border border-white/10 rounded-xl px-6 py-4 text-center">
                                <div class="text-sm text-gray-400 line-through">{{ formatMoney(currentFee.regular_fee, currentFee.currency) }}</div>
                                <div class="text-3xl font-black text-[#42b6c5]">{{ formatMoney(currentFee.amount, currentFee.currency) }}</div>
                                <div class="text-xs text-[#42b6c5] font-semibold mt-1">Early Bird Price</div>
                                <div v-if="currentFee.early_bird_deadline" class="text-xs text-gray-400 mt-2">Ends {{ formatDate(currentFee.early_bird_deadline) }}</div>
                            </div>
                            <div v-else class="bg-white/5 border border-white/10 rounded-xl px-6 py-4 text-center">
                                <div class="text-3xl font-black text-[#42b6c5]">{{ formatMoney(currentFee.amount, currentFee.currency) }}</div>
                                <div class="text-xs text-gray-400 mt-1">Registration Fee</div>
                            </div>
                        </div>
                    </template>
                    <p v-if="event.registration_note" class="text-[#42b6c5] mt-4 text-sm">{{ event.registration_note }}</p>
                </div>

                <!-- Already registered -->
                <div v-if="isRegistered" class="bg-green-500/10 border border-green-500/30 rounded-2xl p-8 text-center">
                    <svg class="w-16 h-16 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-2xl font-bold text-white mb-2">You're Registered!</h3>
                    <p class="text-gray-300">You're all set for AI Forge. We'll send you updates and reminders.</p>
                </div>

                <!-- Registration closed -->
                <div v-else-if="!event.registration_open" class="bg-yellow-500/10 border border-yellow-500/30 rounded-2xl p-8 text-center">
                    <h3 class="text-2xl font-bold text-white mb-2">Registration Closed</h3>
                    <p class="text-gray-300">Registration for this event is currently closed. Check back later!</p>
                </div>

                <!-- Registration form -->
                <form v-else @submit.prevent="submitRegistration" class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 sm:p-10">
                    <div class="grid sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">First Name *</label>
                            <input v-model="form.first_name" type="text" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent transition-colors" placeholder="Your first name" required />
                            <p v-if="form.errors.first_name" class="text-red-400 text-sm mt-1">{{ form.errors.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Last Name *</label>
                            <input v-model="form.last_name" type="text" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent transition-colors" placeholder="Your last name" required />
                            <p v-if="form.errors.last_name" class="text-red-400 text-sm mt-1">{{ form.errors.last_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Email Address *</label>
                            <input v-model="form.email" type="email" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent transition-colors" placeholder="your@email.com" required />
                            <p v-if="form.errors.email" class="text-red-400 text-sm mt-1">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Phone Number</label>
                            <input v-model="form.phone" type="text" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent transition-colors" placeholder="+237..." />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Country</label>
                            <input v-model="form.country" type="text" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent transition-colors" placeholder="Your country" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Organization</label>
                            <input v-model="form.organization" type="text" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent transition-colors" placeholder="Company or school" />
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-semibold text-gray-300 mb-2">What motivates you to join? (optional)</label>
                        <textarea v-model="form.motivation" rows="3" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent transition-colors resize-none" placeholder="Tell us why you're excited about AI Forge..."></textarea>
                    </div>

                    <button type="submit" :disabled="form.processing" class="w-full mt-8 bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] text-white font-bold text-lg py-4 rounded-xl hover:shadow-lg hover:shadow-[#42b6c5]/25 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ form.processing ? 'Registering...' : (currentFee.amount === 0 ? 'Register for Free' : `Register`) }}
                    </button>
                </form>
            </div>
        </section>

        <!-- Description / About Section -->
        <section v-if="event.description" class="py-20 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold text-[#000928] mb-4">About AI Forge</h2>
                </div>
                <div class="prose prose-lg prose-headings:text-[#000928] prose-h3:text-2xl prose-h3:mt-8 prose-h3:mb-3 prose-strong:text-gray-800 prose-li:marker:text-[#42b6c5] prose-a:text-[#42b6c5] max-w-none text-gray-600" v-html="event.description"></div>
            </div>
        </section>
    </PublicLayout>
</template>
