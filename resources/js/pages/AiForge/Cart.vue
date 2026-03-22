<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import { useCart } from '@/composables/useCart'
import { computed } from 'vue'

interface CartItem {
    cart_key: string
    swag: {
        id: number
        name: string
        slug: string
        price: number
        currency: string
        image: string | null
        stock_quantity: number
    }
    variation: string | null
    quantity: number
    total: number
}

interface Props {
    cartItems: CartItem[]
    cartTotal: number
    surchargePercentage: number
    surchargeAmount: number
    grandTotal: number
}

const props = defineProps<Props>()
const { formatMoney, updateQuantity, removeItem, updating } = useCart()

const currency = computed(() => props.cartItems[0]?.swag?.currency ?? 'XAF')
const isEmpty = computed(() => props.cartItems.length === 0)

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}
</script>

<template>
    <PublicLayout>
        <Head title="Shopping Cart - AI Forge" />

        <!-- Header -->
        <section class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-black text-white">Your Cart</h1>
                        <p class="text-gray-400 mt-1">{{ cartItems.length }} item{{ cartItems.length !== 1 ? 's' : '' }}</p>
                    </div>
                    <Link href="/ai-forge/swags" class="text-[#42b6c5] hover:text-white transition-colors font-medium">
                        ← Continue Shopping
                    </Link>
                </div>
            </div>
        </section>

        <section class="py-12 bg-gray-50 min-h-[60vh]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Empty Cart -->
                <div v-if="isEmpty" class="text-center py-20">
                    <svg class="w-20 h-20 text-gray-300 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-500 mb-2">Your cart is empty</h2>
                    <p class="text-gray-400 mb-6">Browse the AI Forge swag store and add items to your cart.</p>
                    <Link href="/ai-forge/swags" class="inline-flex items-center gap-2 bg-[#42b6c5] text-white font-bold py-3 px-8 rounded-xl hover:bg-[#2d9aa8] transition-colors">
                        Browse Swag Store
                    </Link>
                </div>

                <!-- Cart Items -->
                <div v-else class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-4">
                        <div v-for="item in cartItems" :key="item.cart_key" class="bg-white rounded-xl p-4 sm:p-6 shadow-sm flex gap-4 sm:gap-6">
                            <!-- Image -->
                            <Link :href="`/ai-forge/swags/${item.swag.slug}`" class="shrink-0">
                                <div class="w-20 h-20 sm:w-28 sm:h-28 rounded-lg overflow-hidden bg-gray-100">
                                    <img v-if="item.swag.image" :src="getImageUrl(item.swag.image)" :alt="item.swag.name" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </div>
                                </div>
                            </Link>

                            <!-- Details -->
                            <div class="flex-1 min-w-0">
                                <Link :href="`/ai-forge/swags/${item.swag.slug}`">
                                    <h3 class="font-bold text-[#000928] hover:text-[#42b6c5] transition-colors">{{ item.swag.name }}</h3>
                                </Link>
                                <p v-if="item.variation" class="text-sm text-gray-500 mt-0.5">{{ item.variation }}</p>
                                <p class="text-[#42b6c5] font-bold mt-1">{{ formatMoney(item.swag.price, item.swag.currency) }}</p>

                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex items-center border border-gray-200 rounded-lg">
                                        <button @click="updateQuantity(item.cart_key, Math.max(1, item.quantity - 1))" :disabled="updating" class="px-3 py-1.5 text-gray-500 hover:text-[#000928] disabled:opacity-50">-</button>
                                        <span class="px-3 py-1.5 text-sm font-semibold min-w-[2.5rem] text-center">{{ item.quantity }}</span>
                                        <button @click="updateQuantity(item.cart_key, Math.min(item.swag.stock_quantity, item.quantity + 1))" :disabled="updating" class="px-3 py-1.5 text-gray-500 hover:text-[#000928] disabled:opacity-50">+</button>
                                    </div>
                                    <button @click="removeItem(item.cart_key)" :disabled="updating" class="text-red-500 hover:text-red-700 text-sm font-medium disabled:opacity-50">
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <!-- Item Total -->
                            <div class="text-right shrink-0">
                                <p class="font-black text-[#000928]">{{ formatMoney(item.total, item.swag.currency) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl p-6 shadow-sm sticky top-24">
                            <h3 class="text-lg font-bold text-[#000928] mb-4">Order Summary</h3>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Subtotal</span>
                                    <span class="font-semibold">{{ formatMoney(cartTotal, currency) }}</span>
                                </div>
                                <div v-if="surchargePercentage > 0" class="flex justify-between">
                                    <span class="text-gray-500">Online surcharge ({{ surchargePercentage }}%)</span>
                                    <span class="font-semibold">{{ formatMoney(surchargeAmount, currency) }}</span>
                                </div>
                                <hr class="border-gray-100" />
                                <div class="flex justify-between text-lg">
                                    <span class="font-bold text-[#000928]">Total</span>
                                    <span class="font-black text-[#42b6c5]">{{ formatMoney(grandTotal, currency) }}</span>
                                </div>
                            </div>

                            <Link href="/ai-forge/checkout" class="block w-full bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] text-white font-bold py-4 rounded-xl text-center hover:from-[#2d9aa8] hover:to-[#42b6c5] transition-all mt-6">
                                Proceed to Checkout
                            </Link>

                            <p class="text-xs text-gray-400 text-center mt-3">Payment via Mobile Money (MTN / Orange)</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
