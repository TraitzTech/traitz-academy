<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import AssignmentManager from '@/components/assignments/AssignmentManager.vue'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface GroupStudent {
  id: number
  name: string
  email: string
}

interface GroupRow {
  id: number
  title: string
  student_count: number
  students: GroupStudent[]
}

interface AssignmentRow {
  id: number
  title: string
  instructions: string
  audience: 'all_course_students' | 'selected_students'
  attachable: {
    type: 'course' | 'cohort' | 'program' | null
    id: number | null
    title: string | null
  }
  created_by: string | null
  due_at: string | null
  attachment_url: string | null
  selected_students_count: number | null
  created_at: string | null
}

defineProps<{
  courses: GroupRow[]
  cohorts: GroupRow[]
  programs: GroupRow[]
  assignments: AssignmentRow[]
}>()
</script>

<template>
  <div>
    <Head title="Tasks" />
    <AssignmentManager :courses="courses" :cohorts="cohorts" :programs="programs" :assignments="assignments" submit-url="/tutor/assignments" />
  </div>
</template>
