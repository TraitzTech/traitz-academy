<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import NotificationComposer from '@/components/notifications/NotificationComposer.vue'
import AppLayout from '@/layouts/AppLayout.vue'

interface Member {
  id: number
  name: string
  email: string
}

interface GroupOption {
  id: number
  title: string
  students: Member[]
  student_count: number
}

defineOptions({ layout: AppLayout })

defineProps<{
  mode: 'admin'
  courses: GroupOption[]
  cohorts: GroupOption[]
  programs: GroupOption[]
}>()
</script>

<template>
  <div>
    <Head title="Notifications" />
    <NotificationComposer
      mode="admin"
      :courses="courses"
      :cohorts="cohorts"
      :programs="programs"
      submit-url="/admin/lms/notifications"
    />
  </div>
</template>
