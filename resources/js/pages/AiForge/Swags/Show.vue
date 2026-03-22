<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import { useCart } from '@/composables/useCart'
import { ref, computed, watch } from 'vue'

interface VariationOption {
    name: string
    image?: string | null
}

interface Variation {
    type: string
    options: (string | VariationOption)[]
}

interface Swag {
    id: number
    name: string
    slug: string
    category: string
    description: string | null
    price: number
    currency: string
    image: string | null
    gallery_images: string[] | null
    variations: Variation[] | null
    stock_quantity: number
    is_featured: boolean
}

interface Props {
    swag: Swag
    relatedSwags: Swag[]
}

const props = defineProps<Props>()
const { formatMoney, addToCart, updating } = useCart()

const selectedVariations = ref<Record<string, string>>({})
const quantity = ref(1)
const activeImage = ref(0)

const getOptionName = (opt: string | VariationOption): string =>
    typeof opt === 'string' ? opt : opt.name

const getOptionImage = (opt: string | VariationOption): string | null =>
    typeof opt === 'string' ? null : (opt.image ?? null)

const normalizedVariations = computed(() =>
    props.swag.variations?.map(v => ({
        type: v.type,
        options: v.options.map(opt => ({
            name: getOptionName(opt),
            image: getOptionImage(opt),
        })),
    })) ?? []
)

const allImages = computed(() => {
    const images: string[] = []
    if (props.swag.image) images.push(props.swag.image)
    if (props.swag.gallery_images?.length) images.push(...props.swag.gallery_images)
    // Add variation images that have images
    for (const v of normalizedVariations.value) {
        for (const opt of v.options) {
            if (opt.image && !images.includes(opt.image)) {
                images.push(opt.image)
            }
        }
    }
    return images
})

const selectedVariationString = computed(() => {
    const parts = Object.entries(selectedVariations.value)
        .filter(([, v]) => v)
        .map(([type, val]) => `${type}: ${val}`)
    return parts.join(', ')
})

const hasVariationsRequiringSelection = computed(() =>
    normalizedVariations.value.length > 0
)

// Watch for variation selection to switch image
watch(selectedVariations, (newVal) => {
    for (const v of normalizedVariations.value) {
        const selectedOpt = v.options.find(opt => newVal[v.type] === opt.name)
        if (selectedOpt?.image) {
            const imgIdx = allImages.value.indexOf(selectedOpt.image)
            if (imgIdx >= 0) {
                activeImage.value = imgIdx
                return
            }
        }
    }
}, { deep: true })

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}

const categoryLabels: Record<string, string> = {
    't-shirt': 'T-Shirt', 'polo': 'Polo', 'hoodie': 'Hoodie', 'cap': 'Cap',
    'water-bottle': 'Water Bottle', 'sticker-pack': 'Sticker Pack',
    'tote-bag': 'Tote Bag', 'notebook': 'Notebook', 'other': 'Other',
}

const handleAddToCart = () => {
    addToCart(props.swag.id, selectedVariationString.value || null, quantity.value)
}

</script>

<template>
    <PublicLayout>
        <Head :title="`${swag.name} - AI Forge Swag`" />

        <section class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center gap-2 text-sm text-gray-400">
                    <Link href="/ai-forge" class="hover:text-[#42b6c5] transition-colors">AI Forge</Link>
                    <span>/</span>
                    <Link href="/ai-forge/swags" class="hover:text-[#42b6c5] transition-colors">Swag Store</Link>
                    <span>/</span>
                    <span class="text-white">{{ swag.name }}</span>
                </nav>
            </div>
        </section>

        <section class="py-12 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12">
                    <!-- Image Gallery -->
                    <div>
                        <div class="aspect-square bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl overflow-hidden mb-4">
                            <img v-if="allImages.length" :src="getImageUrl(allImages[activeImage])" :alt="swag.name" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-32 h-32 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                        </div>
                        <div v-if="allImages.length > 1" class="grid grid-cols-5 gap-3">
                            <button v-for="(img, idx) in allImages" :key="idx" @click="activeImage = idx" :class="['aspect-square rounded-lg overflow-hidden border-2 transition-colors', activeImage === idx ? 'border-[#42b6c5]' : 'border-gray-200 hover:border-gray-300']">
                                <img :src="getImageUrl(img)" :alt="`${swag.name} ${idx + 1}`" class="w-full h-full object-cover" />
                            </button>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider">{{ categoryLabels[swag.category] || swag.category }}</span>
                            <span v-if="swag.is_featured" class="bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] text-white text-xs font-bold px-3 py-1 rounded-full">Featured</span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl font-black text-[#000928] mb-4">{{ swag.name }}</h1>

                        <div class="text-4xl font-black text-[#42b6c5] mb-6">{{ formatMoney(swag.price, swag.currency) }}</div>

                        <div v-if="swag.description" class="prose prose-gray mb-8 text-gray-600 leading-relaxed" v-html="swag.description" />

                        <!-- Stock Indicator -->
                        <div class="mb-6">
                            <div v-if="swag.stock_quantity > 10" class="flex items-center gap-2 text-green-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5" /></svg>
                                <span class="text-sm font-medium">In Stock</span>
                            </div>
                            <div v-else-if="swag.stock_quantity > 0" class="flex items-center gap-2 text-amber-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5" /></svg>
                                <span class="text-sm font-medium">Only {{ swag.stock_quantity }} left</span>
                            </div>
                            <div v-else class="flex items-center gap-2 text-red-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5" /></svg>
                                <span class="text-sm font-medium">Out of Stock</span>
                            </div>
                        </div>

                        <!-- Variations -->
                        <div v-if="normalizedVariations.length" class="mb-6">
                            <div v-for="variation in normalizedVariations" :key="variation.type" class="mb-4">
                                <label class="block text-sm font-bold text-[#000928] mb-2">{{ variation.type }}</label>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="opt in variation.options" :key="opt.name" @click="selectedVariations[variation.type] = opt.name" :class="['rounded-lg border-2 text-sm font-medium transition-all', selectedVariations[variation.type] === opt.name ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#42b6c5]' : 'border-gray-200 text-gray-600 hover:border-gray-300', opt.image ? 'p-1.5' : 'px-4 py-2']">
                                        <div v-if="opt.image" class="flex flex-col items-center gap-1.5">
                                            <img :src="getImageUrl(opt.image)" :alt="opt.name" class="w-14 h-14 rounded object-cover" />
                                            <span class="text-xs">{{ opt.name }}</span>
                                        </div>
                                        <span v-else>{{ opt.name }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="mb-8" v-if="swag.stock_quantity > 0">
                            <label class="block text-sm font-bold text-[#000928] mb-2">Quantity</label>
                            <div class="inline-flex items-center border-2 border-gray-200 rounded-xl">
                                <button @click="quantity = Math.max(1, quantity - 1)" class="px-4 py-3 text-gray-500 hover:text-[#000928] font-bold text-lg">−</button>
                                <span class="px-6 py-3 font-bold text-lg border-x-2 border-gray-200">{{ quantity }}</span>
                                <button @click="quantity = Math.min(swag.stock_quantity, quantity + 1)" class="px-4 py-3 text-gray-500 hover:text-[#000928] font-bold text-lg">+</button>
                            </div>
                        </div>

                        <!-- Add to Cart -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button v-if="swag.stock_quantity > 0" @click="handleAddToCart" :disabled="updating || (hasVariationsRequiringSelection && !selectedVariationString)" class="flex-1 bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] text-white font-bold py-4 px-8 rounded-xl hover:from-[#2d9aa8] hover:to-[#42b6c5] transition-all text-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ updating ? 'Adding...' : 'Add to Cart' }}
                            </button>
                            <button v-else disabled class="flex-1 bg-gray-200 text-gray-500 font-bold py-4 px-8 rounded-xl text-lg cursor-not-allowed">
                                Out of Stock
                            </button>
                            <Link href="/ai-forge/cart" class="flex items-center justify-center gap-2 bg-[#000928] text-white font-bold py-4 px-8 rounded-xl hover:bg-[#000928]/90 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                                </svg>
                                Cart
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Related Products -->
        <section v-if="relatedSwags.length" class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-black text-[#000928] mb-8">You might also like</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Link v-for="item in relatedSwags" :key="item.id" :href="`/ai-forge/swags/${item.slug}`" class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
                        <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-50 overflow-hidden">
                            <img v-if="item.image" :src="getImageUrl(item.image)" :alt="item.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-[#000928] group-hover:text-[#42b6c5] transition-colors">{{ item.name }}</h3>
                            <span class="text-lg font-black text-[#42b6c5]">{{ formatMoney(item.price, item.currency) }}</span>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
