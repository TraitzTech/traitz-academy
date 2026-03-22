<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { ref, watch } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface Order {
    id: number
    order_number: string
    customer_name: string
    customer_email: string
    total_amount: number
    payment_status: string
    payment_provider: string
    items_count: number
    created_at: string
}

interface Props {
    event: { id: number; title: string } | null
    orders: { data: Order[]; links: any[] }
    filters: { search?: string; status?: string }
    stats: { total: number; completed: number; pending: number; failed: number; totalRevenue: number }
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()
const search = ref(props.filters.search ?? '')
const statusFilter = ref(props.filters.status ?? '')

const formatMoney = (amount: number) =>
    new Intl.NumberFormat('en-CM', { style: 'currency', currency: 'XAF' }).format(amount)

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

const applyFilters = debounce(() => {
    router.get('/admin/ai-forge/orders', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true })
}, 300)

watch([search, statusFilter], applyFilters)

const updateStatus = (order: Order, status: string) => {
    router.patch(`/admin/ai-forge/orders/${order.id}/status`, { status }, {
        preserveState: true,
        onSuccess: () => toast.success(`Order ${status}!`),
    })
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    refunded: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400',
}
</script>

<template>
    <div>
        <Head title="AI Forge Orders" />

        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Orders</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">{{ event?.title ?? 'AI Forge' }} &mdash; {{ stats.total }} total orders</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Orders</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                <p class="text-2xl font-bold text-green-600">{{ stats.completed }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Pending</p>
                <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Failed</p>
                <p class="text-2xl font-bold text-red-600">{{ stats.failed }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                <p class="text-2xl font-bold text-[#42b6c5]">{{ formatMoney(stats.totalRevenue) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input v-model="search" type="text" placeholder="Search by order number, name or email..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select v-model="statusFilter" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100">
                        <option value="">All</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Provider</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-semibold text-gray-900 dark:text-gray-100">{{ order.order_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ order.customer_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ order.customer_email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ order.items_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(order.total_amount) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', order.payment_provider === 'MTN' ? 'bg-yellow-100 text-yellow-800' : 'bg-orange-100 text-orange-800']">{{ order.payment_provider }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', statusColors[order.payment_status] || '']">{{ order.payment_status }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ formatDate(order.created_at) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/admin/ai-forge/orders/${order.id}`" class="text-[#42b6c5] hover:text-[#35919e]">View</Link>
                                    <select v-if="order.payment_status !== 'completed'" @change="updateStatus(order, ($event.target as HTMLSelectElement).value)" class="text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 dark:bg-gray-700 dark:text-gray-100">
                                        <option value="" disabled selected>Update...</option>
                                        <option value="completed">Completed</option>
                                        <option value="failed">Failed</option>
                                        <option value="refunded">Refunded</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="orders.data.length === 0">
                            <td colspan="8" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">No orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="orders.links && orders.links.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                <div class="flex items-center justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <Link v-for="(link, index) in orders.links" :key="index" :href="link.url || '#'" :class="['relative inline-flex items-center px-4 py-2 border text-sm font-medium', link.active ? 'z-10 bg-[#42b6c5] border-[#42b6c5] text-white' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700', !link.url ? 'cursor-not-allowed opacity-50' : '', index === 0 ? 'rounded-l-md' : '', index === orders.links.length - 1 ? 'rounded-r-md' : '']" v-html="link.label" />
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
