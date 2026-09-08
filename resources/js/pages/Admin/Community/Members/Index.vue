<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { computed, ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    CommunityMember,
    Paginated,
    SelectOption,
    TacTrack,
} from '@/types/community';

interface Props {
    members: Paginated<CommunityMember>;
    filters: Record<string, string | undefined>;
    tracks: TacTrack[];
    schools: string[];
    stats: {
        total: number;
        active: number;
        joined_this_month: number;
        auto_included: number;
        in_directory: number;
        mentors: number;
    };
    options: {
        currentStatuses: SelectOption[];
        membershipStatuses: SelectOption[];
        sources: SelectOption[];
        lifecycleStatuses: SelectOption[];
    };
    can: { update: boolean; export: boolean };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const toast = useToast();
const { formatDate, initials, membershipStatus } = useCommunity();

/* ------------------------------------------------------------- Filters */

const filters = ref({
    search: props.filters.search ?? '',
    track: props.filters.track ?? '',
    source: props.filters.source ?? '',
    membership_status: props.filters.membership_status ?? '',
    lifecycle_status: props.filters.lifecycle_status ?? '',
    school: props.filters.school ?? '',
    directory: props.filters.directory ?? '',
    sort: props.filters.sort ?? 'joined',
});

const go = () =>
    router.get(
        '/admin/community/members',
        Object.fromEntries(
            Object.entries(filters.value).filter(([, value]) => value),
        ),
        { preserveState: true, preserveScroll: true, replace: true },
    );

const goDebounced = debounce(go, 350);

watch(() => filters.value.search, goDebounced);
watch(
    () => [
        filters.value.track,
        filters.value.source,
        filters.value.membership_status,
        filters.value.lifecycle_status,
        filters.value.school,
        filters.value.directory,
        filters.value.sort,
    ],
    () => {
        goDebounced.cancel();
        go();
    },
);

const activeFilterCount = computed(
    () =>
        Object.entries(filters.value).filter(
            ([key, value]) => value && key !== 'sort',
        ).length,
);

const resetFilters = () => {
    filters.value = {
        search: '',
        track: '',
        source: '',
        membership_status: '',
        lifecycle_status: '',
        school: '',
        directory: '',
        sort: 'joined',
    };
};

const exportUrl = computed(() => {
    const params = new URLSearchParams(
        Object.entries(filters.value).filter(
            ([, value]) => value,
        ) as [string, string][],
    );
    return `/admin/community/members/export?${params.toString()}`;
});

/* ------------------------------------------------------------ Selection */

const selected = ref<number[]>([]);

const allSelected = computed(
    () =>
        props.members.data.length > 0 &&
        selected.value.length === props.members.data.length,
);

const toggleAll = () => {
    selected.value = allSelected.value
        ? []
        : props.members.data.map((member) => member.id);
};

const toggleOne = (id: number) => {
    const index = selected.value.indexOf(id);
    index > -1 ? selected.value.splice(index, 1) : selected.value.push(id);
};

watch(
    () => props.members.data,
    () => (selected.value = []),
);

/* ---------------------------------------------------------- Bulk actions */

const bulkAction = ref('');
const bulkValue = ref('');
const showBulkConfirm = ref(false);

const bulkOptions = computed(() => [
    { value: '', label: 'Bulk action…' },
    ...props.options.membershipStatuses.map((option) => ({
        value: `membership_status:${option.value}`,
        label: `Set membership → ${option.label}`,
    })),
    ...props.options.lifecycleStatuses.map((option) => ({
        value: `lifecycle_status:${option.value}`,
        label: `Set status → ${option.label}`,
    })),
    ...props.tracks.map((track) => ({
        value: `add_track:${track.id}`,
        label: `Add to track → ${track.name}`,
    })),
    { value: 'delete:', label: 'Remove from community' },
]);

const runBulk = () => {
    if (!bulkAction.value) return;
    if (selected.value.length === 0) {
        toast.error('Select at least one member first.');
        return;
    }

    const [action, value] = bulkAction.value.split(':');

    if (action === 'delete') {
        showBulkConfirm.value = true;
        return;
    }

    submitBulk(action, value);
};

const submitBulk = (action: string, value: string) => {
    router.post(
        '/admin/community/members/bulk',
        {
            ids: selected.value,
            action,
            value: action === 'add_track' ? undefined : value,
            track_id: action === 'add_track' ? Number(value) : undefined,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = [];
                bulkAction.value = '';
                showBulkConfirm.value = false;
            },
        },
    );
};

/* ------------------------------------------------------------ Add member */

const showAddMember = ref(false);

const addForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    school: '',
    current_status: 'student',
    track_ids: [] as number[],
    notify: true,
});

const submitAdd = () =>
    addForm.post('/admin/community/members', {
        preserveScroll: true,
        onSuccess: () => {
            addForm.reset();
            showAddMember.value = false;
        },
    });
</script>

<template>
    <div class="lms-page">
        <Head title="Community members — Admin" />

        <!-- Header -->
        <div class="lms-hero">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight">
                        Community members
                    </h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        Everyone in TAC — including everybody auto-included from
                        a program, event or course registration.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="can.update"
                        type="button"
                        class="rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#35919e]"
                        @click="showAddMember = true"
                    >
                        Add member
                    </button>
                    <a
                        v-if="can.export"
                        :href="exportUrl"
                        class="rounded-xl border border-white/25 bg-white/10 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-white/20"
                    >
                        Export CSV
                    </a>
                </div>
            </div>

            <dl class="mt-7 grid grid-cols-2 gap-3 lg:grid-cols-6">
                <div
                    v-for="stat in [
                        { label: 'Total', value: stats.total },
                        { label: 'Active', value: stats.active },
                        { label: 'This month', value: stats.joined_this_month },
                        { label: 'Auto-included', value: stats.auto_included },
                        { label: 'In directory', value: stats.in_directory },
                        { label: 'Mentors & leads', value: stats.mentors },
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
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2">
                    <label for="member-search" class="sr-only">Search</label>
                    <input
                        id="member-search"
                        v-model="filters.search"
                        type="search"
                        placeholder="Search name, email, phone or school…"
                        class="lms-input"
                    />
                </div>

                <select v-model="filters.track" class="lms-input" aria-label="Track">
                    <option value="">All tracks</option>
                    <option
                        v-for="track in tracks"
                        :key="track.id"
                        :value="track.slug"
                    >
                        {{ track.name }}
                    </option>
                </select>

                <select v-model="filters.source" class="lms-input" aria-label="Source">
                    <option value="">Any source</option>
                    <option
                        v-for="option in options.sources"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>

                <select
                    v-model="filters.membership_status"
                    class="lms-input"
                    aria-label="Membership"
                >
                    <option value="">Any membership</option>
                    <option
                        v-for="option in options.membershipStatuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>

                <select
                    v-model="filters.lifecycle_status"
                    class="lms-input"
                    aria-label="Status"
                >
                    <option value="">Any status</option>
                    <option
                        v-for="option in options.lifecycleStatuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>

                <select v-model="filters.school" class="lms-input" aria-label="School">
                    <option value="">All schools</option>
                    <option v-for="s in schools" :key="s" :value="s">
                        {{ s }}
                    </option>
                </select>

                <select v-model="filters.sort" class="lms-input" aria-label="Sort by">
                    <option value="joined">Newest first</option>
                    <option value="name">Name A–Z</option>
                    <option value="engagement">Most engaged</option>
                </select>
            </div>

            <div
                v-if="activeFilterCount"
                class="mt-3 flex items-center gap-3 text-sm"
            >
                <span class="text-gray-500 dark:text-gray-400">
                    {{ members.total }} member(s) match
                    {{ activeFilterCount }} filter(s)
                </span>
                <button
                    type="button"
                    class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                    @click="resetFilters"
                >
                    Clear all
                </button>
            </div>
        </section>

        <!-- Bulk bar -->
        <div
            v-if="selected.length && can.update"
            class="flex flex-wrap items-center gap-3 rounded-2xl border border-[#42b6c5]/30 bg-[#42b6c5]/8 p-4"
        >
            <span class="text-sm font-bold text-[#000928] dark:text-white">
                {{ selected.length }} selected
            </span>
            <select v-model="bulkAction" class="lms-input sm:w-72" aria-label="Bulk action">
                <option
                    v-for="option in bulkOptions"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <button
                type="button"
                class="lms-btn-primary"
                :disabled="!bulkAction"
                @click="runBulk"
            >
                Apply
            </button>
            <button
                type="button"
                class="text-sm font-semibold text-gray-500 hover:underline"
                @click="selected = []"
            >
                Clear selection
            </button>
        </div>

        <!-- Table -->
        <section class="lms-panel overflow-hidden p-0">
            <div v-if="members.data.length" class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead
                        class="border-b border-gray-100 bg-gray-50/70 text-left dark:border-white/10 dark:bg-white/5"
                    >
                        <tr>
                            <th v-if="can.update" class="w-10 px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    aria-label="Select all members"
                                    @change="toggleAll"
                                />
                            </th>
                            <th class="px-4 py-3 font-bold">Member</th>
                            <th class="px-4 py-3 font-bold">Tracks</th>
                            <th class="px-4 py-3 font-bold">School</th>
                            <th class="px-4 py-3 font-bold">Source</th>
                            <th class="px-4 py-3 font-bold">Standing</th>
                            <th class="px-4 py-3 font-bold">Joined</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        <tr
                            v-for="member in members.data"
                            :key="member.id"
                            class="transition-colors hover:bg-gray-50/70 dark:hover:bg-white/5"
                        >
                            <td v-if="can.update" class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="selected.includes(member.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                    :aria-label="`Select ${member.first_name}`"
                                    @change="toggleOne(member.id)"
                                />
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#381998]/10 text-xs font-black text-[#381998] dark:text-[#b9a5f5]"
                                        aria-hidden="true"
                                    >
                                        {{
                                            initials(
                                                `${member.first_name} ${member.last_name ?? ''}`,
                                            )
                                        }}
                                    </span>
                                    <div class="min-w-0">
                                        <Link
                                            :href="`/admin/community/members/${member.id}`"
                                            class="block truncate font-semibold text-[#000928] hover:underline dark:text-white"
                                        >
                                            {{ member.first_name }}
                                            {{ member.last_name }}
                                        </Link>
                                        <p
                                            class="truncate text-xs text-gray-500"
                                        >
                                            {{ member.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="track in member.tracks ?? []"
                                        :key="track.id"
                                        class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300"
                                    >
                                        {{ track.name }}
                                    </span>
                                    <span
                                        v-if="!member.tracks?.length"
                                        class="text-xs text-gray-400"
                                        >—</span
                                    >
                                </div>
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ member.school || '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300"
                                >
                                    {{
                                        options.sources.find(
                                            (s) => s.value === member.source,
                                        )?.label ?? member.source
                                    }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <span
                                        :class="[
                                            'w-fit rounded-full px-2 py-0.5 text-[10px] font-bold',
                                            membershipStatus(
                                                member.membership_status,
                                            ).classes,
                                        ]"
                                    >
                                        {{
                                            membershipStatus(
                                                member.membership_status,
                                            ).label
                                        }}
                                    </span>
                                    <span
                                        v-if="member.lifecycle_status !== 'active'"
                                        class="w-fit rounded-full bg-amber-500/12 px-2 py-0.5 text-[10px] font-bold text-amber-700 capitalize dark:text-amber-300"
                                    >
                                        {{ member.lifecycle_status }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{
                                    formatDate(member.joined_at, {
                                        day: 'numeric',
                                        month: 'short',
                                        year: 'numeric',
                                    })
                                }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="`/admin/community/members/${member.id}`"
                                    class="text-sm font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="p-6">
                <EmptyState
                    icon="users"
                    :title="
                        activeFilterCount
                            ? 'No members match those filters'
                            : 'No members yet'
                    "
                    :description="
                        activeFilterCount
                            ? 'Try clearing a filter or broadening your search.'
                            : 'Members appear here as soon as somebody joins or registers for anything at the academy.'
                    "
                >
                    <button
                        v-if="activeFilterCount"
                        class="lms-btn-outline"
                        @click="resetFilters"
                    >
                        Clear filters
                    </button>
                </EmptyState>
            </div>

            <!-- Pagination -->
            <nav
                v-if="members.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3 dark:border-white/10"
                aria-label="Pagination"
            >
                <p class="text-xs text-gray-500">
                    Showing {{ members.from }}–{{ members.to }} of
                    {{ members.total }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in members.links"
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

        <!-- Add member modal -->
        <ConfirmationModal
            :open="showAddMember"
            title="Add a member by hand"
            description="For a lead you met at a campus visit or an event sign-up sheet. If the email already exists, their details are updated instead of duplicated."
            confirm-text="Add member"
            variant="default"
            :processing="addForm.processing"
            @update:open="showAddMember = $event"
            @confirm="submitAdd"
        >
            <template #body>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="add_first" class="lms-label">First name *</label>
                        <input
                            id="add_first"
                            v-model="addForm.first_name"
                            type="text"
                            class="lms-input mt-1.5"
                        />
                        <InputError :message="addForm.errors.first_name" />
                    </div>
                    <div>
                        <label for="add_last" class="lms-label">Last name</label>
                        <input
                            id="add_last"
                            v-model="addForm.last_name"
                            type="text"
                            class="lms-input mt-1.5"
                        />
                    </div>
                    <div>
                        <label for="add_email" class="lms-label">Email *</label>
                        <input
                            id="add_email"
                            v-model="addForm.email"
                            type="email"
                            class="lms-input mt-1.5"
                        />
                        <InputError :message="addForm.errors.email" />
                    </div>
                    <div>
                        <label for="add_phone" class="lms-label">Phone</label>
                        <input
                            id="add_phone"
                            v-model="addForm.phone"
                            type="tel"
                            class="lms-input mt-1.5"
                        />
                    </div>
                    <div>
                        <label for="add_school" class="lms-label">School</label>
                        <input
                            id="add_school"
                            v-model="addForm.school"
                            type="text"
                            class="lms-input mt-1.5"
                        />
                    </div>
                    <div>
                        <label for="add_status" class="lms-label">Status</label>
                        <select
                            id="add_status"
                            v-model="addForm.current_status"
                            class="lms-input mt-1.5"
                        >
                            <option
                                v-for="option in options.currentStatuses"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <fieldset class="mt-4">
                    <legend class="lms-label">Tracks</legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <label
                            v-for="track in tracks"
                            :key="track.id"
                            :class="[
                                'cursor-pointer rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
                                addForm.track_ids.includes(track.id)
                                    ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#26808c]'
                                    : 'border-gray-200 text-gray-600 dark:border-white/10 dark:text-gray-300',
                            ]"
                        >
                            <input
                                v-model="addForm.track_ids"
                                type="checkbox"
                                :value="track.id"
                                class="sr-only"
                            />
                            {{ track.name }}
                        </label>
                    </div>
                </fieldset>

                <label class="mt-4 flex items-start gap-2.5 text-sm">
                    <input
                        v-model="addForm.notify"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                    />
                    <span class="text-gray-600 dark:text-gray-400">
                        Send them the TAC welcome email
                    </span>
                </label>
            </template>
        </ConfirmationModal>

        <!-- Bulk delete confirmation -->
        <ConfirmationModal
            :open="showBulkConfirm"
            title="Remove members from the community?"
            :description="`This permanently removes ${selected.length} member record(s), including their RSVP history. It cannot be undone.`"
            confirm-text="Remove them"
            @update:open="showBulkConfirm = $event"
            @confirm="submitBulk('delete', '')"
        />
    </div>
</template>
