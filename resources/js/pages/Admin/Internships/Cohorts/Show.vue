<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    CheckCheck,
    ChevronDown,
    FileCheck2,
    Globe,
    LayoutList,
    Plus,
    Search,
    Settings2,
    Trash2,
    UserMinus,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface Option {
    id: number;
    title?: string;
    name?: string;
    role?: string;
}
interface Intern {
    id: number;
    name: string | null;
    email: string | null;
    program: string | null;
    status: string;
    supervisor: string | null;
    effective_supervisor_id: number | null;
    working_days: number[] | null;
    effective_working_days: number[];
}
interface CohortProgram {
    id: number;
    title: string;
    supervisor_id: number | null;
}
interface Cohort {
    id: number;
    name: string;
    start_date: string | null;
    end_date: string | null;
    intake_opens_at: string | null;
    intake_closes_at: string | null;
    status: string;
    is_intake: boolean;
    timezone: string | null;
    working_days: number[] | null;
    programs: CohortProgram[];
}
interface AvailableIntern {
    id: number;
    name: string | null;
    email: string | null;
    program: string | null;
    accepted_at: string | null;
}

const props = defineProps<{
    cohort: Cohort;
    interns: Intern[];
    available: AvailableIntern[];
    programs: Option[];
    supervisors: Option[];
}>();

const toast = useToast();

// Shared field styles so every input/select on the page stays consistent.
const field =
    'w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20';
const fieldSm =
    'rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20';
const label =
    'mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200';
const primaryBtn =
    'inline-flex items-center gap-2 rounded-xl bg-[#42b6c5] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35919e] disabled:cursor-not-allowed disabled:opacity-50';
const textBtn =
    'inline-flex items-center gap-1 text-xs font-semibold text-[#381998] hover:underline';

const statusStyles: Record<string, string> = {
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    upcoming:
        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    completed: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    cancelled: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-300',
};

/* ------------------------------------------------------------------ */
/* Cohort settings (collapsed by default — managing interns is the    */
/* day-to-day job; settings are occasional)                            */
/* ------------------------------------------------------------------ */
const settingsOpen = ref(false);

const settings = useForm({
    name: props.cohort.name,
    start_date: props.cohort.start_date ?? '',
    end_date: props.cohort.end_date ?? '',
    intake_opens_at: props.cohort.intake_opens_at ?? '',
    intake_closes_at: props.cohort.intake_closes_at ?? '',
    status: props.cohort.status,
    is_intake: props.cohort.is_intake ?? false,
    timezone: props.cohort.timezone ?? '',
    working_days: (props.cohort.working_days ?? [1, 2, 3, 4, 5]) as number[],
    programs: props.cohort.programs.map((p) => ({
        program_id: p.id as number | string,
        supervisor_id: (p.supervisor_id ?? '') as number | string,
    })),
});

const WEEKDAYS = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 7, label: 'Sun' },
];
function toggleDay(days: number[], day: number) {
    const i = days.indexOf(day);
    if (i === -1) days.push(day);
    else days.splice(i, 1);
}

function addProgramRow() {
    settings.programs.push({ program_id: '', supervisor_id: '' });
}
function removeProgramRow(i: number) {
    settings.programs.splice(i, 1);
}
const allProgramsSelected = computed(() => {
    const selected = new Set(
        settings.programs.map((r) => r.program_id).filter(Boolean),
    );
    return (
        props.programs.length > 0 &&
        props.programs.every((p) => selected.has(p.id))
    );
});
function selectAllPrograms() {
    const existing = new Map(
        settings.programs
            .filter((r) => r.program_id)
            .map((r) => [r.program_id, r.supervisor_id]),
    );
    settings.programs = props.programs.map((p) => ({
        program_id: p.id,
        supervisor_id: existing.get(p.id) ?? '',
    }));
}

function saveSettings() {
    settings
        .transform((data) => ({
            ...data,
            programs: data.programs.filter((p) => p.program_id),
        }))
        .put(`/admin/internships/cohorts/${props.cohort.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                settingsOpen.value = false;
            },
            onError: () => toast.error('Could not update cohort.'),
        });
}

/* ------------------------------------------------------------------ */
/* Add interns — one panel, two tabs:                                  */
/*   applications: accepted-but-unassigned interns (with date filter)  */
/*   manual: someone who never applied (existing user or new account)  */
/* ------------------------------------------------------------------ */
const addOpen = ref(false);
const addTab = ref<'applications' | 'manual'>('applications');

// -- applications tab -------------------------------------------------
const selectedInterns = ref<number[]>([]);
const availProgram = ref('');
const acceptedFrom = ref('');
const acceptedTo = ref('');
const availSearch = ref('');
const dateFilterActive = computed(() =>
    Boolean(acceptedFrom.value || acceptedTo.value),
);
const availFilterActive = computed(() =>
    Boolean(availProgram.value || dateFilterActive.value || availSearch.value),
);

// Programs that actually have unassigned interns waiting, with a count each,
// so the admin can pick one and bulk-add just that program's applicants.
const availProgramCounts = computed(() => {
    const map = new Map<string, number>();
    for (const a of props.available) {
        const key = a.program ?? 'No program';
        map.set(key, (map.get(key) ?? 0) + 1);
    }
    return [...map.entries()]
        .sort((a, b) => a[0].localeCompare(b[0]))
        .map(([program, count]) => ({ program, count }));
});

const availableFiltered = computed(() =>
    props.available.filter((a) => {
        if (availProgram.value && a.program !== availProgram.value)
            return false;
        if (!a.accepted_at) return !dateFilterActive.value;
        if (acceptedFrom.value && a.accepted_at < acceptedFrom.value)
            return false;
        if (acceptedTo.value && a.accepted_at > acceptedTo.value) return false;
        const search = availSearch.value.trim().toLowerCase();
        if (
            search &&
            !(
                a.name?.toLowerCase().includes(search) ||
                a.email?.toLowerCase().includes(search)
            )
        )
            return false;
        return true;
    }),
);
const allVisibleSelected = computed(() => {
    const visible = availableFiltered.value;
    return (
        visible.length > 0 &&
        visible.every((a) => selectedInterns.value.includes(a.id))
    );
});

function clearAvailFilters() {
    availProgram.value = '';
    acceptedFrom.value = '';
    acceptedTo.value = '';
    availSearch.value = '';
}
function toggleSelectedIntern(id: number) {
    selectedInterns.value = selectedInterns.value.includes(id)
        ? selectedInterns.value.filter((i) => i !== id)
        : [...selectedInterns.value, id];
}
function toggleSelectAllInterns() {
    const visibleIds = availableFiltered.value.map((a) => a.id);
    selectedInterns.value = allVisibleSelected.value
        ? selectedInterns.value.filter((id) => !visibleIds.includes(id))
        : [...new Set([...selectedInterns.value, ...visibleIds])];
}
function addSelectedInterns() {
    if (selectedInterns.value.length === 0) return;
    router.post(
        `/admin/internships/cohorts/${props.cohort.id}/interns`,
        { internship_ids: selectedInterns.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedInterns.value = [];
            },
            onError: (e) =>
                toast.error(
                    (Object.values(e)[0] as string) ||
                        'Could not add intern(s).',
                ),
        },
    );
}

// -- manual tab -------------------------------------------------------
interface FoundUser {
    id: number;
    name: string;
    email: string;
}
const manualMode = ref<'existing' | 'new'>('existing');
const manualQuery = ref('');
const manualResults = ref<FoundUser[]>([]);
const manualSelectedUser = ref<FoundUser | null>(null);
const manualSearching = ref(false);

const manualForm = useForm({
    program_id: '' as number | string,
    mode: 'existing' as 'existing' | 'new',
    user_id: '' as number | string,
    name: '',
    email: '',
    phone: '',
});

const canSubmitManual = computed(() => {
    if (manualForm.processing || !manualForm.program_id) return false;
    return manualMode.value === 'existing'
        ? manualSelectedUser.value !== null
        : Boolean(manualForm.name && manualForm.email);
});

let searchTimer: ReturnType<typeof setTimeout> | undefined;
let suppressNextQueryWatch = false;
watch(manualQuery, (q) => {
    if (suppressNextQueryWatch) {
        suppressNextQueryWatch = false;
        return;
    }
    manualSelectedUser.value = null;
    clearTimeout(searchTimer);
    if (q.trim().length < 2) {
        manualResults.value = [];
        return;
    }
    searchTimer = setTimeout(async () => {
        manualSearching.value = true;
        try {
            const res = await fetch(
                `/admin/internships/users/search?q=${encodeURIComponent(q)}`,
                { headers: { Accept: 'application/json' } },
            );
            manualResults.value = res.ok ? await res.json() : [];
        } finally {
            manualSearching.value = false;
        }
    }, 300);
});

function pickManualUser(u: FoundUser) {
    manualSelectedUser.value = u;
    suppressNextQueryWatch = true;
    manualQuery.value = `${u.name} (${u.email})`;
    manualResults.value = [];
}

function submitManualIntern() {
    manualForm
        .transform((data) => ({
            ...data,
            mode: manualMode.value,
            user_id:
                manualMode.value === 'existing'
                    ? (manualSelectedUser.value?.id ?? '')
                    : '',
        }))
        .post(`/admin/internships/cohorts/${props.cohort.id}/interns/manual`, {
            preserveScroll: true,
            onSuccess: () => {
                manualForm.reset();
                manualQuery.value = '';
                manualSelectedUser.value = null;
                addOpen.value = false;
            },
            onError: (e) =>
                toast.error(
                    (Object.values(e)[0] as string) || 'Could not add intern.',
                ),
        });
}

/* ------------------------------------------------------------------ */
/* Roster: filter + group                                              */
/* ------------------------------------------------------------------ */
const rosterProgram = ref('');
const rosterSearch = ref('');
const groupByProgram = ref(false);

const rosterFiltered = computed(() => {
    const q = rosterSearch.value.trim().toLowerCase();
    return props.interns.filter((i) => {
        if (rosterProgram.value && i.program !== rosterProgram.value)
            return false;
        if (q && !`${i.name ?? ''} ${i.email ?? ''}`.toLowerCase().includes(q))
            return false;
        return true;
    });
});

const rosterFilterActive = computed(() =>
    Boolean(rosterProgram.value || rosterSearch.value),
);

// One flat list of table rows. When grouping is on, a header row is injected
// before each program's block — so the intern row markup is written once.
type RosterRow =
    | { type: 'header'; program: string; count: number }
    | { type: 'intern'; intern: Intern };
const rosterRows = computed<RosterRow[]>(() => {
    if (!groupByProgram.value) {
        return rosterFiltered.value.map((intern) => ({
            type: 'intern',
            intern,
        }));
    }
    const map = new Map<string, Intern[]>();
    for (const i of rosterFiltered.value) {
        const key = i.program ?? 'No program';
        if (!map.has(key)) map.set(key, []);
        map.get(key)!.push(i);
    }
    const rows: RosterRow[] = [];
    for (const [program, list] of [...map.entries()].sort((a, b) =>
        a[0].localeCompare(b[0]),
    )) {
        rows.push({ type: 'header', program, count: list.length });
        for (const intern of list) rows.push({ type: 'intern', intern });
    }
    return rows;
});

/* ------------------------------------------------------------------ */
/* Roster actions                                                      */
/* ------------------------------------------------------------------ */
function removeIntern(internshipId: number) {
    router.delete(
        `/admin/internships/cohorts/${props.cohort.id}/interns/${internshipId}`,
        { preserveScroll: true },
    );
}
function setInternSupervisor(internshipId: number, supervisorId: string) {
    router.put(
        `/admin/internships/internships/${internshipId}/supervisor`,
        { supervisor_id: supervisorId || null },
        { preserveScroll: true },
    );
}

// Per-intern working-days override popover — a shared button toggles editing
// for the row whose id is currently open; other rows' state is untouched
// until they're opened themselves.
const editingDaysFor = ref<number | null>(null);
const editingDays = ref<number[]>([]);
function openDaysEditor(intern: Intern) {
    editingDaysFor.value = intern.id;
    editingDays.value = [
        ...(intern.working_days ?? intern.effective_working_days),
    ];
}
function closeDaysEditor() {
    editingDaysFor.value = null;
}
function saveInternWorkingDays(internshipId: number) {
    router.put(
        `/admin/internships/internships/${internshipId}/working-days`,
        { working_days: editingDays.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                editingDaysFor.value = null;
            },
        },
    );
}
function clearInternWorkingDays(internshipId: number) {
    router.put(
        `/admin/internships/internships/${internshipId}/working-days`,
        { working_days: [] },
        {
            preserveScroll: true,
            onSuccess: () => {
                editingDaysFor.value = null;
            },
        },
    );
}
const DAY_LETTERS: Record<number, string> = {
    1: 'M',
    2: 'T',
    3: 'W',
    4: 'T',
    5: 'F',
    6: 'S',
    7: 'S',
};
</script>

<template>
    <div class="space-y-6">
        <Head :title="`Cohort: ${cohort.name}`" />

        <Link
            href="/admin/internships/cohorts"
            class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-[#381998] dark:text-gray-300"
        >
            <ArrowLeft class="h-4 w-4" /> All cohorts
        </Link>

        <!-- Header: everything at a glance + the two page-level actions -->
        <div
            class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                class="h-1.5 bg-gradient-to-r from-[#381998] via-[#42b6c5] to-[#000928]"
            ></div>
            <div class="flex flex-wrap items-start justify-between gap-4 p-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1
                            class="text-2xl font-bold text-[#000928] dark:text-white"
                        >
                            {{ cohort.name }}
                        </h1>
                        <span
                            :class="[
                                'rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                statusStyles[cohort.status] ??
                                    statusStyles.completed,
                            ]"
                            >{{ cohort.status }}</span
                        >
                        <span
                            v-if="cohort.is_intake"
                            class="rounded-full bg-[#381998]/10 px-2.5 py-0.5 text-xs font-semibold text-[#381998] dark:bg-[#381998]/30 dark:text-[#b9a6f0]"
                            >Intake cohort</span
                        >
                    </div>
                    <div
                        class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        <span class="inline-flex items-center gap-1.5"
                            ><CalendarDays class="h-4 w-4" />
                            {{ cohort.start_date }} →
                            {{ cohort.end_date }}</span
                        >
                        <span class="inline-flex items-center gap-1.5"
                            ><Users class="h-4 w-4" />
                            {{ interns.length }} intern{{
                                interns.length === 1 ? '' : 's'
                            }}</span
                        >
                        <span
                            v-if="cohort.timezone"
                            class="inline-flex items-center gap-1.5"
                            ><Globe class="h-4 w-4" />
                            {{ cohort.timezone }}</span
                        >
                    </div>
                    <div
                        v-if="cohort.programs.length > 0"
                        class="mt-3 flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="p in cohort.programs"
                            :key="p.id"
                            class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                        >
                            {{ p.title }}
                        </span>
                    </div>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-[#381998] hover:text-[#381998] dark:border-gray-600 dark:text-gray-200"
                        @click="settingsOpen = !settingsOpen"
                    >
                        <Settings2 class="h-4 w-4" /> Settings
                        <ChevronDown
                            :class="[
                                'h-4 w-4 transition-transform',
                                settingsOpen ? 'rotate-180' : '',
                            ]"
                        />
                    </button>
                    <button
                        type="button"
                        :class="primaryBtn"
                        @click="addOpen = !addOpen"
                    >
                        <UserPlus class="h-4 w-4" /> Add interns
                    </button>
                </div>
            </div>
        </div>

        <!-- Settings (on demand) -->
        <div
            v-if="settingsOpen"
            class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700"
            >
                <h2 class="text-sm font-bold text-[#000928] dark:text-white">
                    Cohort settings
                </h2>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    @click="settingsOpen = false"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
            <div class="space-y-4 p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">Name</label>
                        <input
                            v-model="settings.name"
                            type="text"
                            :class="field"
                        />
                    </div>
                    <div>
                        <label :class="label">Status</label>
                        <select v-model="settings.status" :class="field">
                            <option value="upcoming">Upcoming</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label :class="label">Start date</label>
                        <input
                            v-model="settings.start_date"
                            type="date"
                            :class="field"
                        />
                    </div>
                    <div>
                        <label :class="label">End date</label>
                        <input
                            v-model="settings.end_date"
                            type="date"
                            :class="field"
                        />
                    </div>
                </div>

                <div>
                    <label :class="label">Working days (logbook)</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="d in WEEKDAYS"
                            :key="d.value"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium dark:border-gray-600"
                            :class="
                                settings.working_days.includes(d.value)
                                    ? 'border-[#381998]/40 bg-[#381998]/10 text-[#381998]'
                                    : 'text-gray-500 dark:text-gray-300'
                            "
                        >
                            <input
                                type="checkbox"
                                class="hidden"
                                :checked="
                                    settings.working_days.includes(d.value)
                                "
                                @change="
                                    toggleDay(settings.working_days, d.value)
                                "
                            />
                            {{ d.label }}
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Interns default to these days unless a per-intern
                        override is set below.
                    </p>
                </div>

                <div
                    class="rounded-xl border border-dashed border-gray-300 p-4 dark:border-gray-600"
                >
                    <p
                        class="mb-1 text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Application window
                        <span class="font-normal text-gray-400"
                            >(optional)</span
                        >
                    </p>
                    <p class="mb-3 text-xs text-gray-500">
                        Saving stamps these dates onto every program in this
                        cohort. Leave blank to keep each program's own window.
                    </p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300"
                                >Applications open</label
                            >
                            <input
                                v-model="settings.intake_opens_at"
                                type="date"
                                :class="field"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300"
                                >Applications close</label
                            >
                            <input
                                v-model="settings.intake_closes_at"
                                type="date"
                                :class="field"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-gray-200 p-4 dark:border-gray-600"
                >
                    <p
                        class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Programs & supervisors
                    </p>
                    <p class="mb-3 text-xs text-gray-500">
                        Interns for each program are placed under its
                        supervisor.
                    </p>
                    <div
                        v-for="(row, i) in settings.programs"
                        :key="i"
                        class="mb-2 flex items-center gap-2"
                    >
                        <select
                            v-model="row.program_id"
                            :class="[fieldSm, 'flex-1 px-3 py-2']"
                        >
                            <option value="">Select program…</option>
                            <option
                                v-for="p in programs"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.title }}
                            </option>
                        </select>
                        <select
                            v-model="row.supervisor_id"
                            :class="[fieldSm, 'flex-1 px-3 py-2']"
                        >
                            <option value="">Supervisor (optional)</option>
                            <option
                                v-for="s in supervisors"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-gray-400 hover:text-red-500"
                            @click="removeProgramRow(i)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-4">
                        <button
                            type="button"
                            :class="textBtn"
                            @click="addProgramRow"
                        >
                            <Plus class="h-3.5 w-3.5" /> Add program
                        </button>
                        <button
                            v-if="!allProgramsSelected"
                            type="button"
                            :class="textBtn"
                            @click="selectAllPrograms"
                        >
                            <CheckCheck class="h-3.5 w-3.5" /> Select all
                            programs
                        </button>
                    </div>
                </div>

                <label
                    class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-600"
                >
                    <input
                        v-model="settings.is_intake"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 accent-[#381998]"
                    />
                    <span>
                        <span
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                            >Open for intake</span
                        >
                        <span class="block text-xs text-gray-500"
                            >Newly-accepted applicants are auto-placed here
                            under their program's supervisor. Only one intake
                            cohort at a time.</span
                        >
                    </span>
                </label>

                <div class="flex justify-end">
                    <button
                        :disabled="settings.processing"
                        class="rounded-xl bg-[#381998] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-60"
                        @click="saveSettings"
                    >
                        {{ settings.processing ? 'Saving…' : 'Save settings' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Add interns (on demand): two tabs, one panel -->
        <div
            v-if="addOpen"
            class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                class="flex items-center justify-between border-b border-gray-100 px-6 py-3 dark:border-gray-700"
            >
                <div class="flex gap-1">
                    <button
                        type="button"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                            addTab === 'applications'
                                ? 'bg-[#381998] text-white'
                                : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50',
                        ]"
                        @click="addTab = 'applications'"
                    >
                        <FileCheck2 class="h-4 w-4" /> From applications
                        <span
                            v-if="available.length > 0"
                            :class="[
                                'rounded-full px-1.5 text-[11px]',
                                addTab === 'applications'
                                    ? 'bg-white/20'
                                    : 'bg-gray-100 dark:bg-gray-700',
                            ]"
                            >{{ available.length }}</span
                        >
                    </button>
                    <button
                        type="button"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                            addTab === 'manual'
                                ? 'bg-[#381998] text-white'
                                : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50',
                        ]"
                        @click="addTab = 'manual'"
                    >
                        <UserPlus class="h-4 w-4" /> Manually
                    </button>
                </div>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    @click="addOpen = false"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Tab: accepted applicants -->
            <div v-if="addTab === 'applications'" class="space-y-3 p-6">
                <p class="text-xs text-gray-500">
                    Accepted applicants not yet in any cohort, for the programs
                    this cohort runs. Filter by acceptance date to isolate a
                    batch — e.g. everyone accepted before cohorts existed.
                </p>

                <div
                    v-if="available.length === 0"
                    class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-600"
                >
                    No unassigned interns right now. Interns appear here once
                    their application is accepted (or auto-join if this is the
                    intake cohort).
                </div>
                <template v-else>
                    <div
                        class="space-y-3 rounded-xl bg-gray-50 p-3 dark:bg-gray-900/40"
                    >
                        <!-- Program picker: the primary way to add a whole program's batch at once -->
                        <div
                            v-if="availProgramCounts.length > 1"
                            class="flex flex-wrap items-center gap-1.5"
                        >
                            <button
                                type="button"
                                :class="[
                                    'rounded-full px-3 py-1 text-xs font-semibold transition-colors',
                                    availProgram === ''
                                        ? 'bg-[#381998] text-white'
                                        : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:ring-[#381998] dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600',
                                ]"
                                @click="availProgram = ''"
                            >
                                All
                                <span class="opacity-70"
                                    >· {{ available.length }}</span
                                >
                            </button>
                            <button
                                v-for="pc in availProgramCounts"
                                :key="pc.program"
                                type="button"
                                :class="[
                                    'rounded-full px-3 py-1 text-xs font-semibold transition-colors',
                                    availProgram === pc.program
                                        ? 'bg-[#381998] text-white'
                                        : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:ring-[#381998] dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600',
                                ]"
                                @click="availProgram = pc.program"
                            >
                                {{ pc.program }}
                                <span class="opacity-70">· {{ pc.count }}</span>
                            </button>
                        </div>

                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label
                                    class="mb-1 block text-xs font-semibold text-gray-500"
                                    >Search</label
                                >
                                <div class="relative">
                                    <Search
                                        class="pointer-events-none absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                                    />
                                    <input
                                        v-model="availSearch"
                                        type="text"
                                        placeholder="Name or email"
                                        :class="[fieldSm, 'w-48 pl-8']"
                                    />
                                </div>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-xs font-semibold text-gray-500"
                                    >Accepted from</label
                                >
                                <input
                                    v-model="acceptedFrom"
                                    type="date"
                                    :class="fieldSm"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-xs font-semibold text-gray-500"
                                    >Accepted to</label
                                >
                                <input
                                    v-model="acceptedTo"
                                    type="date"
                                    :class="fieldSm"
                                />
                            </div>
                            <button
                                v-if="availFilterActive"
                                type="button"
                                class="pb-2 text-xs font-semibold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                @click="clearAvailFilters"
                            >
                                Clear filters
                            </button>
                            <div class="ml-auto flex items-center gap-3 pb-1">
                                <span class="text-xs text-gray-400"
                                    >{{ availableFiltered.length }} of
                                    {{ available.length }} shown</span
                                >
                                <button
                                    v-if="availableFiltered.length > 0"
                                    type="button"
                                    :class="textBtn"
                                    @click="toggleSelectAllInterns"
                                >
                                    {{
                                        allVisibleSelected
                                            ? 'Clear all'
                                            : 'Select all'
                                    }}{{ availFilterActive ? ' shown' : '' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="availableFiltered.length === 0"
                        class="rounded-xl border border-gray-200 p-4 text-center text-sm text-gray-500 dark:border-gray-600"
                    >
                        No interns match these filters.
                    </div>
                    <div
                        v-else
                        class="max-h-72 space-y-1 overflow-y-auto rounded-xl border border-gray-200 p-2 dark:border-gray-600"
                    >
                        <label
                            v-for="a in availableFiltered"
                            :key="a.id"
                            class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        >
                            <input
                                type="checkbox"
                                :checked="selectedInterns.includes(a.id)"
                                class="accent-[#381998]"
                                @change="toggleSelectedIntern(a.id)"
                            />
                            <span class="min-w-0">
                                <span
                                    class="block truncate font-medium text-gray-800 dark:text-gray-100"
                                    >{{ a.name }}</span
                                >
                                <span
                                    class="block truncate text-xs text-gray-400"
                                    >{{ a.program }} · {{ a.email }}</span
                                >
                            </span>
                            <span
                                v-if="a.accepted_at"
                                class="ml-auto shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                            >
                                Accepted {{ a.accepted_at }}
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button
                            :disabled="selectedInterns.length === 0"
                            :class="primaryBtn"
                            @click="addSelectedInterns"
                        >
                            <UserPlus class="h-4 w-4" /> Add
                            {{ selectedInterns.length || '' }} intern{{
                                selectedInterns.length === 1 ? '' : 's'
                            }}
                        </button>
                    </div>
                </template>
            </div>

            <!-- Tab: manual -->
            <div v-else class="space-y-4 p-6">
                <p class="text-xs text-gray-500">
                    For someone who never submitted an application. Pick an
                    existing account, or create a new one — login credentials
                    are emailed automatically.
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">Program</label>
                        <select v-model="manualForm.program_id" :class="field">
                            <option value="">Select program…</option>
                            <option
                                v-for="p in cohort.programs"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.title }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label :class="label">Person</label>
                        <div
                            class="flex gap-1 rounded-xl border border-gray-200 p-1 dark:border-gray-600"
                        >
                            <button
                                type="button"
                                :class="[
                                    'flex-1 rounded-lg py-1.5 text-sm font-semibold transition-colors',
                                    manualMode === 'existing'
                                        ? 'bg-[#381998] text-white'
                                        : 'text-gray-600 dark:text-gray-300',
                                ]"
                                @click="manualMode = 'existing'"
                            >
                                Existing user
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'flex-1 rounded-lg py-1.5 text-sm font-semibold transition-colors',
                                    manualMode === 'new'
                                        ? 'bg-[#381998] text-white'
                                        : 'text-gray-600 dark:text-gray-300',
                                ]"
                                @click="manualMode = 'new'"
                            >
                                New person
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="manualMode === 'existing'">
                    <label :class="label">Search by name or email</label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            v-model="manualQuery"
                            type="text"
                            placeholder="Start typing a name or email…"
                            :class="[field, 'pl-9']"
                        />
                    </div>
                    <div
                        v-if="manualResults.length > 0"
                        class="mt-1 max-h-48 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-600"
                    >
                        <button
                            v-for="u in manualResults"
                            :key="u.id"
                            type="button"
                            class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            @click="pickManualUser(u)"
                        >
                            <span
                                class="font-medium text-gray-800 dark:text-gray-100"
                                >{{ u.name }}</span
                            >
                            <span class="text-xs text-gray-400">{{
                                u.email
                            }}</span>
                        </button>
                    </div>
                    <p
                        v-if="manualSearching"
                        class="mt-1 text-xs text-gray-400"
                    >
                        Searching…
                    </p>
                </div>

                <div v-else class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label :class="label">Full name</label>
                        <input
                            v-model="manualForm.name"
                            type="text"
                            :class="field"
                        />
                    </div>
                    <div>
                        <label :class="label">Email</label>
                        <input
                            v-model="manualForm.email"
                            type="email"
                            :class="field"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label :class="label"
                            >Phone
                            <span class="font-normal text-gray-400"
                                >(optional)</span
                            ></label
                        >
                        <input
                            v-model="manualForm.phone"
                            type="text"
                            :class="field"
                        />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        :disabled="!canSubmitManual"
                        :class="primaryBtn"
                        @click="submitManualIntern"
                    >
                        <UserPlus class="h-4 w-4" />
                        {{ manualForm.processing ? 'Adding…' : 'Add intern' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Roster -->
        <div
            class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-gray-700"
            >
                <h2 class="text-sm font-bold text-[#000928] dark:text-white">
                    Interns ({{ interns.length }})
                </h2>
                <div
                    v-if="interns.length > 0"
                    class="flex flex-wrap items-center gap-2"
                >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            v-model="rosterSearch"
                            type="text"
                            placeholder="Search name or email…"
                            :class="[fieldSm, 'w-48 pl-8']"
                        />
                    </div>
                    <select v-model="rosterProgram" :class="fieldSm">
                        <option value="">All programs</option>
                        <option
                            v-for="p in cohort.programs"
                            :key="p.id"
                            :value="p.title"
                        >
                            {{ p.title }}
                        </option>
                    </select>
                    <button
                        type="button"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors',
                            groupByProgram
                                ? 'border-[#381998] bg-[#381998]/10 text-[#381998] dark:bg-[#381998]/30 dark:text-[#b9a6f0]'
                                : 'border-gray-200 text-gray-600 hover:border-[#381998] hover:text-[#381998] dark:border-gray-600 dark:text-gray-300',
                        ]"
                        @click="groupByProgram = !groupByProgram"
                    >
                        <LayoutList class="h-3.5 w-3.5" /> Group by program
                    </button>
                </div>
            </div>

            <div v-if="interns.length === 0" class="p-10 text-center">
                <Users
                    class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600"
                />
                <p class="mt-2 text-sm text-gray-500">
                    No interns in this cohort yet.
                </p>
                <button
                    type="button"
                    class="mt-3 text-sm font-semibold text-[#381998] hover:underline"
                    @click="addOpen = true"
                >
                    Add your first interns
                </button>
            </div>
            <div
                v-else-if="rosterFiltered.length === 0"
                class="p-10 text-center text-sm text-gray-500"
            >
                No interns match your filters.
                <button
                    v-if="rosterFilterActive"
                    type="button"
                    class="ml-1 font-semibold text-[#381998] hover:underline"
                    @click="
                        rosterProgram = '';
                        rosterSearch = '';
                    "
                >
                    Clear
                </button>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="text-left text-xs tracking-wide text-gray-500 uppercase"
                    >
                        <tr>
                            <th class="px-6 py-3">Intern</th>
                            <th class="px-6 py-3">Program</th>
                            <th class="px-6 py-3">Supervisor (override)</th>
                            <th class="px-6 py-3">Working days</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-50 dark:divide-gray-700/50"
                    >
                        <template
                            v-for="(row, idx) in rosterRows"
                            :key="
                                row.type === 'intern'
                                    ? `i-${row.intern.id}`
                                    : `h-${row.program}-${idx}`
                            "
                        >
                            <tr
                                v-if="row.type === 'header'"
                                class="bg-gray-50 dark:bg-gray-900/40"
                            >
                                <td
                                    colspan="5"
                                    class="px-6 py-2 text-xs font-bold tracking-wide text-[#381998] uppercase dark:text-[#b9a6f0]"
                                >
                                    {{ row.program }}
                                    <span class="ml-1 font-medium text-gray-400"
                                        >· {{ row.count }}</span
                                    >
                                </td>
                            </tr>
                            <tr v-else>
                                <td class="px-6 py-3">
                                    <p
                                        class="font-semibold text-[#000928] dark:text-white"
                                    >
                                        {{ row.intern.name }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ row.intern.email }}
                                    </p>
                                </td>
                                <td
                                    class="px-6 py-3 text-gray-600 dark:text-gray-300"
                                >
                                    {{ row.intern.program }}
                                </td>
                                <td class="px-6 py-3">
                                    <select
                                        :value="
                                            row.intern
                                                .effective_supervisor_id ?? ''
                                        "
                                        :class="[fieldSm, 'text-xs']"
                                        @change="
                                            setInternSupervisor(
                                                row.intern.id,
                                                (
                                                    $event.target as HTMLSelectElement
                                                ).value,
                                            )
                                        "
                                    >
                                        <option value="">
                                            Use program supervisor
                                        </option>
                                        <option
                                            v-for="s in supervisors"
                                            :key="s.id"
                                            :value="s.id"
                                        >
                                            {{ s.name }}
                                        </option>
                                    </select>
                                </td>
                                <td class="relative px-6 py-3">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-gray-200 px-2 py-1 text-xs font-medium text-gray-600 hover:border-[#381998] hover:text-[#381998] dark:border-gray-600 dark:text-gray-300"
                                        @click="
                                            editingDaysFor === row.intern.id
                                                ? closeDaysEditor()
                                                : openDaysEditor(row.intern)
                                        "
                                    >
                                        <span
                                            v-for="v in [1, 2, 3, 4, 5, 6, 7]"
                                            :key="v"
                                            :class="
                                                row.intern.effective_working_days.includes(
                                                    v,
                                                )
                                                    ? 'font-bold text-[#381998] dark:text-[#b9a6f0]'
                                                    : 'text-gray-300 dark:text-gray-600'
                                            "
                                            >{{ DAY_LETTERS[v] }}</span
                                        >
                                        <span
                                            v-if="row.intern.working_days"
                                            class="ml-1 text-[10px] text-gray-400"
                                            >(override)</span
                                        >
                                    </button>
                                    <div
                                        v-if="editingDaysFor === row.intern.id"
                                        class="absolute top-full right-6 z-10 mt-1 w-56 rounded-xl border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-600 dark:bg-gray-800"
                                    >
                                        <div
                                            class="mb-2 flex flex-wrap gap-1.5"
                                        >
                                            <label
                                                v-for="d in WEEKDAYS"
                                                :key="d.value"
                                                class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-gray-200 px-2 py-1 text-[11px] font-medium dark:border-gray-600"
                                                :class="
                                                    editingDays.includes(
                                                        d.value,
                                                    )
                                                        ? 'border-[#381998]/40 bg-[#381998]/10 text-[#381998]'
                                                        : 'text-gray-500 dark:text-gray-300'
                                                "
                                            >
                                                <input
                                                    type="checkbox"
                                                    class="hidden"
                                                    :checked="
                                                        editingDays.includes(
                                                            d.value,
                                                        )
                                                    "
                                                    @change="
                                                        toggleDay(
                                                            editingDays,
                                                            d.value,
                                                        )
                                                    "
                                                />
                                                {{ d.label }}
                                            </label>
                                        </div>
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <button
                                                type="button"
                                                class="text-[11px] font-semibold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                                @click="
                                                    clearInternWorkingDays(
                                                        row.intern.id,
                                                    )
                                                "
                                            >
                                                Use cohort default
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg bg-[#381998] px-3 py-1 text-[11px] font-semibold text-white hover:bg-[#000928]"
                                                @click="
                                                    saveInternWorkingDays(
                                                        row.intern.id,
                                                    )
                                                "
                                            >
                                                Save
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-red-500 hover:underline"
                                        @click="removeIntern(row.intern.id)"
                                    >
                                        <UserMinus class="h-3.5 w-3.5" /> Remove
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
