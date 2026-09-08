<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

interface DiscussionRow {
    id: number;
    body: string;
    author_name: string | null;
    replies_count: number;
    has_accepted_answer: boolean;
    created_at: string | null;
    lesson?: { id: number; title: string | null } | null;
    course?: {
        id: number;
        title: string | null;
        tutor_name?: string | null;
    } | null;
    destination_url?: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedDiscussions {
    data: DiscussionRow[];
    links: PaginationLink[];
}

const props = defineProps<{
    title: string;
    subtitle: string;
    baseUrl: string;
    filters: { search?: string; status?: string };
    discussions: PaginatedDiscussions;
    showTutorName?: boolean;
}>();

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

const filterOptions = [
    { value: '', label: 'All' },
    { value: 'unanswered', label: 'Unanswered' },
    { value: 'answered', label: 'Answered' },
    { value: 'accepted', label: 'Accepted Answer' },
    { value: 'mine', label: 'My Posts' },
];

function applyFilters() {
    router.get(
        props.baseUrl,
        {
            search: form.search || undefined,
            status: form.status || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

const hasRows = computed(() => props.discussions.data.length > 0);

function postedAt(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

function rowStatus(row: DiscussionRow): { label: string; classes: string } {
    if (row.has_accepted_answer) {
        return {
            label: 'Accepted',
            classes:
                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        };
    }

    if (row.replies_count > 0) {
        return {
            label: 'Answered',
            classes:
                'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        };
    }

    return {
        label: 'Unanswered',
        classes:
            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    };
}

function plainLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/<[^>]*>/g, '')
        .trim();
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ title }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ subtitle }}
            </p>
        </div>

        <div
            class="mb-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="grid gap-3 md:grid-cols-[1fr_220px_auto]">
                <input
                    v-model="form.search"
                    type="text"
                    placeholder="Search by text, lesson, course, or author"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                    @keyup.enter="applyFilters"
                />
                <select
                    v-model="form.status"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                    @change="applyFilters"
                >
                    <option
                        v-for="option in filterOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <button
                    type="button"
                    class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white hover:bg-[#000928]"
                    @click="applyFilters"
                >
                    Apply
                </button>
            </div>
        </div>

        <div
            v-if="!hasRows"
            class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-600"
        >
            No discussions found for the selected filters.
        </div>

        <div v-else class="space-y-3">
            <article
                v-for="row in discussions.data"
                :key="row.id"
                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p
                            class="line-clamp-2 text-sm text-gray-700 dark:text-gray-200"
                        >
                            {{ row.body }}
                        </p>
                        <p class="mt-2 text-xs text-gray-500">
                            <span class="font-semibold">Author:</span>
                            {{ row.author_name || 'Unknown' }}
                            <span class="mx-1">·</span>
                            <span class="font-semibold">Course:</span>
                            {{ row.course?.title || '—' }}
                            <template
                                v-if="showTutorName && row.course?.tutor_name"
                            >
                                <span class="mx-1">·</span>
                                <span class="font-semibold">Tutor:</span>
                                {{ row.course.tutor_name }}
                            </template>
                            <span class="mx-1">·</span>
                            <span class="font-semibold">Lesson:</span>
                            {{ row.lesson?.title || '—' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Posted {{ postedAt(row.created_at) }} ·
                            {{ row.replies_count }} repl{{
                                row.replies_count === 1 ? 'y' : 'ies'
                            }}
                        </p>
                    </div>
                    <span
                        :class="[
                            'rounded-full px-2 py-0.5 text-xs font-semibold',
                            rowStatus(row).classes,
                        ]"
                        >{{ rowStatus(row).label }}</span
                    >
                </div>

                <div class="mt-3">
                    <Link
                        v-if="row.destination_url"
                        :href="row.destination_url"
                        class="inline-flex rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Open context
                    </Link>
                </div>
            </article>
        </div>

        <div
            v-if="discussions.links?.length > 3"
            class="mt-5 flex flex-wrap items-center gap-2"
        >
            <Link
                v-for="link in discussions.links"
                :key="`${link.label}-${link.url}`"
                :href="link.url || '#'"
                :class="[
                    'rounded-md px-3 py-1.5 text-xs font-semibold',
                    link.active
                        ? 'bg-[#381998] text-white'
                        : 'border border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-300',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            >
                {{ plainLabel(link.label) }}
            </Link>
        </div>
    </div>
</template>
