<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
defineProps<{ classes: any[] }>();

function removeClass(id: number) {
    if (!confirm('Delete this live class?')) return;
    router.delete(`/tutor/live-classes/${id}`);
}
</script>

<template>
    <div>
        <Head title="Tutor Live Classes" />
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Live classes
                </h1>
                <p class="text-sm text-gray-500">
                    Create and run your live sessions.
                </p>
            </div>
            <Link
                href="/tutor/live-classes/create"
                class="rounded-lg bg-[#381998] px-4 py-2 text-sm font-semibold text-white"
                >Create live class</Link
            >
        </div>

        <div
            v-if="classes.length === 0"
            class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-600"
        >
            No live classes yet.
        </div>
        <div
            v-else
            class="overflow-hidden rounded-xl bg-white shadow dark:bg-gray-800"
        >
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Start</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Access</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr v-for="row in classes" :key="row.id">
                        <td class="px-4 py-3">{{ row.title }}</td>
                        <td class="px-4 py-3">
                            {{ new Date(row.start_time).toLocaleString() }}
                        </td>
                        <td class="px-4 py-3">{{ row.duration }} min</td>
                        <td class="px-4 py-3 capitalize">
                            {{ row.access_type }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="`/tutor/live-classes/${row.id}`"
                                class="mr-2 text-xs font-semibold text-[#381998]"
                                >View</Link
                            >
                            <Link
                                :href="`/tutor/live-classes/${row.id}/edit`"
                                class="mr-2 text-xs font-semibold text-[#381998]"
                                >Edit</Link
                            >
                            <button
                                class="text-xs font-semibold text-red-600"
                                @click="removeClass(row.id)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
