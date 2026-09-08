<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    ChevronDown,
    Clock,
    Flame,
    NotebookPen,
    Pencil,
    PlusCircle,
    Sparkles,
    Target,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import RichTextEditor from '@/components/RichTextEditor.vue';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { lessonBodyHtml } from '@/utils/lessonContentHtml';

defineOptions({ layout: AppLayout });

interface TodayEntry {
    content: string;
    hours_spent: string | number | null;
    learnings: string | null;
    blockers: string | null;
    solution: string | null;
    status: string;
    supervisor_feedback: string | null;
}
interface HistoryEntry {
    id: number;
    date: string;
    content: string;
    hours_spent: string | number | null;
    blockers: string | null;
    solution: string | null;
    status: string;
    supervisor_feedback: string | null;
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    internship: {
        id: number;
        program: string | null;
        start_date: string | null;
    };
    today: string;
    todayIsOfficeDay: boolean | null;
    todayEntry: TodayEntry | null;
    entriesLogged: number;
    streak: number;
    entries: { data: HistoryEntry[]; links: PaginationLink[]; total: number };
}>();

const toast = useToast();
const logbookLocked = computed(() => props.todayEntry?.status === 'approved');

// Hours are only asked on remote days — on office days they're derived from
// clock-in/out. When there's no configured schedule (null) we still ask.
const showHours = computed(() => props.todayIsOfficeDay !== true);

const logbookForm = useForm({
    content: props.todayEntry?.content ?? '',
    hours_spent: props.todayEntry?.hours_spent ?? '',
    learnings: props.todayEntry?.learnings ?? '',
    has_difficulty: Boolean(props.todayEntry?.blockers),
    blockers: props.todayEntry?.blockers ?? '',
    solution: props.todayEntry?.solution ?? '',
});

function saveLogbook() {
    logbookForm.post('/dashboard/internship/logbook', {
        preserveScroll: true,
        onError: (e) =>
            toast.error(
                (Object.values(e)[0] as string) ||
                    'Please write what you did today.',
            ),
        onSuccess: () => {
            formOpen.value = false;
        },
    });
}

// The form starts collapsed so the page reads as a log to scan, not an
// always-open editor — it only expands on demand (new entry or edit).
const formOpen = ref(false);

const statusStyles: Record<string, string> = {
    approved:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    needs_revision:
        'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    submitted: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200',
    draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200',
};

function statusClass(status: string) {
    return statusStyles[status] ?? statusStyles.draft;
}

// "approved" reads to interns as "reviewed" — the supervisor acknowledged the
// entry, not necessarily graded it (nothing is gated on this status).
const statusLabels: Record<string, string> = {
    approved: 'Reviewed',
    needs_revision: 'Needs revision',
    submitted: 'Submitted',
    draft: 'Draft',
};

function statusLabel(status: string) {
    return statusLabels[status] ?? status.replace('_', ' ');
}

function fmtDate(value: string) {
    return new Date(value).toLocaleDateString(undefined, {
        weekday: 'long',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function fmtDateShort(value: string) {
    return new Date(value).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
    });
}

const dayNumber = computed(() => {
    if (!props.internship.start_date) return null;
    const start = new Date(props.internship.start_date);
    const now = new Date(props.today);
    const days = Math.floor((now.getTime() - start.getTime()) / 86_400_000) + 1;
    return days > 0 ? days : 1;
});

function preview(html: string): string {
    const text = html
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    return text.length > 140
        ? text.slice(0, 140).trimEnd() + '…'
        : text || 'No entry text.';
}

const expanded = ref<Set<number>>(new Set());
function toggleExpanded(id: number) {
    if (expanded.value.has(id)) expanded.value.delete(id);
    else expanded.value.add(id);
}
</script>

<template>
    <div class="space-y-6">
        <Head title="My Logbook" />

        <Link
            href="/dashboard/internship"
            class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-[#381998] dark:text-gray-300"
        >
            <ArrowLeft class="h-4 w-4" /> My Internship
        </Link>

        <!-- Today's entry -->
        <div
            class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                class="h-1.5 bg-gradient-to-r from-[#42b6c5] to-[#381998]"
            ></div>

            <!-- Header zone reads as one designed unit with the gradient bar, not a title slapped above a form -->
            <div
                class="border-b border-gray-100 bg-gradient-to-br from-[#42b6c5]/[0.06] to-[#381998]/[0.04] px-6 py-5 sm:px-8 dark:border-gray-700 dark:from-[#42b6c5]/[0.08] dark:to-[#381998]/[0.06]"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-gray-800"
                        >
                            <NotebookPen class="h-5 w-5 text-[#2a8a96]" />
                        </div>
                        <div>
                            <p
                                class="text-[11px] font-semibold tracking-wider text-[#2a8a96] uppercase"
                            >
                                My Logbook · {{ internship.program }}
                            </p>
                            <h1
                                class="text-xl leading-tight font-bold text-[#000928] dark:text-white"
                            >
                                {{ fmtDate(today) }}
                            </h1>
                        </div>
                    </div>
                    <span
                        v-if="todayEntry"
                        :class="[
                            'shrink-0 rounded-full px-3 py-1 text-xs font-semibold',
                            statusClass(todayEntry.status),
                        ]"
                    >
                        {{ statusLabel(todayEntry.status) }}
                    </span>
                </div>

                <!-- Progress: makes today feel like part of a sequence, not an isolated form -->
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <span
                        v-if="dayNumber"
                        class="inline-flex items-center gap-1 rounded-full bg-white/70 px-2.5 py-1 font-semibold text-[#381998] dark:bg-white/10 dark:text-[#b9a6f0]"
                    >
                        Day {{ dayNumber }} of your internship
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-white/70 px-2.5 py-1 font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300"
                    >
                        {{ entriesLogged }} entr{{
                            entriesLogged === 1 ? 'y' : 'ies'
                        }}
                        logged
                    </span>
                    <span
                        v-if="streak > 1"
                        class="inline-flex items-center gap-1 rounded-full bg-orange-100 px-2.5 py-1 font-semibold text-orange-700 dark:bg-orange-900/30 dark:text-orange-300"
                    >
                        <Flame class="h-3 w-3" /> {{ streak }}-day streak
                    </span>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <div
                    v-if="todayEntry?.supervisor_feedback"
                    class="mb-6 flex gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200"
                >
                    <Sparkles class="mt-0.5 h-4 w-4 shrink-0" />
                    <div>
                        <p class="font-semibold">Supervisor feedback</p>
                        <p class="mt-0.5">
                            {{ todayEntry.supervisor_feedback }}
                        </p>
                    </div>
                </div>

                <!-- Collapsed: reads as a scan row, matching how past entries display -->
                <div
                    v-if="!formOpen"
                    class="flex flex-wrap items-center justify-between gap-4"
                >
                    <div v-if="todayEntry" class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                :class="[
                                    'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    statusClass(todayEntry.status),
                                ]"
                                >{{ statusLabel(todayEntry.status) }}</span
                            >
                            <span
                                v-if="todayEntry.hours_spent"
                                class="inline-flex items-center gap-0.5 text-xs text-gray-400"
                                ><Clock class="h-3 w-3" />{{
                                    todayEntry.hours_spent
                                }}h</span
                            >
                        </div>
                        <p
                            class="mt-1.5 truncate text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ preview(todayEntry.content) }}
                        </p>
                    </div>
                    <p v-else class="text-sm text-gray-400">
                        You haven't filled in today's entry yet.
                    </p>

                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35919e]"
                        @click="formOpen = true"
                    >
                        <template v-if="!todayEntry"
                            ><PlusCircle class="h-4 w-4" /> Fill today's
                            logbook</template
                        >
                        <template v-else-if="logbookLocked"
                            ><CheckCircle2 class="h-4 w-4" /> View
                            entry</template
                        >
                        <template v-else
                            ><Pencil class="h-4 w-4" /> Edit entry</template
                        >
                    </button>
                </div>

                <fieldset v-else :disabled="logbookLocked" class="space-y-6">
                    <div class="flex items-center justify-between">
                        <label
                            class="flex items-center gap-1.5 text-base font-bold text-[#000928] dark:text-white"
                        >
                            <Target class="h-4 w-4 text-[#381998]" /> What did
                            you do today? <span class="text-red-500">*</span>
                        </label>
                        <button
                            type="button"
                            class="text-xs font-semibold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            @click="formOpen = false"
                        >
                            Collapse
                        </button>
                    </div>
                    <div>
                        <RichTextEditor
                            v-model="logbookForm.content"
                            placeholder="Tasks worked on, progress made, what you learned along the way… you can format text and paste in screenshots or other images."
                            upload-url="/dashboard/internship/logbook/media"
                            body-class="min-h-[180px]"
                            :disabled="logbookLocked"
                            compact
                        />
                        <p
                            v-if="logbookForm.errors.content"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ logbookForm.errors.content }}
                        </p>
                    </div>

                    <!-- Difficulties: an explicit yes/no, revealing the problem + solution when yes -->
                    <div>
                        <label
                            class="mb-2 flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-400 uppercase"
                        >
                            <AlertTriangle class="h-3.5 w-3.5" /> Did you run
                            into any difficulties today?
                        </label>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                :class="[
                                    'rounded-lg border px-4 py-1.5 text-sm font-semibold transition-colors',
                                    logbookForm.has_difficulty
                                        ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#2a8a96]'
                                        : 'border-gray-200 text-gray-600 hover:border-gray-300 dark:border-gray-600 dark:text-gray-300',
                                ]"
                                @click="logbookForm.has_difficulty = true"
                            >
                                Yes
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-lg border px-4 py-1.5 text-sm font-semibold transition-colors',
                                    !logbookForm.has_difficulty
                                        ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#2a8a96]'
                                        : 'border-gray-200 text-gray-600 hover:border-gray-300 dark:border-gray-600 dark:text-gray-300',
                                ]"
                                @click="logbookForm.has_difficulty = false"
                            >
                                No
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="logbookForm.has_difficulty"
                        class="space-y-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900/40 dark:bg-amber-900/10"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-semibold tracking-wide text-gray-500 uppercase"
                            >
                                The difficulty
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="logbookForm.blockers"
                                rows="3"
                                placeholder="What got in your way today…"
                                class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 transition-shadow placeholder:text-gray-400 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            />
                            <p
                                v-if="logbookForm.errors.blockers"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ logbookForm.errors.blockers }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-semibold tracking-wide text-gray-500 uppercase"
                            >
                                Solution
                                <span
                                    class="font-normal text-gray-400 normal-case"
                                    >— how you solved it, or plan to</span
                                >
                            </label>
                            <textarea
                                v-model="logbookForm.solution"
                                rows="3"
                                placeholder="How you resolved it — or the steps you'll take to if it's still open…"
                                class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 transition-shadow placeholder:text-gray-400 focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div v-if="showHours" class="flex items-center gap-3">
                        <label
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-400 uppercase"
                        >
                            <Clock class="h-3.5 w-3.5" /> Hours spent
                        </label>
                        <input
                            v-model="logbookForm.hours_spent"
                            type="number"
                            min="0"
                            max="24"
                            step="0.5"
                            placeholder="0"
                            class="w-24 rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-800 transition-shadow focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        />
                    </div>
                    <p
                        v-else
                        class="flex items-center gap-1.5 text-xs text-gray-400"
                    >
                        <Clock class="h-3.5 w-3.5" /> Hours today are tracked
                        from your office clock-in / clock-out.
                    </p>

                    <div
                        class="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700"
                    >
                        <p v-if="!logbookLocked" class="text-xs text-gray-400">
                            "What did you do today?" and the difficulties
                            question are required.
                        </p>
                        <button
                            v-if="!logbookLocked"
                            :disabled="logbookForm.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#42b6c5] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35919e] disabled:cursor-not-allowed disabled:opacity-60 sm:ml-auto"
                            @click="saveLogbook"
                        >
                            {{
                                logbookForm.processing
                                    ? 'Saving…'
                                    : 'Submit logbook'
                            }}
                        </button>
                    </div>
                    <div
                        v-if="logbookLocked"
                        class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-900/20"
                    >
                        <CheckCircle2
                            class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                        />
                        <p
                            class="text-sm font-medium text-emerald-700 dark:text-emerald-300"
                        >
                            Reviewed by your supervisor — locked.
                        </p>
                    </div>
                </fieldset>
            </div>
        </div>

        <!-- History: primary scan-value, not just click targets -->
        <div
            class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                class="border-b border-gray-100 px-6 py-4 dark:border-gray-700"
            >
                <h2 class="font-bold text-[#000928] dark:text-white">
                    Past entries
                </h2>
                <p class="text-xs text-gray-400">
                    {{ entries.total }} recorded
                </p>
            </div>

            <div
                v-if="entries.data.length === 0"
                class="p-10 text-center text-sm text-gray-500 dark:text-gray-400"
            >
                No past entries yet — today's is your first.
            </div>

            <div
                v-else
                class="relative divide-y divide-gray-100 dark:divide-gray-700"
            >
                <div
                    v-for="entry in entries.data"
                    :key="entry.id"
                    class="relative pl-6"
                >
                    <!-- Timeline rail -->
                    <span
                        class="absolute top-0 left-[22px] h-full w-px bg-gray-100 dark:bg-gray-700"
                    ></span>
                    <span
                        :class="[
                            'absolute top-6 left-[18px] h-2 w-2 rounded-full ring-4 ring-white dark:ring-gray-800',
                            entry.status === 'approved'
                                ? 'bg-emerald-500'
                                : entry.status === 'needs_revision'
                                  ? 'bg-amber-500'
                                  : 'bg-gray-300 dark:bg-gray-500',
                        ]"
                    ></span>

                    <button
                        type="button"
                        class="flex w-full items-start justify-between gap-3 px-4 py-4 text-left"
                        @click="toggleExpanded(entry.id)"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="text-sm font-bold text-[#000928] dark:text-white"
                                    >{{ fmtDateShort(entry.date) }}</span
                                >
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                        statusClass(entry.status),
                                    ]"
                                    >{{ statusLabel(entry.status) }}</span
                                >
                                <span
                                    v-if="entry.hours_spent"
                                    class="inline-flex items-center gap-0.5 text-xs text-gray-400"
                                    ><Clock class="h-3 w-3" />{{
                                        entry.hours_spent
                                    }}h</span
                                >
                                <span
                                    v-if="entry.blockers"
                                    class="inline-flex items-center gap-0.5 text-xs text-amber-500"
                                    title="Had difficulties"
                                    ><AlertTriangle class="h-3 w-3"
                                /></span>
                            </div>
                            <p
                                v-if="!expanded.has(entry.id)"
                                class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ preview(entry.content) }}
                            </p>
                        </div>
                        <ChevronDown
                            :class="[
                                'mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition-transform',
                                expanded.has(entry.id) ? 'rotate-180' : '',
                            ]"
                        />
                    </button>

                    <div
                        v-show="expanded.has(entry.id)"
                        class="space-y-3 px-4 pb-5"
                    >
                        <div
                            class="prose prose-sm max-w-none text-gray-700 dark:text-gray-200 dark:prose-invert"
                            v-html="
                                lessonBodyHtml(entry.content, 'No entry text.')
                            "
                        />
                        <div
                            v-if="entry.blockers"
                            class="space-y-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/20 dark:text-amber-200"
                        >
                            <p class="flex gap-2">
                                <AlertTriangle
                                    class="mt-0.5 h-4 w-4 shrink-0"
                                />
                                <span
                                    ><span class="font-semibold"
                                        >Difficulty:</span
                                    >
                                    {{ entry.blockers }}</span
                                >
                            </p>
                            <p
                                v-if="entry.solution"
                                class="flex gap-2 border-t border-amber-200/70 pt-2 dark:border-amber-900/40"
                            >
                                <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0" />
                                <span
                                    ><span class="font-semibold"
                                        >Solution:</span
                                    >
                                    {{ entry.solution }}</span
                                >
                            </p>
                        </div>
                        <p
                            v-if="entry.supervisor_feedback"
                            class="flex gap-2 rounded-lg bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-900/40 dark:text-gray-200"
                        >
                            <Sparkles
                                class="mt-0.5 h-4 w-4 shrink-0 text-[#42b6c5]"
                            />
                            {{ entry.supervisor_feedback }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="entries.links.length > 3"
                class="flex flex-wrap items-center justify-center gap-2 border-t border-gray-100 p-4 dark:border-gray-700"
            >
                <template v-for="link in entries.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        :class="[
                            'flex h-9 min-w-[36px] items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors',
                            link.active
                                ? 'bg-[#381998] text-white'
                                : 'border border-gray-200 bg-white text-gray-600 hover:border-[#42b6c5] hover:text-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300',
                        ]"
                        ><span v-html="link.label"
                    /></Link>
                    <span
                        v-else
                        :class="[
                            'flex h-9 min-w-[36px] items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-300 dark:text-gray-600',
                        ]"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
