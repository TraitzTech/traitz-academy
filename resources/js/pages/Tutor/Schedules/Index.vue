<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import ScheduleManager from '@/components/schedules/ScheduleManager.vue'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

defineProps<{
  courses: any[]
  cohorts: any[]
  programs: any[]
  schedules: any[]
}>()
</script>

<template>
  <div>
    <Head title="Tutor Schedule" />
    <ScheduleManager mode="tutor" :courses="courses" :cohorts="cohorts" :programs="programs" :schedules="schedules" submit-url="/tutor/schedules" />
  </div>
</template>
