<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Form } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface AiForgeEvent {
    id: number
    title: string
    slug: string
    tagline: string | null
    description: string | null
    short_description: string | null
    start_date: string
    end_date: string
    location: string | null
    capacity: number | null
    hero_image: string | null
    logo_image: string | null
    benefits: { icon: string; title: string; description: string }[]
    schedule: { date: string; title: string; description: string; type: string }[]
    sponsors: { name: string; logo: string; url: string }[]
    faqs: { question: string; answer: string }[]
    stats: Record<string, any>
    is_active: boolean
    registration_open: boolean
    registration_fee: number
    early_bird_fee: number | null
    early_bird_deadline: string | null
    currency: string
    swag_store_active: boolean
}

interface Props {
    event: AiForgeEvent | null
    registrationCount: number
    orderCount: number
    totalRevenue: number
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const editing = ref(!!props.event)
const showJsonEditor = ref<string | null>(null)

// Form fields
const title = ref(props.event?.title ?? '')
const tagline = ref(props.event?.tagline ?? '')
const description = ref(props.event?.description ?? '')
const shortDescription = ref(props.event?.short_description ?? '')
const startDate = ref(props.event?.start_date ? props.event.start_date.substring(0, 10) : '')
const endDate = ref(props.event?.end_date ? props.event.end_date.substring(0, 10) : '')
const location = ref(props.event?.location ?? '')
const capacity = ref<number | null>(props.event?.capacity ?? null)
const registrationFee = ref<number>(props.event?.registration_fee ?? 0)
const earlyBirdFee = ref<number | null>(props.event?.early_bird_fee ?? null)
const earlyBirdDeadline = ref(props.event?.early_bird_deadline ? props.event.early_bird_deadline.substring(0, 10) : '')
const currency = ref(props.event?.currency ?? 'XAF')

// JSON fields
const benefits = ref(JSON.stringify(props.event?.benefits ?? [], null, 2))
const schedule = ref(JSON.stringify(props.event?.schedule ?? [], null, 2))
const faqs = ref(JSON.stringify(props.event?.faqs ?? [], null, 2))
const sponsors = ref(JSON.stringify(props.event?.sponsors ?? [], null, 2))

// File uploads
const heroImage = ref<File | null>(null)
const logoImage = ref<File | null>(null)

const submitting = ref(false)
const errors = ref<Record<string, string>>({})

const formatMoney = (amount: number) => {
    return new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(amount)
}

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}

const saveEvent = () => {
    submitting.value = true
    errors.value = {}

    const formData = new FormData()
    formData.append('title', title.value)
    formData.append('tagline', tagline.value)
    formData.append('description', description.value)
    formData.append('short_description', shortDescription.value)
    formData.append('start_date', startDate.value)
    formData.append('end_date', endDate.value)
    formData.append('location', location.value)
    if (capacity.value) formData.append('capacity', String(capacity.value))
    formData.append('registration_fee', String(registrationFee.value))
    if (earlyBirdFee.value !== null) formData.append('early_bird_fee', String(earlyBirdFee.value))
    if (earlyBirdDeadline.value) formData.append('early_bird_deadline', earlyBirdDeadline.value)
    formData.append('currency', currency.value)
    formData.append('benefits', benefits.value)
    formData.append('schedule', schedule.value)
    formData.append('faqs', faqs.value)
    formData.append('sponsors', sponsors.value)
    if (heroImage.value) formData.append('hero_image_file', heroImage.value)
    if (logoImage.value) formData.append('logo_image_file', logoImage.value)

    const url = editing.value ? `/admin/ai-forge/${props.event!.slug}` : '/admin/ai-forge'
    const method = editing.value ? 'post' : 'post'

    if (editing.value) formData.append('_method', 'PUT')

    router.post(url, formData, {
        forceFormData: true,
        onSuccess: () => {
            toast.success(editing.value ? 'AI Forge event updated!' : 'AI Forge event created!')
            submitting.value = false
        },
        onError: (errs) => {
            errors.value = errs
            toast.error('Please fix the validation errors.')
            submitting.value = false
        },
    })
}

const toggleActive = () => {
    router.post(`/admin/ai-forge/${props.event!.slug}/toggle-active`, {}, {
        preserveState: true,
        onSuccess: () => toast.success('Active status toggled!'),
    })
}

const toggleRegistration = () => {
    router.post(`/admin/ai-forge/${props.event!.slug}/toggle-registration`, {}, {
        preserveState: true,
        onSuccess: () => toast.success('Registration status toggled!'),
    })
}

const toggleSwagStore = () => {
    router.post(`/admin/ai-forge/${props.event!.slug}/toggle-swag-store`, {}, {
        preserveState: true,
        onSuccess: () => toast.success('Swag store status toggled!'),
    })
}

const updateStats = () => {
    router.put(`/admin/ai-forge/${props.event!.slug}/stats`, {}, {
        preserveState: true,
        onSuccess: () => toast.success('Stats refreshed!'),
    })
}

const onFileChange = (field: 'hero' | 'logo', e: Event) => {
    const input = e.target as HTMLInputElement
    if (input.files?.length) {
        if (field === 'hero') heroImage.value = input.files[0]
        else logoImage.value = input.files[0]
    }
}
</script>

<template>
    <div>
        <Head title="AI Forge Settings" />

        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">AI Forge Settings</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Manage the AI Forge event configuration</p>
            </div>
            <div v-if="event" class="flex items-center gap-2">
                <button @click="updateStats" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm">
                    Refresh Stats
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div v-if="event" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Registrations</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ registrationCount }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Orders</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ orderCount }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                <p class="text-2xl font-bold text-[#42b6c5]">{{ formatMoney(totalRevenue) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">Capacity</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ event.capacity ?? 'Unlimited' }}</p>
            </div>
        </div>

        <!-- Toggle Controls -->
        <div v-if="event" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Quick Controls</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Page Active</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Show or hide the AI Forge page</p>
                    </div>
                    <button @click="toggleActive" :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', event.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600']">
                        <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', event.is_active ? 'translate-x-6' : 'translate-x-1']" />
                    </button>
                </div>
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Registration Open</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Allow new registrations</p>
                    </div>
                    <button @click="toggleRegistration" :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', event.registration_open ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600']">
                        <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', event.registration_open ? 'translate-x-6' : 'translate-x-1']" />
                    </button>
                </div>
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">Swag Store</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Enable swag store</p>
                    </div>
                    <button @click="toggleSwagStore" :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', event.swag_store_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600']">
                        <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', event.swag_store_active ? 'translate-x-6' : 'translate-x-1']" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Event Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">{{ editing ? 'Edit Event' : 'Create Event' }}</h3>

            <form @submit.prevent="saveEvent" class="space-y-6">
                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                        <input v-model="title" type="text" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="errors.title" class="text-red-500 text-sm mt-1">{{ errors.title }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tagline</label>
                        <input v-model="tagline" type="text" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="errors.tagline" class="text-red-500 text-sm mt-1">{{ errors.tagline }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                        <input v-model="startDate" type="date" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="errors.start_date" class="text-red-500 text-sm mt-1">{{ errors.start_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                        <input v-model="endDate" type="date" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="errors.end_date" class="text-red-500 text-sm mt-1">{{ errors.end_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                        <input v-model="location" type="text" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="errors.location" class="text-red-500 text-sm mt-1">{{ errors.location }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Capacity</label>
                        <input v-model.number="capacity" type="number" min="1" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" placeholder="Leave empty for unlimited" />
                        <p v-if="errors.capacity" class="text-red-500 text-sm mt-1">{{ errors.capacity }}</p>
                    </div>
                </div>

                <!-- Registration Fee -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-4">Registration Pricing</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Registration Fee</label>
                            <input v-model.number="registrationFee" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" placeholder="0 = Free" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Set to 0 for free registration</p>
                            <p v-if="errors.registration_fee" class="text-red-500 text-sm mt-1">{{ errors.registration_fee }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency</label>
                            <select v-model="currency" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100">
                                <option value="XAF">XAF (CFA Franc)</option>
                                <option value="USD">USD (US Dollar)</option>
                                <option value="EUR">EUR (Euro)</option>
                            </select>
                            <p v-if="errors.currency" class="text-red-500 text-sm mt-1">{{ errors.currency }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Early Bird Fee</label>
                            <input v-model.number="earlyBirdFee" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" placeholder="Leave empty for no early bird" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Discounted fee before the deadline</p>
                            <p v-if="errors.early_bird_fee" class="text-red-500 text-sm mt-1">{{ errors.early_bird_fee }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Early Bird Deadline</label>
                            <input v-model="earlyBirdDeadline" type="date" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                            <p v-if="errors.early_bird_deadline" class="text-red-500 text-sm mt-1">{{ errors.early_bird_deadline }}</p>
                        </div>
                    </div>
                </div>

                <!-- Descriptions -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Short Description</label>
                    <textarea v-model="shortDescription" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                    <p v-if="errors.short_description" class="text-red-500 text-sm mt-1">{{ errors.short_description }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Description</label>
                    <textarea v-model="description" rows="12" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg font-mono text-sm focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Supports HTML tags for formatting: &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, etc.</p>
                    <p v-if="errors.description" class="text-red-500 text-sm mt-1">{{ errors.description }}</p>
                </div>

                <!-- Images -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hero Image</label>
                        <input type="file" accept="image/*" @change="(e: Event) => onFileChange('hero', e)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#42b6c5]/10 file:text-[#42b6c5] hover:file:bg-[#42b6c5]/20" />
                        <img v-if="event?.hero_image" :src="getImageUrl(event.hero_image)" alt="Hero" class="mt-2 h-20 rounded-lg object-cover" />
                        <p v-if="errors.hero_image" class="text-red-500 text-sm mt-1">{{ errors.hero_image }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo Image</label>
                        <input type="file" accept="image/*" @change="(e: Event) => onFileChange('logo', e)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#42b6c5]/10 file:text-[#42b6c5] hover:file:bg-[#42b6c5]/20" />
                        <img v-if="event?.logo_image" :src="getImageUrl(event.logo_image)" alt="Logo" class="mt-2 h-20 rounded-lg object-cover" />
                        <p v-if="errors.logo_image" class="text-red-500 text-sm mt-1">{{ errors.logo_image }}</p>
                    </div>
                </div>

                <!-- JSON Editors -->
                <div>
                    <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-3">Advanced Settings (JSON)</h4>
                    <div class="space-y-4">
                        <div v-for="field in [{ key: 'benefits', label: 'Benefits', model: benefits }, { key: 'schedule', label: 'Schedule', model: schedule }, { key: 'faqs', label: 'FAQs', model: faqs }, { key: 'sponsors', label: 'Sponsors', model: sponsors }]" :key="field.key">
                            <button type="button" @click="showJsonEditor = showJsonEditor === field.key ? null : field.key" class="flex items-center justify-between w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-left">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ field.label }}</span>
                                <svg :class="['w-5 h-5 text-gray-500 transition-transform', showJsonEditor === field.key ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-if="showJsonEditor === field.key" class="mt-2">
                                <textarea v-if="field.key === 'benefits'" v-model="benefits" rows="10" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg font-mono text-sm focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                                <textarea v-else-if="field.key === 'schedule'" v-model="schedule" rows="10" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg font-mono text-sm focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                                <textarea v-else-if="field.key === 'faqs'" v-model="faqs" rows="10" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg font-mono text-sm focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                                <textarea v-else-if="field.key === 'sponsors'" v-model="sponsors" rows="10" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg font-mono text-sm focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                                <p v-if="errors[field.key]" class="text-red-500 text-sm mt-1">{{ errors[field.key] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Enter valid JSON array. Check the documentation for the expected structure.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 pt-4">
                    <button type="submit" :disabled="submitting" class="px-8 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-50">
                        {{ submitting ? 'Saving...' : (editing ? 'Update Event' : 'Create Event') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
