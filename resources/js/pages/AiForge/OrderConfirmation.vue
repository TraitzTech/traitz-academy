<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import { useCart } from '@/composables/useCart'

interface OrderItem {
    id: number
    swag: { name: string; image: string | null }
    variation: string | null
    quantity: number
    unit_price: number
    total_price: number
}

interface Order {
    id: number
    order_number: string
    customer_name: string
    customer_email: string
    customer_phone: string
    subtotal: number
    surcharge: number
    total_amount: number
    payment_status: string
    payment_provider: string
    receipt_number: string | null
    created_at: string
    items: OrderItem[]
}

interface Props {
    order: Order
    event: { title: string }
}

const props = defineProps<Props>()
const { formatMoney } = useCart()
const currency = 'XAF'

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}

const formattedDate = new Date(props.order.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
})
</script>

<template>
    <PublicLayout>
        <Head title="Order Confirmed - AI Forge" />

        <section class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-20">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <!-- Success checkmark -->
                <div class="mb-6">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-r from-green-400 to-emerald-500 rounded-full flex items-center justify-center animate-bounce">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-white mb-3">Order Confirmed!</h1>
                <p class="text-gray-300 text-lg">Thank you for your purchase, {{ order.customer_name }}.</p>
                <p class="text-gray-400 mt-1">A receipt has been sent to {{ order.customer_email }}</p>
            </div>
        </section>

        <section class="py-12 bg-gray-50">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Order Details Card -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
                    <!-- Order header -->
                    <div class="bg-gradient-to-r from-[#42b6c5]/10 to-transparent p-6 border-b border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <p class="text-sm text-gray-500">Order Number</p>
                                <p class="text-xl font-black text-[#000928]">{{ order.order_number }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Date</p>
                                <p class="font-semibold text-[#000928]">{{ formattedDate }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="p-6">
                        <h3 class="font-bold text-[#000928] mb-4">Items Ordered</h3>
                        <div class="space-y-4">
                            <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                                    <img v-if="item.swag?.image" :src="getImageUrl(item.swag.image)" :alt="item.swag?.name" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-[#000928]">{{ item.swag?.name ?? 'Item' }}</p>
                                    <p v-if="item.variation" class="text-sm text-gray-500">{{ item.variation }}</p>
                                    <p class="text-sm text-gray-400">{{ formatMoney(item.unit_price, currency) }} × {{ item.quantity }}</p>
                                </div>
                                <p class="font-bold text-[#000928] shrink-0">{{ formatMoney(item.total_price, currency) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="bg-gray-50 p-6 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-semibold">{{ formatMoney(order.subtotal, currency) }}</span>
                        </div>
                        <div v-if="order.surcharge > 0" class="flex justify-between text-sm">
                            <span class="text-gray-500">Online surcharge</span>
                            <span class="font-semibold">{{ formatMoney(order.surcharge, currency) }}</span>
                        </div>
                        <hr class="border-gray-200" />
                        <div class="flex justify-between text-lg">
                            <span class="font-bold text-[#000928]">Total Paid</span>
                            <span class="font-black text-[#42b6c5]">{{ formatMoney(order.total_amount, currency) }}</span>
                        </div>
                    </div>

                    <!-- Payment & Customer Info -->
                    <div class="p-6 grid sm:grid-cols-2 gap-6 border-t border-gray-100">
                        <div>
                            <h4 class="font-bold text-[#000928] mb-2">Payment Info</h4>
                            <p class="text-sm text-gray-500">Provider: <span class="font-semibold text-gray-700">{{ order.payment_provider }}</span></p>
                            <p class="text-sm text-gray-500">Status: <span class="font-semibold text-green-600 capitalize">{{ order.payment_status }}</span></p>
                            <p v-if="order.receipt_number" class="text-sm text-gray-500">Receipt: <span class="font-semibold text-gray-700">{{ order.receipt_number }}</span></p>
                        </div>
                        <div>
                            <h4 class="font-bold text-[#000928] mb-2">Customer</h4>
                            <p class="text-sm text-gray-500">{{ order.customer_name }}</p>
                            <p class="text-sm text-gray-500">{{ order.customer_email }}</p>
                            <p class="text-sm text-gray-500">{{ order.customer_phone }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link href="/ai-forge/swags" class="inline-flex items-center justify-center gap-2 bg-[#000928] text-white font-bold py-3 px-8 rounded-xl hover:bg-[#000928]/90 transition-colors">
                        Continue Shopping
                    </Link>
                    <Link href="/ai-forge" class="inline-flex items-center justify-center gap-2 bg-white border-2 border-gray-200 text-[#000928] font-bold py-3 px-8 rounded-xl hover:border-[#42b6c5] transition-colors">
                        Back to AI Forge
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
