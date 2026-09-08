<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

import { useToast } from '@/composables/useToast';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Program {
    id: number;
    title: string;
    slug: string;
    category: string;
    description: string;
    is_cv_required?: boolean;
}

interface Props {
    program: Program;
}

const props = defineProps<Props>();
const page = usePage();
const toast = useToast();

const isAcademic = computed(
    () => props.program.category === 'academic-internship',
);
const isJobOpportunity = computed(
    () => props.program.category === 'job-opportunity',
);
const requiresCv = computed(() => !!props.program.is_cv_required);
const isCareerRole = computed(() =>
    ['job-opportunity', 'professional-internship'].includes(
        props.program.category,
    ),
);

const form = useForm({
    program_id: props.program.id,
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    country: '',
    bio: '',
    education_level: isAcademic.value ? undefined : '',
    institution_name: isAcademic.value ? '' : undefined,
    academic_duration: isAcademic.value ? '' : undefined,
    motivation: '',
    experience: '',
    cv: null as File | null,
    internship_letter: null as File | null,
});

// Pre-populate form fields if user is authenticated
onMounted(() => {
    const authUser = page.props.auth?.user;
    if (authUser) {
        form.first_name = authUser.first_name || '';
        form.last_name = authUser.last_name || '';
        form.email = authUser.email || '';
        form.phone = authUser.phone || '';
    }
});

const submit = () => {
    // No local success toast here — the redirect to /dashboard carries a
    // flash message that AppSidebarLayout's global watcher already shows.
    // A local toast on top of that produced two success messages.
    form.post('/applications', {
        forceFormData: true,
        onError: () => {
            toast.error('Failed to submit your application. Please try again.');
        },
    });
};
</script>

<template>
    <PublicLayout>
        <Head :title="`Apply for ${program.title} - Traitz Academy`" />

        <!-- Header -->
        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] py-12 text-white"
        >
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <Link
                    href="/programs"
                    class="mb-4 inline-flex items-center text-[#42b6c5] hover:text-white"
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
                <h1 class="mb-2 text-4xl font-bold">
                    Apply for {{ program.title }}
                </h1>
                <p class="text-xl text-gray-300">
                    {{
                        isCareerRole
                            ? 'Submit your application for this role'
                            : 'Complete the form below to submit your application'
                    }}
                </p>
            </div>
        </section>

        <!-- Form Section -->
        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-8 shadow-lg">
                    <form @submit.prevent="submit" class="space-y-8">
                        <!-- Personal Information -->
                        <div>
                            <h2 class="mb-6 text-2xl font-bold text-[#000928]">
                                Personal Information
                            </h2>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label
                                        for="first_name"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                        >First Name *</label
                                    >
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500':
                                                form.errors.first_name,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.first_name"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.first_name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="last_name"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Last Name *</label
                                    >
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500':
                                                form.errors.last_name,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.last_name"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.last_name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="email"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Email Address *</label
                                    >
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500': form.errors.email,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.email"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="phone"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Phone Number *</label
                                    >
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500': form.errors.phone,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.phone"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.phone }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="country"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Country *</label
                                    >
                                    <input
                                        id="country"
                                        v-model="form.country"
                                        type="text"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500':
                                                form.errors.country,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.country"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.country }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="bio"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Bio</label
                                    >
                                    <input
                                        id="bio"
                                        v-model="form.bio"
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Education Information -->
                        <div v-if="!isAcademic">
                            <h2 class="mb-6 text-2xl font-bold text-[#000928]">
                                {{
                                    isCareerRole
                                        ? 'Professional Background'
                                        : 'Education Background'
                                }}
                            </h2>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label
                                        for="education_level"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Education Level</label
                                    >
                                    <select
                                        id="education_level"
                                        v-model="form.education_level"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                    >
                                        <option value="">Select Level</option>
                                        <option value="High School">
                                            High School
                                        </option>
                                        <option value="Bachelor">
                                            Bachelor Degree
                                        </option>
                                        <option value="Master">
                                            Master Degree
                                        </option>
                                        <option value="Doctorate">
                                            Doctorate
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Internship Fields -->
                        <div v-if="isAcademic">
                            <h2 class="mb-6 text-2xl font-bold text-[#000928]">
                                Academic Information
                            </h2>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label
                                        for="institution_name"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Institution Name *</label
                                    >
                                    <input
                                        id="institution_name"
                                        v-model="form.institution_name"
                                        type="text"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                    />
                                </div>

                                <div>
                                    <label
                                        for="academic_duration"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                        >Academic Duration *</label
                                    >
                                    <select
                                        id="academic_duration"
                                        v-model="form.academic_duration"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                    >
                                        <option value="">
                                            Select Duration
                                        </option>
                                        <option value="1 semester">
                                            1 Semester
                                        </option>
                                        <option value="2 semesters">
                                            2 Semesters
                                        </option>
                                        <option value="1 year">1 Year</option>
                                        <option value="2 years">2 Years</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Motivation & Experience -->
                        <div>
                            <h2 class="mb-6 text-2xl font-bold text-[#000928]">
                                {{
                                    isCareerRole
                                        ? 'Why Are You the Right Fit?'
                                        : 'Why Do You Want to Join?'
                                }}
                            </h2>
                            <div class="space-y-6">
                                <div>
                                    <label
                                        for="motivation"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                    >
                                        {{
                                            isCareerRole
                                                ? "Tell us why you're the right fit * (minimum 20 characters)"
                                                : 'Tell us your motivation * (minimum 20 characters)'
                                        }}
                                    </label>
                                    <textarea
                                        id="motivation"
                                        v-model="form.motivation"
                                        required
                                        rows="6"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500':
                                                form.errors.motivation,
                                        }"
                                        :placeholder="
                                            isCareerRole
                                                ? 'Describe your relevant experience, what interests you about this role, and why you would be a great fit...'
                                                : 'Share your goals, aspirations, and what you hope to achieve...'
                                        "
                                    ></textarea>
                                    <p
                                        v-if="form.errors.motivation"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.motivation }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="experience"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                    >
                                        {{
                                            isCareerRole
                                                ? 'Professional Experience'
                                                : 'Relevant Experience (Optional)'
                                        }}
                                    </label>
                                    <textarea
                                        id="experience"
                                        v-model="form.experience"
                                        rows="6"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :placeholder="
                                            isCareerRole
                                                ? 'Summarize your professional experience, key projects, and achievements relevant to this role...'
                                                : 'Tell us about any relevant work experience, projects, or skills...'
                                        "
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Application Documents -->
                        <div>
                            <h2 class="mb-6 text-2xl font-bold text-[#000928]">
                                Application Documents
                            </h2>
                            <div class="space-y-4">
                                <div>
                                    <label
                                        for="cv"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                    >
                                        CV / Resume
                                        <span v-if="requiresCv">*</span
                                        ><span v-else>(Optional)</span>
                                    </label>
                                    <p class="mb-3 text-sm text-gray-500">
                                        Upload your most recent CV in PDF, DOC,
                                        or DOCX format.
                                    </p>
                                    <input
                                        id="cv"
                                        type="file"
                                        :required="requiresCv"
                                        accept=".pdf,.doc,.docx"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none file:mr-4 file:rounded file:border-0 file:bg-[#42b6c5] file:px-4 file:py-1 file:text-sm file:font-semibold file:text-white hover:file:bg-[#35919e] focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500': form.errors.cv,
                                        }"
                                        @input="
                                            form.cv =
                                                (
                                                    $event.target as HTMLInputElement
                                                ).files?.[0] ?? null
                                        "
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Accepted formats: PDF, DOC, DOCX. Max
                                        5MB.
                                    </p>
                                    <p
                                        v-if="form.errors.cv"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.cv }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="internship_letter"
                                        class="mb-2 block text-sm font-semibold text-gray-700"
                                    >
                                        {{
                                            isAcademic
                                                ? 'Upload Internship Letter (Optional)'
                                                : 'Additional Supporting Document (Optional)'
                                        }}
                                    </label>
                                    <p class="mb-3 text-sm text-gray-500">
                                        {{
                                            isAcademic
                                                ? 'If your school has issued an internship letter, upload it here so we can keep it on file.'
                                                : 'You may upload any supporting document relevant to your application.'
                                        }}
                                    </p>
                                    <input
                                        id="internship_letter"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none file:mr-4 file:rounded file:border-0 file:bg-[#42b6c5] file:px-4 file:py-1 file:text-sm file:font-semibold file:text-white hover:file:bg-[#35919e] focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500':
                                                form.errors.internship_letter,
                                        }"
                                        @input="
                                            form.internship_letter =
                                                (
                                                    $event.target as HTMLInputElement
                                                ).files?.[0] ?? null
                                        "
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Accepted formats: PDF, JPG, PNG, DOC,
                                        DOCX. Max 5MB.
                                    </p>
                                    <p
                                        v-if="form.errors.internship_letter"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.internship_letter }}
                                    </p>
                                </div>

                                <div
                                    v-if="isJobOpportunity"
                                    class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
                                >
                                    Job applications are reviewed based on
                                    profile fit, interview performance, and role
                                    requirements.
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-lg bg-[#42b6c5] px-6 py-3 text-lg font-bold text-white transition-colors hover:bg-[#35919e] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span
                                    v-if="form.processing"
                                    class="inline-flex items-center"
                                >
                                    <svg
                                        class="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
                                        xmlns="http://www.w3.org/2000/svg"
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
                                        ></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    Submitting...
                                </span>
                                <span v-else>Submit Application</span>
                            </button>
                            <Link
                                href="/programs"
                                class="rounded-lg border border-gray-300 px-6 py-3 text-lg font-bold text-gray-700 transition-colors hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                        </div>

                        <!-- Privacy Notice -->
                        <div
                            class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
                        >
                            {{
                                isCareerRole
                                    ? "By submitting this application, you agree to our privacy policy and terms of service. We'll use your information to process your application and contact you regarding this role."
                                    : "By submitting this application, you agree to our privacy policy and terms of service. We'll use your information to process your application and contact you about your enrollment."
                            }}
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
