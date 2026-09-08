<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import {
    Check,
    Pencil,
    Search,
    Trash2,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';

interface CourseOption {
    id: number;
    title: string;
    instructor?: { id: number; name: string } | null;
}

interface TutorOption {
    id: number;
    name: string;
}

interface UserBrief {
    id: number;
    name: string;
    email: string;
}

interface StudentOption {
    id: number;
    name: string;
    email: string;
}

interface Enrollment {
    id: number;
    user: UserBrief | null;
    course:
        | (CourseOption & { instructor?: { id: number; name: string } | null })
        | null;
    access_status: string;
    payment_type: string;
    progress: number;
    enrolled_at: string | null;
}

interface Paginated<T> {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    enrollments: Paginated<Enrollment>;
    courses: CourseOption[];
    tutors: TutorOption[];
    students: StudentOption[];
    filters: {
        search?: string | null;
        course?: number | null;
        tutor?: number | null;
        status?: string | null;
    };
}>();

defineOptions({ layout: AppLayout });

const search = ref(props.filters.search ?? '');
const courseId = ref(props.filters.course ? String(props.filters.course) : '');
const tutorId = ref(props.filters.tutor ? String(props.filters.tutor) : '');
const status = ref(props.filters.status ?? '');

const applyFilters = debounce(() => {
    router.get(
        '/admin/enrollments',
        {
            search: search.value.trim() || undefined,
            course: courseId.value || undefined,
            tutor: tutorId.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}, 300);

watch(search, applyFilters);
watch([courseId, tutorId, status], applyFilters);

const accessColors: Record<string, string> = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    completed:
        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    suspended:
        'bg-amber-100 text-amber-900 dark:bg-amber-900/30 dark:text-amber-300',
    revoked: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
};

function accessLabel(s: string) {
    const m: Record<string, string> = {
        active: 'Active',
        completed: 'Completed',
        suspended: 'Suspended',
        revoked: 'Revoked',
    };
    return m[s] ?? s;
}

function formatWhen(iso: string | null) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return iso;
    }
}

function paymentLabel(t: string | null | undefined) {
    if (!t) return '—';
    return t.replace(/_/g, ' ');
}

// ─── Edit / Delete ───────────────────────────────────────────────────────────

const editOpen = ref(false);
const deleteOpen = ref(false);
const editing = ref<Enrollment | null>(null);
const deleteTarget = ref<Enrollment | null>(null);

const form = useForm({
    access_status: 'active',
    progress: 0,
});

function openEdit(row: Enrollment) {
    editing.value = row;
    form.access_status = row.access_status;
    form.progress = row.progress;
    form.clearErrors();
    editOpen.value = true;
}

function closeEdit() {
    editOpen.value = false;
    editing.value = null;
    form.reset();
}

function submitEdit() {
    if (!editing.value) return;
    form.patch(`/admin/enrollments/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
}

function openDelete(row: Enrollment) {
    deleteTarget.value = row;
    deleteOpen.value = true;
}

function confirmDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/admin/enrollments/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteOpen.value = false;
            deleteTarget.value = null;
        },
    });
}

// ─── Enrol a student ─────────────────────────────────────────────────────────

const enrollOpen = ref(false);
const enrollForm = useForm({
    course_id: '',
    email: '',
});

// --- Student search combobox state ---
const studentSearchQuery = ref('');
const studentSearchOpen = ref(false);
const studentSearchRef = ref<HTMLElement | null>(null);
const studentInputRef = ref<HTMLInputElement | null>(null);
const studentHighlightedIndex = ref(-1);

const filteredStudents = computed(() => {
    const q = studentSearchQuery.value.trim().toLowerCase();
    if (!q) return props.students;
    return props.students.filter((s) => {
        return (
            s.name.toLowerCase().includes(q) ||
            s.email.toLowerCase().includes(q)
        );
    });
});

function selectStudent(student: StudentOption) {
    enrollForm.email = student.email;
    studentSearchQuery.value = `${student.name} (${student.email})`;
    studentSearchOpen.value = false;
    studentHighlightedIndex.value = -1;
}

function clearStudent() {
    enrollForm.email = '';
    studentSearchQuery.value = '';
    studentSearchOpen.value = false;
    studentHighlightedIndex.value = -1;
    nextTick(() => studentInputRef.value?.focus());
}

function onStudentInputFocus() {
    studentSearchOpen.value = true;
    if (enrollForm.email) {
        studentSearchQuery.value = '';
    }
    studentHighlightedIndex.value = -1;
}

function onStudentInputBlur() {
    // Delay to allow click on dropdown item
    setTimeout(() => {
        studentSearchOpen.value = false;
        const current = props.students.find(
            (s) => s.email === enrollForm.email,
        );
        if (current) {
            studentSearchQuery.value = `${current.name} (${current.email})`;
        }
    }, 150);
}

function onStudentKeydown(e: KeyboardEvent) {
    if (!studentSearchOpen.value) {
        if (e.key === 'ArrowDown' || e.key === 'Enter') {
            studentSearchOpen.value = true;
        }
        return;
    }
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        studentHighlightedIndex.value = Math.min(
            studentHighlightedIndex.value + 1,
            filteredStudents.value.length - 1,
        );
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        studentHighlightedIndex.value = Math.max(
            studentHighlightedIndex.value - 1,
            0,
        );
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (
            studentHighlightedIndex.value >= 0 &&
            filteredStudents.value[studentHighlightedIndex.value]
        ) {
            selectStudent(
                filteredStudents.value[studentHighlightedIndex.value],
            );
        }
    } else if (e.key === 'Escape') {
        studentSearchOpen.value = false;
    }
}
// --- End combobox state ---

function openEnroll() {
    enrollForm.reset();
    enrollForm.clearErrors();
    studentSearchQuery.value = '';
    studentSearchOpen.value = false;
    studentHighlightedIndex.value = -1;
    enrollOpen.value = true;
}

function closeEnroll() {
    enrollOpen.value = false;
    enrollForm.reset();
    enrollForm.clearErrors();
    studentSearchQuery.value = '';
    studentSearchOpen.value = false;
    studentHighlightedIndex.value = -1;
}

function submitEnroll() {
    if (!enrollForm.course_id || !enrollForm.email) return;
    enrollForm.post(`/admin/courses/${enrollForm.course_id}/enroll-student`, {
        preserveScroll: true,
        onSuccess: () => closeEnroll(),
    });
}
</script>

<template>
    <div>
        <Head title="LMS Enrollments — Admin" />

        <div
            class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    LMS enrollments
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    All online course enrollments ({{
                        enrollments.total
                    }}
                    record{{ enrollments.total === 1 ? '' : 's' }})
                </p>
            </div>
            <Button
                type="button"
                class="gap-2 bg-[#381998] hover:bg-[#2b1275]"
                @click="openEnroll"
            >
                <UserPlus class="h-4 w-4" />
                Enrol a student
            </Button>
        </div>

        <div
            class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between"
        >
            <div class="relative max-w-md flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search student name or email…"
                    class="w-full rounded-lg border border-gray-300 py-2 pr-3 pl-10 text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                />
            </div>
            <div class="flex flex-wrap gap-2">
                <select
                    v-model="tutorId"
                    class="max-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                >
                    <option value="">All tutors</option>
                    <option
                        v-for="t in tutors"
                        :key="t.id"
                        :value="String(t.id)"
                    >
                        {{ t.name }}
                    </option>
                </select>
                <select
                    v-model="courseId"
                    class="max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                >
                    <option value="">All courses</option>
                    <option
                        v-for="c in courses"
                        :key="c.id"
                        :value="String(c.id)"
                    >
                        {{ c.title }}
                    </option>
                </select>
                <select
                    v-model="status"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="suspended">Suspended</option>
                    <option value="revoked">Revoked</option>
                </select>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-xl bg-white shadow dark:bg-gray-800"
        >
            <div
                v-if="enrollments.data.length === 0"
                class="flex flex-col items-center justify-center py-16 text-center"
            >
                <Users class="mb-3 h-12 w-12 text-gray-300" />
                <p class="font-medium text-gray-500 dark:text-gray-400">
                    No enrollments match your filters
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead
                        class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40"
                    >
                        <tr>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Student
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Course
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Tutor
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Status
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Payment
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Progress
                            </th>
                            <th
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Enrolled
                            </th>
                            <th
                                class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-100 dark:divide-gray-700"
                    >
                        <tr
                            v-for="row in enrollments.data"
                            :key="row.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30"
                        >
                            <td class="px-4 py-3">
                                <p
                                    class="font-medium text-gray-900 dark:text-gray-100"
                                >
                                    {{ row.user?.name ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ row.user?.email ?? '' }}
                                </p>
                            </td>
                            <td class="max-w-[200px] px-4 py-3">
                                <p
                                    class="truncate text-gray-800 dark:text-gray-200"
                                >
                                    {{ row.course?.title ?? '—' }}
                                </p>
                            </td>
                            <td
                                class="px-4 py-3 text-gray-700 dark:text-gray-300"
                            >
                                {{ row.course?.instructor?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        accessColors[row.access_status] ??
                                            'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{ accessLabel(row.access_status) }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-gray-600 capitalize dark:text-gray-400"
                            >
                                {{ paymentLabel(row.payment_type) }}
                            </td>
                            <td
                                class="px-4 py-3 text-gray-700 dark:text-gray-300"
                            >
                                {{ row.progress }}%
                            </td>
                            <td
                                class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400"
                            >
                                {{ formatWhen(row.enrolled_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-[#381998]/10 hover:text-[#381998] dark:hover:bg-purple-900/20 dark:hover:text-purple-300"
                                        title="Edit enrollment"
                                        @click="openEdit(row)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                        title="Delete enrollment"
                                        @click="openDelete(row)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="enrollments.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3 dark:border-gray-700"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <template
                        v-if="
                            enrollments.from != null && enrollments.to != null
                        "
                    >
                        Showing {{ enrollments.from }}–{{ enrollments.to }} of
                        {{ enrollments.total }}
                    </template>
                </p>
                <div class="flex flex-wrap gap-1">
                    <template
                        v-for="link in enrollments.links"
                        :key="link.label"
                    >
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

        <!-- Edit enrollment -->
        <Dialog :open="editOpen" @update:open="(v) => !v && closeEdit()">
            <DialogContent
                class="border border-gray-200 bg-white sm:max-w-md dark:border-gray-700 dark:bg-gray-900"
            >
                <DialogHeader>
                    <DialogTitle class="text-gray-900 dark:text-gray-100"
                        >Edit enrollment</DialogTitle
                    >
                    <DialogDescription class="text-gray-600 dark:text-gray-400">
                        <template v-if="editing">
                            {{ editing.user?.name }} ·
                            {{ editing.course?.title }}
                        </template>
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Access status</label
                        >
                        <select
                            v-model="form.access_status"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="suspended">Suspended</option>
                            <option value="revoked">Revoked</option>
                        </select>
                        <p
                            v-if="form.errors.access_status"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.access_status }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Progress (%)</label
                        >
                        <input
                            v-model.number="form.progress"
                            type="number"
                            min="0"
                            max="100"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.progress"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.progress }}
                        </p>
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:justify-end">
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeEdit"
                            >Cancel</Button
                        >
                    </DialogClose>
                    <Button
                        type="button"
                        :disabled="form.processing"
                        @click="submitEdit"
                    >
                        {{ form.processing ? 'Saving…' : 'Save' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Enrol a student -->
        <Dialog :open="enrollOpen" @update:open="(v) => !v && closeEnroll()">
            <DialogContent
                class="border border-gray-200 bg-white sm:max-w-md dark:border-gray-700 dark:bg-gray-900"
            >
                <DialogHeader>
                    <DialogTitle class="text-gray-900 dark:text-gray-100"
                        >Enrol a student</DialogTitle
                    >
                    <DialogDescription class="text-gray-600 dark:text-gray-400">
                        Grant course access to a registered student.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Course</label
                        >
                        <select
                            v-model="enrollForm.course_id"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="" disabled>Select a course…</option>
                            <option
                                v-for="c in courses"
                                :key="c.id"
                                :value="String(c.id)"
                            >
                                {{ c.title }}
                            </option>
                        </select>
                        <p
                            v-if="enrollForm.errors.course_id"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ enrollForm.errors.course_id }}
                        </p>
                    </div>

                    <!-- Student search combobox -->
                    <div ref="studentSearchRef">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Student</label
                        >
                        <div class="relative">
                            <div class="relative flex items-center">
                                <Search
                                    class="pointer-events-none absolute left-3 h-4 w-4 text-gray-400 dark:text-gray-500"
                                />
                                <input
                                    ref="studentInputRef"
                                    v-model="studentSearchQuery"
                                    type="text"
                                    autocomplete="off"
                                    :placeholder="
                                        enrollForm.email
                                            ? 'Type to search another…'
                                            : 'Search by name or email…'
                                    "
                                    class="w-full rounded-lg border py-2 pr-8 pl-9 text-sm transition focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                    :class="[
                                        enrollForm.errors.email
                                            ? 'border-red-400 dark:border-red-500'
                                            : enrollForm.email
                                              ? 'border-[#42b6c5] dark:border-[#42b6c5]'
                                              : 'border-gray-300 dark:border-gray-600',
                                        'bg-white text-gray-900 placeholder:text-gray-400 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500',
                                    ]"
                                    @focus="onStudentInputFocus"
                                    @blur="onStudentInputBlur"
                                    @keydown="onStudentKeydown"
                                />
                                <button
                                    v-if="enrollForm.email"
                                    type="button"
                                    tabindex="-1"
                                    class="absolute right-2 rounded p-0.5 text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-200"
                                    title="Clear selection"
                                    @mousedown.prevent="clearStudent"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>

                            <div
                                v-if="enrollForm.email && !studentSearchOpen"
                                class="mt-1.5 flex items-center gap-1.5"
                            >
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-cyan-200 bg-cyan-50 px-2 py-0.5 text-xs text-cyan-700 dark:border-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300"
                                >
                                    <Check class="h-3 w-3" />
                                    {{ enrollForm.email }} selected
                                </span>
                            </div>

                            <div
                                v-if="studentSearchOpen"
                                class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"
                            >
                                <div
                                    v-if="filteredStudents.length === 0"
                                    class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    No registered students match your search.
                                </div>
                                <button
                                    v-for="(student, index) in filteredStudents"
                                    :key="student.id"
                                    type="button"
                                    class="flex w-full flex-col gap-0.5 px-4 py-2.5 text-left text-sm transition-colors"
                                    :class="[
                                        index === studentHighlightedIndex
                                            ? 'bg-[#42b6c5]/10 text-gray-900 dark:bg-[#42b6c5]/20 dark:text-gray-100'
                                            : 'text-gray-800 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/50',
                                        student.email === enrollForm.email
                                            ? 'bg-cyan-50 dark:bg-cyan-900/20'
                                            : '',
                                    ]"
                                    @mousedown.prevent="selectStudent(student)"
                                >
                                    <span class="truncate font-medium">{{
                                        student.name
                                    }}</span>
                                    <span
                                        class="truncate text-xs text-gray-400 dark:text-gray-500"
                                        >{{ student.email }}</span
                                    >
                                </button>
                            </div>
                        </div>
                        <p
                            v-if="enrollForm.errors.email"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ enrollForm.errors.email }}
                        </p>
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:justify-end">
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeEnroll"
                            >Cancel</Button
                        >
                    </DialogClose>
                    <Button
                        type="button"
                        :disabled="
                            enrollForm.processing ||
                            !enrollForm.course_id ||
                            !enrollForm.email
                        "
                        @click="submitEnroll"
                    >
                        {{
                            enrollForm.processing
                                ? 'Enrolling…'
                                : 'Grant access'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <ConfirmationModal
            :open="deleteOpen"
            title="Delete enrollment"
            :description="
                deleteTarget
                    ? `Remove ${deleteTarget.user?.name ?? 'this learner'} from &quot;${deleteTarget.course?.title ?? 'course'}&quot;? Related progress and payment records may be removed.`
                    : ''
            "
            confirm-text="Delete"
            cancel-text="Cancel"
            variant="destructive"
            @update:open="
                (v) => {
                    if (!v) {
                        deleteOpen = false;
                        deleteTarget = null;
                    }
                }
            "
            @confirm="confirmDelete"
        />
    </div>
</template>
