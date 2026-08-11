<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import ResourceManager from '@/components/resources/ResourceManager.vue'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface GroupStudent {
  id: number
  name: string
  email: string
}

interface GroupProgram {
  id: number
  title: string
  student_count: number
  students: GroupStudent[]
}

interface ResourceRow {
  id: number
  title: string
  type: 'document' | 'youtube_video' | 'writing' | 'external_link'
  description: string | null
  youtube_url: string | null
  external_url: string | null
  content: string | null
  document_url: string | null
  audience: 'all_program_interns' | 'selected_students'
  program: { id: number; title: string } | null
  created_by: string | null
  selected_students_count: number | null
  created_at: string | null
}

defineProps<{
  programs: GroupProgram[]
  resources: ResourceRow[]
}>()
</script>

<template>
  <div>
    <Head title="Resources" />
    <ResourceManager :programs="programs" :resources="resources" />
  </div>
</template>
