<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface Program {
    id: number;
    title: string;
}

interface Question {
    question: string;
    type: 'multiple_choice' | 'text' | 'boolean';
    options: string[];
    correct_answer: string;
    points: number;
}

interface Props {
    programs: Program[];
}

defineProps<Props>();
defineOptions({ layout: AppLayout });

const toast = useToast();

const form = useForm({
    title: '',
    description: '',
    program_id: '' as string | number,
    passing_score: 60,
    time_limit_minutes: null as number | null,
    is_active: true,
    questions: [
        {
            question: '',
            type: 'multiple_choice' as const,
            options: ['', '', '', ''],
            correct_answer: '',
            points: 10,
        },
    ] as Question[],
});

const addQuestion = () => {
    form.questions.push({
        question: '',
        type: 'multiple_choice',
        options: ['', '', '', ''],
        correct_answer: '',
        points: 10,
    });
};

const removeQuestion = (index: number) => {
    if (form.questions.length <= 1) {
        toast.error('An interview must have at least one question.');
        return;
    }
    form.questions.splice(index, 1);
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

const onTypeChange = (qIndex: number) => {
    const q = form.questions[qIndex];
    if (q.type === 'boolean') {
        q.options = ['True', 'False'];
        q.correct_answer = '';
    } else if (q.type === 'text') {
        q.options = [];
        q.correct_answer = '';
    } else {
        if (q.options.length < 2) {
            q.options = ['', '', '', ''];
        }
        q.correct_answer = '';
    }
};

const expandedQuestions = ref<number[]>([0]);

const toggleQuestion = (index: number) => {
    const pos = expandedQuestions.value.indexOf(index);
    if (pos >= 0) {
        expandedQuestions.value.splice(pos, 1);
    } else {
        expandedQuestions.value.push(index);
    }
};

const submit = () => {
    form.post('/admin/interviews', {
        onSuccess: () => {
            // Flash message handled by global watcher (InterviewController::store flashes 'success')
        },
        onError: () => {
            toast.error('Failed to create interview. Please check the form.');
        },
    });
};
</script>

<template>
    <div class="mx-auto max-w-5xl">
        <Head title="Create Interview" />

        <!-- Header -->
        <div class="mb-8">
            <Link
                href="/admin/interviews"
                class="mb-4 inline-flex items-center text-[#42b6c5] hover:text-[#35919e]"
            >
                <svg
                    class="mr-2 h-5 w-5"
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
                Back to Interviews
            </Link>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Create New Interview
            </h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Set up interview questions for applicants
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <!-- Interview Details -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Interview Details
                </h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Title *</label
                        >
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="e.g., Web Development Interview Assessment"
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Description</label
                        >
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="Brief description of the interview..."
                        ></textarea>
                        <p
                            v-if="form.errors.description"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Program (Optional)</label
                        >
                        <select
                            v-model="form.program_id"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">No Program</option>
                            <option
                                v-for="program in programs"
                                :key="program.id"
                                :value="program.id"
                            >
                                {{ program.title }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.program_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.program_id }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Passing Score (%) *</label
                        >
                        <input
                            v-model="form.passing_score"
                            type="number"
                            min="1"
                            max="100"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.passing_score"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.passing_score }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Time Limit (minutes)</label
                        >
                        <input
                            v-model="form.time_limit_minutes"
                            type="number"
                            min="1"
                            max="480"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="Leave empty for no time limit"
                        />
                        <p
                            v-if="form.errors.time_limit_minutes"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.time_limit_minutes }}
                        </p>
                    </div>

                    <div class="flex items-end">
                        <label class="flex items-center">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600"
                            />
                            <span
                                class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                                >Active (visible to applicants)</span
                            >
                        </label>
                    </div>
                </div>
            </div>

            <!-- Questions -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Questions ({{ form.questions.length }})
                    </h3>
                    <button
                        type="button"
                        @click="addQuestion"
                        class="rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#35919e]"
                    >
                        + Add Question
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(question, qIndex) in form.questions"
                        :key="qIndex"
                        class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <!-- Question Header -->
                        <div
                            @click="toggleQuestion(qIndex)"
                            class="flex cursor-pointer items-center justify-between bg-gray-50 px-4 py-3 hover:bg-gray-100 dark:bg-gray-700/50 dark:hover:bg-gray-700"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-semibold text-gray-500 dark:text-gray-400"
                                    >Q{{ qIndex + 1 }}</span
                                >
                                <span
                                    class="max-w-md truncate text-sm text-gray-900 dark:text-gray-100"
                                >
                                    {{ question.question || 'New Question' }}
                                </span>
                                <span
                                    class="rounded bg-gray-200 px-2 py-0.5 text-xs text-gray-600 capitalize dark:bg-gray-600 dark:text-gray-300"
                                >
                                    {{ question.type.replace('_', ' ') }}
                                </span>
                                <span class="text-xs text-gray-500"
                                    >{{ question.points }} pts</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click.stop="removeQuestion(qIndex)"
                                    class="text-sm text-red-500 hover:text-red-700"
                                >
                                    Remove
                                </button>
                                <svg
                                    :class="
                                        expandedQuestions.includes(qIndex)
                                            ? 'rotate-180'
                                            : ''
                                    "
                                    class="h-5 w-5 text-gray-400 transition-transform"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Question Body -->
                        <div
                            v-show="expandedQuestions.includes(qIndex)"
                            class="space-y-4 p-4"
                        >
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Question *</label
                                >
                                <textarea
                                    v-model="question.question"
                                    rows="2"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    placeholder="Enter your question..."
                                ></textarea>
                                <p
                                    v-if="
                                        form.errors[
                                            `questions.${qIndex}.question`
                                        ]
                                    "
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{
                                        form.errors[
                                            `questions.${qIndex}.question`
                                        ]
                                    }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Type *</label
                                    >
                                    <select
                                        v-model="question.type"
                                        @change="onTypeChange(qIndex)"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    >
                                        <option value="multiple_choice">
                                            Multiple Choice
                                        </option>
                                        <option value="boolean">
                                            True / False
                                        </option>
                                        <option value="text">
                                            Text Answer
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Points *</label
                                    >
                                    <input
                                        v-model="question.points"
                                        type="number"
                                        min="1"
                                        max="100"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                </div>

                                <div v-if="question.type !== 'text'">
                                    <label
                                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Correct Answer *</label
                                    >
                                    <select
                                        v-if="question.type === 'boolean'"
                                        v-model="question.correct_answer"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    >
                                        <option value="">Select answer</option>
                                        <option value="True">True</option>
                                        <option value="False">False</option>
                                    </select>
                                    <select
                                        v-else
                                        v-model="question.correct_answer"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    >
                                        <option value="">
                                            Select correct option
                                        </option>
                                        <option
                                            v-for="(opt, i) in question.options"
                                            :key="i"
                                            :value="opt"
                                            :disabled="!opt"
                                        >
                                            {{
                                                opt || `Option ${i + 1} (empty)`
                                            }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Options for Multiple Choice -->
                            <div
                                v-if="question.type === 'multiple_choice'"
                                class="space-y-2"
                            >
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Options</label
                                >
                                <div
                                    v-for="(option, oIndex) in question.options"
                                    :key="oIndex"
                                    class="flex items-center gap-2"
                                >
                                    <span
                                        class="w-6 text-sm text-gray-500 dark:text-gray-400"
                                        >{{
                                            String.fromCharCode(65 + oIndex)
                                        }}.</span
                                    >
                                    <input
                                        v-model="question.options[oIndex]"
                                        type="text"
                                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        :placeholder="`Option ${String.fromCharCode(65 + oIndex)}`"
                                    />
                                    <button
                                        v-if="question.options.length > 2"
                                        type="button"
                                        @click="removeOption(qIndex, oIndex)"
                                        class="p-1 text-red-500 hover:text-red-700"
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
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    @click="addOption(qIndex)"
                                    class="text-sm font-medium text-[#42b6c5] hover:text-[#35919e]"
                                >
                                    + Add Option
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4">
                <Link
                    href="/admin/interviews"
                    class="rounded-lg border border-gray-300 px-6 py-3 font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-[#42b6c5] px-6 py-3 font-semibold text-white transition-colors hover:bg-[#35919e] disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create Interview' }}
                </button>
            </div>
        </form>
    </div>
</template>
