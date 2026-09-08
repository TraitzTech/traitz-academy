<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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

interface AssignmentRow {
    id: number;
    title: string;
    instructions: string;
    audience: 'all_course_students' | 'selected_students';
    attachable: {
        type: AttachableType | null;
        id: number | null;
        title: string | null;
    };
    created_by: string | null;
    due_at: string | null;
    attachment_url: string | null;
    selected_students_count: number | null;
    created_at: string | null;
}

const props = defineProps<{
    courses: GroupRow[];
    cohorts: GroupRow[];
    programs: GroupRow[];
    assignments: AssignmentRow[];
    submitUrl: string;
}>();

const showCreateForm = ref(false);

const searchQuery = ref('');
const visibleCount = ref(10);
const PAGE_SIZE = 10;

const filteredAssignments = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.assignments;
    return props.assignments.filter(
        (a) =>
            a.title.toLowerCase().includes(q) ||
            (a.attachable.title ?? '').toLowerCase().includes(q),
    );
});

watch(searchQuery, () => {
    visibleCount.value = PAGE_SIZE;
});

const visibleAssignments = computed(() =>
    filteredAssignments.value.slice(0, visibleCount.value),
);
const hasMoreAssignments = computed(
    () => visibleCount.value < filteredAssignments.value.length,
);

function loadMoreAssignments() {
    visibleCount.value += PAGE_SIZE;
}

const typeLabels: Record<AttachableType, string> = {
    course: 'Course',
    cohort: 'Internship Cohort',
    program: 'Program',
};

const form = useForm({
    attachable_type: 'course' as AttachableType,
    attachable_id: '' as number | '',
    title: '',
    instructions: '',
    audience: 'all_course_students' as
        | 'all_course_students'
        | 'selected_students',
    student_ids: [] as number[],
    due_at: '',
    attachment: null as File | null,
});

const groupsForType = computed<GroupRow[]>(() => {
    if (form.attachable_type === 'cohort') return props.cohorts;
    if (form.attachable_type === 'program') return props.programs;
    return props.courses;
});

// Only offer a target type the user actually manages something in. Supervisors
// get programs only (no whole-cohort targeting); admins get all three.
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

// Cohorts span multiple programs — this narrows the checklist below to one
// program's interns without changing what the task is actually attached to
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
const allStudentsSelected = computed(() => {
    return (
        availableStudents.value.length > 0 &&
        form.student_ids.length === availableStudents.value.length
    );
});

watch(
    () => form.attachable_type,
    () => {
        form.attachable_id = '';
        form.student_ids = [];
        programFilter.value = '';
    },
);

watch(
    () => form.attachable_id,
    () => {
        form.student_ids = [];
        programFilter.value = '';
    },
);

watch(programFilter, () => {
    form.student_ids = [];
});

const canSubmit = computed(() => {
    if (!form.attachable_id || !form.title.trim() || !form.instructions.trim())
        return false;
    if (form.audience === 'selected_students' && form.student_ids.length === 0)
        return false;
    return true;
});

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    form.attachment = target.files?.[0] ?? null;
}

function submit() {
    form.post(props.submitUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.audience = 'all_course_students';
            form.attachable_type = availableTypes.value[0] ?? 'course';
            form.attachable_id = '';
            form.student_ids = [];
            programFilter.value = '';
            showCreateForm.value = false;
        },
        onError: (errors: Record<string, string>) => {
            const first = Object.values(errors)[0];
            if (first) toast.error(first);
        },
    });
}

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

function formatDateTime(value: string | null) {
    if (!value) return 'No due date';
    return new Date(value).toLocaleString();
}

function isOverdue(value: string | null) {
    if (!value) return false;
    return new Date(value).getTime() < Date.now();
}
</script>

<template>
    <div class="space-y-6">
        <div
            class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Tasks
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Send tasks to a course, an internship cohort, or a program.
                </p>
            </div>
            <button
                type="button"
                class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white"
                @click="showCreateForm = !showCreateForm"
            >
                {{ showCreateForm ? 'Cancel' : '+ Create task' }}
            </button>
        </div>

        <form
            v-if="showCreateForm"
            @submit.prevent="submit"
            class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Create task
            </h2>

            <div
                class="grid gap-4"
                :class="availableTypes.length > 1 ? 'md:grid-cols-2' : ''"
            >
                <div v-if="availableTypes.length > 1">
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
                <div>
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
            </div>

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
                        >Due date (optional)</label
                    >
                    <input
                        v-model="form.due_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    />
                </div>
            </div>

            <div>
                <label
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Instructions</label
                >
                <textarea
                    v-model="form.instructions"
                    rows="5"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    placeholder="Describe what learners should do, submit, or revise."
                />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Audience</label
                    >
                    <select
                        v-model="form.audience"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    >
                        <option value="all_course_students">
                            Everyone in the selected
                            {{ typeLabels[form.attachable_type].toLowerCase() }}
                        </option>
                        <option value="selected_students">
                            Selected students only
                        </option>
                    </select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Attachment (optional)</label
                    >
                    <input
                        type="file"
                        @change="onFileChange"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    />
                </div>
            </div>

            <div v-if="form.audience === 'selected_students'">
                <div v-if="cohortPrograms.length > 0" class="mb-3">
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Filter by program
                        <span class="font-normal text-gray-400"
                            >(optional — narrows the list below only)</span
                        ></label
                    >
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
                <div class="mb-1 flex items-center justify-between">
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Select students</label
                    >
                    <button
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
                        Select a group with enrolled/assigned students first.
                    </p>
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing || !canSubmit"
                    class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ form.processing ? 'Sending...' : 'Send task' }}
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
                    Recent tasks
                    <span class="text-sm font-normal text-gray-400"
                        >({{ filteredAssignments.length }})</span
                    >
                </h2>
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Search tasks..."
                    class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900"
                />
            </div>
            <div
                v-if="assignments.length === 0"
                class="p-6 text-sm text-gray-500 dark:text-gray-400"
            >
                No tasks created yet.
            </div>
            <div
                v-else-if="filteredAssignments.length === 0"
                class="p-6 text-sm text-gray-500 dark:text-gray-400"
            >
                No tasks match "{{ searchQuery }}".
            </div>
            <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                <div
                    v-for="assignment in visibleAssignments"
                    :key="assignment.id"
                    class="flex flex-wrap items-start justify-between gap-4 p-6 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3
                                class="font-semibold text-gray-900 dark:text-gray-100"
                            >
                                {{ assignment.title }}
                            </h3>
                            <span
                                v-if="assignment.attachable.type"
                                class="rounded-full bg-[#42b6c5]/10 px-2 py-0.5 text-xs font-semibold text-[#2a8a96]"
                            >
                                {{ typeLabels[assignment.attachable.type] }}
                            </span>
                            <span
                                v-if="isOverdue(assignment.due_at)"
                                class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600 dark:bg-red-900/30 dark:text-red-400"
                            >
                                Overdue
                            </span>
                        </div>
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ assignment.attachable.title }}
                        </p>
                        <p
                            class="mt-2 line-clamp-3 text-sm text-gray-700 dark:text-gray-300"
                        >
                            {{ assignment.instructions }}
                        </p>

                        <a
                            v-if="assignment.attachment_url"
                            :href="assignment.attachment_url"
                            target="_blank"
                            class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-[#381998] hover:underline"
                        >
                            📎 View attachment
                        </a>
                    </div>

                    <div
                        class="flex shrink-0 flex-col items-end gap-1.5 text-xs text-gray-500 dark:text-gray-400"
                    >
                        <div
                            class="flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                        >
                            🗓️ Due {{ formatDateTime(assignment.due_at) }}
                        </div>
                        <div>
                            {{
                                assignment.audience === 'all_course_students'
                                    ? 'Everyone in group'
                                    : `${assignment.selected_students_count ?? 0} selected students`
                            }}
                        </div>
                        <div class="text-gray-400 dark:text-gray-500">
                            Created {{ formatDateTime(assignment.created_at) }}
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="hasMoreAssignments"
                class="border-t border-gray-100 p-4 text-center dark:border-gray-700"
            >
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-1.5 text-sm font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-300"
                    @click="loadMoreAssignments"
                >
                    Load more ({{
                        filteredAssignments.length - visibleCount
                    }}
                    remaining)
                </button>
            </div>
        </div>
    </div>
</template>
