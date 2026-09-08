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

const form = useForm({
    title: '',
    description: '',
    is_active: true,
    allow_anonymous: true,
    send_thank_you_email: true,
    closes_at: '' as string,
    questions: [
        {
            question: '',
            type: 'text' as const,
            options: [] as string[],
            required: true,
        },
    ] as Question[],
});

const expandedQuestions = ref<number[]>([0]);

const toggleQuestion = (index: number) => {
    const pos = expandedQuestions.value.indexOf(index);
    if (pos >= 0) {
        expandedQuestions.value.splice(pos, 1);
    } else {
        expandedQuestions.value.push(index);
    }
};

const addQuestion = () => {
    form.questions.push({
        question: '',
        type: 'text',
        options: [],
        required: true,
    });
    expandedQuestions.value.push(form.questions.length - 1);
};

const removeQuestion = (index: number) => {
    if (form.questions.length <= 1) {
        toast.error('A form must have at least one question.');
        return;
    }
    form.questions.splice(index, 1);
};

const onTypeChange = (qIndex: number) => {
    const q = form.questions[qIndex];
    if (q.type === 'multiple_choice') {
        q.options = ['', '', '', ''];
    } else {
        q.options = [];
    }
};

const addOption = (qIndex: number) => {
    form.questions[qIndex].options.push('');
};

const removeOption = (qIndex: number, oIndex: number) => {
    if (form.questions[qIndex].options.length <= 2) {
        return;
    }
    form.questions[qIndex].options.splice(oIndex, 1);
};

const submit = () => {
    const payload = {
        ...form.data(),
        questions: form.questions.map((q) => ({
            ...q,
            options: q.type === 'multiple_choice' ? q.options : null,
        })),
    };
    form.transform(() => payload).post('/admin/feedback', {
        // Flash message handled by global watcher (FeedbackController::store flashes 'success')
        onError: () => toast.error('Please fix the errors and try again.'),
    });
};
</script>

<template>
    <Head title="Create Feedback Form" />

    <div class="mx-auto max-w-4xl p-4 lg:p-8">
        <!-- Header -->
        <div class="mb-6 flex items-center gap-3 lg:mb-8">
            <Link
                href="/admin/feedback"
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
                    Create Feedback Form
                </h1>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Design questions for interns to answer
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Basic Info Card -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <h2
                    class="mb-5 text-base font-bold text-gray-800 dark:text-gray-200"
                >
                    Form Details
                </h2>

                <div class="space-y-4">
                    <!-- Title -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                        >
                            Form Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="e.g. Internship Experience Q1 2026"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                            >Description</label
                        >
                        <textarea
                            v-model="form.description"
                            placeholder="Briefly describe what this feedback form is about..."
                            rows="3"
                            class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        />
                    </div>

                    <!-- Closes At -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                            >Close Date
                            <span class="text-xs font-normal text-gray-400"
                                >(optional)</span
                            ></label
                        >
                        <input
                            v-model="form.closes_at"
                            type="datetime-local"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.closes_at"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.closes_at }}
                        </p>
                    </div>

                    <!-- Toggles -->
                    <div class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-3">
                        <label
                            v-for="(item, key) in [
                                {
                                    field: 'is_active',
                                    label: 'Active',
                                    desc: 'Accept responses now',
                                },
                                {
                                    field: 'allow_anonymous',
                                    label: 'Allow Anonymous',
                                    desc: 'No name/email required',
                                },
                                {
                                    field: 'send_thank_you_email',
                                    label: 'Thank-You Email',
                                    desc: 'Send email on submit',
                                },
                            ]"
                            :key="key"
                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-900"
                        >
                            <input
                                v-if="item.field === 'is_active'"
                                v-model="form.is_active"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 accent-[#42b6c5]"
                            />
                            <input
                                v-else-if="item.field === 'allow_anonymous'"
                                v-model="form.allow_anonymous"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 accent-[#42b6c5]"
                            />
                            <input
                                v-else
                                v-model="form.send_thank_you_email"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 accent-[#42b6c5]"
                            />
                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    {{ item.label }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ item.desc }}
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Questions -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2
                        class="text-base font-bold text-gray-800 dark:text-gray-200"
                    >
                        Questions
                        <span class="text-[#42b6c5]"
                            >({{ form.questions.length }})</span
                        >
                    </h2>
                </div>

                <p v-if="form.errors.questions" class="text-sm text-red-500">
                    {{ form.errors.questions }}
                </p>

                <div
                    v-for="(question, qIndex) in form.questions"
                    :key="qIndex"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <!-- Question header -->
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
                            class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 capitalize dark:bg-gray-700 dark:text-gray-400"
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

                    <!-- Question body -->
                    <div
                        v-if="expandedQuestions.includes(qIndex)"
                        class="space-y-4 border-t border-gray-100 px-5 pt-4 pb-5 dark:border-gray-700"
                    >
                        <!-- Question text -->
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                            >
                                Question Text
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="question.question"
                                placeholder="Enter your question..."
                                rows="2"
                                class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#42b6c5]/40 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                            <p
                                v-if="
                                    form.errors[`questions.${qIndex}.question`]
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    form.errors[`questions.${qIndex}.question`]
                                }}
                            </p>
                        </div>

                        <!-- Type & Required -->
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

                        <!-- Multiple choice options -->
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
                                <PlusCircle class="h-4 w-4" />
                                Add Option
                            </button>
                            <p
                                v-if="
                                    form.errors[`questions.${qIndex}.options`]
                                "
                                class="text-xs text-red-500"
                            >
                                {{ form.errors[`questions.${qIndex}.options`] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Add question button -->
                <button
                    type="button"
                    @click="addQuestion"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[#42b6c5]/40 py-3.5 text-sm font-semibold text-[#42b6c5] transition-colors hover:border-[#42b6c5] hover:bg-[#42b6c5]/5"
                >
                    <PlusCircle class="h-5 w-5" />
                    Add Question
                </button>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3 pt-2">
                <Link
                    href="/admin/feedback"
                    class="rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 rounded-lg bg-[#42b6c5] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#35a3b2] disabled:opacity-60"
                >
                    <svg
                        v-if="form.processing"
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
                    {{ form.processing ? 'Creating…' : 'Create Form' }}
                </button>
            </div>
        </form>
    </div>
</template>
