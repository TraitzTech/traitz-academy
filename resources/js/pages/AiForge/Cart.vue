<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useCart } from '@/composables/useCart';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface CartItem {
    cart_key: string;
    swag: {
        id: number;
        name: string;
        slug: string;
        price: number;
        currency: string;
        image: string | null;
        stock_quantity: number;
    };
    variation: string | null;
    quantity: number;
    total: number;
}

interface Props {
    cartItems: CartItem[];
    cartTotal: number;
    surchargePercentage: number;
    surchargeAmount: number;
    grandTotal: number;
}

const props = defineProps<Props>();
const { formatMoney, updateQuantity, removeItem, updating } = useCart();

const currency = computed(() => props.cartItems[0]?.swag?.currency ?? 'XAF');
const isEmpty = computed(() => props.cartItems.length === 0);

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined;
    if (path.startsWith('http')) return path;
    return `/storage/${path}`;
};
</script>

<template>
    <PublicLayout>
        <Head title="Shopping Cart - AI Forge" />

        <!-- Header -->
        <section
            class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-12"
        >
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-black text-white">
                            Your Cart
                        </h1>
                        <p class="mt-1 text-gray-400">
                            {{ cartItems.length }} item{{
                                cartItems.length !== 1 ? 's' : ''
                            }}
                        </p>
                    </div>
                    <Link
                        href="/ai-forge/swags"
                        class="font-medium text-[#42b6c5] transition-colors hover:text-white"
                    >
                        ← Continue Shopping
                    </Link>
                </div>
            </div>
        </section>

        <section class="min-h-[60vh] bg-gray-50 py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Empty Cart -->
                <div v-if="isEmpty" class="py-20 text-center">
                    <svg
                        class="mx-auto mb-6 h-20 w-20 text-gray-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"
                        />
                    </svg>
                    <h2 class="mb-2 text-2xl font-bold text-gray-500">
                        Your cart is empty
                    </h2>
                    <p class="mb-6 text-gray-400">
                        Browse the AI Forge swag store and add items to your
                        cart.
                    </p>
                    <Link
                        href="/ai-forge/swags"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#42b6c5] px-8 py-3 font-bold text-white transition-colors hover:bg-[#2d9aa8]"
                    >
                        Browse Swag Store
                    </Link>
                </div>

                <!-- Cart Items -->
                <div v-else class="grid gap-8 lg:grid-cols-3">
                    <div class="space-y-4 lg:col-span-2">
                        <div
                            v-for="item in cartItems"
                            :key="item.cart_key"
                            class="flex gap-4 rounded-xl bg-white p-4 shadow-sm sm:gap-6 sm:p-6"
                        >
                            <!-- Image -->
                            <Link
                                :href="`/ai-forge/swags/${item.swag.slug}`"
                                class="shrink-0"
                            >
                                <div
                                    class="h-20 w-20 overflow-hidden rounded-lg bg-gray-100 sm:h-28 sm:w-28"
                                >
                                    <img
                                        v-if="item.swag.image"
                                        :src="getImageUrl(item.swag.image)"
                                        :alt="item.swag.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center"
                                    >
                                        <svg
                                            class="h-10 w-10 text-gray-200"
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
                            </Link>

                            <!-- Details -->
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="`/ai-forge/swags/${item.swag.slug}`"
                                >
                                    <h3
                                        class="font-bold text-[#000928] transition-colors hover:text-[#42b6c5]"
                                    >
                                        {{ item.swag.name }}
                                    </h3>
                                </Link>
                                <p
                                    v-if="item.variation"
                                    class="mt-0.5 text-sm text-gray-500"
                                >
                                    {{ item.variation }}
                                </p>
                                <p class="mt-1 font-bold text-[#42b6c5]">
                                    {{
                                        formatMoney(
                                            item.swag.price,
                                            item.swag.currency,
                                        )
                                    }}
                                </p>

                                <div
                                    class="mt-3 flex items-center justify-between"
                                >
                                    <div
                                        class="flex items-center rounded-lg border border-gray-200"
                                    >
                                        <button
                                            @click="
                                                updateQuantity(
                                                    item.cart_key,
                                                    Math.max(
                                                        1,
                                                        item.quantity - 1,
                                                    ),
                                                )
                                            "
                                            :disabled="updating"
                                            class="px-3 py-1.5 text-gray-500 hover:text-[#000928] disabled:opacity-50"
                                        >
                                            -
                                        </button>
                                        <span
                                            class="min-w-[2.5rem] px-3 py-1.5 text-center text-sm font-semibold"
                                            >{{ item.quantity }}</span
                                        >
                                        <button
                                            @click="
                                                updateQuantity(
                                                    item.cart_key,
                                                    Math.min(
                                                        item.swag
                                                            .stock_quantity,
                                                        item.quantity + 1,
                                                    ),
                                                )
                                            "
                                            :disabled="updating"
                                            class="px-3 py-1.5 text-gray-500 hover:text-[#000928] disabled:opacity-50"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <button
                                        @click="removeItem(item.cart_key)"
                                        :disabled="updating"
                                        class="text-sm font-medium text-red-500 hover:text-red-700 disabled:opacity-50"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <!-- Item Total -->
                            <div class="shrink-0 text-right">
                                <p class="font-black text-[#000928]">
                                    {{
                                        formatMoney(
                                            item.total,
                                            item.swag.currency,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="lg:col-span-1">
                        <div
                            class="sticky top-24 rounded-xl bg-white p-6 shadow-sm"
                        >
                            <h3 class="mb-4 text-lg font-bold text-[#000928]">
                                Order Summary
                            </h3>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Subtotal</span>
                                    <span class="font-semibold">{{
                                        formatMoney(cartTotal, currency)
                                    }}</span>
                                </div>
                                <div
                                    v-if="surchargePercentage > 0"
                                    class="flex justify-between"
                                >
                                    <span class="text-gray-500"
                                        >Online surcharge ({{
                                            surchargePercentage
                                        }}%)</span
                                    >
                                    <span class="font-semibold">{{
                                        formatMoney(surchargeAmount, currency)
                                    }}</span>
                                </div>
                                <hr class="border-gray-100" />
                                <div class="flex justify-between text-lg">
                                    <span class="font-bold text-[#000928]"
                                        >Total</span
                                    >
                                    <span class="font-black text-[#42b6c5]">{{
                                        formatMoney(grandTotal, currency)
                                    }}</span>
                                </div>
                            </div>

                            <Link
                                href="/ai-forge/checkout"
                                class="mt-6 block w-full rounded-xl bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] py-4 text-center font-bold text-white transition-all hover:from-[#2d9aa8] hover:to-[#42b6c5]"
                            >
                                Proceed to Checkout
                            </Link>

                            <p class="mt-3 text-center text-xs text-gray-400">
                                Payment via Mobile Money (MTN / Orange)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
