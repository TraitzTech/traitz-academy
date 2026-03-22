<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

import { useToast } from '@/composables/useToast'
import AppLayout from '@/layouts/AppLayout.vue'

interface VariationOption {
    name: string
    imageFile: File | null
    imagePreview: string | null
    existingImage: string | null
}

interface VariationType {
    type: string
    options: VariationOption[]
}

interface Swag {
    id: number
    name: string
    slug: string
    category: string
    description: string | null
    price: number
    currency: string
    image: string | null
    gallery_images: string[] | null
    variations: { type: string; options: (string | { name: string; image?: string | null })[] }[] | null
    stock_quantity: number
    sort_order: number
    is_active: boolean
    is_featured: boolean
}

interface Props {
    event: { id: number; title: string } | null
    swag: Swag
}

const props = defineProps<Props>()

defineOptions({ layout: AppLayout })

const toast = useToast()

const form = useForm({
    _method: 'PUT',
    name: props.swag.name,
    category: props.swag.category,
    description: props.swag.description ?? '',
    price: props.swag.price,
    currency: props.swag.currency,
    image_file: null as File | null,
    stock_quantity: props.swag.stock_quantity,
    sort_order: props.swag.sort_order,
    is_active: props.swag.is_active,
    is_featured: props.swag.is_featured,
    variations: '[]',
})

const imagePreview = ref<string | null>(null)

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
]

const variationPresets = ['Color', 'Size', 'Style', 'Material']

const getImageUrl = (path: string | null): string | undefined => {
    if (!path) return undefined
    if (path.startsWith('http')) return path
    return `/storage/${path}`
}

const initVariationTypes = (): VariationType[] => {
    if (!props.swag.variations?.length) return []
    return props.swag.variations.map(vt => ({
        type: vt.type,
        options: vt.options.map(opt => {
            if (typeof opt === 'string') {
                return { name: opt, imageFile: null, imagePreview: null, existingImage: null }
            }
            return {
                name: opt.name,
                imageFile: null,
                imagePreview: opt.image ? (getImageUrl(opt.image) ?? null) : null,
                existingImage: opt.image ?? null,
            }
        }),
    }))
}

const variationTypes = ref<VariationType[]>(initVariationTypes())

const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement
    if (target.files?.[0]) {
        form.image_file = target.files[0]
        imagePreview.value = URL.createObjectURL(target.files[0])
    }
}

const addVariationType = () => {
    variationTypes.value.push({ type: '', options: [{ name: '', imageFile: null, imagePreview: null, existingImage: null }] })
}

const removeVariationType = (idx: number) => {
    variationTypes.value.splice(idx, 1)
}

const addOption = (typeIdx: number) => {
    variationTypes.value[typeIdx].options.push({ name: '', imageFile: null, imagePreview: null, existingImage: null })
}

const removeOption = (typeIdx: number, optIdx: number) => {
    variationTypes.value[typeIdx].options.splice(optIdx, 1)
}

const handleOptionImage = (typeIdx: number, optIdx: number, e: Event) => {
    const target = e.target as HTMLInputElement
    if (target.files?.[0]) {
        const file = target.files[0]
        variationTypes.value[typeIdx].options[optIdx].imageFile = file
        variationTypes.value[typeIdx].options[optIdx].imagePreview = URL.createObjectURL(file)
        variationTypes.value[typeIdx].options[optIdx].existingImage = null
    }
}

const removeOptionImage = (typeIdx: number, optIdx: number) => {
    variationTypes.value[typeIdx].options[optIdx].imageFile = null
    variationTypes.value[typeIdx].options[optIdx].imagePreview = null
    variationTypes.value[typeIdx].options[optIdx].existingImage = null
}

const submit = () => {
    form.transform((data) => {
        const transformed: Record<string, any> = { ...data }

        const validTypes = variationTypes.value
            .filter(vt => vt.type.trim() && vt.options.some(o => o.name.trim()))
            .map(vt => ({
                type: vt.type.trim(),
                options: vt.options.filter(o => o.name.trim()).map(o => ({
                    name: o.name.trim(),
                    image: o.existingImage || null,
                })),
            }))

        transformed.variations = validTypes.length ? JSON.stringify(validTypes) : null

        let typeIdx = 0
        variationTypes.value.forEach((vt) => {
            if (!vt.type.trim() || !vt.options.some(o => o.name.trim())) return
            let optIdx = 0
            vt.options.forEach((opt) => {
                if (!opt.name.trim()) return
                if (opt.imageFile) {
                    transformed[`variation_images_${typeIdx}_${optIdx}`] = opt.imageFile
                }
                optIdx++
            })
            typeIdx++
        })

        return transformed
    }).post(`/admin/ai-forge/swags/${props.swag.slug}`, {
        forceFormData: true,
        onSuccess: () => toast.success('Swag updated successfully!'),
        onError: () => toast.error('Failed to update swag. Please check the form.'),
    })
}
</script>

<template>
    <div>
        <Head :title="`Edit ${swag.name} - AI Forge`" />

        <div class="mb-8">
            <Link href="/admin/ai-forge/swags" class="inline-flex items-center text-[#42b6c5] hover:text-[#35919e] mb-4">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Swags
            </Link>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Edit Swag</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Update {{ swag.name }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Product Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                        <input v-model="form.name" type="text" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                        <select v-model="form.category" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100">
                            <option value="">Select category</option>
                            <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                        </select>
                        <p v-if="form.errors.category" class="text-red-500 text-sm mt-1">{{ form.errors.category }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Price (XAF) *</label>
                        <input v-model.number="form.price" type="number" min="0" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="form.errors.price" class="text-red-500 text-sm mt-1">{{ form.errors.price }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stock Quantity</label>
                        <input v-model.number="form.stock_quantity" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="form.errors.stock_quantity" class="text-red-500 text-sm mt-1">{{ form.errors.stock_quantity }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                        <input v-model.number="form.sort_order" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea v-model="form.description" rows="4" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                        <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</p>
                    </div>
                </div>
            </div>

            <!-- Image -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Product Image</h3>
                <div v-if="swag.image && !imagePreview" class="mb-4">
                    <img :src="getImageUrl(swag.image)" alt="Current image" class="h-40 rounded-lg object-cover" />
                    <p class="text-sm text-gray-500 mt-1">Current image</p>
                </div>
                <input type="file" accept="image/*" @change="handleImageChange" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#42b6c5]/10 file:text-[#42b6c5] hover:file:bg-[#42b6c5]/20" />
                <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="mt-4 h-40 rounded-lg object-cover" />
                <p v-if="form.errors.image_file" class="text-red-500 text-sm mt-1">{{ form.errors.image_file }}</p>
            </div>

            <!-- Variations Builder -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Variations</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add options like Color, Size, etc. Each option can have its own image.</p>
                    </div>
                    <button type="button" @click="addVariationType" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#42b6c5]/10 text-[#42b6c5] font-semibold rounded-lg hover:bg-[#42b6c5]/20 transition-colors text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add Variation Type
                    </button>
                </div>

                <div v-if="!variationTypes.length" class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No variations yet. Add types like Color, Size, Style, etc.</p>
                    <div class="flex flex-wrap gap-2 justify-center mt-3">
                        <button v-for="preset in variationPresets" :key="preset" type="button" @click="variationTypes.push({ type: preset, options: [{ name: '', imageFile: null, imagePreview: null, existingImage: null }] })" class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            + {{ preset }}
                        </button>
                    </div>
                </div>

                <div v-else class="space-y-6">
                    <div v-for="(vType, typeIdx) in variationTypes" :key="typeIdx" class="border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <input v-model="vType.type" type="text" placeholder="e.g. Color, Size, Style" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100 font-semibold" />
                            <button type="button" @click="removeVariationType(typeIdx)" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Remove variation type">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div v-for="(opt, optIdx) in vType.options" :key="optIdx" class="flex items-start gap-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                                <div class="flex-1">
                                    <input v-model="opt.name" type="text" :placeholder="`Option name (e.g. ${vType.type === 'Color' ? 'Blue, Red, Black' : vType.type === 'Size' ? 'S, M, L, XL' : 'Option ' + (optIdx + 1)})`" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-[#42b6c5] focus:border-transparent dark:bg-gray-700 dark:text-gray-100" />
                                </div>
                                <div class="shrink-0">
                                    <div v-if="opt.imagePreview" class="relative group">
                                        <img :src="opt.imagePreview" alt="Option preview" class="w-12 h-12 rounded-lg object-cover border border-gray-200 dark:border-gray-600" />
                                        <button type="button" @click="removeOptionImage(typeIdx, optIdx)" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity">×</button>
                                    </div>
                                    <label v-else class="w-12 h-12 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center cursor-pointer hover:border-[#42b6c5] transition-colors" :title="`Upload image for ${opt.name || 'option'}`">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        <input type="file" accept="image/*" class="hidden" @change="handleOptionImage(typeIdx, optIdx, $event)" />
                                    </label>
                                </div>
                                <button type="button" @click="removeOption(typeIdx, optIdx)" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors shrink-0" :disabled="vType.options.length <= 1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>

                        <button type="button" @click="addOption(typeIdx)" class="mt-3 inline-flex items-center gap-1 text-sm text-[#42b6c5] hover:text-[#35919e] font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Add Option
                        </button>
                    </div>
                </div>
                <p v-if="form.errors.variations" class="text-red-500 text-sm mt-2">{{ form.errors.variations }}</p>
            </div>

            <!-- Options -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Options</h3>
                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-[#42b6c5] focus:ring-[#42b6c5] border-gray-300 rounded" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_featured" type="checkbox" class="h-4 w-4 text-[#42b6c5] focus:ring-[#42b6c5] border-gray-300 rounded" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Featured</span>
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3">
                <Link href="/admin/ai-forge/swags" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="px-8 py-3 bg-[#42b6c5] text-white font-semibold rounded-lg hover:bg-[#35919e] transition-colors disabled:opacity-50">
                    {{ form.processing ? 'Saving...' : 'Update Swag' }}
                </button>
            </div>
        </form>
    </div>
</template>
