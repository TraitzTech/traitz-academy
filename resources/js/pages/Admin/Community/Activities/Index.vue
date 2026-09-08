<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { ref, watch } from 'vue';

import EmptyState from '@/components/community/EmptyState.vue';
import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    Paginated,
    SelectOption,
    TacActivity,
    TacTrack,
} from '@/types/community';

interface Props {
    activities: Paginated<TacActivity>;
    filters: Record<string, string | undefined>;
    tracks: TacTrack[];
    types: SelectOption[];
    stats: {
        total: number;
        published: number;
        drafts: number;
        upcoming: number;
        rsvps: number;
    };
    can: { publish: boolean };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { dateRange, activityType } = useCommunity();

const filters = ref({
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
    track: props.filters.track ?? '',
    window: props.filters.window ?? '',
});

const go = () =>
    router.get(
        '/admin/community/activities',
        Object.fromEntries(
            Object.entries(filters.value).filter(([, v]) => v),
        ),
        { preserveState: true, preserveScroll: true, replace: true },
    );

const goDebounced = debounce(go, 350);

watch(() => filters.value.search, goDebounced);
watch(
    () => [
        filters.value.type,
        filters.value.status,
        filters.value.track,
        filters.value.window,
    ],
    () => {
        goDebounced.cancel();
        go();
    },
);

const setStatus = (activity: TacActivity, status: string) =>
    router.post(
        `/admin/community/activities/${activity.slug}/status`,
        { status },
        { preserveScroll: true },
    );

const toggleFeatured = (activity: TacActivity) =>
    router.post(
        `/admin/community/activities/${activity.slug}/featured`,
        {},
        { preserveScroll: true },
    );

const statusClasses = (status: string) =>
    ({
        published: 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300',
        draft: 'bg-gray-500/12 text-gray-600 dark:text-gray-300',
        cancelled: 'bg-red-500/12 text-red-700 dark:text-red-300',
        completed: 'bg-[#381998]/12 text-[#381998] dark:text-[#b9a5f5]',
    })[status] ?? 'bg-gray-500/12 text-gray-600';
</script>

<template>
    <div class="lms-page">
        <Head title="Community activities — Admin" />

        <div class="lms-hero">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight">
                        Activities
                    </h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        Events, workshops, trainings, bootcamps, handouts and
                        competitions — published without a deploy.
                    </p>
                </div>
                <Link
                    href="/admin/community/activities/create"
                    class="rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#35919e]"
                >
                    New activity
                </Link>
            </div>

            <dl class="mt-7 grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div
                    v-for="stat in [
                        { label: 'Total', value: stats.total },
                        { label: 'Published', value: stats.published },
                        { label: 'Drafts', value: stats.drafts },
                        { label: 'Upcoming', value: stats.upcoming },
                        { label: 'RSVPs', value: stats.rsvps },
                    ]"
                    :key="stat.label"
                    class="rounded-xl border border-white/10 bg-white/5 p-3.5 backdrop-blur"
                >
                    <dd class="text-xl font-black">{{ stat.value }}</dd>
                    <dt
                        class="mt-0.5 text-[10px] font-semibold tracking-wider text-white/60 uppercase"
                    >
                        {{ stat.label }}
                    </dt>
                </div>
            </dl>
        </div>

        <!-- Filters -->
        <section class="lms-panel">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search activities…"
                    class="lms-input lg:col-span-2"
                    aria-label="Search activities"
                />
                <select v-model="filters.type" class="lms-input" aria-label="Type">
                    <option value="">All types</option>
                    <option v-for="t in types" :key="t.value" :value="t.value">
                        {{ t.label }}
                    </option>
                </select>
                <select v-model="filters.status" class="lms-input" aria-label="Status">
                    <option value="">Any status</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select v-model="filters.window" class="lms-input" aria-label="Window">
                    <option value="">All dates</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="past">Past</option>
                </select>
            </div>
        </section>

        <!-- List -->
        <section class="lms-panel overflow-hidden p-0">
            <div v-if="activities.data.length" class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-sm">
                    <thead
                        class="border-b border-gray-100 bg-gray-50/70 text-left dark:border-white/10 dark:bg-white/5"
                    >
                        <tr>
                            <th class="px-4 py-3 font-bold">Activity</th>
                            <th class="px-4 py-3 font-bold">Track</th>
                            <th class="px-4 py-3 font-bold">When</th>
                            <th class="px-4 py-3 font-bold">RSVPs</th>
                            <th class="px-4 py-3 font-bold">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        <tr
                            v-for="activity in activities.data"
                            :key="activity.id"
                            class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/5"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-start gap-2">
                                    <span
                                        :class="[
                                            'mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold',
                                            activityType(activity.type).classes,
                                        ]"
                                    >
                                        {{ activityType(activity.type).label }}
                                    </span>
                                    <div class="min-w-0">
                                        <Link
                                            :href="`/admin/community/activities/${activity.slug}`"
                                            class="block truncate font-semibold text-[#000928] hover:underline dark:text-white"
                                        >
                                            {{ activity.title }}
                                        </Link>
                                        <p
                                            class="truncate text-xs text-gray-500"
                                        >
                                            {{ activity.location || 'Online' }}
                                            <template v-if="activity.is_paid"
                                                >· {{ activity.price }}
                                                {{ activity.currency }}</template
                                            >
                                            <template v-if="activity.is_recurring"
                                                >· recurring</template
                                            >
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ activity.track?.name ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                {{ dateRange(activity) }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="font-bold text-[#000928] dark:text-white"
                                >
                                    {{ activity.rsvp_count }}
                                </span>
                                <span
                                    v-if="activity.capacity"
                                    class="text-gray-400"
                                    >/{{ activity.capacity }}</span
                                >
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-0.5 text-[10px] font-bold capitalize',
                                            statusClasses(activity.status),
                                        ]"
                                    >
                                        {{ activity.status }}
                                    </span>
                                    <button
                                        v-if="can.publish"
                                        type="button"
                                        :aria-label="
                                            activity.is_featured
                                                ? 'Unfeature'
                                                : 'Feature'
                                        "
                                        :title="
                                            activity.is_featured
                                                ? 'Featured — click to unfeature'
                                                : 'Feature on the public site'
                                        "
                                        :class="[
                                            'text-sm transition-opacity',
                                            activity.is_featured
                                                ? 'opacity-100'
                                                : 'opacity-25 hover:opacity-70',
                                        ]"
                                        @click="toggleFeatured(activity)"
                                    >
                                        ★
                                    </button>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center justify-end gap-3 text-xs"
                                >
                                    <button
                                        v-if="
                                            can.publish &&
                                            activity.status === 'draft'
                                        "
                                        type="button"
                                        class="font-bold text-emerald-600 hover:underline"
                                        @click="setStatus(activity, 'published')"
                                    >
                                        Publish
                                    </button>
                                    <button
                                        v-else-if="
                                            can.publish &&
                                            activity.status === 'published'
                                        "
                                        type="button"
                                        class="font-bold text-gray-500 hover:underline"
                                        @click="setStatus(activity, 'draft')"
                                    >
                                        Unpublish
                                    </button>
                                    <Link
                                        v-if="activity.type === 'competition'"
                                        :href="`/admin/community/activities/${activity.slug}/judge`"
                                        class="font-bold text-amber-600 hover:underline"
                                    >
                                        Judge
                                    </Link>
                                    <Link
                                        :href="`/admin/community/activities/${activity.slug}/edit`"
                                        class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                                    >
                                        Edit
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="p-6">
                <EmptyState
                    title="No activities yet"
                    description="Create the first workshop, training or competition — it goes live the moment you publish it."
                >
                    <Link
                        href="/admin/community/activities/create"
                        class="lms-btn-accent"
                        >Create an activity</Link
                    >
                </EmptyState>
            </div>

            <nav
                v-if="activities.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3 dark:border-white/10"
                aria-label="Pagination"
            >
                <p class="text-xs text-gray-500">
                    Showing {{ activities.from }}–{{ activities.to }} of
                    {{ activities.total }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in activities.links"
                        :key="index"
                        :href="link.url ?? undefined"
                        preserve-scroll
                        preserve-state
                        :class="[
                            'min-w-8 rounded-lg px-2.5 py-1.5 text-center text-xs font-semibold transition-colors',
                            link.active
                                ? 'bg-[#000928] text-white dark:bg-[#42b6c5]'
                                : link.url
                                  ? 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10'
                                  : 'cursor-not-allowed text-gray-300 dark:text-gray-600',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </nav>
        </section>
    </div>
</template>
