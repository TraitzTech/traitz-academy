<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface VariationOption {
    name: string;
    imageFile: File | null;
    imagePreview: string | null;
}

interface VariationType {
    type: string;
    options: VariationOption[];
}

interface Props {
    event: { id: number; title: string } | null;
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const toast = useToast();

const form = useForm({
    name: '',
    category: '',
    description: '',
    price: 0,
    currency: 'XAF',
    image_file: null as File | null,
    stock_quantity: 100,
    sort_order: 0,
    is_active: true,
    is_featured: false,
    variations: '[]',
});

const imagePreview = ref<string | null>(null);
const variationTypes = ref<VariationType[]>([]);

const categories = [
    { value: 't-shirt', label: 'T-Shirt' },
    { value: 'polo', label: 'Polo' },
    { value: 'hoodie', label: 'Hoodie' },
    { value: 'cap', label: 'Cap' },
    { value: 'water-bottle', label: 'Water Bottle' },
    { value: 'sticker-pack', label: 'Sticker Pack' },
    { value: 'tote-bag', label: 'Tote Bag' },
    { value: 'notebook', label: 'Notebook' },
    { value: 'other', label: 'Other' },
];

const variationPresets = ['Color', 'Size', 'Style', 'Material'];

const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files?.[0]) {
        form.image_file = target.files[0];
        imagePreview.value = URL.createObjectURL(target.files[0]);
    }
};

const addVariationType = () => {
    variationTypes.value.push({
        type: '',
        options: [{ name: '', imageFile: null, imagePreview: null }],
    });
};

const removeVariationType = (idx: number) => {
    variationTypes.value.splice(idx, 1);
};

const addOption = (typeIdx: number) => {
    variationTypes.value[typeIdx].options.push({
        name: '',
        imageFile: null,
        imagePreview: null,
    });
};

const removeOption = (typeIdx: number, optIdx: number) => {
    variationTypes.value[typeIdx].options.splice(optIdx, 1);
};

const handleOptionImage = (typeIdx: number, optIdx: number, e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files?.[0]) {
        const file = target.files[0];
        variationTypes.value[typeIdx].options[optIdx].imageFile = file;
        variationTypes.value[typeIdx].options[optIdx].imagePreview =
            URL.createObjectURL(file);
    }
};

const removeOptionImage = (typeIdx: number, optIdx: number) => {
    variationTypes.value[typeIdx].options[optIdx].imageFile = null;
    variationTypes.value[typeIdx].options[optIdx].imagePreview = null;
};

const submit = () => {
    form.transform((data) => {
        const transformed: Record<string, any> = { ...data };

        const validTypes = variationTypes.value
            .filter(
                (vt) => vt.type.trim() && vt.options.some((o) => o.name.trim()),
            )
            .map((vt) => ({
                type: vt.type.trim(),
                options: vt.options
                    .filter((o) => o.name.trim())
                    .map((o) => ({
                        name: o.name.trim(),
                        image: null,
                    })),
            }));

        transformed.variations = validTypes.length
            ? JSON.stringify(validTypes)
            : null;

        let typeIdx = 0;
        variationTypes.value.forEach((vt) => {
            if (!vt.type.trim() || !vt.options.some((o) => o.name.trim()))
                return;
            let optIdx = 0;
            vt.options.forEach((opt) => {
                if (!opt.name.trim()) return;
                if (opt.imageFile) {
                    transformed[`variation_images_${typeIdx}_${optIdx}`] =
                        opt.imageFile;
                }
                optIdx++;
            });
            typeIdx++;
        });

        return transformed;
    }).post('/admin/ai-forge/swags', {
        forceFormData: true,
        // Flash message handled by global watcher (AiForgeSwagController::store flashes 'success')
        onError: () =>
            toast.error(
                'Failed to create swag. Please check the form for errors.',
            ),
    });
};
</script>

<template>
    <div class="mx-auto max-w-5xl">
        <Head title="Add Swag - AI Forge" />

        <div class="mb-8">
            <Link
                href="/admin/ai-forge/swags"
                class="mb-4 inline-flex items-center text-[#42b6c5] hover:text-[#35919e]"
            >
                <svg
                    class="mr-2 h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                Back to Swags
            </Link>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Add New Swag
            </h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Add merchandise for {{ event?.title ?? 'AI Forge' }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Product Details
                </h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Name *</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Category *</label
                        >
                        <select
                            v-model="form.category"
                            required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">Select category</option>
                            <option
                                v-for="cat in categories"
                                :key="cat.value"
                                :value="cat.value"
                            >
                                {{ cat.label }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.category"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.category }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Price (XAF) *</label
                        >
                        <input
                            v-model.number="form.price"
                            type="number"
                            min="0"
                            required
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.price"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.price }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Stock Quantity</label
                        >
                        <input
                            v-model.number="form.stock_quantity"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.stock_quantity"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.stock_quantity }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Sort Order</label
                        >
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Description</label
                        >
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.description"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Image -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Product Image
                </h3>
                <input
                    type="file"
                    accept="image/*"
                    @change="handleImageChange"
                    class="w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-[#42b6c5]/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[#42b6c5] hover:file:bg-[#42b6c5]/20"
                />
                <img
                    v-if="imagePreview"
                    :src="imagePreview"
                    alt="Preview"
                    class="mt-4 h-40 rounded-lg object-cover"
                />
                <p
                    v-if="form.errors.image_file"
                    class="mt-1 text-sm text-red-500"
                >
                    {{ form.errors.image_file }}
                </p>
            </div>

            <!-- Variations Builder -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3
                            class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Variations
                        </h3>
                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Add options like Color, Size, etc. Each option can
                            have its own image.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="addVariationType"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-[#42b6c5]/10 px-4 py-2 text-sm font-semibold text-[#42b6c5] transition-colors hover:bg-[#42b6c5]/20"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Add Variation Type
                    </button>
                </div>

                <div
                    v-if="!variationTypes.length"
                    class="rounded-xl border-2 border-dashed border-gray-200 py-8 text-center dark:border-gray-700"
                >
                    <svg
                        class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No variations yet. Add types like Color, Size, Style,
                        etc.
                    </p>
                    <div class="mt-3 flex flex-wrap justify-center gap-2">
                        <button
                            v-for="preset in variationPresets"
                            :key="preset"
                            type="button"
                            @click="
                                variationTypes.push({
                                    type: preset,
                                    options: [
                                        {
                                            name: '',
                                            imageFile: null,
                                            imagePreview: null,
                                        },
                                    ],
                                })
                            "
                            class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            + {{ preset }}
                        </button>
                    </div>
                </div>

                <div v-else class="space-y-6">
                    <div
                        v-for="(vType, typeIdx) in variationTypes"
                        :key="typeIdx"
                        class="rounded-xl border border-gray-200 p-5 dark:border-gray-700"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <input
                                v-model="vType.type"
                                type="text"
                                placeholder="e.g. Color, Size, Style"
                                class="flex-1 rounded-lg border border-gray-300 px-4 py-2 font-semibold focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            />
                            <button
                                type="button"
                                @click="removeVariationType(typeIdx)"
                                class="rounded-lg p-2 text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-900/20"
                                title="Remove variation type"
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
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="(opt, optIdx) in vType.options"
                                :key="optIdx"
                                class="flex items-start gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50"
                            >
                                <div class="flex-1">
                                    <input
                                        v-model="opt.name"
                                        type="text"
                                        :placeholder="`Option name (e.g. ${vType.type === 'Color' ? 'Blue, Red, Black' : vType.type === 'Size' ? 'S, M, L, XL' : 'Option ' + (optIdx + 1)})`"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                </div>
                                <div class="shrink-0">
                                    <div
                                        v-if="opt.imagePreview"
                                        class="group relative"
                                    >
                                        <img
                                            :src="opt.imagePreview"
                                            alt="Option preview"
                                            class="h-12 w-12 rounded-lg border border-gray-200 object-cover dark:border-gray-600"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                removeOptionImage(
                                                    typeIdx,
                                                    optIdx,
                                                )
                                            "
                                            class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            ×
                                        </button>
                                    </div>
                                    <label
                                        v-else
                                        class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 transition-colors hover:border-[#42b6c5] dark:border-gray-600"
                                        :title="`Upload image for ${opt.name || 'option'}`"
                                    >
                                        <svg
                                            class="h-5 w-5 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="
                                                handleOptionImage(
                                                    typeIdx,
                                                    optIdx,
                                                    $event,
                                                )
                                            "
                                        />
                                    </label>
                                </div>
                                <button
                                    type="button"
                                    @click="removeOption(typeIdx, optIdx)"
                                    class="shrink-0 p-1.5 text-gray-400 transition-colors hover:text-red-500"
                                    :disabled="vType.options.length <= 1"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="addOption(typeIdx)"
                            class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-[#42b6c5] transition-colors hover:text-[#35919e]"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            Add Option
                        </button>
                    </div>
                </div>
                <p
                    v-if="form.errors.variations"
                    class="mt-2 text-sm text-red-500"
                >
                    {{ form.errors.variations }}
                </p>
            </div>

            <!-- Options -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Options
                </h3>
                <div class="flex flex-wrap gap-6">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                        />
                        <span
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Active</span
                        >
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            v-model="form.is_featured"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                        />
                        <span
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Featured</span
                        >
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3">
                <Link
                    href="/admin/ai-forge/swags"
                    class="rounded-lg bg-gray-100 px-6 py-3 font-semibold text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >Cancel</Link
                >
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-[#42b6c5] px-8 py-3 font-semibold text-white transition-colors hover:bg-[#35919e] disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create Swag' }}
                </button>
            </div>
        </form>
    </div>
</template>
