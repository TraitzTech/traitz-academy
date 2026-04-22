<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import NotificationComposer from '@/components/notifications/NotificationComposer.vue'
import AppLayout from '@/layouts/AppLayout.vue'

interface CourseStudent {
  id: number
  name: string
  email: string
}

interface CourseOption {
  id: number
  title: string
  students: CourseStudent[]
  student_count: number
}

defineOptions({ layout: AppLayout })

defineProps<{
  mode: 'admin'
  courses: CourseOption[]
}>()
</script>

<template>
  <div>
    <Head title="LMS Notifications" />
    <NotificationComposer mode="admin" :courses="courses" submit-url="/admin/lms/notifications" />
  </div>
</template>
