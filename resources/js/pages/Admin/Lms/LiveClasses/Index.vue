<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'
defineOptions({ layout: AppLayout })
defineProps<{ classes: any[] }>()
function removeClass(id: number) {
  if (!confirm('Delete this live class?')) return
  router.delete(`/admin/lms/live-classes/${id}`)
}
</script>

<template>
  <div>
    <Head title="Admin Live Classes" />
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Live classes</h1>
        <p class="text-sm text-gray-500">Manage sessions across the LMS.</p>
      </div>
      <Link href="/admin/lms/live-classes/create" class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white">Create live class</Link>
    </div>
    <div class="overflow-hidden rounded-xl bg-white shadow dark:bg-gray-800">
      <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 dark:bg-gray-900/50">
          <tr><th class="px-4 py-3">Title</th><th class="px-4 py-3">Tutor</th><th class="px-4 py-3">Start</th><th class="px-4 py-3">Duration</th><th class="px-4 py-3 text-right">Actions</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr v-for="row in classes" :key="row.id">
            <td class="px-4 py-3">{{ row.title }}</td>
            <td class="px-4 py-3">{{ row.tutor?.name || '—' }}</td>
            <td class="px-4 py-3">{{ new Date(row.start_time).toLocaleString() }}</td>
            <td class="px-4 py-3">{{ row.duration }} min</td>
            <td class="px-4 py-3 text-right">
              <Link :href="`/admin/lms/live-classes/${row.id}`" class="mr-2 text-xs font-semibold text-[#381998]">View</Link>
              <Link :href="`/admin/lms/live-classes/${row.id}/edit`" class="mr-2 text-xs font-semibold text-[#381998]">Edit</Link>
              <button class="text-xs font-semibold text-red-600" @click="removeClass(row.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
