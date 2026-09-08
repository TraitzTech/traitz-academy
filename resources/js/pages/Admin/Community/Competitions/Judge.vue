<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    CommunityMember,
    SelectOption,
    TacCompetitionCriterion,
} from '@/types/community';

interface JudgeEntry {
    id: number;
    title: string;
    description: string | null;
    repo_url: string | null;
    demo_url: string | null;
    video_url: string | null;
    attachment_path: string | null;
    team_name: string | null;
    team_members: string[] | null;
    status: string;
    submitted_at: string | null;
    total_score: number | null;
    rank: number | null;
    is_winner: boolean;
    award: string | null;
    judge_notes: string | null;
    member: CommunityMember | null;
    my_scores: Record<number, { score: number; comment: string | null }>;
    judge_count: number;
}

interface Props {
    activity: {
        id: number;
        title: string;
        slug: string;
        type: string;
        status: string;
        starts_at: string | null;
        ends_at: string | null;
    };
    criteria: TacCompetitionCriterion[];
    entries: JudgeEntry[];
    filters: { status?: string };
    statuses: SelectOption[];
    resultsPublished: boolean;
    can: { publishResults: boolean };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { asset, initials, formatDate } = useCommunity();

const expanded = ref<number | null>(props.entries[0]?.id ?? null);

/** One local score sheet per entry, seeded from this judge's saved scores. */
const sheets = reactive<
    Record<number, { scores: Record<number, number>; comments: Record<number, string>; judge_notes: string }>
>(
    Object.fromEntries(
        props.entries.map((entry) => [
            entry.id,
            {
                scores: Object.fromEntries(
                    props.criteria.map((criterion) => [
                        criterion.id,
                        entry.my_scores[criterion.id]?.score ?? 0,
                    ]),
                ),
                comments: Object.fromEntries(
                    props.criteria.map((criterion) => [
                        criterion.id,
                        entry.my_scores[criterion.id]?.comment ?? '',
                    ]),
                ),
                judge_notes: entry.judge_notes ?? '',
            },
        ]),
    ),
);

const saving = ref<number | null>(null);

const saveScores = (entry: JudgeEntry) => {
    const sheet = sheets[entry.id];
    saving.value = entry.id;

    router.post(
        `/admin/community/activities/${props.activity.slug}/judge/${entry.id}/score`,
        {
            scores: props.criteria.map((criterion) => ({
                criterion_id: criterion.id,
                score: sheet.scores[criterion.id] ?? 0,
                comment: sheet.comments[criterion.id] || null,
            })),
            judge_notes: sheet.judge_notes || null,
        },
        {
            preserveScroll: true,
            onFinish: () => (saving.value = null),
        },
    );
};

const updateEntry = (entry: JudgeEntry, payload: Record<string, unknown>) =>
    router.post(
        `/admin/community/activities/${props.activity.slug}/judge/${entry.id}`,
        payload as never,
        { preserveScroll: true },
    );

/** Live preview of what this judge's sheet scores out of 100. */
const previewScore = (entryId: number): number => {
    const sheet = sheets[entryId];
    const weightTotal = props.criteria.reduce((sum, c) => sum + c.weight, 0);
    if (weightTotal === 0) return 0;

    const weighted = props.criteria.reduce((sum, criterion) => {
        const score = sheet.scores[criterion.id] ?? 0;
        return sum + (score / criterion.max_score) * 100 * criterion.weight;
    }, 0);

    return Math.round((weighted / weightTotal) * 10) / 10;
};

const scored = computed(
    () => props.entries.filter((entry) => entry.total_score !== null).length,
);

/* -------------------------------------------------------- Publish results */

const showPublish = ref(false);

const publishForm = useForm({ outcome_summary: '', notify: true });

const publish = () =>
    publishForm.post(
        `/admin/community/activities/${props.activity.slug}/judge/publish`,
        {
            preserveScroll: true,
            onSuccess: () => (showPublish.value = false),
        },
    );
</script>

<template>
    <div class="lms-page">
        <Head :title="`Judging — ${activity.title}`" />

        <nav class="text-sm" aria-label="Breadcrumb">
            <Link
                :href="`/admin/community/activities/${activity.slug}`"
                class="font-semibold text-gray-500 hover:text-[#000928] dark:hover:text-white"
            >
                ← Back to {{ activity.title }}
            </Link>
        </nav>

        <div class="lms-hero">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight">
                        Judging room
                    </h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        {{ activity.title }} · {{ entries.length }}
                        {{ entries.length === 1 ? 'entry' : 'entries' }} ·
                        {{ scored }} scored
                    </p>
                </div>

                <button
                    v-if="can.publishResults && !resultsPublished"
                    type="button"
                    class="rounded-xl bg-amber-400 px-5 py-2.5 text-sm font-bold text-[#000928] transition-colors hover:bg-amber-300"
                    @click="showPublish = true"
                >
                    Publish results
                </button>
                <span
                    v-else-if="resultsPublished"
                    class="rounded-xl border border-white/25 bg-white/10 px-5 py-2.5 text-sm font-bold backdrop-blur"
                >
                    Results published
                </span>
            </div>
        </div>

        <!-- Rubric summary -->
        <section v-if="criteria.length" class="lms-panel">
            <h2 class="lms-title text-lg">Rubric</h2>
            <p class="lms-subtitle">
                Scores are normalised to 100, weighted per criterion, then
                averaged across every judge who scores an entry.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <span
                    v-for="criterion in criteria"
                    :key="criterion.id"
                    class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-300"
                >
                    {{ criterion.label }} · {{ criterion.max_score }} pts
                    <template v-if="criterion.weight > 1"
                        >· ×{{ criterion.weight }}</template
                    >
                </span>
            </div>
        </section>

        <div
            v-else
            class="rounded-2xl border border-amber-300/50 bg-amber-50 p-5 dark:bg-amber-500/10"
        >
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                No judging criteria set for this competition yet.
            </p>
            <Link
                :href="`/admin/community/activities/${activity.slug}/edit`"
                class="mt-2 inline-block text-sm font-bold text-amber-700 hover:underline dark:text-amber-300"
            >
                Add criteria →
            </Link>
        </div>

        <EmptyState
            v-if="!entries.length"
            icon="trophy"
            title="No entries yet"
            description="Entries appear here as members submit them from the public competition page."
        />

        <!-- Entries -->
        <section
            v-for="entry in entries"
            :key="entry.id"
            class="lms-panel"
        >
            <!-- Entry header -->
            <button
                type="button"
                class="flex w-full flex-wrap items-start justify-between gap-4 text-left"
                :aria-expanded="expanded === entry.id"
                @click="expanded = expanded === entry.id ? null : entry.id"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <span
                        :class="[
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-black',
                            entry.is_winner
                                ? 'bg-amber-400 text-[#000928]'
                                : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                        ]"
                    >
                        {{ entry.rank ?? '—' }}
                    </span>
                    <div class="min-w-0">
                        <p
                            class="truncate font-bold text-[#000928] dark:text-white"
                        >
                            {{ entry.title }}
                            <span
                                v-if="entry.award"
                                class="ml-2 rounded-full bg-amber-400/25 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:text-amber-300"
                                >{{ entry.award }}</span
                            >
                        </p>
                        <p class="truncate text-xs text-gray-500">
                            {{
                                entry.team_name ||
                                `${entry.member?.first_name} ${entry.member?.last_name ?? ''}`
                            }}
                            <template v-if="entry.member?.school"
                                >· {{ entry.member.school }}</template
                            >
                            · submitted {{ formatDate(entry.submitted_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-4">
                    <div class="text-right">
                        <p
                            class="text-lg font-black text-[#000928] dark:text-white"
                        >
                            {{
                                entry.total_score !== null
                                    ? Number(entry.total_score).toFixed(1)
                                    : '—'
                            }}
                        </p>
                        <p class="text-[10px] text-gray-500">
                            {{ entry.judge_count }}
                            {{ entry.judge_count === 1 ? 'judge' : 'judges' }}
                        </p>
                    </div>
                    <span class="text-gray-400" aria-hidden="true">
                        {{ expanded === entry.id ? '▲' : '▼' }}
                    </span>
                </div>
            </button>

            <!-- Entry detail -->
            <div
                v-if="expanded === entry.id"
                class="mt-6 border-t border-gray-100 pt-6 dark:border-white/10"
            >
                <div class="grid gap-6 lg:grid-cols-[1fr_1.1fr]">
                    <!-- The submission -->
                    <div>
                        <h3
                            class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                        >
                            The submission
                        </h3>

                        <p
                            v-if="entry.description"
                            class="mt-3 leading-relaxed whitespace-pre-line text-sm text-gray-700 dark:text-gray-300"
                        >
                            {{ entry.description }}
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a
                                v-for="link in [
                                    { label: 'Repository', url: entry.repo_url },
                                    { label: 'Live demo', url: entry.demo_url },
                                    { label: 'Video', url: entry.video_url },
                                    {
                                        label: 'Attachment',
                                        url: asset(entry.attachment_path),
                                    },
                                ].filter((l) => l.url)"
                                :key="link.label"
                                :href="link.url as string"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-[#381998] transition-colors hover:bg-gray-50 dark:border-white/10 dark:text-[#42b6c5] dark:hover:bg-white/5"
                            >
                                {{ link.label }} ↗
                            </a>
                        </div>

                        <div
                            v-if="entry.member"
                            class="mt-6 flex items-center gap-3 rounded-xl border border-gray-100 p-3 dark:border-white/10"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#381998]/10 text-xs font-black text-[#381998] dark:text-[#b9a5f5]"
                                aria-hidden="true"
                            >
                                {{
                                    initials(
                                        `${entry.member.first_name} ${entry.member.last_name ?? ''}`,
                                    )
                                }}
                            </span>
                            <div class="min-w-0">
                                <Link
                                    :href="`/admin/community/members/${entry.member.id}`"
                                    class="block truncate text-sm font-semibold text-[#000928] hover:underline dark:text-white"
                                >
                                    {{ entry.member.first_name }}
                                    {{ entry.member.last_name }}
                                </Link>
                                <p class="truncate text-xs text-gray-500">
                                    {{ entry.member.email }}
                                </p>
                            </div>
                        </div>

                        <!-- Outcome controls -->
                        <div
                            class="mt-6 space-y-3 rounded-xl border border-gray-100 p-4 dark:border-white/10"
                        >
                            <h4
                                class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                            >
                                Outcome
                            </h4>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label
                                        :for="`entry_status_${entry.id}`"
                                        class="lms-label"
                                        >Status</label
                                    >
                                    <select
                                        :id="`entry_status_${entry.id}`"
                                        :value="entry.status"
                                        class="lms-input mt-1.5"
                                        @change="
                                            updateEntry(entry, {
                                                status: (
                                                    $event.target as HTMLSelectElement
                                                ).value,
                                            })
                                        "
                                    >
                                        <option
                                            v-for="option in statuses"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        :for="`entry_award_${entry.id}`"
                                        class="lms-label"
                                        >Award</label
                                    >
                                    <input
                                        :id="`entry_award_${entry.id}`"
                                        :value="entry.award ?? ''"
                                        type="text"
                                        placeholder="e.g. Best design"
                                        class="lms-input mt-1.5"
                                        @change="
                                            updateEntry(entry, {
                                                award: (
                                                    $event.target as HTMLInputElement
                                                ).value,
                                                is_winner: entry.is_winner,
                                            })
                                        "
                                    />
                                </div>
                            </div>

                            <label class="flex items-center gap-2.5 text-sm">
                                <input
                                    type="checkbox"
                                    :checked="entry.is_winner"
                                    class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    @change="
                                        updateEntry(entry, {
                                            is_winner: (
                                                $event.target as HTMLInputElement
                                            ).checked,
                                            award: entry.award,
                                        })
                                    "
                                />
                                <span class="text-gray-600 dark:text-gray-400"
                                    >Mark as a winner</span
                                >
                            </label>
                        </div>
                    </div>

                    <!-- Score sheet -->
                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <h3
                                class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                            >
                                Your score sheet
                            </h3>
                            <p class="text-sm">
                                <span class="text-gray-500">Your score:</span>
                                <span
                                    class="ml-1.5 text-lg font-black text-[#000928] dark:text-white"
                                    >{{ previewScore(entry.id) }}</span
                                >
                                <span class="text-gray-400">/100</span>
                            </p>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div
                                v-for="criterion in criteria"
                                :key="criterion.id"
                            >
                                <div class="flex items-baseline justify-between">
                                    <label
                                        :for="`score_${entry.id}_${criterion.id}`"
                                        class="lms-label"
                                    >
                                        {{ criterion.label }}
                                        <span
                                            v-if="criterion.weight > 1"
                                            class="text-xs font-normal text-gray-400"
                                            >(×{{ criterion.weight }})</span
                                        >
                                    </label>
                                    <span
                                        class="text-sm font-bold text-[#000928] dark:text-white"
                                    >
                                        {{ sheets[entry.id].scores[criterion.id] }}
                                        <span class="text-gray-400"
                                            >/ {{ criterion.max_score }}</span
                                        >
                                    </span>
                                </div>

                                <p
                                    v-if="criterion.description"
                                    class="mt-0.5 text-xs text-gray-500"
                                >
                                    {{ criterion.description }}
                                </p>

                                <input
                                    :id="`score_${entry.id}_${criterion.id}`"
                                    v-model.number="
                                        sheets[entry.id].scores[criterion.id]
                                    "
                                    type="range"
                                    min="0"
                                    :max="criterion.max_score"
                                    step="0.5"
                                    class="mt-2 w-full accent-[#42b6c5]"
                                />

                                <input
                                    v-model="
                                        sheets[entry.id].comments[criterion.id]
                                    "
                                    type="text"
                                    placeholder="Comment (optional)"
                                    class="lms-input mt-2 text-xs"
                                    :aria-label="`Comment on ${criterion.label}`"
                                />
                            </div>
                        </div>

                        <div class="mt-5">
                            <label
                                :for="`judge_notes_${entry.id}`"
                                class="lms-label"
                                >Feedback for the entrant</label
                            >
                            <textarea
                                :id="`judge_notes_${entry.id}`"
                                v-model="sheets[entry.id].judge_notes"
                                rows="3"
                                maxlength="2000"
                                placeholder="Shared with the entrant when results are published."
                                class="lms-input mt-1.5 resize-y"
                            />
                        </div>

                        <button
                            type="button"
                            class="lms-btn-primary mt-4"
                            :disabled="saving === entry.id || !criteria.length"
                            @click="saveScores(entry)"
                        >
                            {{
                                saving === entry.id
                                    ? 'Saving…'
                                    : 'Save my scores'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Publish modal -->
        <ConfirmationModal
            :open="showPublish"
            title="Publish the results?"
            description="This freezes the leaderboard, marks the competition completed, and makes rankings public. Entries can no longer be edited."
            confirm-text="Publish results"
            variant="default"
            :processing="publishForm.processing"
            @update:open="showPublish = $event"
            @confirm="publish"
        >
            <template #body>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="outcome_summary" class="lms-label"
                            >Write-up for the archive</label
                        >
                        <textarea
                            id="outcome_summary"
                            v-model="publishForm.outcome_summary"
                            rows="4"
                            placeholder="How the competition went, who stood out, what happens next."
                            class="lms-input mt-1.5 resize-y"
                        />
                    </div>
                    <label class="flex items-start gap-2.5 text-sm">
                        <input
                            v-model="publishForm.notify"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                        />
                        <span class="text-gray-600 dark:text-gray-400">
                            Email every entrant their result, rank and judges'
                            feedback
                        </span>
                    </label>
                </div>
            </template>
        </ConfirmationModal>
    </div>
</template>
