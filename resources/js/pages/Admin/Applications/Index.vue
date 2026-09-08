<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { computed, ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface Application {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    country: string;
    status: 'pending' | 'accepted' | 'rejected';
    created_at: string;
    reviewed_at: string | null;
    program: {
        id: number;
        title: string;
        category: string;
    };
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
    payment_summary: {
        program_price: number;
        paid_amount: number;
        remaining_amount: number;
        max_installments: number;
        completed_installments: number;
        status: 'paid' | 'partially-paid' | 'unpaid' | 'not-required';
        can_send_reminder: boolean;
    };
}

interface Program {
    id: number;
    title: string;
}

interface InterviewOption {
    id: number;
    title: string;
    program_id: number | null;
    questions_count: number;
}

interface Props {
    applications: {
        data: Application[];
        links: any[];
    };
    filters: {
        search?: string;
        status?: string;
        program_id?: string;
        country?: string;
        interview_status?: string;
        submitted_from?: string;
        submitted_to?: string;
    };
    stats: {
        total: number;
        pending: number;
        accepted: number;
        rejected: number;
    };
    programs: Program[];
    interviews: InterviewOption[];
    countries: string[];
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const toast = useToast();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const programId = ref(props.filters.program_id || '');
const country = ref(props.filters.country || '');
const interviewStatus = ref(props.filters.interview_status || '');
const submittedFrom = ref(props.filters.submitted_from || '');
const submittedTo = ref(props.filters.submitted_to || '');
const selectedIds = ref<number[]>([]);
const showRejectModal = ref(false);
const rejectingApp = ref<Application | null>(null);
const rejectNotes = ref('');
const showAcceptModal = ref(false);
const acceptingApp = ref<Application | null>(null);

const applyFilters = debounce(() => {
    router.get(
        '/admin/applications',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            program_id: programId.value || undefined,
            country: country.value || undefined,
            interview_status: interviewStatus.value || undefined,
            submitted_from: submittedFrom.value || undefined,
            submitted_to: submittedTo.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}, 300);

watch(
    [
        search,
        status,
        programId,
        country,
        interviewStatus,
        submittedFrom,
        submittedTo,
    ],
    applyFilters,
);

const allSelected = computed(() => {
    return (
        props.applications.data.length > 0 &&
        selectedIds.value.length === props.applications.data.length
    );
});

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.applications.data.map((a) => a.id);
    }
};

const openAcceptModal = (app: Application) => {
    acceptingApp.value = app;
    showAcceptModal.value = true;
};

const acceptApplication = () => {
    if (!acceptingApp.value) return;
    const app = acceptingApp.value;
    router.post(
        `/admin/applications/${app.id}/accept`,
        {},
        {
            preserveState: true,
            onSuccess: () => {
                showAcceptModal.value = false;
                acceptingApp.value = null;
                // Flash message handled by global watcher (ApplicationController::accept flashes 'success')
            },
            onError: () => {
                toast.error('Failed to accept application.');
            },
        },
    );
};

const openRejectModal = (app: Application) => {
    rejectingApp.value = app;
    rejectNotes.value = '';
    showRejectModal.value = true;
};

const rejectApplication = () => {
    if (!rejectingApp.value) return;
    const appName = `${rejectingApp.value.first_name} ${rejectingApp.value.last_name}`;
    router.post(
        `/admin/applications/${rejectingApp.value.id}/reject`,
        {
            notes: rejectNotes.value,
        },
        {
            preserveState: true,
            onSuccess: () => {
                showRejectModal.value = false;
                rejectingApp.value = null;
                // Flash message handled by global watcher (ApplicationController::reject flashes 'success')
            },
            onError: () => {
                toast.error('Failed to reject application.');
            },
        },
    );
};

const showDeleteModal = ref(false);
const deletingApp = ref<Application | null>(null);

const openDeleteModal = (app: Application) => {
    deletingApp.value = app;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!deletingApp.value) return;
    router.delete(`/admin/applications/${deletingApp.value.id}`, {
        onSuccess: () => {
            // Flash message handled by global watcher (ApplicationController::destroy flashes 'success')
            showDeleteModal.value = false;
            deletingApp.value = null;
        },
        onError: () => {
            toast.error('Failed to delete application.');
        },
    });
};

const showBulkDeleteModal = ref(false);
const showBulkAcceptModal = ref(false);
const showBulkRejectModal = ref(false);
const showBulkPaymentReminderModal = ref(false);

const bulkAction = (action: string) => {
    if (selectedIds.value.length === 0) {
        toast.warning('Please select at least one application');
        return;
    }
    if (action === 'delete') {
        showBulkDeleteModal.value = true;
        return;
    }
    if (action === 'accept') {
        showBulkAcceptModal.value = true;
        return;
    }
    if (action === 'reject') {
        showBulkRejectModal.value = true;
        return;
    }
    if (action === 'payment-reminder') {
        showBulkPaymentReminderModal.value = true;
        return;
    }
};

const confirmBulkAccept = () => {
    router.post(
        '/admin/applications/bulk',
        {
            ids: selectedIds.value,
            action: 'accept',
        },
        {
            preserveState: true,
            onSuccess: () => {
                const count = selectedIds.value.length;
                selectedIds.value = [];
                showBulkAcceptModal.value = false;
                // Flash message handled by global watcher (ApplicationController::bulkAction flashes 'success')
            },
            onError: () => {
                toast.error('Failed to accept selected applications.');
            },
        },
    );
};

const confirmBulkReject = () => {
    router.post(
        '/admin/applications/bulk',
        {
            ids: selectedIds.value,
            action: 'reject',
        },
        {
            preserveState: true,
            onSuccess: () => {
                const count = selectedIds.value.length;
                selectedIds.value = [];
                showBulkRejectModal.value = false;
                // Flash message handled by global watcher (ApplicationController::bulkAction flashes 'success')
            },
            onError: () => {
                toast.error('Failed to reject selected applications.');
            },
        },
    );
};

const confirmBulkDelete = () => {
    router.post(
        '/admin/applications/bulk',
        {
            ids: selectedIds.value,
            action: 'delete',
        },
        {
            preserveState: true,
            onSuccess: () => {
                const count = selectedIds.value.length;
                selectedIds.value = [];
                showBulkDeleteModal.value = false;
                // Flash message handled by global watcher (ApplicationController::bulkAction flashes 'success')
            },
            onError: () => {
                toast.error('Failed to delete selected applications.');
            },
        },
    );
};

const sendPaymentReminder = (app: Application) => {
    router.post(
        `/admin/applications/${app.id}/payment-reminder`,
        {},
        {
            preserveState: true,
            onSuccess: () => {
                // Flash message handled by global watcher (ApplicationController::sendPaymentReminder flashes 'success'/'error')
            },
            onError: () => {
                toast.error('Failed to send payment reminder.');
            },
        },
    );
};

const confirmBulkPaymentReminder = () => {
    router.post(
        '/admin/applications/bulk-payment-reminder',
        {
            ids: selectedIds.value,
        },
        {
            preserveState: true,
            onSuccess: () => {
                showBulkPaymentReminderModal.value = false;
                selectedIds.value = [];
                // Flash message handled by global watcher (ApplicationController::bulkPaymentReminder flashes 'success'/'error')
            },
            onError: () => {
                toast.error('Failed to send bulk payment reminders.');
            },
        },
    );
};

// Bulk Schedule Interview
const showBulkScheduleModal = ref(false);
const selectedInterviewId = ref<number | null>(null);

const openBulkScheduleModal = () => {
    if (selectedIds.value.length === 0) {
        toast.warning('Please select at least one application');
        return;
    }
    selectedInterviewId.value = null;
    showBulkScheduleModal.value = true;
};

const confirmBulkSchedule = () => {
    if (!selectedInterviewId.value) {
        toast.warning('Please select an interview');
        return;
    }
    router.post(
        '/admin/applications/bulk-schedule-interview',
        {
            ids: selectedIds.value,
            interview_id: selectedInterviewId.value,
        },
        {
            preserveState: true,
            onSuccess: () => {
                const count = selectedIds.value.length;
                selectedIds.value = [];
                showBulkScheduleModal.value = false;
                selectedInterviewId.value = null;
                // Flash message handled by global watcher (ApplicationController::bulkScheduleInterview flashes 'success')
            },
            onError: () => {
                toast.error('Failed to schedule interviews.');
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

const getPaymentStatusColor = (status: string) => {
    switch (status) {
        case 'paid':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'partially-paid':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'unpaid':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const formatMoney = (amount: number) => {
    return new Intl.NumberFormat('en-CM', {
        style: 'currency',
        currency: 'XAF',
    }).format(amount || 0);
};
</script>

<template>
    <div>
        <Head title="Application Reviews" />

        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Application Reviews
            </h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Review and manage all program applications
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div
                class="rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow dark:bg-gray-800"
            >
                <div
                    class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                >
                    {{ stats.total }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Total Applications
                </div>
            </div>
            <div
                class="rounded-lg border-l-4 border-yellow-500 bg-white p-4 shadow dark:bg-gray-800"
            >
                <div
                    class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                >
                    {{ stats.pending }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Pending Review
                </div>
            </div>
            <div
                class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow dark:bg-gray-800"
            >
                <div
                    class="text-2xl font-bold text-green-600 dark:text-green-400"
                >
                    {{ stats.accepted }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Accepted
                </div>
            </div>
            <div
                class="rounded-lg border-l-4 border-red-500 bg-white p-4 shadow dark:bg-gray-800"
            >
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ stats.rejected }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Rejected
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                        v-model="status"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Program</label
                    >
                    <select
                        v-model="programId"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">All Programs</option>
                        <option
                            v-for="program in programs"
                            :key="program.id"
                            :value="program.id"
                        >
                            {{ program.title }}
                        </option>
                    </select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Country</label
                    >
                    <select
                        v-model="country"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">All Countries</option>
                        <option
                            v-for="item in countries"
                            :key="item"
                            :value="item"
                        >
                            {{ item }}
                        </option>
                    </select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Interview</label
                    >
                    <select
                        v-model="interviewStatus"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">All Interview Statuses</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Submitted From</label
                    >
                    <input
                        v-model="submittedFrom"
                        type="date"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Submitted To</label
                    >
                    <input
                        v-model="submittedTo"
                        type="date"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    />
                </div>
                <div class="col-span-full flex items-end">
                    <div
                        v-if="selectedIds.length > 0"
                        class="flex w-full flex-wrap items-center gap-2"
                    >
                        <button
                            @click="bulkAction('accept')"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium whitespace-nowrap text-white transition-colors hover:bg-green-700"
                        >
                            Accept ({{ selectedIds.length }})
                        </button>
                        <button
                            @click="bulkAction('reject')"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium whitespace-nowrap text-white transition-colors hover:bg-red-700"
                        >
                            Reject
                        </button>
                        <button
                            @click="openBulkScheduleModal"
                            class="rounded-lg bg-[#42b6c5] px-4 py-2 text-sm font-medium whitespace-nowrap text-white transition-colors hover:bg-[#35919e]"
                        >
                            Schedule Interview
                        </button>
                        <button
                            @click="bulkAction('payment-reminder')"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium whitespace-nowrap text-white transition-colors hover:bg-indigo-700"
                        >
                            Payment Reminder
                        </button>
                        <button
                            @click="bulkAction('delete')"
                            class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium whitespace-nowrap text-white transition-colors hover:bg-black"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Table -->
        <div
            class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800"
        >
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleSelectAll"
                                    class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600"
                                />
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Applicant
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Program
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Payment
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Status
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
                            v-for="app in applications.data"
                            :key="app.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        >
                            <td class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    :value="app.id"
                                    v-model="selectedIds"
                                    class="rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-600"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div
                                    class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                >
                                    {{ app.first_name }} {{ app.last_name }}
                                </div>
                                <div
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ app.email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div
                                    class="text-sm text-gray-900 dark:text-gray-100"
                                >
                                    {{ app.program?.title || 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                            getPaymentStatusColor(
                                                app.payment_summary.status,
                                            ),
                                        ]"
                                    >
                                        {{
                                            app.payment_summary.status ===
                                            'partially-paid'
                                                ? 'Partially Paid'
                                                : app.payment_summary.status.replace(
                                                      '-',
                                                      ' ',
                                                  )
                                        }}
                                    </span>
                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{
                                            formatMoney(
                                                app.payment_summary.paid_amount,
                                            )
                                        }}
                                        /
                                        {{
                                            formatMoney(
                                                app.payment_summary
                                                    .program_price,
                                            )
                                        }}
                                    </div>
                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Installments:
                                        {{
                                            app.payment_summary
                                                .completed_installments
                                        }}/{{
                                            app.payment_summary.max_installments
                                        }}
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ formatDate(app.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-1 text-xs font-medium',
                                        getStatusColor(app.status),
                                    ]"
                                >
                                    {{
                                        app.status.charAt(0).toUpperCase() +
                                        app.status.slice(1)
                                    }}
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="`/admin/applications/${app.id}`"
                                        class="text-[#42b6c5] hover:text-[#35919e]"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="`/admin/applications/${app.id}/edit`"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Edit
                                    </Link>
                                    <template v-if="app.status === 'pending'">
                                        <button
                                            @click="openAcceptModal(app)"
                                            class="text-green-600 hover:text-green-900"
                                        >
                                            Accept
                                        </button>
                                        <button
                                            @click="openRejectModal(app)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Reject
                                        </button>
                                    </template>
                                    <button
                                        v-if="
                                            app.payment_summary
                                                .can_send_reminder
                                        "
                                        @click="sendPaymentReminder(app)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Remind
                                    </button>
                                    <button
                                        @click="openDeleteModal(app)"
                                        class="text-gray-400 hover:text-red-600"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="applications.data.length === 0">
                            <td
                                colspan="7"
                                class="px-6 py-10 text-center text-gray-500 dark:text-gray-400"
                            >
                                No applications found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="applications.links && applications.links.length > 3"
                class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6 dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex items-center justify-center">
                    <nav
                        class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm"
                        aria-label="Pagination"
                    >
                        <Link
                            v-for="(link, index) in applications.links"
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
                                index === applications.links.length - 1
                                    ? 'rounded-r-md'
                                    : '',
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>

        <!-- Accept Confirmation Modal -->
        <ConfirmationModal
            :open="showAcceptModal"
            title="Accept Application"
            :description="`Are you sure you want to accept the application from ${acceptingApp?.first_name} ${acceptingApp?.last_name}? They will be notified via email.`"
            confirm-text="Accept Application"
            variant="default"
            @update:open="showAcceptModal = $event"
            @confirm="acceptApplication"
        />

        <!-- Reject Modal -->
        <Dialog :open="showRejectModal" @update:open="showRejectModal = $event">
            <DialogContent>
                <DialogHeader class="space-y-3">
                    <DialogTitle>Reject Application</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to reject the application from
                        {{ rejectingApp?.first_name }}
                        {{ rejectingApp?.last_name }}?
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <label class="text-sm font-medium">Notes (optional)</label>
                    <textarea
                        v-model="rejectNotes"
                        rows="3"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        placeholder="Internal notes about rejection reason..."
                    ></textarea>
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="rejectApplication">
                        Reject Application
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Modal -->
        <ConfirmationModal
            :open="showDeleteModal"
            title="Delete Application"
            :description="`Are you sure you want to delete the application from ${deletingApp?.first_name} ${deletingApp?.last_name}? This action cannot be undone.`"
            confirm-text="Delete"
            variant="destructive"
            @update:open="showDeleteModal = $event"
            @confirm="confirmDelete"
        />

        <!-- Bulk Accept Modal -->
        <ConfirmationModal
            :open="showBulkAcceptModal"
            title="Accept Selected Applications"
            :description="`Are you sure you want to accept ${selectedIds.length} selected application(s)? Each applicant will be notified via email.`"
            confirm-text="Accept Selected"
            variant="default"
            @update:open="showBulkAcceptModal = $event"
            @confirm="confirmBulkAccept"
        />

        <!-- Bulk Reject Modal -->
        <ConfirmationModal
            :open="showBulkRejectModal"
            title="Reject Selected Applications"
            :description="`Are you sure you want to reject ${selectedIds.length} selected application(s)? Each applicant will be notified via email.`"
            confirm-text="Reject Selected"
            variant="destructive"
            @update:open="showBulkRejectModal = $event"
            @confirm="confirmBulkReject"
        />

        <!-- Bulk Delete Modal -->
        <ConfirmationModal
            :open="showBulkDeleteModal"
            title="Delete Selected Applications"
            :description="`Are you sure you want to delete ${selectedIds.length} selected application(s)? This action cannot be undone.`"
            confirm-text="Delete Selected"
            variant="destructive"
            @update:open="showBulkDeleteModal = $event"
            @confirm="confirmBulkDelete"
        />

        <!-- Bulk Payment Reminder Modal -->
        <ConfirmationModal
            :open="showBulkPaymentReminderModal"
            title="Send Bulk Payment Reminders"
            :description="`Send payment reminder emails to ${selectedIds.length} selected application(s). Only accepted applicants with pending balances will be notified.`"
            confirm-text="Send Reminders"
            variant="default"
            @update:open="showBulkPaymentReminderModal = $event"
            @confirm="confirmBulkPaymentReminder"
        />

        <!-- Bulk Schedule Interview Modal -->
        <Dialog
            :open="showBulkScheduleModal"
            @update:open="showBulkScheduleModal = $event"
        >
            <DialogContent>
                <DialogHeader class="space-y-3">
                    <DialogTitle>Schedule Interview</DialogTitle>
                    <DialogDescription>
                        Select an interview to send to
                        {{ selectedIds.length }} selected applicant(s). Each
                        applicant will receive an email invitation.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <label class="text-sm font-medium">Choose Interview</label>
                    <select
                        v-model="selectedInterviewId"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option :value="null" disabled>
                            Select an interview...
                        </option>
                        <option
                            v-for="interview in interviews"
                            :key="interview.id"
                            :value="interview.id"
                        >
                            {{ interview.title }} ({{
                                interview.questions_count
                            }}
                            questions)
                        </option>
                    </select>
                </div>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        :disabled="!selectedInterviewId"
                        class="bg-[#42b6c5] hover:bg-[#35919e]"
                        @click="confirmBulkSchedule"
                    >
                        Schedule Interview
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
