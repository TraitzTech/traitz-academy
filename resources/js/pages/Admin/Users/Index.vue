<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: 'user' | 'cto' | 'ceo' | 'program_coordinator' | 'admin';
    email_verified_at: string | null;
    created_at: string;
    applications_count: number;
}

interface Props {
    users: {
        data: User[];
        links: any[];
    };
    filters: {
        search?: string;
        role?: string;
    };
    roleOptions: string[];
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const toast = useToast();

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingUser = ref<User | null>(null);

// Selection state
const selectedIds = ref<number[]>([]);

const allSelected = computed(() => {
    return (
        props.users.data.length > 0 &&
        selectedIds.value.length === props.users.data.length
    );
});

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.users.data.map((u) => u.id);
    }
};

const toggleSelect = (id: number) => {
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
};

// Delete modal state
const showDeleteModal = ref(false);
const showBulkDeleteModal = ref(false);
const userToDelete = ref<User | null>(null);

const createForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});

const applyFilters = debounce(() => {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            role: role.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}, 300);

watch([search, role], applyFilters);

const openCreateModal = () => {
    createForm.reset();
    showCreateModal.value = true;
};

const createUser = () => {
    createForm.post('/admin/users', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            // Flash message handled by global watcher (UserController::store flashes 'success')
        },
        onError: () => {
            toast.error(
                'Failed to create user. Please check the form for errors.',
            );
        },
    });
};

const openEditModal = (user: User) => {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.phone = user.phone || '';
    editForm.role = user.role;
    editForm.password = '';
    editForm.password_confirmation = '';
    showEditModal.value = true;
};

const updateUser = () => {
    if (!editingUser.value) return;
    editForm.put(`/admin/users/${editingUser.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingUser.value = null;
            // Flash message handled by global watcher (UserController::update flashes 'success')
        },
        onError: () => {
            toast.error(
                'Failed to update user. Please check the form for errors.',
            );
        },
    });
};

const deleteUser = (user: User) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!userToDelete.value) return;

    router.delete(`/admin/users/${userToDelete.value.id}`, {
        onSuccess: () => {
            // Flash message handled by global watcher (UserController::destroy flashes 'success')
            showDeleteModal.value = false;
            userToDelete.value = null;
        },
        onError: () => {
            toast.error('Failed to delete user.');
        },
    });
};

const openBulkDeleteModal = () => {
    if (selectedIds.value.length === 0) {
        toast.error('Please select at least one user to delete.');
        return;
    }
    showBulkDeleteModal.value = true;
};

const confirmBulkDelete = () => {
    router.post(
        '/admin/users/bulk-destroy',
        { ids: selectedIds.value },
        {
            onSuccess: () => {
                // Flash message handled by global watcher (UserController::bulkDestroy flashes 'success')
                selectedIds.value = [];
                showBulkDeleteModal.value = false;
            },
            onError: () => {
                toast.error('Failed to delete users.');
            },
        },
    );
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getRoleBadgeColor = (role: string) => {
    if (role === 'cto' || role === 'ceo' || role === 'admin') {
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400';
    }

    if (role === 'program_coordinator') {
        return 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400';
    }

    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const formatRole = (role: string) => {
    if (role === 'admin') return 'CTO (Legacy)';
    // The base "user" role is every applicant/learner — label it "Student" so
    // it's unambiguous when filtering/exporting contacts.
    if (role === 'user') return 'Student';
    return role
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

// Export
const showExportDropdown = ref(false);
const exportDropdownRef = ref<HTMLElement | null>(null);

const exportUrl = (format: string) => {
    const params = new URLSearchParams();
    params.set('format', format);
    if (search.value) params.set('search', search.value);
    if (role.value) params.set('role', role.value);
    return `/admin/users/export?${params.toString()}`;
};

const handleClickOutside = (event: MouseEvent) => {
    if (
        exportDropdownRef.value &&
        !exportDropdownRef.value.contains(event.target as Node)
    ) {
        showExportDropdown.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div>
        <Head title="User Management" />

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    User Management
                </h2>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Manage all users and assign roles
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button
                    v-if="selectedIds.length > 0"
                    @click="openBulkDeleteModal"
                    class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-red-700"
                >
                    <svg
                        class="mr-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                    Delete Selected ({{ selectedIds.length }})
                </button>
                <!-- Export Dropdown -->
                <div class="relative" ref="exportDropdownRef">
                    <button
                        @click="showExportDropdown = !showExportDropdown"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg
                            class="mr-2 h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        Export
                        <svg
                            class="ml-1 h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>
                    <div
                        v-if="showExportDropdown"
                        class="absolute right-0 z-50 mt-2 w-56 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="py-1">
                            <a
                                :href="exportUrl('csv')"
                                @click="showExportDropdown = false"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <svg
                                    class="mr-3 h-4 w-4 text-green-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Export as CSV
                            </a>
                            <a
                                :href="exportUrl('xlsx')"
                                @click="showExportDropdown = false"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <svg
                                    class="mr-3 h-4 w-4 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Export as Excel
                            </a>
                            <hr
                                class="my-1 border-gray-200 dark:border-gray-700"
                            />
                            <a
                                :href="exportUrl('phones')"
                                @click="showExportDropdown = false"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <svg
                                    class="mr-3 h-4 w-4 text-purple-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                    />
                                </svg>
                                Export Phone Numbers
                            </a>
                        </div>
                    </div>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center rounded-lg bg-[#42b6c5] px-4 py-2 font-medium text-white transition-colors hover:bg-[#35919e]"
                >
                    <svg
                        class="mr-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                        />
                    </svg>
                    Add User
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Search</label
                    >
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name or email..."
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Role</label
                    >
                    <select
                        v-model="role"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">All Roles</option>
                        <option
                            v-for="option in roleOptions"
                            :key="option"
                            :value="option"
                        >
                            {{ formatRole(option) }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div
            class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800"
        >
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-600"
                                />
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                User
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Phone
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Role
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Applications
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Verified
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Joined
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800"
                    >
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            :class="[
                                'hover:bg-gray-50 dark:hover:bg-gray-700',
                                selectedIds.includes(user.id)
                                    ? 'bg-cyan-50 dark:bg-cyan-900/20'
                                    : '',
                            ]"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(user.id)"
                                    @change="toggleSelect(user.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-600"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-[#42b6c5]"
                                    >
                                        <span class="font-medium text-white">{{
                                            user.name.charAt(0).toUpperCase()
                                        }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            {{ user.name }}
                                        </div>
                                        <div
                                            class="text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                <span v-if="user.phone">{{ user.phone }}</span>
                                <span
                                    v-else
                                    class="text-gray-300 dark:text-gray-600"
                                    >—</span
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-1 text-xs font-medium',
                                        getRoleBadgeColor(user.role),
                                    ]"
                                >
                                    {{ formatRole(user.role) }}
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ user.applications_count || 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-if="user.email_verified_at"
                                    class="text-green-600 dark:text-green-400"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </span>
                                <span
                                    v-else
                                    class="text-gray-400 dark:text-gray-500"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        @click="openEditModal(user)"
                                        class="text-[#42b6c5] hover:text-[#35919e]"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteUser(user)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td
                                colspan="8"
                                class="px-6 py-10 text-center text-gray-500 dark:text-gray-400"
                            >
                                No users found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="users.links && users.links.length > 3"
                class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6 dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex items-center justify-center">
                    <nav
                        class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm"
                        aria-label="Pagination"
                    >
                        <Link
                            v-for="(link, index) in users.links"
                            :key="index"
                            :href="link.url || '#'"
                            :class="[
                                'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                                link.active
                                    ? 'z-10 border-[#42b6c5] bg-[#42b6c5] text-white'
                                    : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                                !link.url
                                    ? 'cursor-not-allowed opacity-50'
                                    : '',
                                index === 0 ? 'rounded-l-md' : '',
                                index === users.links.length - 1
                                    ? 'rounded-r-md'
                                    : '',
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 overflow-y-auto"
            >
                <div
                    class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0"
                >
                    <div
                        class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity"
                        @click="showCreateModal = false"
                    ></div>
                    <div
                        class="relative inline-block transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg dark:bg-gray-800"
                    >
                        <h3
                            class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Create New User
                        </h3>
                        <form @submit.prevent="createUser" class="space-y-4">
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Name</label
                                >
                                <input
                                    v-model="createForm.name"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p
                                    v-if="createForm.errors.name"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ createForm.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Email</label
                                >
                                <input
                                    v-model="createForm.email"
                                    type="email"
                                    required
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p
                                    v-if="createForm.errors.email"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ createForm.errors.email }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Phone (WhatsApp)</label
                                >
                                <input
                                    v-model="createForm.phone"
                                    type="text"
                                    placeholder="e.g. +1234567890"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p
                                    v-if="createForm.errors.phone"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ createForm.errors.phone }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Password (optional)</label
                                >
                                <input
                                    v-model="createForm.password"
                                    type="password"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p
                                    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Leave blank to auto-generate a secure
                                    password and send it by email.
                                </p>
                                <p
                                    v-if="createForm.errors.password"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ createForm.errors.password }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Confirm Password</label
                                >
                                <input
                                    v-model="createForm.password_confirmation"
                                    type="password"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Role</label
                                >
                                <select
                                    v-model="createForm.role"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                >
                                    <option
                                        v-for="option in roleOptions"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ formatRole(option) }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="showCreateModal = false"
                                    class="rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="createForm.processing"
                                    class="rounded-lg bg-[#42b6c5] px-4 py-2 font-medium text-white transition-colors hover:bg-[#35919e] disabled:opacity-50"
                                >
                                    Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Modal -->
        <Teleport to="body">
            <div
                v-if="showEditModal"
                class="fixed inset-0 z-50 overflow-y-auto"
            >
                <div
                    class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:p-0"
                >
                    <div
                        class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity"
                        @click="showEditModal = false"
                    ></div>
                    <div
                        class="relative inline-block transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg dark:bg-gray-800"
                    >
                        <h3
                            class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Edit User
                        </h3>
                        <form @submit.prevent="updateUser" class="space-y-4">
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Name</label
                                >
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p
                                    v-if="editForm.errors.name"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ editForm.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Email</label
                                >
                                <input
                                    v-model="editForm.email"
                                    type="email"
                                    required
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p
                                    v-if="editForm.errors.email"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ editForm.errors.email }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Phone (WhatsApp)</label
                                >
                                <input
                                    v-model="editForm.phone"
                                    type="text"
                                    placeholder="e.g. +1234567890"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p
                                    v-if="editForm.errors.phone"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ editForm.errors.phone }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Password (leave blank to keep
                                    current)</label
                                >
                                <input
                                    v-model="editForm.password"
                                    type="password"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                                <p
                                    v-if="editForm.errors.password"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ editForm.errors.password }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Confirm Password</label
                                >
                                <input
                                    v-model="editForm.password_confirmation"
                                    type="password"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Role</label
                                >
                                <select
                                    v-model="editForm.role"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                >
                                    <option
                                        v-for="option in roleOptions"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ formatRole(option) }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="showEditModal = false"
                                    class="rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="editForm.processing"
                                    class="rounded-lg bg-[#42b6c5] px-4 py-2 font-medium text-white transition-colors hover:bg-[#35919e] disabled:opacity-50"
                                >
                                    Update User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            :open="showDeleteModal"
            title="Delete User"
            :description="`Are you sure you want to delete &quot;${userToDelete?.name}&quot;? This action cannot be undone.`"
            confirm-text="Delete"
            variant="destructive"
            @update:open="showDeleteModal = $event"
            @confirm="confirmDelete"
        />

        <!-- Bulk Delete Confirmation Modal -->
        <ConfirmationModal
            :open="showBulkDeleteModal"
            title="Delete Multiple Users"
            :description="`Are you sure you want to delete ${selectedIds.length} user(s)? This action cannot be undone.`"
            :confirm-text="`Delete ${selectedIds.length} User(s)`"
            variant="destructive"
            @update:open="showBulkDeleteModal = $event"
            @confirm="confirmBulkDelete"
        />
    </div>
</template>
