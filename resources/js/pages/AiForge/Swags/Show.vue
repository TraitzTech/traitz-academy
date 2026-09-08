<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import { useCart } from '@/composables/useCart';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface VariationOption {
    name: string;
    image?: string | null;
}

interface Variation {
    type: string;
    options: (string | VariationOption)[];
}

interface Swag {
    id: number;
    name: string;
    slug: string;
    category: string;
    description: string | null;
    price: number;
    currency: string;
    image: string | null;
    gallery_images: string[] | null;
    variations: Variation[] | null;
    stock_quantity: number;
    is_featured: boolean;
}

interface Props {
    swag: Swag;
    relatedSwags: Swag[];
}

const props = defineProps<Props>();
const { formatMoney, addToCart, updating } = useCart();

const selectedVariations = ref<Record<string, string>>({});
const quantity = ref(1);
const activeImage = ref(0);

const getOptionName = (opt: string | VariationOption): string =>
    typeof opt === 'string' ? opt : opt.name;

const getOptionImage = (opt: string | VariationOption): string | null =>
    typeof opt === 'string' ? null : (opt.image ?? null);

const normalizedVariations = computed(
    () =>
        props.swag.variations?.map((v) => ({
            type: v.type,
            options: v.options.map((opt) => ({
                name: getOptionName(opt),
                image: getOptionImage(opt),
            })),
        })) ?? [],
);

const allImages = computed(() => {
    const images: string[] = [];
    if (props.swag.image) images.push(props.swag.image);
    if (props.swag.gallery_images?.length)
        images.push(...props.swag.gallery_images);
    // Add variation images that have images
    for (const v of normalizedVariations.value) {
        for (const opt of v.options) {
            if (opt.image && !images.includes(opt.image)) {
                images.push(opt.image);
            }
        }
    }
    return images;
});

const selectedVariationString = computed(() => {
    const parts = Object.entries(selectedVariations.value)
        .filter(([, v]) => v)
        .map(([type, val]) => `${type}: ${val}`);
    return parts.join(', ');
});

const hasVariationsRequiringSelection = computed(
    () => normalizedVariations.value.length > 0,
);

// Watch for variation selection to switch image
watch(
    selectedVariations,
    (newVal) => {
        for (const v of normalizedVariations.value) {
            const selectedOpt = v.options.find(
                (opt) => newVal[v.type] === opt.name,
            );
            if (selectedOpt?.image) {
                const imgIdx = allImages.value.indexOf(selectedOpt.image);
                if (imgIdx >= 0) {
                    activeImage.value = imgIdx;
                    return;
                }
            }
        }
    },
    { deep: true },
);

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined;
    if (path.startsWith('http')) return path;
    return `/storage/${path}`;
};

const categoryLabels: Record<string, string> = {
    't-shirt': 'T-Shirt',
    polo: 'Polo',
    hoodie: 'Hoodie',
    cap: 'Cap',
    'water-bottle': 'Water Bottle',
    'sticker-pack': 'Sticker Pack',
    'tote-bag': 'Tote Bag',
    notebook: 'Notebook',
    other: 'Other',
};

const handleAddToCart = () => {
    addToCart(
        props.swag.id,
        selectedVariationString.value || null,
        quantity.value,
    );
};
</script>

<template>
    <PublicLayout>
        <Head :title="`${swag.name} - AI Forge Swag`" />

        <section
            class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-8"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center gap-2 text-sm text-gray-400">
                    <Link
                        href="/ai-forge"
                        class="transition-colors hover:text-[#42b6c5]"
                        >AI Forge</Link
                    >
                    <span>/</span>
                    <Link
                        href="/ai-forge/swags"
                        class="transition-colors hover:text-[#42b6c5]"
                        >Swag Store</Link
                    >
                    <span>/</span>
                    <span class="text-white">{{ swag.name }}</span>
                </nav>
            </div>
        </section>

        <section class="bg-white py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-12 lg:grid-cols-2">
                    <!-- Image Gallery -->
                    <div>
                        <div
                            class="mb-4 aspect-square overflow-hidden rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100"
                        >
                            <img
                                v-if="allImages.length"
                                :src="getImageUrl(allImages[activeImage])"
                                :alt="swag.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center"
                            >
                                <svg
                                    class="h-32 w-32 text-gray-200"
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
                        <div
                            v-if="allImages.length > 1"
                            class="grid grid-cols-5 gap-3"
                        >
                            <button
                                v-for="(img, idx) in allImages"
                                :key="idx"
                                @click="activeImage = idx"
                                :class="[
                                    'aspect-square overflow-hidden rounded-lg border-2 transition-colors',
                                    activeImage === idx
                                        ? 'border-[#42b6c5]'
                                        : 'border-gray-200 hover:border-gray-300',
                                ]"
                            >
                                <img
                                    :src="getImageUrl(img)"
                                    :alt="`${swag.name} ${idx + 1}`"
                                    class="h-full w-full object-cover"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div>
                        <div class="mb-4 flex items-center gap-3">
                            <span
                                class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold tracking-wider text-gray-600 uppercase"
                                >{{
                                    categoryLabels[swag.category] ||
                                    swag.category
                                }}</span
                            >
                            <span
                                v-if="swag.is_featured"
                                class="rounded-full bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] px-3 py-1 text-xs font-bold text-white"
                                >Featured</span
                            >
                        </div>

                        <h1
                            class="mb-4 text-3xl font-black text-[#000928] sm:text-4xl"
                        >
                            {{ swag.name }}
                        </h1>

                        <div class="mb-6 text-4xl font-black text-[#42b6c5]">
                            {{ formatMoney(swag.price, swag.currency) }}
                        </div>

                        <div
                            v-if="swag.description"
                            class="prose mb-8 leading-relaxed text-gray-600 prose-gray"
                            v-html="swag.description"
                        />

                        <!-- Stock Indicator -->
                        <div class="mb-6">
                            <div
                                v-if="swag.stock_quantity > 10"
                                class="flex items-center gap-2 text-green-600"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <circle cx="10" cy="10" r="5" />
                                </svg>
                                <span class="text-sm font-medium"
                                    >In Stock</span
                                >
                            </div>
                            <div
                                v-else-if="swag.stock_quantity > 0"
                                class="flex items-center gap-2 text-amber-600"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <circle cx="10" cy="10" r="5" />
                                </svg>
                                <span class="text-sm font-medium"
                                    >Only {{ swag.stock_quantity }} left</span
                                >
                            </div>
                            <div
                                v-else
                                class="flex items-center gap-2 text-red-600"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <circle cx="10" cy="10" r="5" />
                                </svg>
                                <span class="text-sm font-medium"
                                    >Out of Stock</span
                                >
                            </div>
                        </div>

                        <!-- Variations -->
                        <div v-if="normalizedVariations.length" class="mb-6">
                            <div
                                v-for="variation in normalizedVariations"
                                :key="variation.type"
                                class="mb-4"
                            >
                                <label
                                    class="mb-2 block text-sm font-bold text-[#000928]"
                                    >{{ variation.type }}</label
                                >
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="opt in variation.options"
                                        :key="opt.name"
                                        @click="
                                            selectedVariations[variation.type] =
                                                opt.name
                                        "
                                        :class="[
                                            'rounded-lg border-2 text-sm font-medium transition-all',
                                            selectedVariations[
                                                variation.type
                                            ] === opt.name
                                                ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#42b6c5]'
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300',
                                            opt.image ? 'p-1.5' : 'px-4 py-2',
                                        ]"
                                    >
                                        <div
                                            v-if="opt.image"
                                            class="flex flex-col items-center gap-1.5"
                                        >
                                            <img
                                                :src="getImageUrl(opt.image)"
                                                :alt="opt.name"
                                                class="h-14 w-14 rounded object-cover"
                                            />
                                            <span class="text-xs">{{
                                                opt.name
                                            }}</span>
                                        </div>
                                        <span v-else>{{ opt.name }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="mb-8" v-if="swag.stock_quantity > 0">
                            <label
                                class="mb-2 block text-sm font-bold text-[#000928]"
                                >Quantity</label
                            >
                            <div
                                class="inline-flex items-center rounded-xl border-2 border-gray-200"
                            >
                                <button
                                    @click="
                                        quantity = Math.max(1, quantity - 1)
                                    "
                                    class="px-4 py-3 text-lg font-bold text-gray-500 hover:text-[#000928]"
                                >
                                    −
                                </button>
                                <span
                                    class="border-x-2 border-gray-200 px-6 py-3 text-lg font-bold"
                                    >{{ quantity }}</span
                                >
                                <button
                                    @click="
                                        quantity = Math.min(
                                            swag.stock_quantity,
                                            quantity + 1,
                                        )
                                    "
                                    class="px-4 py-3 text-lg font-bold text-gray-500 hover:text-[#000928]"
                                >
                                    +
                                </button>
                            </div>
                        </div>

                        <!-- Add to Cart -->
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button
                                v-if="swag.stock_quantity > 0"
                                @click="handleAddToCart"
                                :disabled="
                                    updating ||
                                    (hasVariationsRequiringSelection &&
                                        !selectedVariationString)
                                "
                                class="flex-1 rounded-xl bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] px-8 py-4 text-lg font-bold text-white transition-all hover:from-[#2d9aa8] hover:to-[#42b6c5] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ updating ? 'Adding...' : 'Add to Cart' }}
                            </button>
                            <button
                                v-else
                                disabled
                                class="flex-1 cursor-not-allowed rounded-xl bg-gray-200 px-8 py-4 text-lg font-bold text-gray-500"
                            >
                                Out of Stock
                            </button>
                            <Link
                                href="/ai-forge/cart"
                                class="flex items-center justify-center gap-2 rounded-xl bg-[#000928] px-8 py-4 font-bold text-white transition-colors hover:bg-[#000928]/90"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"
                                    />
                                </svg>
                                Cart
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Related Products -->
        <section v-if="relatedSwags.length" class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-8 text-2xl font-black text-[#000928]">
                    You might also like
                </h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="item in relatedSwags"
                        :key="item.id"
                        :href="`/ai-forge/swags/${item.slug}`"
                        class="group overflow-hidden rounded-2xl bg-white shadow-sm transition-all duration-300 hover:shadow-xl"
                    >
                        <div
                            class="aspect-square overflow-hidden bg-gradient-to-br from-gray-100 to-gray-50"
                        >
                            <img
                                v-if="item.image"
                                :src="getImageUrl(item.image)"
                                :alt="item.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center"
                            >
                                <svg
                                    class="h-16 w-16 text-gray-200"
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
                        <div class="p-4">
                            <h3
                                class="font-bold text-[#000928] transition-colors group-hover:text-[#42b6c5]"
                            >
                                {{ item.name }}
                            </h3>
                            <span class="text-lg font-black text-[#42b6c5]">{{
                                formatMoney(item.price, item.currency)
                            }}</span>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
