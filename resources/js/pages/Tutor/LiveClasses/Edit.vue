<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import LiveClassForm from '@/components/live-classes/LiveClassForm.vue'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

defineProps<{ liveClass: any; targets: { type: string; id: number }[]; courses: any[]; cohorts: any[]; programs: any[]; students: any[] }>()
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <Head title="Edit Live Class" />
    <h1 class="mb-4 text-2xl font-bold text-gray-900 dark:text-gray-100">Edit live class</h1>
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
      <LiveClassForm
        :submit-url="`/tutor/live-classes/${liveClass.id}`"
        method="put"
        :initial="liveClass"
        :initial-targets="targets"
        :courses="courses"
        :cohorts="cohorts"
        :programs="programs"
        :students="students"
      />
    </div>
  </div>
</template>
