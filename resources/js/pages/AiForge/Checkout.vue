<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

import { useCart } from '@/composables/useCart';
import { useToast } from '@/composables/useToast';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface CartItem {
    swag_id: number;
    swag: {
        id: number;
        name: string;
        price: number;
        currency: string;
        image: string | null;
    };
    variation: string | null;
    quantity: number;
    total: number;
}

interface Props {
    event?: { id: number; title: string } | null;
    cartItems: CartItem[];
    cartTotal: number;
    surchargePercentage: number;
    surchargeAmount: number;
    grandTotal: number;
    user: {
        first_name: string;
        last_name: string;
        email: string;
        phone: string;
    } | null;
}

const props = defineProps<Props>();
const { formatMoney } = useCart();
const toast = useToast();
const page = usePage();

const currency = props.cartItems[0]?.swag?.currency ?? 'XAF';

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined;
    if (path.startsWith('http')) return path;
    return `/storage/${path}`;
};

// Watch for flash messages and show as toast notifications
watch(
    () => (page.props.flash as Record<string, string | null>)?.error,
    (errorMessage) => {
        if (errorMessage) {
            toast.error(errorMessage);
        }
    },
    { immediate: true },
);

watch(
    () => (page.props.flash as Record<string, string | null>)?.success,
    (successMessage) => {
        if (successMessage) {
            toast.success(successMessage);
        }
    },
    { immediate: true },
);
</script>

<template>
    <PublicLayout>
        <Head title="Checkout - AI Forge" />

        <section
            class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-12"
        >
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-black text-white">Checkout</h1>
                    <Link
                        href="/ai-forge/cart"
                        class="font-medium text-[#42b6c5] transition-colors hover:text-white"
                    >
                        ← Back to Cart
                    </Link>
                </div>
            </div>
        </section>

        <section class="bg-gray-50 py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <Form
                    action="/ai-forge/checkout"
                    method="post"
                    #default="{ errors, processing }"
                >
                    <div class="grid gap-8 lg:grid-cols-3">
                        <!-- Customer Info & Payment -->
                        <div class="space-y-6 lg:col-span-2">
                            <!-- Customer Information -->
                            <div class="rounded-xl bg-white p-6 shadow-sm">
                                <h2
                                    class="mb-4 flex items-center gap-2 text-lg font-bold text-[#000928]"
                                >
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-[#42b6c5] text-sm font-black text-white"
                                        >1</span
                                    >
                                    Customer Information
                                </h2>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label
                                            for="first_name"
                                            class="mb-1 block text-sm font-semibold text-gray-700"
                                            >First Name</label
                                        >
                                        <input
                                            id="first_name"
                                            name="first_name"
                                            type="text"
                                            required
                                            :value="user?.first_name ?? ''"
                                            class="w-full rounded-lg border border-gray-200 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                            placeholder="First name"
                                        />
                                        <p
                                            v-if="errors.first_name"
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{ errors.first_name }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="last_name"
                                            class="mb-1 block text-sm font-semibold text-gray-700"
                                            >Last Name</label
                                        >
                                        <input
                                            id="last_name"
                                            name="last_name"
                                            type="text"
                                            required
                                            :value="user?.last_name ?? ''"
                                            class="w-full rounded-lg border border-gray-200 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                            placeholder="Last name"
                                        />
                                        <p
                                            v-if="errors.last_name"
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{ errors.last_name }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="email"
                                            class="mb-1 block text-sm font-semibold text-gray-700"
                                            >Email</label
                                        >
                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            required
                                            :value="user?.email ?? ''"
                                            class="w-full rounded-lg border border-gray-200 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                            placeholder="your@email.com"
                                        />
                                        <p
                                            v-if="errors.email"
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{ errors.email }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="phone"
                                            class="mb-1 block text-sm font-semibold text-gray-700"
                                            >Phone Number</label
                                        >
                                        <input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            required
                                            :value="user?.phone ?? ''"
                                            class="w-full rounded-lg border border-gray-200 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                            placeholder="6XXXXXXXX"
                                        />
                                        <p
                                            v-if="errors.phone"
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{ errors.phone }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="rounded-xl bg-white p-6 shadow-sm">
                                <h2
                                    class="mb-4 flex items-center gap-2 text-lg font-bold text-[#000928]"
                                >
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-[#42b6c5] text-sm font-black text-white"
                                        >2</span
                                    >
                                    Payment Details
                                </h2>
                                <p class="mb-4 text-sm text-gray-500">
                                    Choose your mobile money provider and enter
                                    the phone number to receive the payment
                                    prompt.
                                </p>

                                <div class="mb-4">
                                    <label
                                        for="payer_phone"
                                        class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Payer Phone Number</label
                                    >
                                    <input
                                        id="payer_phone"
                                        name="payer_phone"
                                        type="tel"
                                        required
                                        :value="user?.phone ?? ''"
                                        class="w-full rounded-lg border border-gray-200 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        placeholder="6XXXXXXXX (MoMo/OM number)"
                                    />
                                    <p
                                        v-if="errors.payer_phone"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ errors.payer_phone }}
                                    </p>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="relative cursor-pointer">
                                        <input
                                            type="radio"
                                            name="provider"
                                            value="MTN"
                                            class="peer sr-only"
                                            required
                                            checked
                                        />
                                        <div
                                            class="flex items-center gap-4 rounded-xl border-2 border-gray-200 p-4 transition-all peer-checked:border-[#42b6c5] peer-checked:bg-[#42b6c5]/5"
                                        >
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-full bg-yellow-400 text-sm font-black text-black"
                                            >
                                                MTN
                                            </div>
                                            <div>
                                                <p
                                                    class="font-bold text-[#000928]"
                                                >
                                                    MTN Mobile Money
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Pay with MTN MoMo
                                                </p>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input
                                            type="radio"
                                            name="provider"
                                            value="ORANGE"
                                            class="peer sr-only"
                                        />
                                        <div
                                            class="flex items-center gap-4 rounded-xl border-2 border-gray-200 p-4 transition-all peer-checked:border-[#42b6c5] peer-checked:bg-[#42b6c5]/5"
                                        >
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-full bg-orange-500 text-sm font-black text-white"
                                            >
                                                OM
                                            </div>
                                            <div>
                                                <p
                                                    class="font-bold text-[#000928]"
                                                >
                                                    Orange Money
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    Pay with Orange Money
                                                </p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <p
                                    v-if="errors.provider"
                                    class="mt-2 text-sm text-red-500"
                                >
                                    {{ errors.provider }}
                                </p>
                            </div>

                            <!-- Error messages -->
                            <div
                                v-if="errors.cart || errors.payment"
                                class="rounded-xl border border-red-200 bg-red-50 p-4"
                            >
                                <p class="font-semibold text-red-600">
                                    {{ errors.cart || errors.payment }}
                                </p>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="lg:col-span-1">
                            <div
                                class="sticky top-24 rounded-xl bg-white p-6 shadow-sm"
                            >
                                <h3
                                    class="mb-4 text-lg font-bold text-[#000928]"
                                >
                                    Order Summary
                                </h3>

                                <div class="mb-4 space-y-3">
                                    <div
                                        v-for="item in cartItems"
                                        :key="`${item.swag_id}-${item.variation}`"
                                        class="flex gap-3"
                                    >
                                        <div
                                            class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-gray-100"
                                        >
                                            <img
                                                v-if="item.swag.image"
                                                :src="
                                                    getImageUrl(item.swag.image)
                                                "
                                                :alt="item.swag.name"
                                                class="h-full w-full object-cover"
                                            />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-semibold text-[#000928]"
                                            >
                                                {{ item.swag.name }}
                                            </p>
                                            <p
                                                v-if="item.variation"
                                                class="text-xs text-gray-400"
                                            >
                                                {{ item.variation }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Qty: {{ item.quantity }}
                                            </p>
                                        </div>
                                        <p class="shrink-0 text-sm font-bold">
                                            {{
                                                formatMoney(
                                                    item.total,
                                                    currency,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <hr class="my-4 border-gray-100" />

                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500"
                                            >Subtotal</span
                                        >
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
                                            formatMoney(
                                                surchargeAmount,
                                                currency,
                                            )
                                        }}</span>
                                    </div>
                                    <hr class="border-gray-100" />
                                    <div class="flex justify-between text-lg">
                                        <span class="font-bold text-[#000928]"
                                            >Total</span
                                        >
                                        <span
                                            class="font-black text-[#42b6c5]"
                                            >{{
                                                formatMoney(
                                                    grandTotal,
                                                    currency,
                                                )
                                            }}</span
                                        >
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="mt-6 w-full rounded-xl bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] py-4 text-center font-bold text-white transition-all hover:from-[#2d9aa8] hover:to-[#42b6c5] disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{
                                        processing
                                            ? 'Processing Payment...'
                                            : `Pay ${formatMoney(grandTotal, currency)}`
                                    }}
                                </button>

                                <p
                                    class="mt-3 text-center text-xs text-gray-400"
                                >
                                    Secure payment via MeSomb. You'll receive a
                                    prompt on your phone.
                                </p>
                            </div>
                        </div>
                    </div>
                </Form>
            </div>
        </section>
    </PublicLayout>
</template>
