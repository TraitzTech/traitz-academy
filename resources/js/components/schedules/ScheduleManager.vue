<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import { useToast } from '@/composables/useToast';

const toast = useToast();

interface GroupStudent {
    id: number;
    name: string;
    email: string;
    program_id?: number | null;
}

interface GroupProgram {
    id: number;
    title: string;
}

interface GroupRow {
    id: number;
    title: string;
    student_count: number;
    students: GroupStudent[];
    programs?: GroupProgram[];
}

type AttachableType = 'course' | 'cohort' | 'program';

interface ScheduleRow {
    id: number;
    title: string;
    description: string | null;
    audience: 'all_students' | 'course_students' | 'selected_students';
    location: string | null;
    starts_at: string | null;
    ends_at: string | null;
    created_at: string | null;
    created_by: string | null;
    attachable: {
        type: AttachableType | null;
        id: number | null;
        title: string | null;
    };
    selected_students_count: number | null;
}

const props = defineProps<{
    courses: GroupRow[];
    cohorts: GroupRow[];
    programs: GroupRow[];
    schedules: ScheduleRow[];
    submitUrl: string;
    mode: 'admin' | 'tutor';
}>();

const showForm = ref(false);

const searchQuery = ref('');
const visibleCount = ref(10);
const PAGE_SIZE = 10;

const filteredSchedules = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.schedules;
    return props.schedules.filter(
        (row) =>
            row.title.toLowerCase().includes(q) ||
            (row.attachable.title ?? '').toLowerCase().includes(q),
    );
});

watch(searchQuery, () => {
    visibleCount.value = PAGE_SIZE;
});

const visibleSchedules = computed(() =>
    filteredSchedules.value.slice(0, visibleCount.value),
);
const hasMoreSchedules = computed(
    () => visibleCount.value < filteredSchedules.value.length,
);

function loadMoreSchedules() {
    visibleCount.value += PAGE_SIZE;
}

const typeLabels: Record<AttachableType, string> = {
    course: 'Course',
    cohort: 'Internship Cohort',
    program: 'Program',
};

const form = useForm({
    id: null as number | null,
    title: '',
    description: '',
    attachable_type: 'course' as AttachableType,
    attachable_id: '' as number | '',
    audience: (props.mode === 'admin' ? 'all_students' : 'course_students') as
        | 'all_students'
        | 'course_students'
        | 'selected_students',
    student_ids: [] as number[],
    location: '',
    starts_at: '',
    ends_at: '',
});

const requiresGroup = computed(() => form.audience !== 'all_students');
const requiresStudents = computed(() => form.audience === 'selected_students');
const groupsForType = computed<GroupRow[]>(() => {
    if (form.attachable_type === 'cohort') return props.cohorts;
    if (form.attachable_type === 'program') return props.programs;
    return props.courses;
});

// Only offer a target type the user actually manages something in.
const availableTypes = computed<AttachableType[]>(() => {
    const types: AttachableType[] = [];
    if (props.courses.length) types.push('course');
    if (props.cohorts.length) types.push('cohort');
    if (props.programs.length) types.push('program');
    return types;
});

watch(
    availableTypes,
    (types) => {
        if (types.length && !types.includes(form.attachable_type)) {
            form.attachable_type = types[0];
        }
    },
    { immediate: true },
);
const selectedGroup = computed(
    () =>
        groupsForType.value.find((g) => g.id === Number(form.attachable_id)) ??
        null,
);

// Cohorts span multiple programs — this narrows the list below to one
// program's interns without changing what the schedule is attached to
// (still the whole cohort).
const programFilter = ref<number | ''>('');
const cohortPrograms = computed(() =>
    form.attachable_type === 'cohort'
        ? (selectedGroup.value?.programs ?? [])
        : [],
);

const availableStudents = computed(() => {
    const students = selectedGroup.value?.students ?? [];
    if (form.attachable_type === 'cohort' && programFilter.value) {
        return students.filter((s) => s.program_id === programFilter.value);
    }
    return students;
});
const editingId = computed(() => form.id);

const allStudentsSelected = computed(() => {
    return (
        availableStudents.value.length > 0 &&
        form.student_ids.length === availableStudents.value.length
    );
});

function toggleStudent(studentId: number) {
    if (form.student_ids.includes(studentId)) {
        form.student_ids = form.student_ids.filter(
            (id: number) => id !== studentId,
        );
        return;
    }

    form.student_ids = [...form.student_ids, studentId];
}

function toggleAllStudents() {
    if (allStudentsSelected.value) {
        form.student_ids = [];
        return;
    }

    form.student_ids = availableStudents.value.map(
        (student: GroupStudent) => student.id,
    );
}

watch(
    () => form.attachable_type,
    () => {
        form.attachable_id = '';
        programFilter.value = '';
    },
);

watch(
    () => form.attachable_id,
    () => {
        programFilter.value = '';
    },
);

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.id = null;
            form.audience =
                props.mode === 'admin' ? 'all_students' : 'course_students';
            form.attachable_type = availableTypes.value[0] ?? 'course';
            form.attachable_id = '';
            form.student_ids = [];
            programFilter.value = '';
            showForm.value = false;
        },
        onError: (errors: Record<string, string>) => {
            const first = Object.values(errors)[0];
            if (first) toast.error(first);
        },
    };

    if (editingId.value) {
        form.put(`${props.submitUrl}/${editingId.value}`, options);
    } else {
        form.post(props.submitUrl, options);
    }
}

function openCreateForm() {
    form.reset();
    form.id = null;
    form.audience = props.mode === 'admin' ? 'all_students' : 'course_students';
    form.attachable_type = availableTypes.value[0] ?? 'course';
    form.attachable_id = '';
    form.student_ids = [];
    programFilter.value = '';
    showForm.value = true;
}

function editSchedule(row: ScheduleRow) {
    form.id = row.id;
    form.title = row.title;
    form.description = row.description ?? '';
    form.attachable_type = row.attachable.type ?? 'course';
    form.attachable_id = row.attachable.id ?? '';
    form.audience = row.audience;
    form.location = row.location ?? '';
    form.starts_at = row.starts_at ? row.starts_at.slice(0, 16) : '';
    form.ends_at = row.ends_at ? row.ends_at.slice(0, 16) : '';
    form.student_ids = [];
    showForm.value = true;
}

function deleteSchedule(row: ScheduleRow) {
    if (!confirm(`Delete schedule "${row.title}"?`)) return;
    router.delete(`${props.submitUrl}/${row.id}`, { preserveScroll: true });
}

function formatDate(value: string | null) {
    if (!value) return 'N/A';
    return new Date(value).toLocaleString();
}

function audienceLabel(value: ScheduleRow['audience']) {
    if (value === 'all_students') return 'All students';
    if (value === 'course_students') return 'Everyone in group';
    return 'Selected students';
}
</script>

<template>
    <div class="space-y-6">
        <div
            class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Schedule
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Create and publish schedule entries for a course, internship
                    cohort, or program.
                </p>
            </div>
            <button
                v-if="!showForm"
                type="button"
                class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white"
                @click="openCreateForm"
            >
                + Create schedule item
            </button>
            <button
                v-else
                type="button"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-300"
                @click="showForm = false"
            >
                Cancel
            </button>
        </div>

        <form
            v-if="showForm"
            @submit.prevent="submit"
            class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ editingId ? 'Edit schedule item' : 'Create schedule item' }}
            </h2>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Title</label
                    >
                    <input
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Location (optional)</label
                    >
                    <input
                        v-model="form.location"
                        type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    />
                </div>
            </div>

            <div>
                <label
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Description (optional)</label
                >
                <textarea
                    v-model="form.description"
                    rows="3"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Starts at</label
                    >
                    <input
                        v-model="form.starts_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Ends at (optional)</label
                    >
                    <input
                        v-model="form.ends_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div v-if="requiresGroup && availableTypes.length > 1">
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Type</label
                    >
                    <select
                        v-model="form.attachable_type"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    >
                        <option
                            v-for="type in availableTypes"
                            :key="type"
                            :value="type"
                        >
                            {{ typeLabels[type] }}
                        </option>
                    </select>
                </div>
                <div v-if="requiresGroup">
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >{{ typeLabels[form.attachable_type] }}</label
                    >
                    <select
                        v-model="form.attachable_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    >
                        <option value="">
                            Select
                            {{ typeLabels[form.attachable_type].toLowerCase() }}
                        </option>
                        <option
                            v-for="group in groupsForType"
                            :key="group.id"
                            :value="group.id"
                        >
                            {{ group.title }} ({{
                                group.student_count
                            }}
                            students)
                        </option>
                    </select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Audience</label
                    >
                    <select
                        v-model="form.audience"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    >
                        <option v-if="mode === 'admin'" value="all_students">
                            All students
                        </option>
                        <option value="course_students">
                            Everyone in a group
                        </option>
                        <option value="selected_students">
                            Selected students in a group
                        </option>
                    </select>
                </div>
            </div>

            <div v-if="requiresStudents && cohortPrograms.length > 0">
                <label
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    Filter by program
                    <span class="font-normal text-gray-400"
                        >(optional — narrows the list below only)</span
                    >
                </label>
                <select
                    v-model="programFilter"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                >
                    <option value="">All programs in this cohort</option>
                    <option
                        v-for="p in cohortPrograms"
                        :key="p.id"
                        :value="p.id"
                    >
                        {{ p.title }}
                    </option>
                </select>
            </div>

            <div v-if="requiresStudents">
                <div class="mb-1 flex items-center justify-between">
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Select students</label
                    >
                    <button
                        v-if="availableStudents.length > 0"
                        type="button"
                        class="text-xs font-semibold text-[#381998]"
                        @click="toggleAllStudents"
                    >
                        {{ allStudentsSelected ? 'Clear all' : 'Select all' }}
                    </button>
                </div>
                <div
                    class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                >
                    <label
                        v-for="student in availableStudents"
                        :key="student.id"
                        class="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <input
                            type="checkbox"
                            :checked="form.student_ids.includes(student.id)"
                            class="mt-0.5"
                            @change="toggleStudent(student.id)"
                        />
                        <span>{{ student.name }} ({{ student.email }})</span>
                    </label>
                    <p
                        v-if="availableStudents.length === 0"
                        class="text-xs text-gray-500 dark:text-gray-400"
                    >
                        Select a
                        {{
                            typeLabels[form.attachable_type].toLowerCase()
                        }}
                        above first to see its students.
                    </p>
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                >
                    {{
                        form.processing
                            ? 'Saving...'
                            : editingId
                              ? 'Update schedule item'
                              : 'Create schedule item'
                    }}
                </button>
            </div>
        </form>

        <div
            class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-gray-700"
            >
                <h2
                    class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Recent schedule items
                    <span class="text-sm font-normal text-gray-400"
                        >({{ filteredSchedules.length }})</span
                    >
                </h2>
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Search schedule items..."
                    class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900"
                />
            </div>
            <div
                v-if="schedules.length === 0"
                class="p-6 text-sm text-gray-500 dark:text-gray-400"
            >
                No schedule items yet.
            </div>
            <div
                v-else-if="filteredSchedules.length === 0"
                class="p-6 text-sm text-gray-500 dark:text-gray-400"
            >
                No schedule items match "{{ searchQuery }}".
            </div>
            <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                <div v-for="row in visibleSchedules" :key="row.id" class="p-6">
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <h3
                                class="font-semibold text-gray-900 dark:text-gray-100"
                            >
                                {{ row.title }}
                            </h3>
                            <p
                                class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ audienceLabel(row.audience)
                                }}<span v-if="row.attachable.title">
                                    • {{ row.attachable.title }}</span
                                >
                            </p>
                            <p
                                v-if="row.description"
                                class="mt-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                {{ row.description }}
                            </p>
                        </div>
                        <div
                            class="text-right text-xs text-gray-500 dark:text-gray-400"
                        >
                            <div>Starts: {{ formatDate(row.starts_at) }}</div>
                            <div v-if="row.ends_at" class="mt-1">
                                Ends: {{ formatDate(row.ends_at) }}
                            </div>
                            <div v-if="row.location" class="mt-1">
                                Location: {{ row.location }}
                            </div>
                            <div class="mt-3 flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="rounded border border-[#381998] px-2 py-1 text-[11px] font-semibold text-[#381998]"
                                    @click="editSchedule(row)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="rounded border border-red-300 px-2 py-1 text-[11px] font-semibold text-red-600"
                                    @click="deleteSchedule(row)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="hasMoreSchedules"
                class="border-t border-gray-100 p-4 text-center dark:border-gray-700"
            >
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-1.5 text-sm font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-300"
                    @click="loadMoreSchedules"
                >
                    Load more ({{
                        filteredSchedules.length - visibleCount
                    }}
                    remaining)
                </button>
            </div>
        </div>
    </div>
</template>
