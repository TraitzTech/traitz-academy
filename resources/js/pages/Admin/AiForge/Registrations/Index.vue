<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { computed, ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface Registration {
    id: number;
    full_name: string;
    email: string;
    phone: string | null;
    occupation: string | null;
    status: string;
    created_at: string;
    confirmed_at: string | null;
}

interface Props {
    event: { id: number; title: string } | null;
    registrations: { data: Registration[]; links: any[] };
    filters: { search?: string; status?: string };
    stats: {
        total: number;
        confirmed: number;
        pending: number;
        cancelled: number;
    };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const toast = useToast();
const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const selectedIds = ref<number[]>([]);
const showDeleteModal = ref(false);
const regToDelete = ref<Registration | null>(null);
const showBulkModal = ref(false);
const bulkAction = ref<'confirmed' | 'cancelled'>('confirmed');

const allSelected = computed(
    () =>
        props.registrations.data.length > 0 &&
        selectedIds.value.length === props.registrations.data.length,
);

const toggleSelectAll = () => {
    selectedIds.value = allSelected.value
        ? []
        : props.registrations.data.map((r) => r.id);
};

const toggleSelect = (id: number) => {
    const idx = selectedIds.value.indexOf(id);
    idx > -1 ? selectedIds.value.splice(idx, 1) : selectedIds.value.push(id);
};

const applyFilters = debounce(() => {
    router.get(
        '/admin/ai-forge/registrations',
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}, 300);

watch([search, statusFilter], applyFilters);

const updateStatus = (reg: Registration, status: string) => {
    router.patch(
        `/admin/ai-forge/registrations/${reg.id}`,
        { status },
        {
            preserveState: true,
            // Flash message handled by global watcher (AiForgeRegistrationController::updateStatus flashes 'success')
        },
    );
};

const deleteReg = (reg: Registration) => {
    regToDelete.value = reg;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!regToDelete.value) return;
    router.delete(`/admin/ai-forge/registrations/${regToDelete.value.id}`, {
        onSuccess: () => {
            // Flash message handled by global watcher (AiForgeRegistrationController::destroy flashes 'success')
            showDeleteModal.value = false;
            regToDelete.value = null;
        },
    });
};

const openBulkAction = (action: 'confirmed' | 'cancelled') => {
    if (selectedIds.value.length === 0) {
        toast.error('Select at least one registration.');
        return;
    }
    bulkAction.value = action;
    showBulkModal.value = true;
};

const confirmBulk = () => {
    router.post(
        '/admin/ai-forge/registrations/bulk-update',
        {
            ids: selectedIds.value,
            status: bulkAction.value,
        },
        {
            onSuccess: () => {
                // Flash message handled by global watcher (AiForgeRegistrationController::bulkUpdateStatus flashes 'success')
                selectedIds.value = [];
                showBulkModal.value = false;
            },
        },
    );
};

const sendReminder = (reg: Registration) => {
    router.post(
        `/admin/ai-forge/registrations/${reg.id}/send-reminder`,
        {},
        {
            preserveState: true,
            // Flash message handled by global watcher (AiForgeRegistrationController::sendReminder flashes 'success')
        },
    );
};

const exportCsv = () => {
    window.location.href = '/admin/ai-forge/registrations/export';
};

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

const statusColors: Record<string, string> = {
    pending:
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    confirmed:
        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};
</script>

<template>
    <div>
        <Head title="AI Forge Registrations" />

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Registrations
                </h2>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ event?.title ?? 'AI Forge' }} &mdash;
                    {{ stats.total }} total registrations
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="exportCsv"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Export CSV
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ stats.total }}
                </p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Confirmed
                </p>
                <p class="text-2xl font-bold text-green-600">
                    {{ stats.confirmed }}
                </p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Pending</p>
                <p class="text-2xl font-bold text-yellow-600">
                    {{ stats.pending }}
                </p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Cancelled
                </p>
                <p class="text-2xl font-bold text-red-600">
                    {{ stats.cancelled }}
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                        >Status</label
                    >
                    <select
                        v-model="statusFilter"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div
            v-if="selectedIds.length > 0"
            class="mb-4 flex items-center justify-between rounded-lg border border-cyan-200 bg-cyan-50 p-4 dark:border-cyan-800 dark:bg-cyan-900/20"
        >
            <span class="font-semibold text-gray-900 dark:text-gray-100"
                >{{ selectedIds.length }} selected</span
            >
            <div class="flex gap-2">
                <button
                    @click="openBulkAction('confirmed')"
                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                >
                    Confirm All
                </button>
                <button
                    @click="openBulkAction('cancelled')"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                >
                    Cancel All
                </button>
            </div>
        </div>

        <!-- Table -->
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
                                    class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:bg-gray-700"
                                />
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Email
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Phone
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Registered
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <tr
                            v-for="reg in registrations.data"
                            :key="reg.id"
                            :class="[
                                'hover:bg-gray-50 dark:hover:bg-gray-700',
                                selectedIds.includes(reg.id)
                                    ? 'bg-cyan-50 dark:bg-cyan-900/20'
                                    : '',
                            ]"
                        >
                            <td class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(reg.id)"
                                    @change="toggleSelect(reg.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:bg-gray-700"
                                />
                            </td>
                            <td
                                class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100"
                            >
                                {{ reg.full_name }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ reg.email }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ reg.phone || '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-1 text-xs font-medium',
                                        statusColors[reg.status] || '',
                                    ]"
                                    >{{ reg.status }}</span
                                >
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ formatDate(reg.created_at) }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        v-if="reg.status === 'pending'"
                                        @click="updateStatus(reg, 'confirmed')"
                                        class="text-green-600 hover:text-green-900 dark:text-green-400"
                                    >
                                        Confirm
                                    </button>
                                    <button
                                        v-if="reg.status !== 'cancelled'"
                                        @click="updateStatus(reg, 'cancelled')"
                                        class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="sendReminder(reg)"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                                    >
                                        Remind
                                    </button>
                                    <button
                                        @click="deleteReg(reg)"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="registrations.data.length === 0">
                            <td
                                colspan="7"
                                class="px-6 py-10 text-center text-gray-500 dark:text-gray-400"
                            >
                                No registrations found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="registrations.links && registrations.links.length > 3"
                class="border-t border-gray-200 px-4 py-3 sm:px-6 dark:border-gray-700"
            >
                <div class="flex items-center justify-center">
                    <nav
                        class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm"
                    >
                        <Link
                            v-for="(link, index) in registrations.links"
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
                                index === registrations.links.length - 1
                                    ? 'rounded-r-md'
                                    : '',
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :open="showDeleteModal"
            title="Delete Registration"
            :description="`Delete registration for &quot;${regToDelete?.full_name}&quot;? This cannot be undone.`"
            confirm-text="Delete"
            variant="destructive"
            @update:open="showDeleteModal = $event"
            @confirm="confirmDelete"
        />

        <ConfirmationModal
            :open="showBulkModal"
            :title="`${bulkAction === 'confirmed' ? 'Confirm' : 'Cancel'} Registrations`"
            :description="`${bulkAction === 'confirmed' ? 'Confirm' : 'Cancel'} ${selectedIds.length} registration(s)?`"
            :confirm-text="`${bulkAction === 'confirmed' ? 'Confirm' : 'Cancel'} ${selectedIds.length}`"
            :variant="bulkAction === 'cancelled' ? 'destructive' : 'default'"
            @update:open="showBulkModal = $event"
            @confirm="confirmBulk"
        />
    </div>
</template>
