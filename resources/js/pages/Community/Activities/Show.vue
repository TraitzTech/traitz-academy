<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ActivityCard from '@/components/community/ActivityCard.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';
import type {
    TacActivity,
    TacActivityRsvp,
    TacCompetitionEntry,
} from '@/types/community';

interface LeaderboardRow {
    id: number;
    rank: number | null;
    title: string;
    team_name: string | null;
    repo_url: string | null;
    demo_url: string | null;
    total_score: number | null;
    is_winner: boolean;
    award: string | null;
    member: { name: string | null; school: string | null; avatar_path: string | null };
}

interface Props {
    activity: TacActivity;
    registration: { open: boolean; reason: string | null };
    seatsLeft: number | null;
    myRsvp: TacActivityRsvp | null;
    myEntry: TacCompetitionEntry | null;
    isMember: boolean;
    leaderboard: LeaderboardRow[] | null;
    related: TacActivity[];
}

const props = defineProps<Props>();
const page = usePage();

const {
    activityType,
    asset,
    dateRange,
    relative,
    money,
    locationLabel,
    initials,
} = useCommunity();

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const meta = computed(() => activityType(props.activity.type));
const cover = computed(() => asset(props.activity.cover_image));

const isPast = computed(() => {
    const end = props.activity.ends_at ?? props.activity.starts_at;
    return end ? new Date(end).getTime() < Date.now() : false;
});

const isFull = computed(
    () =>
        props.activity.capacity !== null &&
        props.activity.rsvp_count >= props.activity.capacity,
);

const activeRsvp = computed(
    () => props.myRsvp && props.myRsvp.status !== 'cancelled',
);

/* ------------------------------------------------------------------ RSVP */

const rsvpForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    school: '',
    note: '',
});

const submitRsvp = () =>
    rsvpForm.post(`/community/activities/${props.activity.slug}/rsvp`, {
        preserveScroll: true,
        onSuccess: () => rsvpForm.reset(),
    });

const cancelForm = useForm({});
const cancelRsvp = () =>
    cancelForm.delete(`/community/activities/${props.activity.slug}/rsvp`, {
        preserveScroll: true,
    });

/* ------------------------------------------------- Competition entry */

const showEntryForm = ref(false);

const entryForm = useForm({
    title: props.myEntry?.title ?? '',
    description: props.myEntry?.description ?? '',
    repo_url: props.myEntry?.repo_url ?? '',
    demo_url: props.myEntry?.demo_url ?? '',
    video_url: props.myEntry?.video_url ?? '',
    team_name: props.myEntry?.team_name ?? '',
    attachment: null as File | null,
});

const submitEntry = () => {
    const url = props.myEntry
        ? `/community/activities/${props.activity.slug}/entries/${props.myEntry.id}`
        : `/community/activities/${props.activity.slug}/entries`;

    entryForm.post(url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (showEntryForm.value = false),
    });
};

const entryLocked = computed(
    () =>
        Boolean(props.myEntry?.total_score !== null && props.myEntry) ||
        Boolean(props.myEntry?.results_published_at),
);

/* ------------------------------------------------------------- Messages */

const closedReason = computed(() => {
    switch (props.registration.reason) {
        case 'closed':
            return 'Registration for this activity has closed.';
        case 'not_yet_open':
            return 'Registration has not opened yet — check back soon.';
        case 'past':
            return 'This activity has already taken place.';
        case 'cancelled':
            return 'This activity has been cancelled.';
        case 'no_registration':
            return 'No registration needed — just turn up.';
        case 'unpublished':
            return 'This activity is not open for registration.';
        default:
            return null;
    }
});
</script>

<template>
    <CommunityShell active="activities">
        <Head :title="`${activity.title} — TAC`">
            <meta
                name="description"
                :content="activity.summary ?? `A TAC ${meta.label.toLowerCase()} at Traitz Academy.`"
            />
        </Head>

        <!-- ============================================== Hero -->
        <section class="relative overflow-hidden bg-[#000928]">
            <div class="absolute inset-0" aria-hidden="true">
                <img
                    v-if="cover"
                    :src="cover"
                    :alt="''"
                    class="h-full w-full object-cover opacity-30"
                />
                <div
                    v-else
                    class="h-full w-full bg-gradient-to-r from-[#000928] to-[#381998]"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-[#000928] via-[#000928]/80 to-[#000928]/40"
                />
            </div>

            <div
                class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20"
            >
                <nav class="mb-6 text-sm" aria-label="Breadcrumb">
                    <Link
                        href="/community/activities"
                        class="font-semibold text-white/60 transition-colors hover:text-white"
                    >
                        ← All activities
                    </Link>
                </nav>

                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full bg-white/90 px-3 py-1 text-[11px] font-black tracking-wide text-[#000928] uppercase"
                    >
                        {{ meta.label }}
                    </span>
                    <Link
                        v-if="activity.track"
                        :href="`/community/tracks/${activity.track.slug}`"
                        class="rounded-full border border-white/25 bg-white/10 px-3 py-1 text-[11px] font-bold text-white backdrop-blur transition-colors hover:bg-white/20"
                    >
                        {{ activity.track.name }}
                    </Link>
                    <span
                        v-if="activity.status === 'cancelled'"
                        class="rounded-full bg-red-500 px-3 py-1 text-[11px] font-black tracking-wide text-white uppercase"
                    >
                        Cancelled
                    </span>
                    <span
                        v-if="activity.is_recurring"
                        class="rounded-full border border-white/25 bg-white/10 px-3 py-1 text-[11px] font-bold text-white backdrop-blur"
                    >
                        Recurring series
                    </span>
                </div>

                <h1
                    class="mt-5 max-w-4xl text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl"
                >
                    {{ activity.title }}
                </h1>

                <p
                    v-if="activity.summary"
                    class="mt-5 max-w-3xl text-lg leading-relaxed text-white/70"
                >
                    {{ activity.summary }}
                </p>

                <div
                    class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm text-white/80"
                >
                    <span class="flex items-center gap-2">
                        <span class="text-[#42b6c5]" aria-hidden="true">📅</span>
                        {{ dateRange(activity) }}
                        <span
                            v-if="!isPast && activity.starts_at"
                            class="text-white/50"
                            >· {{ relative(activity.starts_at) }}</span
                        >
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="text-[#42b6c5]" aria-hidden="true">📍</span>
                        {{ locationLabel(activity) }}
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="text-[#42b6c5]" aria-hidden="true">👥</span>
                        {{ activity.rsvp_count }} registered<template
                            v-if="activity.capacity"
                        >
                            / {{ activity.capacity }}</template
                        >
                    </span>
                    <span
                        v-if="activity.is_paid"
                        class="rounded-lg bg-[#42b6c5] px-3 py-1 font-bold text-white"
                    >
                        {{ money(activity.price, activity.currency) }}
                    </span>
                    <span
                        v-else
                        class="rounded-lg bg-emerald-500 px-3 py-1 font-bold text-white"
                    >
                        Free
                    </span>
                </div>
            </div>
        </section>

        <!-- ============================================== Body -->
        <div
            class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:grid lg:grid-cols-[1fr_380px] lg:gap-12 lg:px-8"
        >
            <!-- ---------------------------------------- Main -->
            <main class="min-w-0">
                <article
                    v-if="activity.description"
                    class="prose prose-slate max-w-none"
                    v-html="activity.description"
                />
                <p v-else class="text-gray-600">
                    Full details for this {{ meta.label.toLowerCase() }} are
                    coming shortly.
                </p>

                <!-- Organiser -->
                <section
                    v-if="activity.organizer"
                    class="mt-10 rounded-2xl border border-gray-200 bg-gray-50/70 p-6"
                >
                    <h2
                        class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Organised by
                    </h2>
                    <div class="mt-4 flex items-start gap-4">
                        <span
                            class="h-14 w-14 shrink-0 overflow-hidden rounded-xl"
                        >
                            <img
                                v-if="asset(activity.organizer.photo_path)"
                                :src="asset(activity.organizer.photo_path)!"
                                :alt="activity.organizer.name"
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#000928] to-[#381998] font-black text-white"
                                aria-hidden="true"
                            >
                                {{ initials(activity.organizer.name) }}
                            </span>
                        </span>
                        <div>
                            <p
                                class="font-bold text-[#000928]"
                            >
                                {{ activity.organizer.name }}
                            </p>
                            <p
                                class="text-xs font-bold tracking-wide text-[#42b6c5] uppercase"
                            >
                                {{
                                    activity.organizer.role_title ??
                                    activity.organizer.role_type.replace('_', ' ')
                                }}
                            </p>
                            <p
                                v-if="activity.organizer.bio"
                                class="mt-2 text-sm text-gray-600"
                            >
                                {{ activity.organizer.bio }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Linked internship program -->
                <section
                    v-if="activity.program"
                    class="mt-6 rounded-2xl border border-[#42b6c5]/30 bg-[#42b6c5]/8 p-6"
                >
                    <h2 class="font-bold text-[#000928]">
                        Part of the Traitz Academy internship program
                    </h2>
                    <p class="mt-1.5 text-sm text-gray-600">
                        This activity connects to
                        <strong>{{ activity.program.title }}</strong
                        >.
                    </p>
                    <Link
                        v-if="activity.program.slug"
                        :href="`/programs/${activity.program.slug}`"
                        class="mt-3 inline-block text-sm font-bold text-[#381998] hover:underline"
                    >
                        View the program →
                    </Link>
                </section>

                <!-- Recurring occurrences -->
                <section
                    v-if="activity.occurrences?.length"
                    class="mt-10"
                >
                    <h2
                        class="text-lg font-black text-[#000928]"
                    >
                        Other dates in this series
                    </h2>
                    <ul class="mt-4 space-y-2">
                        <li
                            v-for="occurrence in activity.occurrences"
                            :key="occurrence.id"
                        >
                            <Link
                                :href="`/community/activities/${occurrence.slug}`"
                                class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 px-4 py-3 text-sm transition-colors hover:border-[#42b6c5]/40 hover:bg-gray-50"
                            >
                                <span
                                    class="font-semibold text-[#000928]"
                                    >{{ occurrence.title }}</span
                                >
                                <span class="text-gray-500">{{
                                    dateRange(occurrence)
                                }}</span>
                            </Link>
                        </li>
                    </ul>
                </section>

                <!-- ------------------------------ Competition -->
                <section
                    v-if="activity.type === 'competition'"
                    class="mt-12"
                >
                    <h2
                        class="text-2xl font-black tracking-tight text-[#000928]"
                    >
                        Competition
                    </h2>

                    <!-- Judging criteria -->
                    <div
                        v-if="activity.competition_criteria?.length"
                        class="mt-5 overflow-hidden rounded-2xl border border-gray-200"
                    >
                        <h3
                            class="border-b border-gray-200 bg-gray-50 px-5 py-3 text-sm font-bold text-[#000928]"
                        >
                            How entries are judged
                        </h3>
                        <ul class="divide-y divide-gray-100">
                            <li
                                v-for="criterion in activity.competition_criteria"
                                :key="criterion.id"
                                class="flex items-start justify-between gap-4 px-5 py-3.5"
                            >
                                <div>
                                    <p
                                        class="text-sm font-semibold text-[#000928]"
                                    >
                                        {{ criterion.label }}
                                    </p>
                                    <p
                                        v-if="criterion.description"
                                        class="mt-0.5 text-xs text-gray-500"
                                    >
                                        {{ criterion.description }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-[#42b6c5]/12 px-2.5 py-1 text-xs font-bold text-[#26808c]"
                                >
                                    {{ criterion.max_score }} pts
                                    <template v-if="criterion.weight > 1"
                                        >· ×{{ criterion.weight }}</template
                                    >
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- Leaderboard -->
                    <div v-if="leaderboard?.length" class="mt-8">
                        <h3
                            class="text-lg font-black text-[#000928]"
                        >
                            Results
                        </h3>
                        <ol class="mt-4 space-y-2.5">
                            <li
                                v-for="row in leaderboard"
                                :key="row.id"
                                :class="[
                                    'flex items-center gap-4 rounded-2xl border p-4',
                                    row.is_winner
                                        ? 'border-amber-400/50 bg-amber-50'
                                        : 'border-gray-200 bg-white',
                                ]"
                            >
                                <span
                                    :class="[
                                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-black',
                                        row.is_winner
                                            ? 'bg-amber-400 text-[#000928]'
                                            : 'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{ row.rank ?? '—' }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate font-bold text-[#000928]"
                                    >
                                        {{ row.title }}
                                        <span
                                            v-if="row.award"
                                            class="ml-2 rounded-full bg-amber-400/20 px-2 py-0.5 text-[11px] font-bold text-amber-700"
                                        >
                                            {{ row.award }}
                                        </span>
                                    </p>
                                    <p
                                        class="truncate text-sm text-gray-500"
                                    >
                                        {{ row.team_name || row.member.name }}
                                        <template v-if="row.member.school"
                                            >· {{ row.member.school }}</template
                                        >
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <a
                                        v-if="row.demo_url"
                                        :href="row.demo_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-xs font-bold text-[#381998] hover:underline"
                                        >Demo</a
                                    >
                                    <a
                                        v-if="row.repo_url"
                                        :href="row.repo_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-xs font-bold text-[#381998] hover:underline"
                                        >Code</a
                                    >
                                    <span
                                        v-if="row.total_score !== null"
                                        class="text-sm font-black text-[#000928]"
                                    >
                                        {{ Number(row.total_score).toFixed(1) }}
                                    </span>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <!-- Entry form -->
                    <div class="mt-8">
                        <div
                            v-if="!isAuthenticated"
                            class="rounded-2xl border border-gray-200 bg-gray-50/70 p-6 text-center"
                        >
                            <p
                                class="text-sm text-gray-600"
                            >
                                Sign in with your account to submit an entry —
                                that way you can edit or withdraw it later.
                            </p>
                            <Link
                                href="/login"
                                class="lms-btn-primary mt-4 inline-flex"
                                >Sign in to enter</Link
                            >
                        </div>

                        <div
                            v-else-if="myEntry && !showEntryForm"
                            class="rounded-2xl border border-[#42b6c5]/30 bg-[#42b6c5]/8 p-6"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-4"
                            >
                                <div>
                                    <h3
                                        class="font-bold text-[#000928]"
                                    >
                                        Your entry: {{ myEntry.title }}
                                    </h3>
                                    <p
                                        class="mt-1 text-sm text-gray-600"
                                    >
                                        Status:
                                        <strong class="capitalize">{{
                                            myEntry.status.replace('_', ' ')
                                        }}</strong>
                                        <template
                                            v-if="myEntry.total_score !== null"
                                        >
                                            · Score
                                            {{
                                                Number(myEntry.total_score).toFixed(1)
                                            }}
                                        </template>
                                    </p>
                                </div>
                                <button
                                    v-if="!entryLocked"
                                    type="button"
                                    class="lms-btn-outline"
                                    @click="showEntryForm = true"
                                >
                                    Edit entry
                                </button>
                            </div>
                            <p
                                v-if="entryLocked"
                                class="mt-3 text-sm text-gray-500"
                            >
                                Judging has begun — your entry is now locked.
                            </p>
                            <p
                                v-if="myEntry.judge_notes"
                                class="mt-4 rounded-xl bg-white p-4 text-sm text-gray-700"
                            >
                                <strong class="block">Judges' feedback</strong>
                                {{ myEntry.judge_notes }}
                            </p>
                        </div>

                        <form
                            v-else-if="!isPast"
                            class="rounded-2xl border border-gray-200 bg-white p-6"
                            @submit.prevent="submitEntry"
                        >
                            <h3
                                class="font-bold text-[#000928]"
                            >
                                {{ myEntry ? 'Update your entry' : 'Submit your entry' }}
                            </h3>

                            <fieldset
                                :disabled="entryForm.processing"
                                class="mt-5 space-y-4"
                            >
                                <div>
                                    <label for="entry_title" class="lms-label"
                                        >Project title
                                        <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        id="entry_title"
                                        v-model="entryForm.title"
                                        type="text"
                                        required
                                        class="lms-input mt-1.5"
                                    />
                                    <InputError :message="entryForm.errors.title" />
                                </div>

                                <div>
                                    <label
                                        for="entry_description"
                                        class="lms-label"
                                        >What did you build?
                                        <span class="text-red-500">*</span></label
                                    >
                                    <textarea
                                        id="entry_description"
                                        v-model="entryForm.description"
                                        rows="4"
                                        required
                                        class="lms-input mt-1.5 resize-y"
                                    />
                                    <InputError
                                        :message="entryForm.errors.description"
                                    />
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="entry_repo" class="lms-label"
                                            >Repository URL</label
                                        >
                                        <input
                                            id="entry_repo"
                                            v-model="entryForm.repo_url"
                                            type="url"
                                            placeholder="https://github.com/…"
                                            class="lms-input mt-1.5"
                                        />
                                        <InputError
                                            :message="entryForm.errors.repo_url"
                                        />
                                    </div>
                                    <div>
                                        <label for="entry_demo" class="lms-label"
                                            >Live demo URL</label
                                        >
                                        <input
                                            id="entry_demo"
                                            v-model="entryForm.demo_url"
                                            type="url"
                                            class="lms-input mt-1.5"
                                        />
                                        <InputError
                                            :message="entryForm.errors.demo_url"
                                        />
                                    </div>
                                    <div>
                                        <label for="entry_video" class="lms-label"
                                            >Video walkthrough</label
                                        >
                                        <input
                                            id="entry_video"
                                            v-model="entryForm.video_url"
                                            type="url"
                                            class="lms-input mt-1.5"
                                        />
                                    </div>
                                    <div>
                                        <label for="entry_team" class="lms-label"
                                            >Team name (if any)</label
                                        >
                                        <input
                                            id="entry_team"
                                            v-model="entryForm.team_name"
                                            type="text"
                                            class="lms-input mt-1.5"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label for="entry_file" class="lms-label"
                                        >Attachment (slides, PDF, zip — max
                                        20MB)</label
                                    >
                                    <input
                                        id="entry_file"
                                        type="file"
                                        class="lms-input mt-1.5"
                                        @input="
                                            entryForm.attachment = (
                                                $event.target as HTMLInputElement
                                            ).files?.[0] ?? null
                                        "
                                    />
                                    <InputError
                                        :message="entryForm.errors.attachment"
                                    />
                                </div>

                                <div class="flex gap-3">
                                    <button
                                        type="submit"
                                        class="lms-btn-primary"
                                        :disabled="entryForm.processing"
                                    >
                                        {{
                                            entryForm.processing
                                                ? 'Submitting…'
                                                : myEntry
                                                  ? 'Update entry'
                                                  : 'Submit entry'
                                        }}
                                    </button>
                                    <button
                                        v-if="myEntry"
                                        type="button"
                                        class="lms-btn-outline"
                                        @click="showEntryForm = false"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </section>

                <!-- ------------------------------ Archive -->
                <section
                    v-if="activity.outcome_summary || activity.media?.length"
                    class="mt-12 rounded-2xl border border-gray-200 bg-gray-50/70 p-6 sm:p-8"
                >
                    <h2
                        class="text-xl font-black text-[#000928]"
                    >
                        How it went
                    </h2>
                    <p
                        v-if="activity.outcome_summary"
                        class="mt-3 leading-relaxed whitespace-pre-line text-gray-700"
                    >
                        {{ activity.outcome_summary }}
                    </p>

                    <div
                        v-if="activity.media?.length"
                        class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3"
                    >
                        <figure
                            v-for="item in activity.media"
                            :key="item.id"
                            class="overflow-hidden rounded-xl"
                        >
                            <img
                                :src="asset(item.path)!"
                                :alt="item.caption ?? activity.title"
                                loading="lazy"
                                class="aspect-[4/3] w-full object-cover"
                            />
                            <figcaption
                                v-if="item.caption"
                                class="mt-1.5 text-xs text-gray-500"
                            >
                                {{ item.caption }}
                            </figcaption>
                        </figure>
                    </div>
                </section>
            </main>

            <!-- ---------------------------------------- Sidebar -->
            <aside class="mt-10 lg:mt-0">
                <div class="lg:sticky lg:top-28">
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <!-- Already registered -->
                        <template v-if="activeRsvp">
                            <div
                                class="rounded-xl bg-emerald-50 p-4 text-center"
                            >
                                <p
                                    class="text-sm font-black text-emerald-700"
                                >
                                    {{
                                        myRsvp!.status === 'waitlisted'
                                            ? 'You’re on the waitlist'
                                            : 'You’re registered'
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-emerald-600"
                                >
                                    {{
                                        myRsvp!.status === 'waitlisted'
                                            ? 'We’ll email you the moment a place opens.'
                                            : 'Confirmation is in your inbox.'
                                    }}
                                </p>
                            </div>

                            <Link
                                v-if="
                                    activity.is_paid &&
                                    myRsvp!.payment_status === 'pending'
                                "
                                :href="`/community/activities/${activity.slug}/checkout`"
                                class="lms-btn-accent mt-4 w-full"
                            >
                                Complete payment ·
                                {{ money(activity.price, activity.currency) }}
                            </Link>

                            <a
                                v-if="
                                    activity.meeting_url &&
                                    activity.location_type !== 'physical'
                                "
                                :href="activity.meeting_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="lms-btn-primary mt-3 w-full"
                            >
                                Join online
                            </a>

                            <button
                                v-if="!isPast && isAuthenticated"
                                type="button"
                                class="mt-3 w-full rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-500 transition-colors hover:border-red-300 hover:text-red-600"
                                :disabled="cancelForm.processing"
                                @click="cancelRsvp"
                            >
                                Cancel my RSVP
                            </button>
                        </template>

                        <!-- Closed -->
                        <template v-else-if="closedReason">
                            <p
                                class="rounded-xl bg-gray-50 p-4 text-center text-sm font-semibold text-gray-600"
                            >
                                {{ closedReason }}
                            </p>
                            <Link
                                href="/community/activities"
                                class="lms-btn-outline mt-4 w-full"
                            >
                                See what's coming up
                            </Link>
                        </template>

                        <!-- RSVP form -->
                        <form v-else @submit.prevent="submitRsvp">
                            <h2
                                class="text-lg font-black text-[#000928]"
                            >
                                {{ isFull ? 'Join the waitlist' : 'Reserve your place' }}
                            </h2>
                            <p
                                class="mt-1.5 text-sm text-gray-600"
                            >
                                <template v-if="isFull">
                                    This activity is full — add your name and
                                    we'll email you if a place opens.
                                </template>
                                <template v-else-if="seatsLeft !== null">
                                    {{ seatsLeft }}
                                    {{ seatsLeft === 1 ? 'place' : 'places' }}
                                    left.
                                </template>
                                <template v-else>
                                    Free to attend and open to everyone.
                                </template>
                            </p>

                            <fieldset
                                :disabled="rsvpForm.processing"
                                class="mt-5 space-y-3"
                            >
                                <template v-if="!isAuthenticated">
                                    <div>
                                        <label
                                            for="rsvp_first_name"
                                            class="lms-label"
                                            >First name
                                            <span class="text-red-500"
                                                >*</span
                                            ></label
                                        >
                                        <input
                                            id="rsvp_first_name"
                                            v-model="rsvpForm.first_name"
                                            type="text"
                                            required
                                            autocomplete="given-name"
                                            class="lms-input mt-1.5"
                                        />
                                        <InputError
                                            :message="rsvpForm.errors.first_name"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            for="rsvp_last_name"
                                            class="lms-label"
                                            >Last name</label
                                        >
                                        <input
                                            id="rsvp_last_name"
                                            v-model="rsvpForm.last_name"
                                            type="text"
                                            autocomplete="family-name"
                                            class="lms-input mt-1.5"
                                        />
                                    </div>
                                    <div>
                                        <label for="rsvp_email" class="lms-label"
                                            >Email
                                            <span class="text-red-500"
                                                >*</span
                                            ></label
                                        >
                                        <input
                                            id="rsvp_email"
                                            v-model="rsvpForm.email"
                                            type="email"
                                            required
                                            autocomplete="email"
                                            class="lms-input mt-1.5"
                                        />
                                        <InputError
                                            :message="rsvpForm.errors.email"
                                        />
                                    </div>
                                    <div>
                                        <label for="rsvp_phone" class="lms-label"
                                            >Phone</label
                                        >
                                        <input
                                            id="rsvp_phone"
                                            v-model="rsvpForm.phone"
                                            type="tel"
                                            autocomplete="tel"
                                            class="lms-input mt-1.5"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            for="rsvp_school"
                                            class="lms-label"
                                            >School / institution</label
                                        >
                                        <input
                                            id="rsvp_school"
                                            v-model="rsvpForm.school"
                                            type="text"
                                            class="lms-input mt-1.5"
                                        />
                                    </div>
                                </template>

                                <button
                                    type="submit"
                                    class="w-full rounded-xl bg-[#42b6c5] px-6 py-3 text-base font-bold text-white transition-all hover:bg-[#35919e] disabled:opacity-60"
                                >
                                    {{
                                        rsvpForm.processing
                                            ? 'Reserving…'
                                            : isFull
                                              ? 'Join the waitlist'
                                              : activity.is_paid
                                                ? `Reserve · ${money(activity.price, activity.currency)}`
                                                : 'Reserve my place'
                                    }}
                                </button>

                                <p
                                    v-if="!isMember"
                                    class="text-center text-xs text-gray-500"
                                >
                                    Registering also makes you a TAC member —
                                    free, and you can opt out any time.
                                </p>
                            </fieldset>
                        </form>

                        <!-- Facts -->
                        <dl
                            class="mt-6 space-y-3 border-t border-gray-100 pt-5 text-sm"
                        >
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">
                                    Format
                                </dt>
                                <dd
                                    class="text-right font-semibold text-[#000928] capitalize"
                                >
                                    {{ activity.location_type }}
                                </dd>
                            </div>
                            <div
                                v-if="activity.registration_closes_at"
                                class="flex justify-between gap-4"
                            >
                                <dt class="text-gray-500">
                                    Registration closes
                                </dt>
                                <dd
                                    class="text-right font-semibold text-[#000928]"
                                >
                                    {{ dateRange({ starts_at: activity.registration_closes_at, ends_at: null }) }}
                                </dd>
                            </div>
                            <div
                                v-if="activity.capacity"
                                class="flex justify-between gap-4"
                            >
                                <dt class="text-gray-500">
                                    Capacity
                                </dt>
                                <dd
                                    class="text-right font-semibold text-[#000928]"
                                >
                                    {{ activity.capacity }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Related -->
        <section
            v-if="related.length"
            class="border-t border-gray-100 bg-gray-50/70 py-14"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2
                    class="text-xl font-black tracking-tight text-[#000928]"
                >
                    You might also like
                </h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-3">
                    <ActivityCard
                        v-for="item in related"
                        :key="item.id"
                        :activity="item"
                        compact
                    />
                </div>
            </div>
        </section>
    </CommunityShell>
</template>
