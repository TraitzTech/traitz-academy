<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Form } from '@inertiajs/vue3'
import { watch } from 'vue'
import PublicLayout from '@/layouts/PublicLayout.vue'
import { useCart } from '@/composables/useCart'
import { useToast } from '@/composables/useToast'

interface CartItem {
    swag_id: number
    swag: { id: number; name: string; price: number; currency: string; image: string | null }
    variation: string | null
    quantity: number
    total: number
}

interface Props {
    event?: { id: number; title: string } | null
    cartItems: CartItem[]
    cartTotal: number
    surchargePercentage: number
    surchargeAmount: number
    grandTotal: number
    user: { first_name: string; last_name: string; email: string; phone: string } | null
}

const props = defineProps<Props>()
const { formatMoney } = useCart()
const toast = useToast()
const page = usePage()

const currency = props.cartItems[0]?.swag?.currency ?? 'XAF'

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}

// Watch for flash messages and show as toast notifications
watch(
    () => (page.props.flash as Record<string, string | null>)?.error,
    (errorMessage) => {
        if (errorMessage) {
            toast.error(errorMessage)
        }
    },
    { immediate: true },
)

watch(
    () => (page.props.flash as Record<string, string | null>)?.success,
    (successMessage) => {
        if (successMessage) {
            toast.success(successMessage)
        }
    },
    { immediate: true },
)
</script>

<template>
    <PublicLayout>
        <Head title="Checkout - AI Forge" />

        <section class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-black text-white">Checkout</h1>
                    <Link href="/ai-forge/cart" class="text-[#42b6c5] hover:text-white transition-colors font-medium">
                        ← Back to Cart
                    </Link>
                </div>
            </div>
        </section>

        <section class="py-12 bg-gray-50">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <Form action="/ai-forge/checkout" method="post" #default="{ errors, processing }">
                    <div class="grid lg:grid-cols-3 gap-8">
                        <!-- Customer Info & Payment -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Customer Information -->
                            <div class="bg-white rounded-xl p-6 shadow-sm">
                                <h2 class="text-lg font-bold text-[#000928] mb-4 flex items-center gap-2">
                                    <span class="w-7 h-7 bg-[#42b6c5] text-white rounded-full flex items-center justify-center text-sm font-black">1</span>
                                    Customer Information
                                </h2>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
                                        <input id="first_name" name="first_name" type="text" required :value="user?.first_name ?? ''" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" placeholder="First name" />
                                        <p v-if="errors.first_name" class="text-red-500 text-sm mt-1">{{ errors.first_name }}</p>
                                    </div>
                                    <div>
                                        <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
                                        <input id="last_name" name="last_name" type="text" required :value="user?.last_name ?? ''" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" placeholder="Last name" />
                                        <p v-if="errors.last_name" class="text-red-500 text-sm mt-1">{{ errors.last_name }}</p>
                                    </div>
                                    <div>
                                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                                        <input id="email" name="email" type="email" required :value="user?.email ?? ''" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" placeholder="your@email.com" />
                                        <p v-if="errors.email" class="text-red-500 text-sm mt-1">{{ errors.email }}</p>
                                    </div>
                                    <div>
                                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Phone Number</label>
                                        <input id="phone" name="phone" type="tel" required :value="user?.phone ?? ''" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" placeholder="6XXXXXXXX" />
                                        <p v-if="errors.phone" class="text-red-500 text-sm mt-1">{{ errors.phone }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="bg-white rounded-xl p-6 shadow-sm">
                                <h2 class="text-lg font-bold text-[#000928] mb-4 flex items-center gap-2">
                                    <span class="w-7 h-7 bg-[#42b6c5] text-white rounded-full flex items-center justify-center text-sm font-black">2</span>
                                    Payment Details
                                </h2>
                                <p class="text-sm text-gray-500 mb-4">Choose your mobile money provider and enter the phone number to receive the payment prompt.</p>

                                <div class="mb-4">
                                    <label for="payer_phone" class="block text-sm font-semibold text-gray-700 mb-1">Payer Phone Number</label>
                                    <input id="payer_phone" name="payer_phone" type="tel" required :value="user?.phone ?? ''" class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent" placeholder="6XXXXXXXX (MoMo/OM number)" />
                                    <p v-if="errors.payer_phone" class="text-red-500 text-sm mt-1">{{ errors.payer_phone }}</p>
                                </div>

                                <div class="grid sm:grid-cols-2 gap-4">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="provider" value="MTN" class="peer sr-only" required checked />
                                        <div class="border-2 border-gray-200 rounded-xl p-4 flex items-center gap-4 peer-checked:border-[#42b6c5] peer-checked:bg-[#42b6c5]/5 transition-all">
                                            <div class="w-12 h-12 bg-yellow-400 rounded-full flex items-center justify-center font-black text-sm text-black">MTN</div>
                                            <div>
                                                <p class="font-bold text-[#000928]">MTN Mobile Money</p>
                                                <p class="text-xs text-gray-500">Pay with MTN MoMo</p>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="provider" value="ORANGE" class="peer sr-only" />
                                        <div class="border-2 border-gray-200 rounded-xl p-4 flex items-center gap-4 peer-checked:border-[#42b6c5] peer-checked:bg-[#42b6c5]/5 transition-all">
                                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center font-black text-sm text-white">OM</div>
                                            <div>
                                                <p class="font-bold text-[#000928]">Orange Money</p>
                                                <p class="text-xs text-gray-500">Pay with Orange Money</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="errors.provider" class="text-red-500 text-sm mt-2">{{ errors.provider }}</p>
                            </div>

                            <!-- Error messages -->
                            <div v-if="errors.cart || errors.payment" class="bg-red-50 border border-red-200 rounded-xl p-4">
                                <p class="text-red-600 font-semibold">{{ errors.cart || errors.payment }}</p>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-xl p-6 shadow-sm sticky top-24">
                                <h3 class="text-lg font-bold text-[#000928] mb-4">Order Summary</h3>

                                <div class="space-y-3 mb-4">
                                    <div v-for="item in cartItems" :key="`${item.swag_id}-${item.variation}`" class="flex gap-3">
                                        <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                                            <img v-if="item.swag.image" :src="getImageUrl(item.swag.image)" :alt="item.swag.name" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-[#000928] truncate">{{ item.swag.name }}</p>
                                            <p v-if="item.variation" class="text-xs text-gray-400">{{ item.variation }}</p>
                                            <p class="text-xs text-gray-500">Qty: {{ item.quantity }}</p>
                                        </div>
                                        <p class="text-sm font-bold shrink-0">{{ formatMoney(item.total, currency) }}</p>
                                    </div>
                                </div>

                                <hr class="border-gray-100 my-4" />

                                <div class="space-y-2 text-sm">
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

                                <button type="submit" :disabled="processing" class="w-full bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] text-white font-bold py-4 rounded-xl text-center hover:from-[#2d9aa8] hover:to-[#42b6c5] transition-all mt-6 disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ processing ? 'Processing Payment...' : `Pay ${formatMoney(grandTotal, currency)}` }}
                                </button>

                                <p class="text-xs text-gray-400 text-center mt-3">
                                    Secure payment via MeSomb. You'll receive a prompt on your phone.
                                </p>
                            </div>
                        </div>
                    </div>
                </Form>
            </div>
        </section>
    </PublicLayout>
</template>
