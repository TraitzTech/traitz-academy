<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface Program {
    id: number;
    title: string;
    category: string;
    description: string;
    overview: string | null;
    who_is_for: string | null;
    skills_and_tools: string | null;
    duration: string;
    learning_outcomes: string | null;
    certification: string | null;
    price: number;
    max_installments: number;
    is_cv_required?: boolean;
    image_url: string | null;
    is_featured: boolean;
    is_active: boolean;
    capacity: number;
    start_date: string | null;
    end_date: string | null;
    applications_open_at: string | null;
    applications_close_at: string | null;
    curriculum: string | null;
    office_days: number[] | null;
}

interface Props {
    program: Program;
    categories: Record<string, string>;
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const toast = useToast();

const form = useForm({
    title: props.program.title,
    category: props.program.category,
    description: props.program.description,
    overview: props.program.overview || '',
    who_is_for: props.program.who_is_for || '',
    skills_and_tools: props.program.skills_and_tools || '',
    duration: props.program.duration,
    learning_outcomes: props.program.learning_outcomes || '',
    certification: props.program.certification || '',
    price: props.program.price,
    max_installments: props.program.max_installments || 1,
    is_cv_required: !!props.program.is_cv_required,
    image: null as File | null,
    is_featured: props.program.is_featured,
    is_active: props.program.is_active,
    capacity: props.program.capacity,
    start_date: props.program.start_date?.split('T')[0] || '',
    end_date: props.program.end_date?.split('T')[0] || '',
    applications_open_at:
        props.program.applications_open_at?.split('T')[0] || '',
    applications_close_at:
        props.program.applications_close_at?.split('T')[0] || '',
    curriculum: props.program.curriculum || '',
    office_days: props.program.office_days || ([] as number[]),
});

const isInternship = computed(() =>
    ['academic-internship', 'professional-internship'].includes(form.category),
);

const weekdays = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 7, label: 'Sun' },
];

function toggleOfficeDay(day: number) {
    form.office_days = form.office_days.includes(day)
        ? form.office_days.filter((d) => d !== day)
        : [...form.office_days, day].sort((a, b) => a - b);
}

const imagePreview = ref<string | null>(
    props.program.image_url
        ? props.program.image_url.startsWith('http')
            ? props.program.image_url
            : `/storage/${props.program.image_url}`
        : null,
);

const isCareerRole = computed(() =>
    ['job-opportunity', 'professional-internship'].includes(form.category),
);

const contentLabels = computed(() => {
    if (isCareerRole.value) {
        return {
            overview: 'Role Overview',
            overviewPlaceholder: 'Detailed role overview...',
            whoIsFor: 'Who Should Apply?',
            whoIsForPlaceholder:
                'Describe the ideal candidate for this role...',
            skills: 'Required Skills & Tools',
            skillsPlaceholder: 'Laravel, Vue.js, Git, Docker, etc.',
            outcomes: 'Key Responsibilities',
            outcomesPlaceholder:
                'What the role involves and what the hire will deliver...',
            curriculum: 'Role Scope & Work Plan',
            curriculumPlaceholder:
                'Month 1: Onboarding & orientation...\nMonth 2: Project assignments...',
            certification: 'What We Offer',
        };
    }
    return {
        overview: 'Overview',
        overviewPlaceholder: 'Detailed program overview...',
        whoIsFor: 'Who Is This For?',
        whoIsForPlaceholder: 'Describe the ideal candidate...',
        skills: 'Skills & Tools',
        skillsPlaceholder: 'React, Node.js, MongoDB, Git, etc.',
        outcomes: 'Learning Outcomes',
        outcomesPlaceholder: 'What students will learn...',
        curriculum: 'Curriculum',
        curriculumPlaceholder:
            'Week 1: Introduction...\nWeek 2: Fundamentals...',
        certification: 'Certification',
    };
});

const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.image = target.files[0];
        imagePreview.value = URL.createObjectURL(target.files[0]);
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    }));

    form.post(`/admin/programs/${props.program.id}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Flash message handled by global watcher (ProgramController::update flashes 'success')
        },
        onError: () => {
            toast.error(
                'Failed to update program. Please check the form for errors.',
            );
        },
        onFinish: () => {
            form.transform((data) => data);
        },
    } as any);
};
</script>

<template>
    <div class="mx-auto max-w-5xl">
        <Head :title="`Edit ${program.title}`" />

        <!-- Header -->
        <div class="mb-8">
            <Link
                href="/admin/programs"
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
                Back to Programs
            </Link>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Edit Program
            </h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Update program details
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <!-- Duplicate Program Alert -->
            <div
                v-if="
                    form.errors.title &&
                    form.errors.title.includes('already exists')
                "
                class="rounded-lg border-2 border-amber-400 bg-amber-50 p-4 dark:border-amber-600 dark:bg-amber-950/30"
            >
                <div class="flex items-start gap-3">
                    <svg
                        class="mt-0.5 h-6 w-6 flex-shrink-0 text-amber-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.27 16.5c-.77.833.192 2.5 1.732 2.5z"
                        />
                    </svg>
                    <div>
                        <h3
                            class="font-semibold text-amber-800 dark:text-amber-300"
                        >
                            Duplicate Program Detected
                        </h3>
                        <p
                            class="mt-1 text-sm text-amber-700 dark:text-amber-400"
                        >
                            {{ form.errors.title }}
                        </p>
                        <p
                            class="mt-2 text-sm text-amber-600 dark:text-amber-500"
                        >
                            Tip: Programs can share the same title only if they
                            belong to different categories (e.g., Academic
                            Internship vs Professional Internship).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Basic Information
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
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Category *</label
                        >
                        <select
                            v-model="form.category"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">Select a category</option>
                            <option
                                v-for="(label, value) in categories"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.category"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.category }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Duration *</label
                        >
                        <input
                            v-model="form.duration"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.duration"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.duration }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Price (XAF) *</label
                        >
                        <input
                            v-model="form.price"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.price"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.price }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Capacity *</label
                        >
                        <input
                            v-model="form.capacity"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            v-if="form.errors.capacity"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.capacity }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Max Installments *</label
                        >
                        <input
                            v-model="form.max_installments"
                            type="number"
                            min="1"
                            max="12"
                            :disabled="Number(form.price) <= 0"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Set to 1 for one-time payment. Free programs always
                            use 1.
                        </p>
                        <p
                            v-if="form.errors.max_installments"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.max_installments }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Program start
                            <span class="text-xs font-normal text-gray-400"
                                >(run/display)</span
                            ></label
                        >
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Program end
                            <span class="text-xs font-normal text-gray-400"
                                >(run/display)</span
                            ></label
                        >
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                        <p class="mt-1 text-xs text-gray-400">
                            When the program runs — separate from the
                            application window below.
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-dashed border-gray-300 p-3 md:col-span-2 dark:border-gray-600"
                    >
                        <p
                            class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Application window
                            <span class="font-normal text-gray-400"
                                >(optional)</span
                            >
                        </p>
                        <p class="mb-3 text-xs text-gray-500">
                            Leave both blank for rolling intake (apply anytime).
                            Set a window to open/close applications by date.
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300"
                                    >Applications open</label
                                >
                                <input
                                    v-model="form.applications_open_at"
                                    type="date"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300"
                                    >Applications close</label
                                >
                                <input
                                    v-model="form.applications_close_at"
                                    type="date"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p
                                    v-if="form.errors.applications_close_at"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.applications_close_at }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Short Description *</label
                        >
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        ></textarea>
                        <p
                            v-if="form.errors.description"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Program Image -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Program Image
                </h3>
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0">
                        <div
                            v-if="imagePreview"
                            class="h-28 w-40 overflow-hidden rounded-lg"
                        >
                            <img
                                :src="imagePreview"
                                alt="Preview"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div
                            v-else
                            class="flex h-28 w-40 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700"
                        >
                            <svg
                                class="h-12 w-12 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleImageChange"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-[#42b6c5] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#35919e] dark:text-gray-400"
                        />
                        <p
                            class="mt-2 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Leave empty to keep current image. Recommended:
                            600x400px, max 2MB
                        </p>
                        <p
                            v-if="form.errors.image"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.image }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Detailed Content -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Detailed Content
                </h3>
                <div class="space-y-6">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >{{ contentLabels.overview }}</label
                        >
                        <textarea
                            v-model="form.overview"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :placeholder="contentLabels.overviewPlaceholder"
                        ></textarea>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >{{ contentLabels.whoIsFor }}</label
                        >
                        <textarea
                            v-model="form.who_is_for"
                            rows="3"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :placeholder="contentLabels.whoIsForPlaceholder"
                        ></textarea>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >{{ contentLabels.skills }}</label
                        >
                        <input
                            v-model="form.skills_and_tools"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :placeholder="contentLabels.skillsPlaceholder"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >{{ contentLabels.outcomes }}</label
                        >
                        <textarea
                            v-model="form.learning_outcomes"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :placeholder="contentLabels.outcomesPlaceholder"
                        ></textarea>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >{{ contentLabels.curriculum }}</label
                        >
                        <textarea
                            v-model="form.curriculum"
                            rows="6"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            :placeholder="contentLabels.curriculumPlaceholder"
                        ></textarea>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >{{ contentLabels.certification }}</label
                        >
                        <input
                            v-model="form.certification"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Settings
                </h3>
                <div class="space-y-4">
                    <label class="flex items-center">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600"
                        />
                        <span
                            class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >Active (visible on website)</span
                        >
                    </label>
                    <label class="flex items-center">
                        <input
                            v-model="form.is_featured"
                            type="checkbox"
                            class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600"
                        />
                        <span
                            class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >Featured (shown on homepage)</span
                        >
                    </label>
                    <label class="flex items-center">
                        <input
                            v-model="form.is_cv_required"
                            type="checkbox"
                            class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600"
                        />
                        <span
                            class="ml-2 text-sm text-gray-700 dark:text-gray-300"
                            >Require CV for applications</span
                        >
                    </label>
                </div>
            </div>

            <!-- Office schedule (internship programs only) -->
            <div
                v-if="isInternship"
                class="rounded-lg bg-white p-6 shadow dark:bg-gray-800"
            >
                <h3
                    class="mb-1 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Office schedule
                </h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    Which weekdays are interns in this program expected at the
                    office? Leave blank if there's no fixed schedule — interns
                    will pick office/remote each day themselves instead.
                </p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="day in weekdays"
                        :key="day.value"
                        type="button"
                        :class="[
                            'rounded-lg border-2 px-4 py-2 text-sm font-semibold transition-colors',
                            form.office_days.includes(day.value)
                                ? 'border-[#381998] bg-[#381998]/10 text-[#381998]'
                                : 'border-gray-200 text-gray-500 hover:border-gray-300 dark:border-gray-600 dark:text-gray-400',
                        ]"
                        @click="toggleOfficeDay(day.value)"
                    >
                        {{ day.label }}
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4">
                <Link
                    href="/admin/programs"
                    class="rounded-lg border border-gray-300 px-6 py-3 font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-[#42b6c5] px-6 py-3 font-semibold text-white transition-colors hover:bg-[#35919e] disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</template>
