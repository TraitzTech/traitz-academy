<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import AssignmentManager from '@/components/assignments/AssignmentManager.vue'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface CourseStudent {
  id: number
  name: string
  email: string
}

interface CourseRow {
  id: number
  title: string
  student_count: number
  students: CourseStudent[]
}

interface AssignmentRow {
  id: number
  title: string
  instructions: string
  audience: 'all_course_students' | 'selected_students'
  course: {
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
  courses: CourseRow[]
  assignments: AssignmentRow[]
}>()
</script>

<template>
  <div>
    <Head title="LMS Assignments" />
    <AssignmentManager :courses="courses" :assignments="assignments" submit-url="/admin/lms/assignments" />
  </div>
</template>
