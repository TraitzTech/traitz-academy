<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import {
    MessageSquare,
    Plus,
    Search,
    ToggleLeft,
    ToggleRight,
    Trash2,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface FeedbackForm {
    id: number;
    title: string;
    description: string | null;
    slug: string;
    is_active: boolean;
    allow_anonymous: boolean;
    responses_count: number;
    closes_at: string | null;
    created_at: string;
    creator: { id: number; name: string } | null;
}

interface Props {
    forms: {
        data: FeedbackForm[];
        links: any[];
        total: number;
    };
    filters: { search?: string };
}

const props = defineProps<Props>();
defineOptions({ layout: AppLayout });
const toast = useToast();

const search = ref(props.filters.search || '');

const applyFilters = debounce(() => {
    router.get(
        '/admin/feedback',
        { search: search.value || undefined },
        { preserveState: true, preserveScroll: true },
    );
}, 300);

watch(search, applyFilters);

const showDeleteModal = ref(false);
const formToDelete = ref<FeedbackForm | null>(null);

const toggleStatus = (form: FeedbackForm) => {
    router.post(
        `/admin/feedback/${form.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
            // Flash message handled by global watcher (FeedbackController::toggleStatus flashes 'success')
        },
    );
};

const openDeleteModal = (form: FeedbackForm) => {
    formToDelete.value = form;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!formToDelete.value) {
        return;
    }
    router.delete(`/admin/feedback/${formToDelete.value.id}`, {
        onSuccess: () => {
            // Flash message handled by global watcher (FeedbackController::destroy flashes 'success')
            showDeleteModal.value = false;
        },
    });
};

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

const shareLink = (slug: string) =>
    `${window.location.origin}/feedback/${slug}`;

const copyLink = (slug: string) => {
    navigator.clipboard.writeText(shareLink(slug));
    toast.success('Share link copied!');
};
</script>

<template>
    <Head title="Feedback Forms" />

    <div class="mx-auto max-w-7xl p-4 lg:p-8">
        <!-- Header -->
        <div
            class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center lg:mb-8"
        >
            <div>
                <h1
                    class="text-2xl font-bold text-gray-900 lg:text-3xl dark:text-gray-100"
                >
                    Feedback Forms
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Create and manage feedback forms for interns.
                </p>
            </div>
            <Link
                href="/admin/feedback/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#42b6c5] px-4 py-2 font-semibold text-white transition-colors hover:bg-[#35a3b2]"
            >
                <Plus class="h-4 w-4" />
                New Form
            </Link>
        </div>

        <!-- Search -->
        <div class="relative mb-6">
            <Search
                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
            />
            <input
                v-model="search"
                type="text"
                placeholder="Search forms..."
                class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pr-4 pl-9 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            />
        </div>

        <!-- Empty state -->
        <div
            v-if="forms.data.length === 0"
            class="rounded-xl bg-white p-12 text-center shadow-sm dark:bg-gray-800"
        >
            <MessageSquare
                class="mx-auto mb-4 h-12 w-12 text-gray-300 dark:text-gray-600"
            />
            <h3
                class="mb-2 text-lg font-semibold text-gray-700 dark:text-gray-300"
            >
                No feedback forms yet
            </h3>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                Create your first feedback form to start collecting responses
                from interns.
            </p>
            <Link
                href="/admin/feedback/create"
                class="inline-flex items-center gap-2 rounded-lg bg-[#42b6c5] px-5 py-2.5 font-semibold text-white transition-colors hover:bg-[#35a3b2]"
            >
                <Plus class="h-4 w-4" />
                Create Form
            </Link>
        </div>

        <!-- Grid -->
        <div
            v-else
            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:gap-6 xl:grid-cols-3"
        >
            <div
                v-for="form in forms.data"
                :key="form.id"
                class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <!-- Card top -->
                <div class="flex-1 p-5">
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                                form.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                            ]"
                        >
                            {{ form.is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <span
                            v-if="form.allow_anonymous"
                            class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"
                        >
                            Allows Anonymous
                        </span>
                    </div>
                    <h3
                        class="mb-1 text-lg leading-tight font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{ form.title }}
                    </h3>
                    <p
                        v-if="form.description"
                        class="mb-3 line-clamp-2 text-sm text-gray-500 dark:text-gray-400"
                    >
                        {{ form.description }}
                    </p>
                    <div
                        class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400"
                    >
                        <span class="flex items-center gap-1">
                            <MessageSquare class="h-3.5 w-3.5" />
                            {{ form.responses_count }}
                            {{
                                form.responses_count === 1
                                    ? 'response'
                                    : 'responses'
                            }}
                        </span>
                        <span>Created {{ formatDate(form.created_at) }}</span>
                    </div>
                    <div
                        v-if="form.closes_at"
                        class="mt-2 text-xs text-amber-600 dark:text-amber-400"
                    >
                        Closes {{ formatDate(form.closes_at) }}
                    </div>
                </div>

                <!-- Copy link -->
                <div class="px-5 pb-3">
                    <button
                        @click="copyLink(form.slug)"
                        class="w-full truncate rounded-lg bg-gray-50 px-3 py-2 text-left text-xs text-gray-500 transition-colors hover:bg-[#42b6c5]/10 hover:text-[#42b6c5] dark:bg-gray-700/50 dark:text-gray-400"
                    >
                        📋 {{ shareLink(form.slug) }}
                    </button>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap items-center gap-2 px-5 pb-5">
                    <Link
                        :href="`/admin/feedback/${form.id}`"
                        class="flex-1 rounded-lg bg-[#42b6c5]/10 px-3 py-2 text-center text-sm font-semibold text-[#42b6c5] transition-colors hover:bg-[#42b6c5]/20"
                    >
                        View
                    </Link>
                    <Link
                        :href="`/admin/feedback/${form.id}/edit`"
                        class="flex-1 rounded-lg bg-gray-100 px-3 py-2 text-center text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        Edit
                    </Link>
                    <button
                        @click="toggleStatus(form)"
                        :title="form.is_active ? 'Deactivate' : 'Activate'"
                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-900/20"
                    >
                        <ToggleRight
                            v-if="form.is_active"
                            class="h-5 w-5 text-green-500"
                        />
                        <ToggleLeft v-else class="h-5 w-5" />
                    </button>
                    <button
                        @click="openDeleteModal(form)"
                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="forms.links && forms.links.length > 3"
            class="mt-8 flex justify-center gap-1"
        >
            <template v-for="link in forms.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :class="[
                        'rounded-lg px-3 py-2 text-sm transition-colors',
                        link.active
                            ? 'bg-[#42b6c5] font-semibold text-white'
                            : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                    ]"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="cursor-not-allowed px-3 py-2 text-sm text-gray-400"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>

    <ConfirmationModal
        :open="showDeleteModal"
        title="Delete Feedback Form"
        :description="`Are you sure you want to delete '${formToDelete?.title}'? All responses will be permanently deleted.`"
        confirm-text="Delete"
        variant="destructive"
        @confirm="confirmDelete"
        @update:open="showDeleteModal = $event"
    />
</template>
