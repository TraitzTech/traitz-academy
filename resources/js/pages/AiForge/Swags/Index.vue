<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import { useCart } from '@/composables/useCart';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Swag {
    id: number;
    name: string;
    slug: string;
    category: string;
    description: string | null;
    price: number;
    currency: string;
    image: string | null;
    variations:
        | {
              type: string;
              options: (string | { name: string; image?: string | null })[];
          }[]
        | null;
    stock_quantity: number;
    is_featured: boolean;
}

interface Props {
    event: { title: string; swag_store_active: boolean };
    swags: Swag[];
    categories: Record<string, number>;
    filters: { category?: string; search?: string };
}

const props = defineProps<Props>();
const { formatMoney, addToCart, updating } = useCart();

const search = ref(props.filters.search ?? '');
const selectedCategory = ref(props.filters.category ?? '');
const quickAddSwag = ref<number | null>(null);
const selectedVariation = ref('');
const selectedQuantity = ref(1);

const categoryLabels: Record<string, string> = {
    't-shirt': 'T-Shirts',
    polo: 'Polos',
    hoodie: 'Hoodies',
    cap: 'Caps',
    'water-bottle': 'Water Bottles',
    'sticker-pack': 'Sticker Packs',
    'tote-bag': 'Tote Bags',
    notebook: 'Notebooks',
    other: 'Other',
};

const getOptionName = (
    opt: string | { name: string; image?: string | null },
): string => (typeof opt === 'string' ? opt : opt.name);

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined;
    if (path.startsWith('http')) return path;
    return `/storage/${path}`;
};

const filterByCategory = (category: string) => {
    selectedCategory.value = category;
    applyFilters();
};

const applyFilters = () => {
    router.get(
        '/ai-forge/swags',
        {
            ...(selectedCategory.value
                ? { category: selectedCategory.value }
                : {}),
            ...(search.value ? { search: search.value } : {}),
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    selectedCategory.value = '';
    search.value = '';
    router.get('/ai-forge/swags', {}, { preserveState: true });
};

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

const openQuickAdd = (swag: Swag) => {
    quickAddSwag.value = swag.id;
    selectedVariation.value = '';
    selectedQuantity.value = 1;
};

const handleAddToCart = (swag: Swag) => {
    addToCart(swag.id, selectedVariation.value || null, selectedQuantity.value);
    quickAddSwag.value = null;
};
</script>

<template>
    <PublicLayout>
        <Head title="AI Forge Swag Store" />

        <!-- Hero -->
        <section
            class="bg-gradient-to-br from-[#000928] via-[#0f0635] to-[#1a0052] py-20"
        >
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="mb-4 text-4xl font-black text-white sm:text-5xl">
                    <span
                        class="bg-gradient-to-r from-[#42b6c5] to-white bg-clip-text text-transparent"
                        >AI Forge</span
                    >
                    Swag Store
                </h1>
                <p class="mx-auto mb-8 max-w-2xl text-lg text-gray-300">
                    Get your exclusive AI Forge merchandise. Rock the gear, rep
                    the community!
                </p>
                <div class="flex items-center justify-center gap-4">
                    <Link
                        href="/ai-forge"
                        class="font-medium text-[#42b6c5] transition-colors hover:text-white"
                    >
                        ← Back to AI Forge
                    </Link>
                    <Link
                        href="/ai-forge/cart"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-2.5 font-semibold text-white transition-colors hover:bg-white/20"
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
                        View Cart
                    </Link>
                </div>
            </div>
        </section>

        <!-- Filters & Products -->
        <section class="min-h-screen bg-gray-50 py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Search & Filters -->
                <div class="mb-8 flex flex-col gap-4 sm:flex-row">
                    <div class="relative flex-1">
                        <svg
                            class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search swag..."
                            class="w-full rounded-xl border border-gray-200 py-3 pr-4 pl-10 transition-colors focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                        />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="clearFilters"
                            :class="[
                                'rounded-xl px-4 py-2 text-sm font-medium transition-colors',
                                !selectedCategory
                                    ? 'bg-[#000928] text-white'
                                    : 'border border-gray-200 bg-white text-gray-700 hover:border-[#42b6c5]',
                            ]"
                        >
                            All
                        </button>
                        <button
                            v-for="(count, category) in categories"
                            :key="category"
                            @click="filterByCategory(category as string)"
                            :class="[
                                'rounded-xl px-4 py-2 text-sm font-medium transition-colors',
                                selectedCategory === category
                                    ? 'bg-[#000928] text-white'
                                    : 'border border-gray-200 bg-white text-gray-700 hover:border-[#42b6c5]',
                            ]"
                        >
                            {{
                                categoryLabels[category as string] || category
                            }}
                            ({{ count }})
                        </button>
                    </div>
                </div>

                <!-- Product Grid -->
                <div
                    v-if="swags.length"
                    class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <div
                        v-for="swag in swags"
                        :key="swag.id"
                        class="group overflow-hidden rounded-2xl bg-white shadow-sm transition-all duration-300 hover:shadow-xl"
                    >
                        <Link
                            :href="`/ai-forge/swags/${swag.slug}`"
                            class="block"
                        >
                            <div
                                class="relative aspect-square overflow-hidden bg-gradient-to-br from-gray-100 to-gray-50"
                            >
                                <img
                                    v-if="swag.image"
                                    :src="getImageUrl(swag.image)"
                                    :alt="swag.name"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center"
                                >
                                    <svg
                                        class="h-20 w-20 text-gray-200"
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
                                <!-- Featured badge -->
                                <div
                                    v-if="swag.is_featured"
                                    class="absolute top-3 left-3 rounded-full bg-gradient-to-r from-[#42b6c5] to-[#2d9aa8] px-3 py-1 text-xs font-bold text-white"
                                >
                                    Featured
                                </div>
                                <!-- Out of stock -->
                                <div
                                    v-if="swag.stock_quantity <= 0"
                                    class="absolute inset-0 flex items-center justify-center bg-black/40"
                                >
                                    <span
                                        class="rounded-xl bg-red-600 px-4 py-2 font-bold text-white"
                                        >Out of Stock</span
                                    >
                                </div>
                            </div>
                        </Link>

                        <div class="p-4">
                            <p
                                class="mb-1 text-xs tracking-wider text-gray-400 uppercase"
                            >
                                {{
                                    categoryLabels[swag.category] ||
                                    swag.category
                                }}
                            </p>
                            <Link :href="`/ai-forge/swags/${swag.slug}`">
                                <h3
                                    class="font-bold text-[#000928] transition-colors group-hover:text-[#42b6c5]"
                                >
                                    {{ swag.name }}
                                </h3>
                            </Link>
                            <div class="mt-3 flex items-center justify-between">
                                <span
                                    class="text-xl font-black text-[#42b6c5]"
                                    >{{
                                        formatMoney(swag.price, swag.currency)
                                    }}</span
                                >
                                <button
                                    v-if="swag.stock_quantity > 0"
                                    @click.prevent="openQuickAdd(swag)"
                                    class="rounded-xl bg-[#000928] p-2 text-white transition-colors hover:bg-[#42b6c5]"
                                    title="Add to cart"
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
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Quick add overlay -->
                        <div
                            v-if="quickAddSwag === swag.id"
                            class="animate-in border-t border-gray-100 p-4 pt-0"
                        >
                            <div v-if="swag.variations?.length" class="mb-3">
                                <label
                                    class="mb-1 block text-xs font-semibold text-gray-500"
                                    >Select option</label
                                >
                                <select
                                    v-model="selectedVariation"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                >
                                    <option value="">Choose...</option>
                                    <template
                                        v-for="variation in swag.variations"
                                        :key="variation.type"
                                    >
                                        <option
                                            v-for="opt in variation.options"
                                            :key="getOptionName(opt)"
                                            :value="`${variation.type}: ${getOptionName(opt)}`"
                                        >
                                            {{ variation.type }}:
                                            {{ getOptionName(opt) }}
                                        </option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex items-center rounded-lg border border-gray-200"
                                >
                                    <button
                                        @click="
                                            selectedQuantity = Math.max(
                                                1,
                                                selectedQuantity - 1,
                                            )
                                        "
                                        class="px-3 py-1.5 text-gray-500 hover:text-[#000928]"
                                    >
                                        -
                                    </button>
                                    <span
                                        class="px-3 py-1.5 text-sm font-semibold"
                                        >{{ selectedQuantity }}</span
                                    >
                                    <button
                                        @click="
                                            selectedQuantity = Math.min(
                                                10,
                                                selectedQuantity + 1,
                                            )
                                        "
                                        class="px-3 py-1.5 text-gray-500 hover:text-[#000928]"
                                    >
                                        +
                                    </button>
                                </div>
                                <button
                                    @click="handleAddToCart(swag)"
                                    :disabled="updating"
                                    class="flex-1 rounded-lg bg-[#42b6c5] py-2 text-sm font-semibold text-white transition-colors hover:bg-[#2d9aa8] disabled:opacity-50"
                                >
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="py-20 text-center">
                    <svg
                        class="mx-auto mb-4 h-16 w-16 text-gray-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                        />
                    </svg>
                    <h3 class="mb-2 text-xl font-bold text-gray-500">
                        No swag items found
                    </h3>
                    <p class="text-gray-400">
                        Try a different search or category filter.
                    </p>
                    <button
                        @click="clearFilters"
                        class="mt-4 font-semibold text-[#42b6c5] hover:underline"
                    >
                        Clear filters
                    </button>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
