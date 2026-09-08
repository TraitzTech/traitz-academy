<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { Edit2, PlusCircle, Trash2, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    CATEGORY_ICON_GROUPS as ICON_GROUPS,
    categoryIconFor as iconFor,
} from '@/utils/categoryIcons';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    is_active: boolean;
    sort_order: number;
    courses_count: number;
}

interface Filters {
    search?: string;
    status?: string;
}

const props = defineProps<{
    categories: Category[];
    filters: Filters;
}>();

defineOptions({ layout: AppLayout });

const toast = useToast();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

// ─── Filters ──────────────────────────────────────────────────────────────────

const applyFilters = debounce(() => {
    router.get(
        '/admin/course-categories',
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}, 300);

watch([search, status], applyFilters);

// ─── Modal state ──────────────────────────────────────────────────────────────

const showModal = ref(false);
const editingId = ref<number | null>(null);
const showDeleteModal = ref(false);
const categoryToDelete = ref<Category | null>(null);

const form = useForm({
    name: '',
    description: '',
    icon: '',
    color: '#381998',
    sort_order: 0,
    is_active: true,
});

const PRESET_COLORS = [
    '#381998',
    '#42b6c5',
    '#000928',
    '#7c3aed',
    '#059669',
    '#dc2626',
    '#d97706',
    '#2563eb',
    '#db2777',
    '#0891b2',
];

function openCreate() {
    editingId.value = null;
    form.reset();
    form.color = '#381998';
    form.is_active = true;
    showModal.value = true;
}

function openEdit(cat: Category) {
    editingId.value = cat.id;
    form.name = cat.name;
    form.description = cat.description ?? '';
    form.icon = cat.icon ?? '';
    form.color = cat.color ?? '#381998';
    form.sort_order = cat.sort_order;
    form.is_active = cat.is_active;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingId.value) {
        form.put(`/admin/course-categories/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/course-categories', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

// ─── Toggle active ────────────────────────────────────────────────────────────

function toggleActive(cat: Category) {
    router.post(
        `/admin/course-categories/${cat.id}/toggle-active`,
        {},
        {
            preserveScroll: true,
            onError: () => toast.error('Failed to update status.'),
        },
    );
}

// ─── Delete ───────────────────────────────────────────────────────────────────

function confirmDelete(cat: Category) {
    categoryToDelete.value = cat;
    showDeleteModal.value = true;
}

function doDelete() {
    if (!categoryToDelete.value) return;
    router.delete(`/admin/course-categories/${categoryToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            categoryToDelete.value = null;
        },
        onError: () => toast.error('Could not delete this category.'),
    });
}
</script>

<template>
    <div>
        <Head title="Course Categories" />

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Course Categories
                </h2>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    Organise courses into categories for tutors to choose from
                </p>
            </div>
            <button
                @click="openCreate"
                class="inline-flex items-center gap-2 rounded-lg bg-[#42b6c5] px-5 py-2.5 font-semibold text-white transition-colors hover:bg-[#35919e]"
            >
                <PlusCircle class="h-5 w-5" />
                New Category
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Search</label
                    >
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search categories…"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Status</label
                    >
                    <select
                        v-model="status"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-[#42b6c5] focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div
            class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800"
        >
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Category
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Description
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Courses
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Order
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800"
                    >
                        <tr
                            v-for="cat in categories"
                            :key="cat.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/40"
                        >
                            <!-- Category name + icon + colour swatch -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm"
                                        :style="{
                                            backgroundColor:
                                                cat.color ?? '#381998',
                                            opacity: cat.is_active ? 1 : 0.5,
                                        }"
                                    >
                                        <component
                                            :is="iconFor(cat.icon)"
                                            v-if="iconFor(cat.icon)"
                                            class="h-5 w-5 text-white"
                                        />
                                        <span
                                            v-else
                                            class="text-xs font-bold text-white"
                                            >{{
                                                cat.name.charAt(0).toUpperCase()
                                            }}</span
                                        >
                                    </div>
                                    <div>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{ cat.name }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ cat.slug }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="max-w-xs px-6 py-4">
                                <p
                                    class="truncate text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ cat.description || '—' }}
                                </p>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center rounded-full bg-[#42b6c5]/10 px-2.5 py-0.5 text-xs font-semibold text-[#42b6c5]"
                                >
                                    {{ cat.courses_count }}
                                </span>
                            </td>

                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-700 dark:text-gray-300"
                            >
                                {{ cat.sort_order }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    @click="toggleActive(cat)"
                                    :class="[
                                        'rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors',
                                        cat.is_active
                                            ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400',
                                    ]"
                                >
                                    {{ cat.is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>

                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <button
                                        @click="openEdit(cat)"
                                        class="text-[#42b6c5] transition-colors hover:text-[#35919e]"
                                    >
                                        <Edit2 class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="confirmDelete(cat)"
                                        :disabled="cat.courses_count > 0"
                                        class="text-red-500 transition-colors hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-30"
                                        :title="
                                            cat.courses_count > 0
                                                ? 'Cannot delete — has courses assigned'
                                                : 'Delete'
                                        "
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="categories.length === 0">
                            <td
                                colspan="6"
                                class="px-6 py-16 text-center text-gray-400"
                            >
                                No categories found. Create your first one.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════════════ CREATE / EDIT MODAL ═══════════════ -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-10"
                @click.self="closeModal"
            >
                <div
                    class="w-full max-w-2xl rounded-xl bg-gray-50 shadow-2xl dark:bg-gray-900"
                >
                    <!-- Modal header -->
                    <div
                        class="flex items-center justify-between rounded-t-xl border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div>
                            <h3
                                class="text-xl font-bold text-gray-900 dark:text-gray-100"
                            >
                                {{
                                    editingId
                                        ? 'Edit Category'
                                        : 'New Course Category'
                                }}
                            </h3>
                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{
                                    editingId
                                        ? 'Update the details below.'
                                        : 'Fill in the details to add a new category.'
                                }}
                            </p>
                        </div>
                        <button
                            @click="closeModal"
                            class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-5 p-6">
                        <!-- ── Basic Information ── -->
                        <div
                            class="rounded-lg bg-white p-6 shadow dark:bg-gray-800"
                        >
                            <h4
                                class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100"
                            >
                                Basic Information
                            </h4>
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <!-- Name -->
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Category Name
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="e.g. Data Science & AI"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        :class="{
                                            'border-red-400 focus:ring-red-300':
                                                form.errors.name,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.name"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Description -->
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Description</label
                                    >
                                    <textarea
                                        v-model="form.description"
                                        rows="2"
                                        maxlength="500"
                                        placeholder="Short description visible to tutors and students (optional)"
                                        class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                    <p
                                        class="mt-1 text-right text-xs text-gray-400"
                                    >
                                        {{ form.description.length }}/500
                                    </p>
                                </div>

                                <!-- Sort Order -->
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Sort Order</label
                                    >
                                    <input
                                        v-model="form.sort_order"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                    <p
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Lower numbers appear first.
                                    </p>
                                </div>

                                <!-- Status -->
                                <div class="flex flex-col justify-center">
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Status</label
                                    >
                                    <label
                                        class="flex cursor-pointer items-center gap-3"
                                    >
                                        <div
                                            @click="
                                                form.is_active = !form.is_active
                                            "
                                            :class="[
                                                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors',
                                                form.is_active
                                                    ? 'bg-[#42b6c5]'
                                                    : 'bg-gray-300 dark:bg-gray-600',
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                                                    form.is_active
                                                        ? 'translate-x-6'
                                                        : 'translate-x-1',
                                                ]"
                                            />
                                        </div>
                                        <span
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {{
                                                form.is_active
                                                    ? 'Active — visible to tutors'
                                                    : 'Inactive — hidden from tutors'
                                            }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- ── Icon & Colour ── -->
                        <div
                            class="rounded-lg bg-white p-6 shadow dark:bg-gray-800"
                        >
                            <h4
                                class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100"
                            >
                                Icon &amp; Colour
                            </h4>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Icon picker -->
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Icon</label
                                    >

                                    <!-- Selected preview badge -->
                                    <div
                                        class="mb-3 flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-600 dark:bg-gray-700"
                                    >
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg shadow-sm"
                                            :style="{
                                                backgroundColor:
                                                    form.color || '#381998',
                                            }"
                                        >
                                            <component
                                                :is="iconFor(form.icon)"
                                                v-if="iconFor(form.icon)"
                                                class="h-5 w-5 text-white"
                                            />
                                            <span
                                                v-else
                                                class="text-sm font-bold text-white/50"
                                                >?</span
                                            >
                                        </div>
                                        <span
                                            class="flex-1 text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            {{
                                                form.icon
                                                    ? 'Icon selected'
                                                    : 'No icon selected'
                                            }}
                                        </span>
                                        <button
                                            v-if="form.icon"
                                            type="button"
                                            @click="form.icon = ''"
                                            class="text-xs text-gray-400 transition-colors hover:text-red-500"
                                        >
                                            Clear
                                        </button>
                                    </div>

                                    <!-- Scrollable icon grid, grouped -->
                                    <div
                                        class="h-40 overflow-y-auto rounded-lg border border-gray-300 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-700"
                                    >
                                        <div
                                            v-for="group in ICON_GROUPS"
                                            :key="group.label"
                                            class="mb-2 last:mb-0"
                                        >
                                            <p
                                                class="mb-1 px-1 text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                                            >
                                                {{ group.label }}
                                            </p>
                                            <div class="grid grid-cols-9 gap-1">
                                                <button
                                                    v-for="opt in group.icons"
                                                    :key="opt.key"
                                                    type="button"
                                                    @click="form.icon = opt.key"
                                                    :class="[
                                                        'flex h-8 w-8 items-center justify-center rounded-lg text-gray-600 transition-all hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-600',
                                                        form.icon === opt.key
                                                            ? 'scale-110 bg-[#381998]/15 text-[#381998] ring-2 ring-[#381998] dark:ring-purple-400'
                                                            : '',
                                                    ]"
                                                >
                                                    <component
                                                        :is="opt.component"
                                                        class="h-4 w-4"
                                                    />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Colour picker -->
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Colour</label
                                    >

                                    <!-- Hex input + native picker -->
                                    <div class="mb-3 flex items-center gap-3">
                                        <input
                                            v-model="form.color"
                                            type="color"
                                            class="h-10 w-14 shrink-0 cursor-pointer rounded-lg border border-gray-300 p-0.5 dark:border-gray-600"
                                        />
                                        <input
                                            v-model="form.color"
                                            type="text"
                                            placeholder="#381998"
                                            class="flex-1 rounded-lg border border-gray-300 px-4 py-2 font-mono text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                            :class="{
                                                'border-red-400':
                                                    form.errors.color,
                                            }"
                                        />
                                    </div>
                                    <p
                                        v-if="form.errors.color"
                                        class="mb-2 text-sm text-red-600"
                                    >
                                        {{ form.errors.color }}
                                    </p>

                                    <!-- Preset swatches -->
                                    <p
                                        class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400"
                                    >
                                        Quick presets
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="c in PRESET_COLORS"
                                            :key="c"
                                            type="button"
                                            @click="form.color = c"
                                            :style="{ backgroundColor: c }"
                                            :class="[
                                                'h-7 w-7 rounded-lg border-2 shadow-sm transition-all hover:scale-110',
                                                form.color === c
                                                    ? 'scale-110 border-gray-800 ring-2 ring-gray-400 ring-offset-1 dark:border-white'
                                                    : 'border-transparent',
                                            ]"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Live preview -->
                            <div
                                class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700/40"
                            >
                                <p
                                    class="mb-3 text-xs font-semibold tracking-wider text-gray-400 uppercase"
                                >
                                    Live Preview
                                </p>
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl shadow"
                                        :style="{
                                            backgroundColor:
                                                form.color || '#381998',
                                        }"
                                    >
                                        <component
                                            :is="iconFor(form.icon)"
                                            v-if="iconFor(form.icon)"
                                            class="h-6 w-6 text-white"
                                        />
                                        <span
                                            v-else
                                            class="text-sm font-bold text-white"
                                            >{{
                                                (form.name || 'C')
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}</span
                                        >
                                    </div>
                                    <div>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{ form.name || 'Category Name' }}
                                        </p>
                                        <p
                                            class="text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            {{
                                                form.description ||
                                                'Category description will appear here.'
                                            }}
                                        </p>
                                    </div>
                                    <span
                                        v-if="!form.is_active"
                                        class="ml-auto rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-600 dark:bg-red-900/30 dark:text-red-400"
                                    >
                                        Inactive
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- ── Actions ── -->
                        <div class="flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="closeModal"
                                class="rounded-lg border border-gray-300 px-6 py-2.5 font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-[#42b6c5] px-6 py-2.5 font-semibold text-white transition-colors hover:bg-[#35919e] disabled:opacity-50"
                            >
                                {{
                                    form.processing
                                        ? 'Saving…'
                                        : editingId
                                          ? 'Save Changes'
                                          : 'Create Category'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete confirmation -->
        <ConfirmationModal
            :open="showDeleteModal"
            title="Delete Category"
            :description="`Delete &quot;${categoryToDelete?.name}&quot;? This cannot be undone.`"
            confirm-text="Delete"
            variant="destructive"
            @update:open="showDeleteModal = $event"
            @confirm="doDelete"
        />
    </div>
</template>
