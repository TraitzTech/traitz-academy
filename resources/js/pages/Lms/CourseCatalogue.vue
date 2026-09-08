<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import {
    BookOpen,
    Clock,
    Search,
    SlidersHorizontal,
    Star,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import PublicLayout from '@/layouts/PublicLayout.vue';
import { categoryIconFor } from '@/utils/categoryIcons';

interface Category {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    color: string | null;
}

interface Instructor {
    id: number;
    name: string;
}

interface CourseCategory {
    id: number;
    name: string;
    slug: string;
    color: string | null;
}

interface Course {
    id: number;
    title: string;
    slug: string;
    short_description: string;
    cover_image: string | null;
    level: 'beginner' | 'intermediate' | 'advanced';
    price: string;
    sale_price: string | null;
    duration: string | null;
    enrolled_count: number;
    rating: string;
    review_count: number;
    is_featured: boolean;
    instructor: Instructor | null;
    category: CourseCategory | null;
}

interface PaginatedCourses {
    data: Course[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filters {
    search?: string;
    category?: string;
    level?: string;
    sort?: string;
    free?: string | boolean;
}

const props = defineProps<{
    courses: PaginatedCourses;
    categories: Category[];
    filters: Filters;
}>();

const search = ref(props.filters.search ?? '');
const selectedCategory = ref(props.filters.category ?? '');
const selectedLevel = ref(props.filters.level ?? '');
const selectedSort = ref(props.filters.sort ?? '');
const freeOnly = ref(
    props.filters.free === true ||
        props.filters.free === '1' ||
        props.filters.free === 1,
);
const showFilters = ref(false);

const levelLabels: Record<string, string> = {
    beginner: 'Beginner',
    intermediate: 'Intermediate',
    advanced: 'Advanced',
};

const levelColors: Record<string, string> = {
    beginner: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200',
    intermediate: 'bg-amber-50 text-amber-900 ring-1 ring-amber-200',
    advanced: 'bg-rose-50 text-rose-900 ring-1 ring-rose-200',
};

const sortOptions = [
    { value: '', label: 'Featured first' },
    { value: 'popular', label: 'Most popular' },
    { value: 'rating', label: 'Highest rated' },
    { value: 'newest', label: 'Newest' },
    { value: 'price_asc', label: 'Price: low to high' },
    { value: 'price_desc', label: 'Price: high to low' },
];

const activeFiltersCount = computed(() => {
    let n = 0;
    if (search.value.trim()) n++;
    if (selectedCategory.value) n++;
    if (selectedLevel.value) n++;
    if (selectedSort.value) n++;
    if (freeOnly.value) n++;
    return n;
});

const applyFilters = debounce(() => {
    router.get(
        '/online-courses',
        {
            search: search.value || undefined,
            category: selectedCategory.value || undefined,
            level: selectedLevel.value || undefined,
            sort: selectedSort.value || undefined,
            free: freeOnly.value ? '1' : undefined,
        },
        { preserveState: true, replace: true },
    );
}, 300);

watch(search, () => applyFilters());
watch([selectedCategory, selectedLevel, selectedSort, freeOnly], () =>
    applyFilters(),
);

function clearFilters() {
    search.value = '';
    selectedCategory.value = '';
    selectedLevel.value = '';
    selectedSort.value = '';
    freeOnly.value = false;
}

function formatPrice(price: string, salePrice: string | null) {
    const p = parseFloat(price);
    if (p === 0) return 'Free';
    const sp = salePrice ? parseFloat(salePrice) : null;
    return sp ? `${sp.toLocaleString()} XAF` : `${p.toLocaleString()} XAF`;
}

function coverUrl(url: string | null) {
    if (!url) return null;
    if (url.startsWith('http')) return url;
    return `/storage/${url}`;
}
</script>

<template>
    <PublicLayout>
        <Head title="Online Courses — Traitz Academy" />

        <!-- Hero — aligned with Programs/Index -->
        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] py-16 text-white"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">
                    Online Courses
                </h1>
                <p class="max-w-3xl text-xl text-gray-300">
                    Learn at your own pace with expert-led courses. Browse by
                    topic, level, and format — earn certificates and track your
                    progress.
                </p>
            </div>
        </section>

        <!-- Sticky search & filters -->
        <section
            class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 py-5 backdrop-blur"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
                >
                    <div
                        class="flex flex-col gap-3 lg:flex-row lg:items-center"
                    >
                        <div class="relative flex-1">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"
                            >
                                <Search class="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search courses by title, topic, or skill…"
                                class="w-full rounded-xl border-2 border-gray-200 py-2.5 pr-10 pl-12 text-gray-800 transition-all placeholder:text-gray-400 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                            />
                            <button
                                v-if="search"
                                type="button"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                aria-label="Clear search"
                                @click="search = ''"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <select
                            v-model="selectedSort"
                            class="rounded-xl border-2 border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 lg:w-56"
                        >
                            <option
                                v-for="opt in sortOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>

                        <button
                            type="button"
                            :class="[
                                'flex shrink-0 items-center justify-center gap-2 rounded-xl border-2 px-5 py-2.5 text-sm font-semibold transition-all',
                                showFilters || activeFiltersCount > 0
                                    ? 'border-[#42b6c5] bg-[#42b6c5] text-white'
                                    : 'border-gray-200 bg-white text-gray-700 hover:border-[#42b6c5]',
                            ]"
                            @click="showFilters = !showFilters"
                        >
                            <SlidersHorizontal class="h-4 w-4 shrink-0" />
                            Filters
                            <span
                                v-if="activeFiltersCount > 0"
                                class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-[#42b6c5]"
                            >
                                {{ activeFiltersCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Expandable: level, free -->
                    <div
                        v-if="showFilters"
                        class="mt-4 border-t border-gray-100 pt-4"
                    >
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                    >Level</label
                                >
                                <select
                                    v-model="selectedLevel"
                                    class="w-full rounded-lg border-2 border-gray-200 px-4 py-2 text-gray-800 transition-all focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20"
                                >
                                    <option value="">Any level</option>
                                    <option
                                        v-for="(label, value) in levelLabels"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <label
                                    class="flex w-full cursor-pointer items-center gap-3 rounded-lg border-2 border-gray-200 px-4 py-2.5 transition-colors hover:border-[#42b6c5]/50"
                                >
                                    <input
                                        v-model="freeOnly"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    />
                                    <span
                                        class="text-sm font-medium text-gray-700"
                                        >Free courses only</span
                                    >
                                </label>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button
                                type="button"
                                class="text-sm font-medium text-[#42b6c5] transition-colors hover:text-[#35919e]"
                                @click="clearFilters"
                            >
                                Clear all filters
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="activeFiltersCount > 0 && !showFilters"
                        class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 text-sm text-gray-600"
                    >
                        <span>
                            Showing
                            <span class="font-semibold text-gray-900">{{
                                courses.data.length
                            }}</span>
                            on this page ·
                            <span class="font-semibold text-gray-900">{{
                                courses.total
                            }}</span>
                            total matches
                        </span>
                        <span class="text-gray-300">|</span>
                        <button
                            type="button"
                            class="text-[#42b6c5] hover:underline"
                            @click="clearFilters"
                        >
                            Clear filters
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sidebar categories + course cards -->
        <section class="bg-gray-50 py-12 lg:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="flex flex-col gap-8 lg:flex-row lg:items-start lg:gap-10"
                >
                    <!-- Categories sidebar -->
                    <aside class="w-full shrink-0 lg:sticky lg:top-28 lg:w-64">
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                        >
                            <h2
                                class="mb-4 text-xs font-bold tracking-wide text-[#000928] uppercase"
                            >
                                Categories
                            </h2>
                            <nav
                                class="space-y-1"
                                aria-label="Course categories"
                            >
                                <button
                                    type="button"
                                    :class="[
                                        'flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition-colors',
                                        !selectedCategory
                                            ? 'bg-[#42b6c5]/15 font-semibold text-[#0d5c66]'
                                            : 'text-gray-600 hover:bg-gray-50',
                                    ]"
                                    @click="selectedCategory = ''"
                                >
                                    <BookOpen
                                        class="h-4 w-4 shrink-0 opacity-70"
                                    />
                                    All categories
                                </button>
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    type="button"
                                    :class="[
                                        'flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-sm transition-colors',
                                        selectedCategory === cat.slug
                                            ? 'bg-[#42b6c5]/15 font-semibold text-[#0d5c66]'
                                            : 'text-gray-600 hover:bg-gray-50',
                                    ]"
                                    @click="selectedCategory = cat.slug"
                                >
                                    <component
                                        :is="categoryIconFor(cat.icon)"
                                        v-if="categoryIconFor(cat.icon)"
                                        class="h-4 w-4 shrink-0"
                                    />
                                    <span class="min-w-0">{{ cat.name }}</span>
                                </button>
                            </nav>
                        </div>
                    </aside>

                    <!-- Main: results + grid -->
                    <div class="min-w-0 flex-1">
                        <div
                            class="mb-6 flex flex-wrap items-end justify-between gap-4"
                        >
                            <p class="text-gray-600">
                                <span class="font-semibold text-gray-900">{{
                                    courses.total
                                }}</span>
                                {{ courses.total === 1 ? 'course' : 'courses' }}
                                <span
                                    v-if="courses.last_page > 1"
                                    class="text-gray-400"
                                >
                                    · Page {{ courses.current_page }} of
                                    {{ courses.last_page }}
                                </span>
                            </p>
                        </div>

                        <!-- Empty -->
                        <div
                            v-if="courses.data.length === 0"
                            class="rounded-2xl border border-dashed border-gray-200 bg-white py-16 text-center"
                        >
                            <div
                                class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100"
                            >
                                <BookOpen class="h-10 w-10 text-gray-400" />
                            </div>
                            <h3
                                class="mb-2 text-lg font-semibold text-gray-900"
                            >
                                No courses found
                            </h3>
                            <p class="mb-6 text-sm text-gray-600">
                                Try another category or adjust your search.
                            </p>
                            <button
                                type="button"
                                class="rounded-lg bg-[#42b6c5] px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#35919e]"
                                @click="clearFilters"
                            >
                                Clear all filters
                            </button>
                        </div>

                        <!-- Grid -->
                        <div
                            v-else
                            class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3"
                        >
                            <div
                                v-for="course in courses.data"
                                :key="course.id"
                                class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                            >
                                <div
                                    class="relative h-44 overflow-hidden bg-gradient-to-br from-[#381998] to-[#42b6c5]"
                                >
                                    <img
                                        v-if="coverUrl(course.cover_image)"
                                        :src="
                                            coverUrl(course.cover_image) ??
                                            undefined
                                        "
                                        :alt="course.title"
                                        class="h-full w-full object-cover opacity-90 transition-transform duration-300 group-hover:scale-105"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full items-center justify-center"
                                    >
                                        <BookOpen
                                            class="h-16 w-16 text-white/35"
                                        />
                                    </div>
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"
                                    />
                                    <div
                                        v-if="course.is_featured"
                                        class="absolute top-3 right-3 rounded-full bg-amber-400 px-3 py-1 text-xs font-bold text-gray-900 shadow"
                                    >
                                        Featured
                                    </div>
                                    <span
                                        :class="[
                                            'absolute top-3 left-3 rounded-full px-2.5 py-0.5 text-xs font-semibold shadow-sm',
                                            levelColors[course.level],
                                        ]"
                                    >
                                        {{ levelLabels[course.level] }}
                                    </span>
                                </div>

                                <div class="flex flex-grow flex-col p-5">
                                    <span
                                        v-if="course.category"
                                        class="mb-2 inline-flex w-fit items-center gap-1.5 rounded-full bg-[#42b6c5]/10 px-3 py-1 text-xs font-semibold text-[#2a8a96]"
                                    >
                                        {{ course.category.name }}
                                    </span>

                                    <h3
                                        class="mb-2 line-clamp-2 text-lg font-bold text-[#000928]"
                                    >
                                        {{ course.title }}
                                    </h3>
                                    <p
                                        class="mb-4 line-clamp-2 flex-grow text-sm text-gray-600"
                                    >
                                        {{ course.short_description }}
                                    </p>

                                    <div
                                        v-if="course.instructor"
                                        class="mb-3 text-xs text-gray-500"
                                    >
                                        <span
                                            class="font-medium text-gray-700"
                                            >{{ course.instructor.name }}</span
                                        >
                                        <span class="text-gray-400">
                                            · Instructor</span
                                        >
                                    </div>

                                    <div
                                        class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500"
                                    >
                                        <span
                                            v-if="course.duration"
                                            class="inline-flex items-center gap-1"
                                            ><Clock class="h-3.5 w-3.5" />
                                            {{ course.duration }}</span
                                        >
                                        <span
                                            class="inline-flex items-center gap-1"
                                            ><Users class="h-3.5 w-3.5" />
                                            {{
                                                course.enrolled_count.toLocaleString()
                                            }}</span
                                        >
                                        <span
                                            v-if="parseFloat(course.rating) > 0"
                                            class="inline-flex items-center gap-1"
                                        >
                                            <Star
                                                class="h-3.5 w-3.5 fill-amber-400 text-amber-400"
                                            />
                                            {{
                                                parseFloat(
                                                    course.rating,
                                                ).toFixed(1)
                                            }}
                                            <span class="text-gray-400"
                                                >({{
                                                    course.review_count
                                                }})</span
                                            >
                                        </span>
                                    </div>

                                    <div
                                        class="mt-auto flex items-center justify-between gap-3 border-t border-gray-100 pt-3"
                                    >
                                        <div>
                                            <span
                                                class="text-lg font-bold text-[#42b6c5]"
                                                >{{
                                                    formatPrice(
                                                        course.price,
                                                        course.sale_price,
                                                    )
                                                }}</span
                                            >
                                            <span
                                                v-if="
                                                    parseFloat(
                                                        course.sale_price ||
                                                            '0',
                                                    ) > 0 &&
                                                    parseFloat(
                                                        course.sale_price!,
                                                    ) < parseFloat(course.price)
                                                "
                                                class="ml-1.5 text-xs text-gray-400 line-through"
                                            >
                                                {{
                                                    parseFloat(
                                                        course.price,
                                                    ).toLocaleString()
                                                }}
                                                XAF
                                            </span>
                                        </div>
                                        <Link
                                            :href="`/online-courses/${course.id}`"
                                            class="shrink-0 rounded-lg bg-[#000928] px-4 py-2 text-center text-sm font-semibold text-white transition-colors hover:bg-[#381998]"
                                        >
                                            View
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="courses.last_page > 1"
                            class="mt-10 flex flex-wrap items-center justify-center gap-2"
                        >
                            <template
                                v-for="link in courses.links"
                                :key="link.label"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'flex h-10 min-w-[40px] items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors',
                                        link.active
                                            ? 'bg-[#381998] text-white shadow'
                                            : 'border border-gray-200 bg-white text-gray-600 hover:border-[#42b6c5] hover:text-[#42b6c5]',
                                    ]"
                                    ><span v-html="link.label"
                                /></Link>
                                <span
                                    v-else
                                    :class="[
                                        'flex h-10 min-w-[40px] items-center justify-center rounded-lg px-3 text-sm font-medium',
                                        link.active
                                            ? 'bg-[#381998] text-white'
                                            : 'cursor-not-allowed text-gray-300',
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
