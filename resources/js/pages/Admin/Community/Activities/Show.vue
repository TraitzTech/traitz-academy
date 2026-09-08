<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { computed, ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import { useCommunity } from '@/composables/useCommunity';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    Paginated,
    SelectOption,
    TacActivity,
    TacActivityRsvp,
} from '@/types/community';

interface Props {
    activity: TacActivity;
    rsvps: Paginated<TacActivityRsvp>;
    filters: { rsvp_status?: string; rsvp_search?: string };
    rsvpStatuses: SelectOption[];
    breakdown: Record<string, number>;
    can: { update: boolean; publish: boolean; judge: boolean };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const toast = useToast();
const { dateRange, locationLabel, activityType, rsvpStatus, money } =
    useCommunity();

/* ---------------------------------------------------------- RSVP filters */

const search = ref(props.filters.rsvp_search ?? '');
const status = ref(props.filters.rsvp_status ?? '');

const go = () =>
    router.get(
        `/admin/community/activities/${props.activity.slug}`,
        {
            rsvp_search: search.value || undefined,
            rsvp_status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );

const goDebounced = debounce(go, 350);
watch(search, goDebounced);
watch(status, () => {
    goDebounced.cancel();
    go();
});

/* -------------------------------------------------------------- Selection */

const selected = ref<number[]>([]);

const allSelected = computed(
    () =>
        props.rsvps.data.length > 0 &&
        selected.value.length === props.rsvps.data.length,
);

const toggleAll = () => {
    selected.value = allSelected.value
        ? []
        : props.rsvps.data.map((rsvp) => rsvp.id);
};

const toggleOne = (id: number) => {
    const index = selected.value.indexOf(id);
    index > -1 ? selected.value.splice(index, 1) : selected.value.push(id);
};

watch(() => props.rsvps.data, () => (selected.value = []));

/* ----------------------------------------------------------- RSVP actions */

const updateRsvp = (rsvp: TacActivityRsvp, newStatus: string) =>
    router.patch(
        `/admin/community/activities/${props.activity.slug}/rsvps/${rsvp.id}`,
        { status: newStatus, payment_status: rsvp.payment_status },
        { preserveScroll: true, preserveState: true },
    );

const bulkStatus = ref('');

const runBulkStatus = () => {
    if (!bulkStatus.value || selected.value.length === 0) {
        toast.error('Select some RSVPs and an action first.');
        return;
    }

    router.post(
        `/admin/community/activities/${props.activity.slug}/rsvps/bulk`,
        { ids: selected.value, action: 'status', status: bulkStatus.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = [];
                bulkStatus.value = '';
            },
        },
    );
};

/* ------------------------------------------------------------- Reminders */

const showReminder = ref(false);
const reminderForm = useForm({ note: '' });

const sendReminder = () =>
    reminderForm.post(
        `/admin/community/activities/${props.activity.slug}/rsvps/remind`,
        {
            preserveScroll: true,
            onSuccess: () => {
                showReminder.value = false;
                reminderForm.reset();
            },
        },
    );

/* --------------------------------------------------------------- Delete */

const showDelete = ref(false);
const destroy = () =>
    router.delete(`/admin/community/activities/${props.activity.slug}`);

const setStatus = (newStatus: string) =>
    router.post(
        `/admin/community/activities/${props.activity.slug}/status`,
        { status: newStatus },
        { preserveScroll: true },
    );
</script>

<template>
    <div class="lms-page">
        <Head :title="`${activity.title} — Admin`" />

        <nav class="text-sm" aria-label="Breadcrumb">
            <Link
                href="/admin/community/activities"
                class="font-semibold text-gray-500 hover:text-[#000928] dark:hover:text-white"
            >
                ← All activities
            </Link>
        </nav>

        <!-- Header -->
        <div class="lms-hero">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-black tracking-wide text-[#000928] uppercase"
                        >
                            {{ activityType(activity.type).label }}
                        </span>
                        <span
                            class="rounded-full border border-white/25 bg-white/10 px-2.5 py-1 text-[10px] font-bold capitalize backdrop-blur"
                        >
                            {{ activity.status }}
                        </span>
                        <span
                            v-if="activity.track"
                            class="rounded-full border border-white/25 bg-white/10 px-2.5 py-1 text-[10px] font-bold backdrop-blur"
                        >
                            {{ activity.track.name }}
                        </span>
                    </div>

                    <h1 class="mt-3 text-2xl font-black tracking-tight">
                        {{ activity.title }}
                    </h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        {{ dateRange(activity) }} ·
                        {{ locationLabel(activity) }}
                        <template v-if="activity.is_paid">
                            · {{ money(activity.price, activity.currency) }}
                        </template>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="activity.status !== 'draft'"
                        :href="`/community/activities/${activity.slug}`"
                        class="rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-white/20"
                    >
                        View public page
                    </Link>
                    <Link
                        v-if="can.judge"
                        :href="`/admin/community/activities/${activity.slug}/judge`"
                        class="rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-bold text-[#000928] transition-colors hover:bg-amber-300"
                    >
                        Judging room
                    </Link>
                    <Link
                        v-if="can.update"
                        :href="`/admin/community/activities/${activity.slug}/edit`"
                        class="rounded-xl bg-[#42b6c5] px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#35919e]"
                    >
                        Edit
                    </Link>
                </div>
            </div>

            <dl class="mt-7 grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div
                    v-for="stat in [
                        { label: 'Total RSVPs', value: activity.rsvps_count ?? 0 },
                        { label: 'Registered', value: breakdown.registered ?? 0 },
                        { label: 'Confirmed', value: breakdown.confirmed ?? 0 },
                        { label: 'Waitlisted', value: breakdown.waitlisted ?? 0 },
                        { label: 'Attended', value: breakdown.attended ?? 0 },
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

        <!-- Quick actions -->
        <section
            v-if="can.publish || can.update"
            class="lms-panel flex flex-wrap items-center gap-3"
        >
            <button
                v-if="can.publish && activity.status === 'draft'"
                type="button"
                class="lms-btn-accent"
                @click="setStatus('published')"
            >
                Publish
            </button>
            <button
                v-if="can.publish && activity.status === 'published'"
                type="button"
                class="lms-btn-outline"
                @click="setStatus('draft')"
            >
                Unpublish
            </button>
            <button
                v-if="can.publish && activity.status === 'published'"
                type="button"
                class="lms-btn-outline"
                @click="setStatus('completed')"
            >
                Mark completed
            </button>
            <button
                v-if="can.update"
                type="button"
                class="lms-btn-outline"
                @click="showReminder = true"
            >
                Send reminder to everyone
            </button>
            <a
                v-if="can.update"
                :href="`/admin/community/activities/${activity.slug}/rsvps/export`"
                class="lms-btn-outline"
            >
                Export RSVPs
            </a>
            <button
                v-if="can.update"
                type="button"
                class="ml-auto text-sm font-semibold text-red-600 hover:underline"
                @click="showDelete = true"
            >
                Delete activity
            </button>
        </section>

        <!-- Occurrences -->
        <section v-if="activity.occurrences_count" class="lms-panel">
            <h2 class="lms-title text-lg">Recurring series</h2>
            <p class="lms-subtitle">
                This activity has {{ activity.occurrences_count }} further
                occurrence(s), each managed as its own activity with its own
                RSVPs.
            </p>
        </section>

        <!-- RSVPs -->
        <section class="lms-panel overflow-hidden p-0">
            <div
                class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4 dark:border-white/10"
            >
                <h2 class="lms-title text-lg">RSVPs</h2>
                <div class="ml-auto flex flex-wrap gap-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search attendees…"
                        class="lms-input sm:w-56"
                        aria-label="Search attendees"
                    />
                    <select v-model="status" class="lms-input sm:w-44" aria-label="Status">
                        <option value="">All statuses</option>
                        <option
                            v-for="option in rsvpStatuses"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>
            </div>

            <div
                v-if="selected.length && can.update"
                class="flex flex-wrap items-center gap-3 border-b border-gray-100 bg-[#42b6c5]/8 p-4 dark:border-white/10"
            >
                <span class="text-sm font-bold text-[#000928] dark:text-white"
                    >{{ selected.length }} selected</span
                >
                <select v-model="bulkStatus" class="lms-input sm:w-52" aria-label="Set status">
                    <option value="">Set status to…</option>
                    <option
                        v-for="option in rsvpStatuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <button
                    type="button"
                    class="lms-btn-primary"
                    :disabled="!bulkStatus"
                    @click="runBulkStatus"
                >
                    Apply
                </button>
                <button
                    type="button"
                    class="text-sm font-semibold text-gray-500 hover:underline"
                    @click="selected = []"
                >
                    Clear
                </button>
            </div>

            <div v-if="rsvps.data.length" class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead
                        class="border-b border-gray-100 bg-gray-50/70 text-left dark:border-white/10 dark:bg-white/5"
                    >
                        <tr>
                            <th v-if="can.update" class="w-10 px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    aria-label="Select all RSVPs"
                                    @change="toggleAll"
                                />
                            </th>
                            <th class="px-4 py-3 font-bold">Attendee</th>
                            <th class="px-4 py-3 font-bold">School</th>
                            <th class="px-4 py-3 font-bold">Payment</th>
                            <th class="px-4 py-3 font-bold">Status</th>
                            <th class="px-4 py-3 font-bold">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        <tr
                            v-for="rsvp in rsvps.data"
                            :key="rsvp.id"
                            class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/5"
                        >
                            <td v-if="can.update" class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="selected.includes(rsvp.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    :aria-label="`Select ${rsvp.member?.first_name}`"
                                    @change="toggleOne(rsvp.id)"
                                />
                            </td>

                            <td class="px-4 py-3">
                                <Link
                                    :href="`/admin/community/members/${rsvp.community_member_id}`"
                                    class="block font-semibold text-[#000928] hover:underline dark:text-white"
                                >
                                    {{ rsvp.member?.first_name }}
                                    {{ rsvp.member?.last_name }}
                                </Link>
                                <p class="text-xs text-gray-500">
                                    {{ rsvp.member?.email }}
                                    <template v-if="rsvp.member?.phone"
                                        >· {{ rsvp.member.phone }}</template
                                    >
                                </p>
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ rsvp.member?.school || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[10px] font-bold capitalize',
                                        rsvp.payment_status === 'paid'
                                            ? 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300'
                                            : rsvp.payment_status === 'pending'
                                              ? 'bg-amber-500/12 text-amber-700 dark:text-amber-300'
                                              : rsvp.payment_status === 'failed'
                                                ? 'bg-red-500/12 text-red-700 dark:text-red-300'
                                                : 'bg-gray-500/12 text-gray-600 dark:text-gray-300',
                                    ]"
                                >
                                    {{ rsvp.payment_status }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <select
                                    v-if="can.update"
                                    :value="rsvp.status"
                                    class="lms-input py-1.5 text-xs"
                                    :aria-label="`Status for ${rsvp.member?.first_name}`"
                                    @change="
                                        updateRsvp(
                                            rsvp,
                                            ($event.target as HTMLSelectElement)
                                                .value,
                                        )
                                    "
                                >
                                    <option
                                        v-for="option in rsvpStatuses"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <span
                                    v-else
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                        rsvpStatus(rsvp.status).classes,
                                    ]"
                                    >{{ rsvpStatus(rsvp.status).label }}</span
                                >
                            </td>

                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{
                                    new Date(rsvp.created_at).toLocaleDateString(
                                        'en-GB',
                                        {
                                            day: 'numeric',
                                            month: 'short',
                                        },
                                    )
                                }}
                                <span
                                    v-if="rsvp.reminded_at"
                                    class="block text-[10px] text-gray-400"
                                    >reminded</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="p-6">
                <EmptyState
                    icon="users"
                    title="No RSVPs yet"
                    :description="
                        activity.status === 'draft'
                            ? 'This activity is still a draft, so nobody can register for it yet.'
                            : 'Nobody has registered so far. Share the public page to get the word out.'
                    "
                />
            </div>

            <nav
                v-if="rsvps.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3 dark:border-white/10"
                aria-label="Pagination"
            >
                <p class="text-xs text-gray-500">
                    Showing {{ rsvps.from }}–{{ rsvps.to }} of {{ rsvps.total }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in rsvps.links"
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

        <!-- Reminder modal -->
        <ConfirmationModal
            :open="showReminder"
            title="Send a reminder"
            description="Emails everyone currently registered or confirmed. Members who opted out of community email are skipped."
            confirm-text="Send reminder"
            variant="default"
            :processing="reminderForm.processing"
            @update:open="showReminder = $event"
            @confirm="sendReminder"
        >
            <template #body>
                <div class="mt-4">
                    <label for="reminder_note" class="lms-label"
                        >Add a note (optional)</label
                    >
                    <textarea
                        id="reminder_note"
                        v-model="reminderForm.note"
                        rows="3"
                        maxlength="1000"
                        placeholder="e.g. Bring your laptop and a charger."
                        class="lms-input mt-1.5 resize-y"
                    />
                </div>
            </template>
        </ConfirmationModal>

        <ConfirmationModal
            :open="showDelete"
            title="Delete this activity?"
            :description="`This permanently removes “${activity.title}” and all ${activity.rsvps_count ?? 0} RSVP(s). It cannot be undone.`"
            confirm-text="Delete activity"
            @update:open="showDelete = $event"
            @confirm="destroy"
        />
    </div>
</template>
