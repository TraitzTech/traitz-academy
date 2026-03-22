<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { ref, watch } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Swag {
    id: number
    name: string
    slug: string
    category: string
    price: number
    currency: string
    image: string | null
    stock_quantity: number
    sold_count: number
    is_active: boolean
    is_featured: boolean
    sort_order: number
}

interface Props {
    event: { id: number; title: string } | null
    swags: { data: Swag[]; links: any[] }
    filters: { search?: string; category?: string }
    categories: string[]
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()
const search = ref(props.filters.search ?? '')
const category = ref(props.filters.category ?? '')
const showDeleteModal = ref(false)
const swagToDelete = ref<Swag | null>(null)

const categoryLabels: Record<string, string> = {
    't-shirt': 'T-Shirt', 'polo': 'Polo', 'hoodie': 'Hoodie', 'cap': 'Cap',
    'water-bottle': 'Water Bottle', 'sticker-pack': 'Sticker Pack',
    'tote-bag': 'Tote Bag', 'notebook': 'Notebook', 'other': 'Other',
}

const formatMoney = (amount: number) => {
    return new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(amount)
}

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}

const applyFilters = debounce(() => {
    router.get('/admin/ai-forge/swags', {
        search: search.value || undefined,
        category: category.value || undefined,
    }, { preserveState: true, replace: true })
}, 300)

watch([search, category], applyFilters)

const toggleActive = (swag: Swag) => {
    router.post(`/admin/ai-forge/swags/${swag.slug}/toggle-active`, {}, {
        preserveState: true,
        onSuccess: () => toast.success(`${swag.name} ${swag.is_active ? 'deactivated' : 'activated'}!`),
    })
}

const toggleFeatured = (swag: Swag) => {
    router.post(`/admin/ai-forge/swags/${swag.slug}/toggle-featured`, {}, {
        preserveState: true,
        onSuccess: () => toast.success(`${swag.name} ${swag.is_featured ? 'unfeatured' : 'featured'}!`),
    })
}

const deleteSwag = (swag: Swag) => {
    swagToDelete.value = swag
    showDeleteModal.value = true
}

const confirmDelete = () => {
    if (!swagToDelete.value) return
    router.delete(`/admin/ai-forge/swags/${swagToDelete.value.slug}`, {
        onSuccess: () => {
            toast.success('Swag deleted!')
            showDeleteModal.value = false
            swagToDelete.value = null
        },
        onError: () => toast.error('Failed to delete swag.'),
    })
}
</script>

<template>
    <div>
        <Head title="Manage AI Forge Swags" />

        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">AI Forge Swags</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Manage merchandise{{ event ? ` for ${event.title}` : '' }}</p>
            </div>
            <Link href="/admin/ai-forge/swags/create" class="inline-flex items-center px-6 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Swag
            </Link>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input v-model="search" type="text" placeholder="Search swags..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <select v-model="category" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100">
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ categoryLabels[cat] || cat }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sold</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Featured</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="swag in swags.data" :key="swag.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="shrink-0 h-12 w-12">
                                        <img v-if="swag.image" :src="getImageUrl(swag.image)" :alt="swag.name" class="h-12 w-12 rounded-lg object-cover" />
                                        <div v-else class="h-12 w-12 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ swag.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">{{ categoryLabels[swag.category] || swag.category }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(swag.price) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['text-sm font-medium', swag.stock_quantity > 10 ? 'text-green-600 dark:text-green-400' : swag.stock_quantity > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400']">{{ swag.stock_quantity }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ swag.sold_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button @click="toggleActive(swag)" :class="['px-2 py-1 text-xs font-medium rounded-full transition-colors', swag.is_active ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400']">
                                    {{ swag.is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button @click="toggleFeatured(swag)" :class="['px-2 py-1 text-xs font-medium rounded-full transition-colors', swag.is_featured ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400']">
                                    {{ swag.is_featured ? '★ Featured' : 'No' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/admin/ai-forge/swags/${swag.slug}/edit`" class="text-[#42b6c5] hover:text-[#35919e]">Edit</Link>
                                    <button @click="deleteSwag(swag)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="swags.data.length === 0">
                            <td colspan="8" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">No swags found. Add your first swag item.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="swags.links && swags.links.length > 3" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                <div class="flex items-center justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <Link v-for="(link, index) in swags.links" :key="index" :href="link.url || '#'" :class="['relative inline-flex items-center px-4 py-2 border text-sm font-medium', link.active ? 'z-10 bg-[#42b6c5] border-[#42b6c5] text-white' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700', !link.url ? 'cursor-not-allowed opacity-50' : '', index === 0 ? 'rounded-l-md' : '', index === swags.links.length - 1 ? 'rounded-r-md' : '']" v-html="link.label" />
                    </nav>
                </div>
            </div>
        </div>

        <ConfirmationModal :open="showDeleteModal" title="Delete Swag" :description="`Are you sure you want to delete &quot;${swagToDelete?.name}&quot;? This cannot be undone.`" confirm-text="Delete" variant="destructive" @update:open="showDeleteModal = $event" @confirm="confirmDelete" />
    </div>
</template>
