<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import LiveClassForm from '@/components/live-classes/LiveClassForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps<{
    courses: any[];
    cohorts: any[];
    programs: any[];
    students: any[];
}>();
</script>

<template>
    <div>
        <Head title="Create Live Class" />
        <h1 class="mb-4 text-2xl font-bold text-gray-900 dark:text-gray-100">
            Create live class
        </h1>
        <div
            class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
        >
            <LiveClassForm
                submit-url="/tutor/live-classes"
                :courses="courses"
                :cohorts="cohorts"
                :programs="programs"
                :students="students"
            />
        </div>
    </div>
</template>
