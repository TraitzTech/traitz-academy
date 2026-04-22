<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

import DiscussionBoard from '@/components/discussions/DiscussionBoard.vue'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

defineProps<{
  title: string
  subtitle: string
  filters: { search?: string; status?: string }
  discussions: { data: any[]; links: Array<{ url: string | null; label: string; active: boolean }> }
}>()
</script>

<template>
  <div>
    <Head :title="title" />
    <DiscussionBoard
      :title="title"
      :subtitle="subtitle"
      base-url="/admin/lms/discussions"
      :filters="filters"
      :discussions="discussions"
      :show-tutor-name="true"
    />
  </div>
</template>

