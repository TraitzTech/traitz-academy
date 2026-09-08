<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    GripVertical,
    PlusCircle,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
const toast = useToast();

interface Question {
    question: string;
    type: 'text' | 'multiple_choice';
    options: string[];
    required: boolean;
}

interface FeedbackFormData {
    id: number;
    title: string;
    description: string | null;
    is_active: boolean;
    allow_anonymous: boolean;
    send_thank_you_email: boolean;
    closes_at: string | null;
    questions: Array<{
        id: number;
        question: string;
        type: 'text' | 'multiple_choice';
        options: string[] | null;
        required: boolean;
        sort_order: number;
    }>;
}

const props = defineProps<{ form: FeedbackFormData }>();

const editForm = useForm({
    title: props.form.title,
    description: props.form.description ?? '',
    is_active: props.form.is_active,
    allow_anonymous: props.form.allow_anonymous,
    send_thank_you_email: props.form.send_thank_you_email,
    closes_at: props.form.closes_at ?? '',
    questions: props.form.questions.map((q) => ({
        question: q.question,
        type: q.type,
        options: q.options ?? [],
        required: q.required,
    })) as Question[],
});

const expandedQuestions = ref<number[]>(props.form.questions.map((_, i) => i));

const toggleQuestion = (index: number) => {
    const pos = expandedQuestions.value.indexOf(index);
    if (pos >= 0) {
        expandedQuestions.value.splice(pos, 1);
    } else {
        expandedQuestions.value.push(index);
    }
};

const addQuestion = () => {
    editForm.questions.push({
        question: '',
        type: 'text',
        options: [],
        required: true,
    });
    expandedQuestions.value.push(editForm.questions.length - 1);
};

const removeQuestion = (index: number) => {
    if (editForm.questions.length <= 1) {
        toast.error('A form must have at least one question.');
        return;
    }
    editForm.questions.splice(index, 1);
};

const onTypeChange = (qIndex: number) => {
    const q = editForm.questions[qIndex];
    if (q.type === 'multiple_choice') {
        q.options = ['', '', '', ''];
    } else {
        q.options = [];
    }
};

const addOption = (qIndex: number) => {
    editForm.questions[qIndex].options.push('');
};

const removeOption = (qIndex: number, oIndex: number) => {
    if (editForm.questions[qIndex].options.length <= 2) {
        return;
    }
    editForm.questions[qIndex].options.splice(oIndex, 1);
};

const submit = () => {
    const payload = {
        ...editForm.data(),
        questions: editForm.questions.map((q) => ({
            ...q,
            options: q.type === 'multiple_choice' ? q.options : null,
        })),
    };
    editForm
        .transform(() => payload)
        .put(`/admin/feedback/${props.form.id}`, {
            // Flash message handled by global watcher (FeedbackController::update flashes 'success')
            onError: () => toast.error('Please fix the errors and try again.'),
        });
};
</script>

<template>
    <Head title="Edit Feedback Form" />

    <div class="mx-auto max-w-4xl p-4 lg:p-8">
        <div class="mb-6 flex items-center gap-3 lg:mb-8">
            <Link
                :href="`/admin/feedback/${props.form.id}`"
                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
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
                <h1
                    class="text-2xl font-bold text-gray-900 lg:text-3xl dark:text-gray-100"
                >
                    Edit Feedback Form
                </h1>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    {{ props.form.title }}
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <h2
                    class="mb-5 text-base font-bold text-gray-800 dark:text-gray-200"
                >
                    Form Details
                </h2>
                <div class="space-y-4">
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                        >
                            Form Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="editForm.title"
                            type="text"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        />
                        <p
                            v-if="editForm.errors.title"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ editForm.errors.title }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                            >Description</label
                        >
                        <textarea
                            v-model="editForm.description"
                            rows="3"
                            class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                            >Close Date</label
                        >
                        <input
                            v-model="editForm.closes_at"
                            type="datetime-local"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        />
                    </div>
                    <div class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-3">
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-900"
                        >
                            <input
                                v-model="editForm.is_active"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 accent-[#42b6c5]"
                            />
                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Active
                                </p>
                                <p class="text-xs text-gray-400">
                                    Accept responses now
                                </p>
                            </div>
                        </label>
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-900"
                        >
                            <input
                                v-model="editForm.allow_anonymous"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 accent-[#42b6c5]"
                            />
                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Allow Anonymous
                                </p>
                                <p class="text-xs text-gray-400">
                                    No name/email required
                                </p>
                            </div>
                        </label>
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-900"
                        >
                            <input
                                v-model="editForm.send_thank_you_email"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 accent-[#42b6c5]"
                            />
                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    Thank-You Email
                                </p>
                                <p class="text-xs text-gray-400">
                                    Send email on submit
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Questions -->
            <div class="space-y-3">
                <h2
                    class="text-base font-bold text-gray-800 dark:text-gray-200"
                >
                    Questions
                    <span class="text-[#42b6c5]"
                        >({{ editForm.questions.length }})</span
                    >
                </h2>

                <div
                    v-for="(question, qIndex) in editForm.questions"
                    :key="qIndex"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <div
                        class="flex cursor-pointer items-center gap-3 px-5 py-4 select-none"
                        @click="toggleQuestion(qIndex)"
                    >
                        <GripVertical
                            class="h-4 w-4 flex-shrink-0 text-gray-300 dark:text-gray-600"
                        />
                        <span
                            class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-[#42b6c5]/10 text-sm font-bold text-[#42b6c5]"
                        >
                            {{ qIndex + 1 }}
                        </span>
                        <span
                            class="flex-1 truncate text-sm font-semibold text-gray-700 dark:text-gray-300"
                        >
                            {{ question.question || `Question ${qIndex + 1}` }}
                        </span>
                        <span
                            class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400"
                        >
                            {{
                                question.type === 'multiple_choice'
                                    ? 'Multiple Choice'
                                    : 'Text'
                            }}
                        </span>
                        <button
                            type="button"
                            @click.stop="removeQuestion(qIndex)"
                            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                        <ChevronDown
                            v-if="!expandedQuestions.includes(qIndex)"
                            class="h-4 w-4 text-gray-400"
                        />
                        <ChevronUp v-else class="h-4 w-4 text-gray-400" />
                    </div>

                    <div
                        v-if="expandedQuestions.includes(qIndex)"
                        class="space-y-4 border-t border-gray-100 px-5 pt-4 pb-5 dark:border-gray-700"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                            >
                                Question Text
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="question.question"
                                rows="2"
                                placeholder="Enter your question..."
                                class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                                    >Type</label
                                >
                                <select
                                    v-model="question.type"
                                    @change="onTypeChange(qIndex)"
                                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                >
                                    <option value="text">Text Response</option>
                                    <option value="multiple_choice">
                                        Multiple Choice
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                                    >Required</label
                                >
                                <label
                                    class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 dark:border-gray-600 dark:bg-gray-900"
                                >
                                    <input
                                        v-model="question.required"
                                        type="checkbox"
                                        class="h-4 w-4 accent-[#42b6c5]"
                                    />
                                    <span
                                        class="text-sm text-gray-700 dark:text-gray-300"
                                        >{{
                                            question.required ? 'Yes' : 'No'
                                        }}</span
                                    >
                                </label>
                            </div>
                        </div>
                        <div
                            v-if="question.type === 'multiple_choice'"
                            class="space-y-2"
                        >
                            <label
                                class="mb-1.5 block text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                            >
                                Answer Options
                                <span class="text-red-500">*</span>
                            </label>
                            <div
                                v-for="(option, oIndex) in question.options"
                                :key="oIndex"
                                class="flex gap-2"
                            >
                                <input
                                    v-model="question.options[oIndex]"
                                    type="text"
                                    :placeholder="`Option ${oIndex + 1}`"
                                    class="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                />
                                <button
                                    type="button"
                                    @click="removeOption(qIndex, oIndex)"
                                    :disabled="question.options.length <= 2"
                                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-red-900/20"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                            <button
                                type="button"
                                @click="addOption(qIndex)"
                                class="mt-1 flex items-center gap-2 text-sm font-semibold text-[#42b6c5] hover:text-[#35a3b2]"
                            >
                                <PlusCircle class="h-4 w-4" /> Add Option
                            </button>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="addQuestion"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[#42b6c5]/40 py-3.5 text-sm font-semibold text-[#42b6c5] transition-colors hover:border-[#42b6c5] hover:bg-[#42b6c5]/5"
                >
                    <PlusCircle class="h-5 w-5" /> Add Question
                </button>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <Link
                    :href="`/admin/feedback/${props.form.id}`"
                    class="rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="editForm.processing"
                    class="flex items-center gap-2 rounded-lg bg-[#42b6c5] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35a3b2] disabled:opacity-60"
                >
                    <svg
                        v-if="editForm.processing"
                        class="h-4 w-4 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                    </svg>
                    {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</template>
