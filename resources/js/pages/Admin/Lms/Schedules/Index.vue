<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import ScheduleManager from '@/components/schedules/ScheduleManager.vue'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

defineProps<{
  courses: any[]
  schedules: any[]
}>()
</script>

<template>
  <div>
    <Head title="LMS Schedule" />
    <ScheduleManager mode="admin" :courses="courses" :schedules="schedules" submit-url="/admin/lms/schedules" />
  </div>
</template>
