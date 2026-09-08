<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

interface Application {
    id: number;
    status: 'pending' | 'accepted' | 'rejected';
    created_at: string;
    program: {
        id: number;
        title: string;
        category: string;
    };
}

interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: 'user' | 'cto' | 'ceo' | 'program_coordinator' | 'admin';
    email_verified_at: string | null;
    created_at: string;
    applications: Application[];
}

interface Props {
    user: User;
    stats: {
        total_applications: number;
        accepted_applications: number;
        pending_applications: number;
    };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'accepted':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    }
};

const formatCategory = (cat: string) =>
    cat.replace(/-/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());

const getRoleBadgeColor = (role: string) => {
    if (role === 'cto' || role === 'ceo' || role === 'admin') {
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400';
    }

    if (role === 'program_coordinator') {
        return 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400';
    }

    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
};

const formatRole = (role: string) => {
    if (role === 'admin') return 'CTO (Legacy)';
    return role.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};
</script>

<template>
    <div class="mx-auto max-w-5xl">
        <Head :title="`User - ${user.name}`" />

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <Link
                    href="/admin/users"
                    class="mb-2 inline-block text-sm text-[#42b6c5] hover:text-[#35919e]"
                >
                    ← Back to Users
                </Link>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    {{ user.name }}
                </h2>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    Member since {{ formatDate(user.created_at) }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span
                    :class="[
                        'rounded-full px-3 py-1 text-sm font-medium',
                        getRoleBadgeColor(user.role),
                    ]"
                >
                    {{ formatRole(user.role) }}
                </span>
                <Link
                    :href="`/admin/users/${user.id}/edit`"
                    class="rounded-lg bg-[#42b6c5] px-4 py-2 font-medium text-white transition-colors hover:bg-[#35919e]"
                >
                    Edit User
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="space-y-6 lg:col-span-2">
                <!-- User Information -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3
                        class="mb-4 border-b pb-2 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-gray-100"
                    >
                        User Information
                    </h3>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-500 dark:text-gray-400"
                                >Full Name</label
                            >
                            <p class="mt-1 text-gray-900 dark:text-gray-100">
                                {{ user.name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-500 dark:text-gray-400"
                                >Email Address</label
                            >
                            <p class="mt-1">
                                <a
                                    :href="`mailto:${user.email}`"
                                    class="text-[#42b6c5] hover:underline"
                                    >{{ user.email }}</a
                                >
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-500 dark:text-gray-400"
                                >Phone Number</label
                            >
                            <p class="mt-1 text-gray-900 dark:text-gray-100">
                                {{ user.phone || 'Not provided' }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-500 dark:text-gray-400"
                                >Email Verified</label
                            >
                            <p class="mt-1">
                                <span
                                    v-if="user.email_verified_at"
                                    class="font-medium text-green-600 dark:text-green-400"
                                >
                                    ✓ Verified on
                                    {{ formatDate(user.email_verified_at) }}
                                </span>
                                <span
                                    v-else
                                    class="font-medium text-yellow-600 dark:text-yellow-400"
                                >
                                    ⚠ Not verified
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3
                        class="mb-4 border-b pb-2 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-gray-100"
                    >
                        Recent Applications
                    </h3>
                    <div v-if="user.applications.length > 0" class="space-y-4">
                        <div
                            v-for="application in user.applications"
                            :key="application.id"
                            class="flex items-center justify-between rounded-lg bg-gray-50 p-4 dark:bg-gray-700"
                        >
                            <div>
                                <Link
                                    :href="`/admin/applications/${application.id}`"
                                    class="font-medium text-[#42b6c5] hover:underline"
                                >
                                    {{
                                        application.program?.title ||
                                        'Unknown Program'
                                    }}
                                </Link>
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{
                                        formatCategory(
                                            application.program?.category || '',
                                        )
                                    }}
                                    • Applied
                                    {{ formatDate(application.created_at) }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'rounded-full px-2 py-1 text-xs font-medium',
                                    getStatusColor(application.status),
                                ]"
                            >
                                {{
                                    application.status.charAt(0).toUpperCase() +
                                    application.status.slice(1)
                                }}
                            </span>
                        </div>
                    </div>
                    <p
                        v-else
                        class="py-8 text-center text-gray-500 dark:text-gray-400"
                    >
                        No applications yet
                    </p>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Stats -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3
                        class="mb-4 border-b pb-2 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-gray-100"
                    >
                        Application Stats
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Total Applications</span
                            >
                            <span
                                class="font-bold text-gray-900 dark:text-gray-100"
                                >{{ stats.total_applications }}</span
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Accepted</span
                            >
                            <span
                                class="font-bold text-green-600 dark:text-green-400"
                                >{{ stats.accepted_applications }}</span
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                                >Pending</span
                            >
                            <span
                                class="font-bold text-yellow-600 dark:text-yellow-400"
                                >{{ stats.pending_applications }}</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3
                        class="mb-4 border-b pb-2 text-lg font-semibold text-gray-900 dark:border-gray-700 dark:text-gray-100"
                    >
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <Link
                            :href="`/admin/users/${user.id}/edit`"
                            class="block w-full rounded-lg bg-[#42b6c5] px-4 py-2 text-center font-medium text-white transition-colors hover:bg-[#35919e]"
                        >
                            Edit User
                        </Link>
                        <a
                            :href="`mailto:${user.email}`"
                            class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-center font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Send Email
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
