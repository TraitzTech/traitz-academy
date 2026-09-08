<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import {
    BookOpen,
    CheckSquare,
    Clock,
    Edit2,
    Eye,
    PlusCircle,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Course {
    id: number;
    title: string;
    cover_image: string | null;
    level: 'beginner' | 'intermediate' | 'advanced';
    status: 'draft' | 'pending_review' | 'published' | 'archived';
    price: string;
    enrollments_count: number;
    sections_count: number;
    category: Category | null;
}

interface PaginatedCourses {
    data: Course[];
    total: number;
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    courses: PaginatedCourses;
    categories: Category[];
    filters: { search?: string; status?: string; category?: string };
    stats: {
        total: number;
        active: number;
        pending: number;
        draft: number;
        students: number;
    };
}>();

defineOptions({ layout: AppLayout });

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const category = ref(props.filters.category ?? '');

const applyFilters = debounce(() => {
    router.get(
        '/tutor/courses',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            category: category.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}, 300);

watch([search, status, category], applyFilters);

const deletingId = ref<number | null>(null);
const deleteTarget = ref<Course | null>(null);

function confirmDelete(course: Course) {
    if (course.status === 'published') return;
    deleteTarget.value = course;
}

function doDelete() {
    if (!deleteTarget.value) return;
    deletingId.value = deleteTarget.value.id;
    router.delete(`/tutor/courses/${deleteTarget.value.id}`, {
        onSuccess: () => {
            deleteTarget.value = null;
        },
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    pending_review: 'Pending Review',
    published: 'Published',
    archived: 'Archived',
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    pending_review:
        'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    published:
        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    archived: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
};

const levelLabels: Record<string, string> = {
    beginner: 'Beginner',
    intermediate: 'Intermediate',
    advanced: 'Advanced',
};

function coverSrc(path: string | null) {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
}

function formatPrice(price: string) {
    const p = parseFloat(price);
    return p === 0 ? 'Free' : `${p.toLocaleString()} XAF`;
}
</script>

<template>
    <div>
        <Head title="My Courses — Tutor" />

        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    My Courses
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage all courses you teach
                </p>
            </div>
            <Link
                href="/tutor/courses/create"
                class="inline-flex items-center gap-2 rounded-xl bg-[#381998] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#000928]"
            >
                <PlusCircle class="h-4 w-4" /> New Course
            </Link>
        </div>

        <!-- Stat cards — aligned with admin Courses index -->
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5 lg:gap-5">
            <div
                class="rounded-lg border-l-4 border-[#381998] bg-white p-4 shadow lg:p-6 dark:bg-gray-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 lg:text-sm dark:text-gray-400"
                        >
                            Total Courses
                        </p>
                        <p
                            class="mt-1 text-2xl font-bold text-[#000928] lg:mt-2 lg:text-3xl dark:text-gray-100"
                        >
                            {{ stats.total }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg bg-purple-100 p-2 lg:p-3 dark:bg-purple-900/30"
                    >
                        <BookOpen
                            class="h-5 w-5 text-purple-600 lg:h-7 lg:w-7 dark:text-purple-400"
                        />
                    </div>
                </div>
            </div>
            <div
                class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow lg:p-6 dark:bg-gray-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 lg:text-sm dark:text-gray-400"
                        >
                            Published
                        </p>
                        <p
                            class="mt-1 text-2xl font-bold text-[#000928] lg:mt-2 lg:text-3xl dark:text-gray-100"
                        >
                            {{ stats.active }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg bg-green-100 p-2 lg:p-3 dark:bg-green-900/30"
                    >
                        <CheckSquare
                            class="h-5 w-5 text-green-600 lg:h-7 lg:w-7 dark:text-green-400"
                        />
                    </div>
                </div>
            </div>
            <div
                class="rounded-lg border-l-4 border-amber-400 bg-white p-4 shadow lg:p-6 dark:bg-gray-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 lg:text-sm dark:text-gray-400"
                        >
                            Pending Review
                        </p>
                        <p
                            class="mt-1 text-2xl font-bold text-[#000928] lg:mt-2 lg:text-3xl dark:text-gray-100"
                        >
                            {{ stats.pending }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg bg-amber-100 p-2 lg:p-3 dark:bg-amber-900/30"
                    >
                        <Clock
                            class="h-5 w-5 text-amber-500 lg:h-7 lg:w-7 dark:text-amber-400"
                        />
                    </div>
                </div>
            </div>
            <div
                class="rounded-lg border-l-4 border-gray-400 bg-white p-4 shadow lg:p-6 dark:bg-gray-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 lg:text-sm dark:text-gray-400"
                        >
                            Drafts
                        </p>
                        <p
                            class="mt-1 text-2xl font-bold text-[#000928] lg:mt-2 lg:text-3xl dark:text-gray-100"
                        >
                            {{ stats.draft }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg bg-gray-100 p-2 lg:p-3 dark:bg-gray-700/50"
                    >
                        <Edit2
                            class="h-5 w-5 text-gray-600 lg:h-7 lg:w-7 dark:text-gray-400"
                        />
                    </div>
                </div>
            </div>
            <div
                class="col-span-2 rounded-lg border-l-4 border-[#42b6c5] bg-white p-4 shadow lg:col-span-1 lg:p-6 dark:bg-gray-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 lg:text-sm dark:text-gray-400"
                        >
                            Total Students
                        </p>
                        <p
                            class="mt-1 text-2xl font-bold text-[#000928] lg:mt-2 lg:text-3xl dark:text-gray-100"
                        >
                            {{ stats.students }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg bg-blue-100 p-2 lg:p-3 dark:bg-blue-900/30"
                    >
                        <Users
                            class="h-5 w-5 text-blue-600 lg:h-7 lg:w-7 dark:text-blue-400"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters — same pattern as admin Courses -->
        <div
            class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div
                class="flex w-fit max-w-full gap-1 overflow-x-auto rounded-xl bg-gray-100 p-1 dark:bg-gray-800"
            >
                <button
                    v-for="[val, label] in [
                        ['', 'All'],
                        ['published', 'Published'],
                        ['draft', 'Draft'],
                        ['pending_review', 'Pending'],
                        ['archived', 'Archived'],
                    ]"
                    :key="val"
                    type="button"
                    @click="status = val"
                    :class="[
                        'shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
                        status === val
                            ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                    ]"
                >
                    {{ label }}
                </button>
            </div>
            <div class="flex flex-wrap gap-2">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search courses…"
                    class="min-w-[10rem] flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] sm:w-48 sm:flex-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                />
                <select
                    v-model="category"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                >
                    <option value="">All categories</option>
                    <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="cat.slug"
                    >
                        {{ cat.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Course list — row layout like admin -->
        <div class="rounded-xl bg-white shadow dark:bg-gray-800">
            <div
                v-if="courses.data.length === 0"
                class="flex flex-col items-center justify-center py-16 text-center"
            >
                <BookOpen class="mb-3 h-12 w-12 text-gray-300" />
                <p class="font-medium text-gray-500 dark:text-gray-400">
                    {{
                        search || status || category
                            ? 'No courses match your filters'
                            : 'No courses yet'
                    }}
                </p>
                <p class="mt-1 text-sm text-gray-400">
                    {{
                        search || status || category
                            ? 'Try adjusting your filters.'
                            : 'Create your first course to get started.'
                    }}
                </p>
                <Link
                    v-if="!search && !status && !category"
                    href="/tutor/courses/create"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white hover:bg-[#000928]"
                >
                    <PlusCircle class="h-4 w-4" /> New Course
                </Link>
            </div>

            <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                <div
                    v-for="course in courses.data"
                    :key="course.id"
                    class="flex items-center gap-4 px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/40"
                >
                    <div
                        class="h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-[#381998] to-[#42b6c5]"
                    >
                        <img
                            v-if="coverSrc(course.cover_image)"
                            :src="coverSrc(course.cover_image)!"
                            :alt="course.title"
                            class="h-full w-full object-cover"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100"
                                >{{ course.title }}</span
                            >
                            <span
                                :class="[
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    statusColors[course.status],
                                ]"
                            >
                                {{ statusLabels[course.status] }}
                            </span>
                        </div>
                        <p
                            class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ course.enrollments_count }} students ·
                            {{ course.sections_count }} sections
                            <template v-if="course.category">
                                · {{ course.category.name }}</template
                            >
                            · {{ levelLabels[course.level] }} ·
                            {{ formatPrice(course.price) }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <Link
                            :href="`/tutor/courses/${course.id}`"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-[#381998]/10 hover:text-[#381998] dark:hover:bg-purple-900/20 dark:hover:text-purple-300"
                            title="View course"
                        >
                            <Eye class="h-4 w-4" />
                        </Link>
                        <Link
                            :href="`/tutor/courses/${course.id}/edit`"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-purple-50 hover:text-purple-600 dark:hover:bg-purple-900/10"
                            title="Edit course"
                        >
                            <Edit2 class="h-4 w-4" />
                        </Link>
                        <button
                            v-if="course.status !== 'published'"
                            type="button"
                            :disabled="deletingId === course.id"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-50 dark:hover:bg-red-900/10"
                            title="Delete course"
                            @click="confirmDelete(course)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="courses.last_page > 1"
                class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-gray-700"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing {{ courses.from }}–{{ courses.to }} of
                    {{ courses.total }}
                </p>
                <div class="flex gap-1">
                    <template v-for="link in courses.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-xs font-medium transition-colors',
                                link.active
                                    ? 'bg-[#381998] text-white'
                                    : 'border border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :open="!!deleteTarget"
            title="Delete Course"
            :description="
                deleteTarget
                    ? `Are you sure you want to delete &quot;${deleteTarget.title}&quot;? This action cannot be undone.`
                    : ''
            "
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="destructive"
            @update:open="
                (val) => {
                    if (!val) deleteTarget = null;
                }
            "
            @confirm="doDelete"
        />
    </div>
</template>
