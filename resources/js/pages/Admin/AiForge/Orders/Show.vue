<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface OrderItem {
    id: number
    swag: { id: number; name: string; image: string | null }
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
    transaction_id: string | null
    receipt_number: string | null
    paid_at: string | null
    created_at: string
    items: OrderItem[]
}

interface Props {
    event: { id: number; title: string } | null
    order: Order
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const formatMoney = (amount: number) =>
    new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(amount)

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}

const updateStatus = (status: string) => {
    router.patch(`/admin/ai-forge/orders/${props.order.id}/status`, { status }, {
        preserveState: true,
        onSuccess: () => toast.success(`Order marked as ${status}!`),
    })
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
}
</script>

<template>
    <div>
        <Head :title="`Order ${order.order_number} - AI Forge`" />

        <div class="mb-8">
            <Link href="/admin/ai-forge/orders" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Orders
            </Link>
            <div class="flex items-center gap-4">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Order {{ order.order_number }}</h2>
                <span :class="['px-3 py-1 text-sm font-medium rounded-full', statusColors[order.payment_status] || '']">{{ order.payment_status }}</span>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Order Items -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Items ({{ order.items.length }})</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="item in order.items" :key="item.id" class="px-6 py-4 flex items-center gap-4">
                            <div class="shrink-0 h-14 w-14">
                                <img v-if="item.swag?.image" :src="getImageUrl(item.swag.image)" :alt="item.swag?.name" class="h-14 w-14 rounded-lg object-cover" />
                                <div v-else class="h-14 w-14 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.swag?.name ?? 'Unknown Item' }}</p>
                                <p v-if="item.variation" class="text-sm text-gray-500 dark:text-gray-400">{{ item.variation }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatMoney(item.unit_price) }} × {{ item.quantity }}</p>
                            </div>
                            <p class="font-bold text-gray-900 dark:text-gray-100">{{ formatMoney(item.total_price) }}</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(order.subtotal) }}</span>
                        </div>
                        <div v-if="order.surcharge > 0" class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Surcharge</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(order.surcharge) }}</span>
                        </div>
                        <hr class="border-gray-200 dark:border-gray-600" />
                        <div class="flex justify-between text-lg">
                            <span class="font-bold text-gray-900 dark:text-gray-100">Total</span>
                            <span class="font-black text-[#42b6c5]">{{ formatMoney(order.total_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Customer Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Customer</h3>
                    <div class="space-y-2 text-sm">
                        <p class="text-gray-900 dark:text-gray-100 font-medium">{{ order.customer_name }}</p>
                        <p class="text-gray-500 dark:text-gray-400">{{ order.customer_email }}</p>
                        <p class="text-gray-500 dark:text-gray-400">{{ order.customer_phone }}</p>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Payment</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Provider</span>
                            <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', order.payment_provider === 'MTN' ? 'bg-yellow-100 text-yellow-800' : 'bg-orange-100 text-orange-800']">{{ order.payment_provider }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Status</span>
                            <span class="font-semibold capitalize text-gray-900 dark:text-gray-100">{{ order.payment_status }}</span>
                        </div>
                        <div v-if="order.transaction_id" class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Transaction ID</span>
                            <span class="font-mono text-xs text-gray-900 dark:text-gray-100">{{ order.transaction_id }}</span>
                        </div>
                        <div v-if="order.receipt_number" class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Receipt</span>
                            <span class="font-mono text-xs text-gray-900 dark:text-gray-100">{{ order.receipt_number }}</span>
                        </div>
                        <div v-if="order.paid_at" class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Paid At</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ formatDate(order.paid_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Created</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ formatDate(order.created_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div v-if="order.payment_status !== 'completed'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Actions</h3>
                    <div class="space-y-2">
                        <button @click="updateStatus('completed')" class="w-full px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors text-sm">Mark Completed</button>
                        <button @click="updateStatus('failed')" class="w-full px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors text-sm">Mark Failed</button>
                        <button @click="updateStatus('refunded')" class="w-full px-4 py-2 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition-colors text-sm">Mark Refunded</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
