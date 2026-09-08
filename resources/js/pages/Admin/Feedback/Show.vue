<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BarChart3,
    ChevronDown,
    Copy,
    ExternalLink,
    Hash,
    MessageSquare,
    Trash2,
    Type,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
const toast = useToast();

interface Answer {
    question: { id: number; question: string; type: string };
    answer: string | null;
}

interface ResponseRow {
    id: number;
    is_anonymous: boolean;
    respondent_name: string | null;
    respondent_email: string | null;
    ip_address: string | null;
    created_at: string;
    user: { id: number; name: string } | null;
    answers: Answer[];
}

interface QuestionStat {
    question_id: number;
    question: string;
    type: string;
    stats: {
        type: 'chart' | 'text';
        labels?: string[];
        data?: number[];
        total: number;
        responses?: string[];
    };
}

interface Props {
    form: {
        id: number;
        title: string;
        description: string | null;
        slug: string;
        is_active: boolean;
        allow_anonymous: boolean;
        send_thank_you_email: boolean;
        closes_at: string | null;
        questions: any[];
        creator: { name: string } | null;
    };
    responses: {
        data: ResponseRow[];
        links: any[];
        total: number;
    };
    analytics: QuestionStat[];
    shareUrl: string;
    stats: {
        total_responses: number;
        anonymous_responses: number;
        identified_responses: number;
    };
}

const props = defineProps<Props>();

const activeTab = ref<'analytics' | 'responses'>('analytics');

const copyShareUrl = () => {
    navigator.clipboard.writeText(props.shareUrl);
    toast.success('Share link copied to clipboard!');
};

const formatDate = (d: string) =>
    new Date(d).toLocaleString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

const displayName = (r: ResponseRow) => {
    if (r.is_anonymous) {
        return 'Anonymous';
    }
    return r.respondent_name ?? r.user?.name ?? 'Unknown';
};

const toggleStatus = () => {
    router.post(
        `/admin/feedback/${props.form.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
            // Flash message handled by global watcher (FeedbackController::toggleStatus flashes 'success')
        },
    );
};

const showDeleteModal = ref(false);
const deleteProcessing = ref(false);

const confirmDelete = () => {
    deleteProcessing.value = true;
    router.delete(`/admin/feedback/${props.form.id}`, {
        onSuccess: () => {
            // Flash message handled by global watcher (FeedbackController::destroy flashes 'success')
            showDeleteModal.value = false;
        },
        onFinish: () => {
            deleteProcessing.value = false;
        },
    });
};

const showDeleteResponseModal = ref(false);
const deleteResponseId = ref<number | null>(null);
const deleteResponseProcessing = ref(false);

const openDeleteResponseModal = (responseId: number) => {
    deleteResponseId.value = responseId;
    showDeleteResponseModal.value = true;
};

const confirmDeleteResponse = () => {
    if (!deleteResponseId.value) {
        return;
    }
    deleteResponseProcessing.value = true;
    router.delete(
        `/admin/feedback/${props.form.id}/responses/${deleteResponseId.value}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                // Flash message handled by global watcher (FeedbackController::destroyResponse flashes 'success')
                showDeleteResponseModal.value = false;
                deleteResponseId.value = null;
            },
            onFinish: () => {
                deleteResponseProcessing.value = false;
            },
        },
    );
};

const maxBarValue = (data?: number[]) => Math.max(...(data ?? [1]), 1);

const barWidth = (value: number, max: number) =>
    `${Math.round((value / max) * 100)}%`;

const barColors = [
    'bg-[#42b6c5]',
    'bg-blue-400',
    'bg-purple-400',
    'bg-pink-400',
    'bg-amber-400',
    'bg-green-400',
];

const expandedAnalytics = ref<Set<number>>(
    new Set(props.analytics.slice(0, 3).map((a) => a.question_id)),
);

const toggleAnalytic = (id: number) => {
    const next = new Set(expandedAnalytics.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedAnalytics.value = next;
};

const isAnalyticExpanded = (id: number) => expandedAnalytics.value.has(id);

const expandAllAnalytics = () => {
    expandedAnalytics.value = new Set(
        props.analytics.map((a) => a.question_id),
    );
};

const collapseAllAnalytics = () => {
    expandedAnalytics.value = new Set();
};

const textResponsesLimit = ref<Record<number, number>>({});

const visibleTextResponses = (item: QuestionStat) => {
    const limit = textResponsesLimit.value[item.question_id] ?? 5;
    return item.stats.responses?.slice(0, limit) ?? [];
};

const showMoreTexts = (questionId: number) => {
    textResponsesLimit.value[questionId] =
        (textResponsesLimit.value[questionId] ?? 5) + 10;
};

const barPercent = (value: number, total: number) => {
    if (total === 0) {
        return '0%';
    }
    return `${Math.round((value / total) * 100)}%`;
};

const analyticSummary = (item: QuestionStat) => {
    if (item.stats.type === 'chart' && item.stats.labels && item.stats.data) {
        const maxIdx = item.stats.data.indexOf(Math.max(...item.stats.data));
        return `Top: ${item.stats.labels[maxIdx]} (${barPercent(item.stats.data[maxIdx], item.stats.total)})`;
    }
    return `${item.stats.total} text ${item.stats.total === 1 ? 'response' : 'responses'}`;
};

const answeredAnalyticsCount = computed(
    () => props.analytics.filter((a) => a.stats.total > 0).length,
);

const expandedResponses = ref<Set<number>>(new Set());

const toggleResponse = (id: number) => {
    const next = new Set(expandedResponses.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedResponses.value = next;
};

const isExpanded = (id: number) => expandedResponses.value.has(id);

const expandAll = () => {
    expandedResponses.value = new Set(props.responses.data.map((r) => r.id));
};

const collapseAll = () => {
    expandedResponses.value = new Set();
};

const answeredCount = (response: ResponseRow) => {
    return response.answers.filter((a) => a.answer?.trim()).length;
};

const answerPreview = (response: ResponseRow) => {
    const first = response.answers.find((a) => a.answer?.trim());
    if (!first?.answer) {
        return '';
    }
    return first.answer.length > 80
        ? first.answer.slice(0, 80) + '…'
        : first.answer;
};
</script>

<template>
    <Head :title="`${form.title} — Feedback`" />

    <div class="mx-auto max-w-6xl p-4 lg:p-8">
        <!-- Header -->
        <div
            class="mb-6 flex flex-col justify-between gap-4 lg:mb-8 lg:flex-row lg:items-start"
        >
            <div class="flex items-start gap-3">
                <Link
                    href="/admin/feedback"
                    class="mt-1 flex-shrink-0 rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
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
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </Link>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1
                            class="text-xl font-bold text-gray-900 lg:text-2xl dark:text-gray-100"
                        >
                            {{ form.title }}
                        </h1>
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
                    </div>
                    <p
                        v-if="form.description"
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        {{ form.description }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        By {{ form.creator?.name ?? 'Admin' }}
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="`/admin/feedback/${form.id}/edit`"
                    class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Edit
                </Link>
                <button
                    @click="toggleStatus"
                    :class="[
                        'rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                        form.is_active
                            ? 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50'
                            : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50',
                    ]"
                >
                    {{ form.is_active ? 'Deactivate' : 'Activate' }}
                </button>
                <a
                    :href="shareUrl"
                    target="_blank"
                    class="flex items-center gap-1.5 rounded-lg bg-[#42b6c5]/10 px-3 py-2 text-sm font-semibold text-[#42b6c5] transition-colors hover:bg-[#42b6c5]/20"
                >
                    <ExternalLink class="h-3.5 w-3.5" />
                    Preview
                </a>
                <button
                    @click="showDeleteModal = true"
                    class="flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    Delete
                </button>
            </div>
        </div>

        <!-- Share Link banner -->
        <div
            class="mb-6 flex flex-col items-start gap-3 rounded-xl border border-[#42b6c5]/20 bg-gradient-to-r from-[#42b6c5]/10 to-blue-500/10 p-4 sm:flex-row sm:items-center"
        >
            <div class="min-w-0 flex-1">
                <p
                    class="mb-0.5 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                >
                    Share Link
                </p>
                <p
                    class="truncate text-sm font-medium text-gray-800 dark:text-gray-200"
                >
                    {{ shareUrl }}
                </p>
            </div>
            <button
                @click="copyShareUrl"
                class="flex flex-shrink-0 items-center gap-2 rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#35a3b2]"
            >
                <Copy class="h-4 w-4" />
                Copy Link
            </button>
        </div>

        <!-- Stats -->
        <div class="mb-6 grid grid-cols-3 gap-3 lg:gap-6">
            <div
                class="rounded-xl border-l-4 border-[#42b6c5] bg-white p-4 shadow-sm lg:p-6 dark:bg-gray-800"
            >
                <p
                    class="mb-1 text-xs font-semibold text-gray-500 dark:text-gray-400"
                >
                    Total Responses
                </p>
                <p
                    class="text-2xl font-bold text-[#000928] lg:text-3xl dark:text-gray-100"
                >
                    {{ stats.total_responses }}
                </p>
            </div>
            <div
                class="rounded-xl border-l-4 border-purple-400 bg-white p-4 shadow-sm lg:p-6 dark:bg-gray-800"
            >
                <p
                    class="mb-1 text-xs font-semibold text-gray-500 dark:text-gray-400"
                >
                    Anonymous
                </p>
                <p
                    class="text-2xl font-bold text-[#000928] lg:text-3xl dark:text-gray-100"
                >
                    {{ stats.anonymous_responses }}
                </p>
            </div>
            <div
                class="rounded-xl border-l-4 border-green-400 bg-white p-4 shadow-sm lg:p-6 dark:bg-gray-800"
            >
                <p
                    class="mb-1 text-xs font-semibold text-gray-500 dark:text-gray-400"
                >
                    Identified
                </p>
                <p
                    class="text-2xl font-bold text-[#000928] lg:text-3xl dark:text-gray-100"
                >
                    {{ stats.identified_responses }}
                </p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 flex border-b border-gray-200 dark:border-gray-700">
            <button
                @click="activeTab = 'analytics'"
                :class="[
                    'flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition-colors',
                    activeTab === 'analytics'
                        ? 'border-[#42b6c5] text-[#42b6c5]'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                ]"
            >
                <BarChart3 class="h-4 w-4" />
                Analytics
            </button>
            <button
                @click="activeTab = 'responses'"
                :class="[
                    'flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition-colors',
                    activeTab === 'responses'
                        ? 'border-[#42b6c5] text-[#42b6c5]'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                ]"
            >
                <Users class="h-4 w-4" />
                Responses
                <span
                    class="ml-0.5 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400"
                >
                    {{ stats.total_responses }}
                </span>
            </button>
        </div>

        <!-- Analytics Tab -->
        <div v-if="activeTab === 'analytics'">
            <div
                v-if="analytics.length === 0"
                class="py-16 text-center text-gray-400"
            >
                No analytics available yet.
            </div>

            <div v-else>
                <!-- Summary bar + controls -->
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ analytics.length }} questions &middot;
                        {{ answeredAnalyticsCount }} with responses
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            @click="expandAllAnalytics"
                            class="text-xs font-medium text-[#42b6c5] transition-colors hover:text-[#35a3b2]"
                        >
                            Expand all
                        </button>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <button
                            @click="collapseAllAnalytics"
                            class="text-xs font-medium text-[#42b6c5] transition-colors hover:text-[#35a3b2]"
                        >
                            Collapse all
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(item, index) in analytics"
                        :key="item.question_id"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                    >
                        <!-- Clickable header -->
                        <div
                            class="flex cursor-pointer items-center justify-between px-5 py-3.5 select-none"
                            @click="toggleAnalytic(item.question_id)"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                    :class="
                                        item.stats.type === 'chart'
                                            ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                                            : 'bg-purple-100 text-purple-500 dark:bg-purple-900/30 dark:text-purple-400'
                                    "
                                >
                                    {{ index + 1 }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-800 dark:text-gray-200"
                                        >
                                            {{ item.question }}
                                        </p>
                                        <span
                                            class="inline-flex flex-shrink-0 items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                                            :class="
                                                item.stats.type === 'chart'
                                                    ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                                                    : 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400'
                                            "
                                        >
                                            <Hash
                                                v-if="
                                                    item.stats.type === 'chart'
                                                "
                                                class="h-2.5 w-2.5"
                                            />
                                            <Type v-else class="h-2.5 w-2.5" />
                                            {{
                                                item.stats.type === 'chart'
                                                    ? 'Choice'
                                                    : 'Text'
                                            }}
                                        </span>
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        <p class="text-xs text-gray-400">
                                            {{ item.stats.total }}
                                            {{
                                                item.stats.total === 1
                                                    ? 'response'
                                                    : 'responses'
                                            }}
                                        </p>
                                        <!-- Collapsed summary -->
                                        <template
                                            v-if="
                                                !isAnalyticExpanded(
                                                    item.question_id,
                                                ) && item.stats.total > 0
                                            "
                                        >
                                            <span
                                                class="text-gray-300 dark:text-gray-600"
                                                >&middot;</span
                                            >
                                            <p
                                                class="truncate text-xs text-gray-400 italic"
                                            >
                                                {{ analyticSummary(item) }}
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <ChevronDown
                                :class="[
                                    'ml-3 h-4 w-4 flex-shrink-0 text-gray-400 transition-transform duration-200',
                                    isAnalyticExpanded(item.question_id)
                                        ? 'rotate-180'
                                        : '',
                                ]"
                            />
                        </div>

                        <!-- Collapsible body -->
                        <transition
                            enter-active-class="transition-all duration-200 ease-out"
                            leave-active-class="transition-all duration-150 ease-in"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-[3000px] opacity-100"
                            leave-from-class="max-h-[3000px] opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div
                                v-show="isAnalyticExpanded(item.question_id)"
                                class="overflow-hidden"
                            >
                                <div
                                    class="border-t border-gray-100 px-5 py-4 dark:border-gray-700"
                                >
                                    <!-- Bar chart for multiple choice -->
                                    <div
                                        v-if="
                                            item.stats.type === 'chart' &&
                                            item.stats.labels &&
                                            item.stats.data
                                        "
                                        class="space-y-3"
                                    >
                                        <div
                                            v-for="(label, li) in item.stats
                                                .labels"
                                            :key="li"
                                            class="flex items-center gap-3"
                                        >
                                            <span
                                                class="w-32 flex-shrink-0 truncate text-right text-xs text-gray-600 dark:text-gray-300"
                                                :title="label"
                                                >{{ label }}</span
                                            >
                                            <div
                                                class="relative h-8 flex-1 overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700"
                                            >
                                                <div
                                                    :class="[
                                                        'h-full rounded-lg transition-all duration-700',
                                                        barColors[
                                                            li %
                                                                barColors.length
                                                        ],
                                                    ]"
                                                    :style="{
                                                        width: barWidth(
                                                            item.stats.data![
                                                                li
                                                            ],
                                                            maxBarValue(
                                                                item.stats.data,
                                                            ),
                                                        ),
                                                    }"
                                                />
                                                <span
                                                    v-if="
                                                        item.stats.data![li] > 0
                                                    "
                                                    class="absolute inset-y-0 right-2 flex items-center text-[10px] font-bold text-gray-500 dark:text-gray-400"
                                                >
                                                    {{
                                                        barPercent(
                                                            item.stats.data![
                                                                li
                                                            ],
                                                            item.stats.total,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                            <span
                                                class="w-8 flex-shrink-0 text-right text-xs font-bold text-gray-700 dark:text-gray-300"
                                            >
                                                {{ item.stats.data![li] }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Text responses -->
                                    <div
                                        v-else-if="
                                            item.stats.type === 'text' &&
                                            item.stats.responses
                                        "
                                        class="space-y-2"
                                    >
                                        <div
                                            v-if="
                                                item.stats.responses.length ===
                                                0
                                            "
                                            class="py-2 text-sm text-gray-400 italic"
                                        >
                                            No text responses yet.
                                        </div>
                                        <template v-else>
                                            <div
                                                v-for="(
                                                    resp, ri
                                                ) in visibleTextResponses(item)"
                                                :key="ri"
                                                class="flex items-start gap-3"
                                            >
                                                <div
                                                    class="w-1 flex-shrink-0 self-stretch rounded-full bg-purple-200 dark:bg-purple-800"
                                                />
                                                <p
                                                    class="py-1 text-sm leading-relaxed text-gray-700 dark:text-gray-300"
                                                >
                                                    {{ resp }}
                                                </p>
                                            </div>
                                            <button
                                                v-if="
                                                    (textResponsesLimit[
                                                        item.question_id
                                                    ] ?? 5) <
                                                    item.stats.responses.length
                                                "
                                                @click.stop="
                                                    showMoreTexts(
                                                        item.question_id,
                                                    )
                                                "
                                                class="mt-2 text-xs font-medium text-[#42b6c5] transition-colors hover:text-[#35a3b2]"
                                            >
                                                Show more ({{
                                                    item.stats.responses
                                                        .length -
                                                    (textResponsesLimit[
                                                        item.question_id
                                                    ] ?? 5)
                                                }}
                                                remaining)
                                            </button>
                                            <p
                                                v-if="item.stats.total > 50"
                                                class="mt-1 text-xs text-gray-400 italic"
                                            >
                                                Showing top 50 of
                                                {{
                                                    item.stats.total
                                                }}
                                                responses.
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
        </div>

        <!-- Responses Tab -->
        <div v-else-if="activeTab === 'responses'">
            <div v-if="responses.data.length === 0" class="py-16 text-center">
                <MessageSquare
                    class="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-600"
                />
                <p class="text-gray-500 dark:text-gray-400">
                    No responses yet. Share the link to start collecting
                    feedback.
                </p>
            </div>

            <div v-else>
                <!-- Expand / Collapse controls -->
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ responses.data.length }} of
                        {{ responses.total }} responses
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            @click="expandAll"
                            class="text-xs font-medium text-[#42b6c5] transition-colors hover:text-[#35a3b2]"
                        >
                            Expand all
                        </button>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <button
                            @click="collapseAll"
                            class="text-xs font-medium text-[#42b6c5] transition-colors hover:text-[#35a3b2]"
                        >
                            Collapse all
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="response in responses.data"
                        :key="response.id"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                    >
                        <!-- Clickable header -->
                        <div
                            class="flex cursor-pointer items-center justify-between px-5 py-3.5 select-none"
                            @click="toggleResponse(response.id)"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <div
                                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                    :class="
                                        response.is_anonymous
                                            ? 'bg-purple-100 text-purple-500 dark:bg-purple-900/30 dark:text-purple-400'
                                            : 'bg-[#42b6c5]/20 text-[#42b6c5]'
                                    "
                                >
                                    {{
                                        response.is_anonymous
                                            ? '?'
                                            : displayName(response)
                                                  .charAt(0)
                                                  .toUpperCase()
                                    }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-800 dark:text-gray-200"
                                        >
                                            {{ displayName(response) }}
                                        </p>
                                        <span
                                            class="flex-shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                                            :class="
                                                response.is_anonymous
                                                    ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400'
                                                    : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                            "
                                        >
                                            {{
                                                response.is_anonymous
                                                    ? 'Anonymous'
                                                    : 'Identified'
                                            }}
                                        </span>
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        <p class="text-xs text-gray-400">
                                            {{
                                                formatDate(response.created_at)
                                            }}
                                        </p>
                                        <span
                                            class="text-gray-300 dark:text-gray-600"
                                            >&middot;</span
                                        >
                                        <p class="text-xs text-gray-400">
                                            {{ answeredCount(response) }}/{{
                                                response.answers.length
                                            }}
                                            answered
                                        </p>
                                    </div>
                                    <!-- Preview snippet when collapsed -->
                                    <p
                                        v-if="
                                            !isExpanded(response.id) &&
                                            answerPreview(response)
                                        "
                                        class="mt-1 truncate text-xs text-gray-400 italic dark:text-gray-500"
                                    >
                                        "{{ answerPreview(response) }}"
                                    </p>
                                </div>
                            </div>
                            <div
                                class="ml-3 flex flex-shrink-0 items-center gap-2"
                            >
                                <button
                                    @click.stop="
                                        openDeleteResponseModal(response.id)
                                    "
                                    class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20"
                                    title="Delete response"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                                <ChevronDown
                                    :class="[
                                        'h-4 w-4 text-gray-400 transition-transform duration-200',
                                        isExpanded(response.id)
                                            ? 'rotate-180'
                                            : '',
                                    ]"
                                />
                            </div>
                        </div>

                        <!-- Collapsible answers body -->
                        <transition
                            enter-active-class="transition-all duration-200 ease-out"
                            leave-active-class="transition-all duration-150 ease-in"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-[2000px] opacity-100"
                            leave-from-class="max-h-[2000px] opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div
                                v-show="isExpanded(response.id)"
                                class="overflow-hidden"
                            >
                                <div
                                    class="border-t border-gray-100 dark:border-gray-700"
                                >
                                    <!-- Meta info row -->
                                    <div
                                        v-if="
                                            response.respondent_email ||
                                            response.ip_address
                                        "
                                        class="flex flex-wrap items-center gap-4 bg-gray-50/50 px-5 py-2.5 text-xs text-gray-400 dark:bg-gray-700/20"
                                    >
                                        <span
                                            v-if="
                                                !response.is_anonymous &&
                                                response.respondent_email
                                            "
                                            class="flex items-center gap-1"
                                        >
                                            <svg
                                                class="h-3 w-3"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                />
                                            </svg>
                                            {{ response.respondent_email }}
                                        </span>
                                        <span
                                            v-if="response.ip_address"
                                            class="flex items-center gap-1"
                                        >
                                            <svg
                                                class="h-3 w-3"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"
                                                />
                                            </svg>
                                            {{ response.ip_address }}
                                        </span>
                                    </div>

                                    <!-- Answers -->
                                    <div class="space-y-3 px-5 py-4">
                                        <div
                                            v-for="answer in response.answers"
                                            :key="answer.question.id"
                                            class="flex gap-3"
                                        >
                                            <div
                                                class="w-1 flex-shrink-0 self-stretch rounded-full"
                                                :class="
                                                    answer.answer
                                                        ? 'bg-[#42b6c5]/30'
                                                        : 'bg-gray-200 dark:bg-gray-700'
                                                "
                                            />
                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="mb-0.5 text-xs font-semibold text-gray-500 dark:text-gray-400"
                                                >
                                                    {{
                                                        answer.question.question
                                                    }}
                                                </p>
                                                <p
                                                    v-if="answer.answer"
                                                    class="text-sm leading-relaxed text-gray-800 dark:text-gray-200"
                                                >
                                                    {{ answer.answer }}
                                                </p>
                                                <p
                                                    v-else
                                                    class="text-sm text-gray-400 italic"
                                                >
                                                    No answer provided
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="responses.links && responses.links.length > 3"
                    class="mt-6 flex justify-center gap-1"
                >
                    <template v-for="link in responses.links" :key="link.label">
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
        </div>
    </div>

    <ConfirmationModal
        :open="showDeleteModal"
        title="Delete Feedback Form"
        :description="`Are you sure you want to delete '${form.title}'? All responses will be permanently deleted.`"
        confirm-text="Delete"
        variant="destructive"
        :processing="deleteProcessing"
        @confirm="confirmDelete"
        @update:open="showDeleteModal = $event"
    />

    <ConfirmationModal
        :open="showDeleteResponseModal"
        title="Delete Response"
        description="Are you sure you want to delete this response? This action cannot be undone."
        confirm-text="Delete"
        variant="destructive"
        :processing="deleteResponseProcessing"
        @confirm="confirmDeleteResponse"
        @update:open="showDeleteResponseModal = $event"
    />
</template>
