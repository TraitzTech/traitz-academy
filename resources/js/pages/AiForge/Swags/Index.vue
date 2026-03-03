<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import { useCart } from '@/composables/useCart'
import { ref, computed, watch } from 'vue'

interface Swag {
    id: number
    name: string
    slug: string
    category: string
    description: string | null
    price: number
    currency: string
    image: string | null
    variations: { type: string; options: (string | { name: string; image?: string | null })[] }[] | null
    stock_quantity: number
    is_featured: boolean
}

interface Props {
    event: { title: string; swag_store_active: boolean }
    swags: Swag[]
    categories: Record<string, number>
    filters: { category?: string; search?: string }
}

const props = defineProps<Props>()
const { formatMoney, addToCart, updating } = useCart()

const search = ref(props.filters.search ?? '')
const selectedCategory = ref(props.filters.category ?? '')
const quickAddSwag = ref<number | null>(null)
const selectedVariation = ref('')
const selectedQuantity = ref(1)

const categoryLabels: Record<string, string> = {
    't-shirt': 'T-Shirts',
    'polo': 'Polos',
    'hoodie': 'Hoodies',
    'cap': 'Caps',
    'water-bottle': 'Water Bottles',
    'sticker-pack': 'Sticker Packs',
    'tote-bag': 'Tote Bags',
    'notebook': 'Notebooks',
    'other': 'Other',
}

const getOptionName = (opt: string | { name: string; image?: string | null }): string =>
    typeof opt === 'string' ? opt : opt.name

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}

const filterByCategory = (category: string) => {
    selectedCategory.value = category
    applyFilters()
}

const applyFilters = () => {
    router.get('/ai-forge/swags', {
        ...(selectedCategory.value ? { category: selectedCategory.value } : {}),
        ...(search.value ? { search: search.value } : {}),
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    selectedCategory.value = ''
    search.value = ''
    router.get('/ai-forge/swags', {}, { preserveState: true })
}

let searchTimeout: ReturnType<typeof setTimeout>
watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 400)
})

const openQuickAdd = (swag: Swag) => {
    quickAddSwag.value = swag.id
    selectedVariation.value = ''
    selectedQuantity.value = 1
}

const handleAddToCart = (swag: Swag) => {
    addToCart(swag.id, selectedVariation.value || null, selectedQuantity.value)
    quickAddSwag.value = null
}
</script>

<template>
    <PublicLayout>
        <Head title="AI Forge Swag Store" />

        <!-- Hero -->
        <section class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl sm:text-5xl font-black text-white mb-4">
                    <span class="bg-gradient-to-r from-[#42b6c5] to-white bg-clip-text text-transparent">AI Forge</span> Swag Store
                </h1>
                <p class="text-gray-300 text-lg max-w-2xl mx-auto mb-8">Get your exclusive AI Forge merchandise. Rock the gear, rep the community!</p>
                <div class="flex items-center justify-center gap-4">
                    <Link href="/ai-forge" class="text-[#42b6c5] hover:text-white transition-colors font-medium">
                        ← Back to AI Forge
                    </Link>
                    <Link href="/ai-forge/cart" class="inline-flex items-center gap-2 bg-white/10 border border-white/20 text-white font-semibold px-5 py-2.5 rounded-xl hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        View Cart
                    </Link>
                </div>
            </div>
        </section>

        <!-- Filters & Products -->
        <section class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Search & Filters -->
                <div class="flex flex-col sm:flex-row gap-4 mb-8">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input v-model="search" type="text" placeholder="Search swag..." class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent transition-colors" />
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <button @click="clearFilters" :class="['px-4 py-2 rounded-xl text-sm font-medium transition-colors', !selectedCategory ? 'bg-[#000928] text-white' : 'bg-white text-gray-700 border border-gray-200 hover:border-[#42b6c5]']">
                            All
                        </button>
                        <button v-for="(count, category) in categories" :key="category" @click="filterByCategory(category as string)" :class="['px-4 py-2 rounded-xl text-sm font-medium transition-colors', selectedCategory === category ? 'bg-[#000928] text-white' : 'bg-white text-gray-700 border border-gray-200 hover:border-[#42b6c5]']">
                            {{ categoryLabels[category as string] || category }} ({{ count }})
                        </button>
                    </div>
                </div>

                <!-- Product Grid -->
                <div v-if="swags.length" class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="swag in swags" :key="swag.id" class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
                        <Link :href="`/ai-forge/swags/${swag.slug}`" class="block">
                            <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-50 overflow-hidden relative">
                                <img v-if="swag.image" :src="getImageUrl(swag.image)" :alt="swag.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <svg class="w-20 h-20 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <!-- Featured badge -->
                                <div v-if="swag.is_featured" class="absolute top-3 left-3 bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] text-white text-xs font-bold px-3 py-1 rounded-full">
                                    Featured
                                </div>
                                <!-- Out of stock -->
                                <div v-if="swag.stock_quantity <= 0" class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                    <span class="bg-red-600 text-white font-bold px-4 py-2 rounded-xl">Out of Stock</span>
                                </div>
                            </div>
                        </Link>

                        <div class="p-4">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">{{ categoryLabels[swag.category] || swag.category }}</p>
                            <Link :href="`/ai-forge/swags/${swag.slug}`">
                                <h3 class="font-bold text-[#000928] group-hover:text-[#42b6c5] transition-colors">{{ swag.name }}</h3>
                            </Link>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xl font-black text-[#42b6c5]">{{ formatMoney(swag.price, swag.currency) }}</span>
                                <button v-if="swag.stock_quantity > 0" @click.prevent="openQuickAdd(swag)" class="p-2 rounded-xl bg-[#000928] text-white hover:bg-[#42b6c5] transition-colors" title="Add to cart">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Quick add overlay -->
                        <div v-if="quickAddSwag === swag.id" class="p-4 pt-0 border-t border-gray-100 animate-in">
                            <div v-if="swag.variations?.length" class="mb-3">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Select option</label>
                                <select v-model="selectedVariation" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent">
                                    <option value="">Choose...</option>
                                    <template v-for="variation in swag.variations" :key="variation.type">
                                        <option v-for="opt in variation.options" :key="getOptionName(opt)" :value="`${variation.type}: ${getOptionName(opt)}`">{{ variation.type }}: {{ getOptionName(opt) }}</option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center border border-gray-200 rounded-lg">
                                    <button @click="selectedQuantity = Math.max(1, selectedQuantity - 1)" class="px-3 py-1.5 text-gray-500 hover:text-[#000928]">-</button>
                                    <span class="px-3 py-1.5 text-sm font-semibold">{{ selectedQuantity }}</span>
                                    <button @click="selectedQuantity = Math.min(10, selectedQuantity + 1)" class="px-3 py-1.5 text-gray-500 hover:text-[#000928]">+</button>
                                </div>
                                <button @click="handleAddToCart(swag)" :disabled="updating" class="flex-1 bg-[#42b6c5] text-white font-semibold py-2 rounded-lg hover:bg-[#2d9aa8] transition-colors text-sm disabled:opacity-50">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-20">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <h3 class="text-xl font-bold text-gray-500 mb-2">No swag items found</h3>
                    <p class="text-gray-400">Try a different search or category filter.</p>
                    <button @click="clearFilters" class="mt-4 text-[#42b6c5] font-semibold hover:underline">Clear filters</button>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
