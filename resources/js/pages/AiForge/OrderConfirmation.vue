<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { useCart } from '@/composables/useCart';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface OrderItem {
    id: number;
    swag: { name: string; image: string | null };
    variation: string | null;
    quantity: number;
    unit_price: number;
    total_price: number;
}

interface Order {
    id: number;
    order_number: string;
    customer_name: string;
    customer_email: string;
    customer_phone: string;
    subtotal: number;
    surcharge: number;
    total_amount: number;
    payment_status: string;
    payment_provider: string;
    receipt_number: string | null;
    created_at: string;
    items: OrderItem[];
}

interface Props {
    order: Order;
    event: { title: string };
}

const props = defineProps<Props>();
const { formatMoney } = useCart();
const currency = 'XAF';

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined;
    if (path.startsWith('http')) return path;
    return `/storage/${path}`;
};

const formattedDate = new Date(props.order.created_at).toLocaleDateString(
    'en-US',
    {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    },
);
</script>

<template>
    <PublicLayout>
        <Head title="Order Confirmed - AI Forge" />

        <section
            class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-20"
        >
            <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                <!-- Success checkmark -->
                <div class="mb-6">
                    <div
                        class="mx-auto flex h-20 w-20 animate-bounce items-center justify-center rounded-full bg-gradient-to-r from-green-400 to-emerald-500"
                    >
                        <svg
                            class="h-10 w-10 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="3"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                </div>
                <h1 class="mb-3 text-3xl font-black text-white sm:text-4xl">
                    Order Confirmed!
                </h1>
                <p class="text-lg text-gray-300">
                    Thank you for your purchase, {{ order.customer_name }}.
                </p>
                <p class="mt-1 text-gray-400">
                    A receipt has been sent to {{ order.customer_email }}
                </p>
            </div>
        </section>

        <section class="bg-gray-50 py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <!-- Order Details Card -->
                <div
                    class="mb-8 overflow-hidden rounded-2xl bg-white shadow-sm"
                >
                    <!-- Order header -->
                    <div
                        class="border-b border-gray-100 bg-gradient-to-r from-[#42b6c5]/10 to-transparent p-6"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="text-sm text-gray-500">
                                    Order Number
                                </p>
                                <p class="text-xl font-black text-[#000928]">
                                    {{ order.order_number }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Date</p>
                                <p class="font-semibold text-[#000928]">
                                    {{ formattedDate }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="p-6">
                        <h3 class="mb-4 font-bold text-[#000928]">
                            Items Ordered
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="flex items-center gap-4"
                            >
                                <div
                                    class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-gray-100"
                                >
                                    <img
                                        v-if="item.swag?.image"
                                        :src="getImageUrl(item.swag.image)"
                                        :alt="item.swag?.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center"
                                    >
                                        <svg
                                            class="h-6 w-6 text-gray-300"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1"
                                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-[#000928]">
                                        {{ item.swag?.name ?? 'Item' }}
                                    </p>
                                    <p
                                        v-if="item.variation"
                                        class="text-sm text-gray-500"
                                    >
                                        {{ item.variation }}
                                    </p>
                                    <p class="text-sm text-gray-400">
                                        {{
                                            formatMoney(
                                                item.unit_price,
                                                currency,
                                            )
                                        }}
                                        × {{ item.quantity }}
                                    </p>
                                </div>
                                <p class="shrink-0 font-bold text-[#000928]">
                                    {{
                                        formatMoney(item.total_price, currency)
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="space-y-2 bg-gray-50 p-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-semibold">{{
                                formatMoney(order.subtotal, currency)
                            }}</span>
                        </div>
                        <div
                            v-if="order.surcharge > 0"
                            class="flex justify-between text-sm"
                        >
                            <span class="text-gray-500">Online surcharge</span>
                            <span class="font-semibold">{{
                                formatMoney(order.surcharge, currency)
                            }}</span>
                        </div>
                        <hr class="border-gray-200" />
                        <div class="flex justify-between text-lg">
                            <span class="font-bold text-[#000928]"
                                >Total Paid</span
                            >
                            <span class="font-black text-[#42b6c5]">{{
                                formatMoney(order.total_amount, currency)
                            }}</span>
                        </div>
                    </div>

                    <!-- Payment & Customer Info -->
                    <div
                        class="grid gap-6 border-t border-gray-100 p-6 sm:grid-cols-2"
                    >
                        <div>
                            <h4 class="mb-2 font-bold text-[#000928]">
                                Payment Info
                            </h4>
                            <p class="text-sm text-gray-500">
                                Provider:
                                <span class="font-semibold text-gray-700">{{
                                    order.payment_provider
                                }}</span>
                            </p>
                            <p class="text-sm text-gray-500">
                                Status:
                                <span
                                    class="font-semibold text-green-600 capitalize"
                                    >{{ order.payment_status }}</span
                                >
                            </p>
                            <p
                                v-if="order.receipt_number"
                                class="text-sm text-gray-500"
                            >
                                Receipt:
                                <span class="font-semibold text-gray-700">{{
                                    order.receipt_number
                                }}</span>
                            </p>
                        </div>
                        <div>
                            <h4 class="mb-2 font-bold text-[#000928]">
                                Customer
                            </h4>
                            <p class="text-sm text-gray-500">
                                {{ order.customer_name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ order.customer_email }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ order.customer_phone }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <Link
                        href="/ai-forge/swags"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#000928] px-8 py-3 font-bold text-white transition-colors hover:bg-[#000928]/90"
                    >
                        Continue Shopping
                    </Link>
                    <Link
                        href="/ai-forge"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-gray-200 bg-white px-8 py-3 font-bold text-[#000928] transition-colors hover:border-[#42b6c5]"
                    >
                        Back to AI Forge
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
