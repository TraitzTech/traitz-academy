<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { computed, nextTick, ref, watch } from 'vue';

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

interface PaymentRow {
    id: number;
    reference: string;
    receipt_number: string | null;
    mesomb_transaction_id: string | null;
    payer_phone: string;
    provider: string;
    amount: number;
    currency: string;
    status: 'pending' | 'successful' | 'failed';
    payment_type: 'full' | 'installment';
    installment_number: number;
    total_installments: number;
    payment_channel: string | null;
    manual_entry: boolean;
    admin_notes: string | null;
    failure_reason: string | null;
    updated_by: {
        id: number;
        name: string;
    } | null;
    recorded_by: {
        id: number;
        name: string;
    } | null;
    paid_at: string | null;
    application: {
        first_name: string;
        last_name: string;
        email: string;
    };
    program: {
        id: number;
        title: string;
    };
}

interface AcceptedApplicationOption {
    id: number;
    applicant_name: string;
    email: string;
    phone: string | null;
    program_id: number;
    program_title: string | null;
    program_price: number;
    paid_amount: number;
    remaining_amount: number;
    max_installments: number;
    completed_installments: number;
}

interface Props {
    payments: {
        data: PaymentRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        status?: string;
        program_id?: string;
        payment_source?: string;
        collected_by?: string;
        collector_role?: string;
    };
    programs: Array<{ id: number; title: string }>;
    collectors: Array<{
        id: number;
        name: string;
        role: 'cto' | 'ceo' | 'program_coordinator' | 'admin';
    }>;
    collectorRoles: Array<'cto' | 'ceo' | 'program_coordinator'>;
    acceptedApplications: AcceptedApplicationOption[];
    stats: {
        successful_count: number;
        pending_count: number;
        failed_count: number;
        total_collected: number;
        total_outstanding: number;
    };
}

const props = defineProps<Props>();
defineOptions({ layout: AppLayout });
const toast = useToast();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const programId = ref(props.filters.program_id || '');
const paymentSource = ref(props.filters.payment_source || '');
const collectedBy = ref(props.filters.collected_by || '');
const collectorRole = ref(props.filters.collector_role || '');

const applyFilters = debounce(() => {
    router.get(
        '/admin/payments',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            program_id: programId.value || undefined,
            payment_source: paymentSource.value || undefined,
            collected_by: collectedBy.value || undefined,
            collector_role: collectorRole.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch(
    [search, status, programId, paymentSource, collectedBy, collectorRole],
    applyFilters,
);

const formatMoney = (amount: number, currency = 'XAF') => {
    return new Intl.NumberFormat('en-CM', {
        style: 'currency',
        currency,
    }).format(amount || 0);
};

const formatDate = (date: string | null) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const hasRows = computed(() => props.payments.data.length > 0);
const acceptedApplications = computed(() => props.acceptedApplications);
const hasCollectorFilters = computed(() => props.collectors.length > 0);

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    if (search.value) {
        params.set('search', search.value);
    }

    if (status.value) {
        params.set('status', status.value);
    }

    if (programId.value) {
        params.set('program_id', String(programId.value));
    }

    if (paymentSource.value) {
        params.set('payment_source', paymentSource.value);
    }

    if (collectedBy.value) {
        params.set('collected_by', String(collectedBy.value));
    }

    if (collectorRole.value) {
        params.set('collector_role', collectorRole.value);
    }

    const query = params.toString();

    return query ? `/admin/payments/export?${query}` : '/admin/payments/export';
});

const formatRole = (role: string) => {
    if (role === 'admin') return 'CTO (Legacy)';
    return role.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const showManualModal = ref(false);
const showEditModal = ref(false);
const editingPayment = ref<PaymentRow | null>(null);

// --- Application search combobox state ---
const appSearchQuery = ref('');
const appSearchOpen = ref(false);
const appSearchRef = ref<HTMLElement | null>(null);
const appInputRef = ref<HTMLInputElement | null>(null);
const highlightedIndex = ref(-1);

const filteredApplications = computed(() => {
    const q = appSearchQuery.value.trim().toLowerCase();
    if (!q) return props.acceptedApplications;
    return props.acceptedApplications.filter((a) => {
        return (
            a.applicant_name.toLowerCase().includes(q) ||
            a.email.toLowerCase().includes(q) ||
            String(a.id).includes(q) ||
            (a.program_title || '').toLowerCase().includes(q) ||
            (a.phone || '').includes(q)
        );
    });
});

const selectApplication = (app: AcceptedApplicationOption) => {
    manualForm.application_id = String(app.id);
    appSearchQuery.value = `#${app.id} — ${app.applicant_name} (${app.program_title})`;
    appSearchOpen.value = false;
    highlightedIndex.value = -1;
};

const clearApplication = () => {
    manualForm.application_id = '';
    appSearchQuery.value = '';
    appSearchOpen.value = false;
    highlightedIndex.value = -1;
    nextTick(() => appInputRef.value?.focus());
};

const onAppInputFocus = () => {
    appSearchOpen.value = true;
    if (manualForm.application_id) {
        appSearchQuery.value = '';
    }
    highlightedIndex.value = -1;
};

const onAppInputBlur = () => {
    // Delay to allow click on dropdown item
    setTimeout(() => {
        appSearchOpen.value = false;
        // Restore label if selection exists
        const current = props.acceptedApplications.find(
            (a) => String(a.id) === String(manualForm.application_id),
        );
        if (current) {
            appSearchQuery.value = `#${current.id} — ${current.applicant_name} (${current.program_title})`;
        }
    }, 150);
};

const onAppKeydown = (e: KeyboardEvent) => {
    if (!appSearchOpen.value) {
        if (e.key === 'ArrowDown' || e.key === 'Enter') {
            appSearchOpen.value = true;
        }
        return;
    }
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIndex.value = Math.min(
            highlightedIndex.value + 1,
            filteredApplications.value.length - 1,
        );
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (
            highlightedIndex.value >= 0 &&
            filteredApplications.value[highlightedIndex.value]
        ) {
            selectApplication(
                filteredApplications.value[highlightedIndex.value],
            );
        }
    } else if (e.key === 'Escape') {
        appSearchOpen.value = false;
    }
};

// Reset combobox when modal closes
watch(showManualModal, (val) => {
    if (!val) {
        appSearchQuery.value = '';
        appSearchOpen.value = false;
        highlightedIndex.value = -1;
    }
});

// --- End combobox state ---

const manualForm = useForm({
    application_id: '',
    amount: '',
    provider: 'CASH',
    payment_channel: 'ONSITE',
    payer_phone: '',
    status: 'successful',
    payment_type: 'full',
    paid_at: '',
    failure_reason: '',
    admin_notes: '',
});

const editForm = useForm({
    reference: '',
    receipt_number: '',
    payer_phone: '',
    provider: 'MTN',
    payment_channel: '',
    amount: '',
    payment_type: 'full',
    installment_number: 1,
    total_installments: 1,
    status: 'pending',
    paid_at: '',
    mesomb_transaction_id: '',
    failure_reason: '',
    admin_notes: '',
    manual_entry: false,
});

const selectedApplication = computed(() => {
    return (
        props.acceptedApplications.find(
            (application) =>
                String(application.id) === String(manualForm.application_id),
        ) || null
    );
});

const manualAmountNumber = computed(() => {
    const parsed = Number(manualForm.amount);
    return Number.isFinite(parsed) ? Math.max(parsed, 0) : 0;
});

const manualRemainingAfter = computed(() => {
    const application = selectedApplication.value;
    if (!application) {
        return 0;
    }

    return Math.max(
        0,
        Number(application.remaining_amount) - manualAmountNumber.value,
    );
});

const manualInstallmentsAfter = computed(() => {
    const application = selectedApplication.value;
    if (!application) {
        return 0;
    }

    if (manualForm.status !== 'successful' || manualAmountNumber.value <= 0) {
        return application.completed_installments;
    }

    const isInstallment =
        manualAmountNumber.value < Number(application.remaining_amount);
    if (isInstallment) {
        return Math.min(
            application.max_installments,
            application.completed_installments + 1,
        );
    }

    return application.max_installments;
});

const manualEffectivePaymentType = computed(() => {
    const application = selectedApplication.value;
    if (!application) {
        return manualForm.payment_type;
    }

    return manualAmountNumber.value < Number(application.remaining_amount)
        ? 'installment'
        : 'full';
});

const openManualModal = () => {
    manualForm.reset();
    manualForm.clearErrors();
    manualForm.provider = 'CASH';
    manualForm.payment_channel = 'ONSITE';
    manualForm.status = 'successful';
    manualForm.payment_type = 'full';
    appSearchQuery.value = '';
    appSearchOpen.value = false;
    showManualModal.value = true;
};

watch(
    () => manualForm.application_id,
    () => {
        const application = selectedApplication.value;

        if (!application) {
            return;
        }

        manualForm.amount =
            application.remaining_amount > 0
                ? String(application.remaining_amount)
                : String(application.program_price);
        manualForm.payer_phone = application.phone || '';
    },
);

watch(
    [
        () => manualForm.amount,
        () => manualForm.status,
        () => manualForm.application_id,
    ],
    () => {
        if (!selectedApplication.value || manualForm.status !== 'successful') {
            return;
        }

        manualForm.payment_type = manualEffectivePaymentType.value;
    },
);

const recordManualPayment = () => {
    manualForm.post('/admin/payments/manual', {
        preserveScroll: true,
        onSuccess: () => {
            showManualModal.value = false;
            // Flash message handled by global watcher (PaymentController::storeManual flashes 'success')
        },
        onError: (errors) => {
            const errorMessage =
                errors.amount ||
                errors.application_id ||
                errors.status ||
                errors.payer_phone ||
                'Failed to record manual payment.';
            toast.error(errorMessage);
        },
    });
};

const openEditModal = (payment: PaymentRow) => {
    editingPayment.value = payment;
    editForm.clearErrors();
    editForm.reference = payment.reference;
    editForm.receipt_number = payment.receipt_number || '';
    editForm.payer_phone = payment.payer_phone;
    editForm.provider = payment.provider;
    editForm.payment_channel = payment.payment_channel || '';
    editForm.amount = String(payment.amount);
    editForm.payment_type = payment.payment_type;
    editForm.installment_number = payment.installment_number;
    editForm.total_installments = payment.total_installments;
    editForm.status = payment.status;
    editForm.paid_at = payment.paid_at
        ? new Date(payment.paid_at).toISOString().slice(0, 16)
        : '';
    editForm.mesomb_transaction_id = payment.mesomb_transaction_id || '';
    editForm.failure_reason = payment.failure_reason || '';
    editForm.admin_notes = payment.admin_notes || '';
    editForm.manual_entry = payment.manual_entry;
    showEditModal.value = true;
};

const updatePayment = () => {
    if (!editingPayment.value) {
        return;
    }

    editForm.patch(`/admin/payments/${editingPayment.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editingPayment.value = null;
            // Flash message handled by global watcher (PaymentController::update flashes 'success')
        },
        onError: () => {
            toast.error('Failed to update payment.');
        },
    });
};
</script>

<template>
    <div>
        <Head title="Payments" />

        <div class="mb-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2
                        class="text-3xl font-bold text-gray-900 dark:text-gray-100"
                    >
                        Payments
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Track all program fee transactions and installment
                        progress
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        v-if="hasCollectorFilters"
                        :href="exportUrl"
                        class="rounded-lg border border-emerald-300 px-4 py-2 text-emerald-700 transition-colors hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20"
                    >
                        Export CSV
                    </a>
                    <Link
                        href="/admin/payments/verify"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-700"
                    >
                        Verify Receipt
                    </Link>
                    <button
                        type="button"
                        class="rounded-lg bg-[#42b6c5] px-4 py-2 font-medium text-white transition-colors hover:bg-[#35919e]"
                        @click="openManualModal"
                    >
                        Record Onsite Payment
                    </button>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div
                class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow dark:bg-gray-800"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Successful
                </p>
                <p
                    class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400"
                >
                    {{ stats.successful_count }}
                </p>
            </div>
            <div
                class="rounded-lg border-l-4 border-yellow-500 bg-white p-4 shadow dark:bg-gray-800"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">Pending</p>
                <p
                    class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                >
                    {{ stats.pending_count }}
                </p>
            </div>
            <div
                class="rounded-lg border-l-4 border-red-500 bg-white p-4 shadow dark:bg-gray-800"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">Failed</p>
                <p
                    class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400"
                >
                    {{ stats.failed_count }}
                </p>
            </div>
            <div
                class="rounded-lg border-l-4 border-[#42b6c5] bg-white p-4 shadow dark:bg-gray-800"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Total Collected
                </p>
                <p class="mt-1 text-xl font-bold text-[#42b6c5]">
                    {{ formatMoney(stats.total_collected) }}
                </p>
            </div>
            <div
                class="rounded-lg border-l-4 border-indigo-500 bg-white p-4 shadow sm:col-span-2 xl:col-span-1 dark:bg-gray-800"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Still Collectable
                </p>
                <p
                    class="mt-1 text-xl font-bold text-indigo-600 dark:text-indigo-400"
                >
                    {{ formatMoney(stats.total_outstanding) }}
                </p>
            </div>
        </div>

        <div class="mb-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6">
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Search</label
                    >
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Reference, receipt, phone, applicant..."
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
                        <option value="">All</option>
                        <option value="successful">Successful</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
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
                        >Source</label
                    >
                    <select
                        v-model="paymentSource"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">Manual + Online</option>
                        <option value="manual">Manual</option>
                        <option value="online">Online</option>
                    </select>
                </div>

                <div v-if="hasCollectorFilters">
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Collected By</label
                    >
                    <select
                        v-model="collectedBy"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">All Collectors</option>
                        <option
                            v-for="collector in collectors"
                            :key="collector.id"
                            :value="collector.id"
                        >
                            {{ collector.name }} ({{
                                formatRole(collector.role)
                            }})
                        </option>
                    </select>
                </div>

                <div v-if="hasCollectorFilters">
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Collector Role</label
                    >
                    <select
                        v-model="collectorRole"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="">All Roles</option>
                        <option
                            v-for="role in collectorRoles"
                            :key="role"
                            :value="role"
                        >
                            {{ formatRole(role) }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800"
        >
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead class="bg-gray-50 dark:bg-gray-700/40">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Receipt
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Applicant
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Program
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Amount
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Type
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Source
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <tr
                            v-for="payment in payments.data"
                            :key="payment.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/40"
                        >
                            <td
                                class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"
                            >
                                <p class="font-semibold">
                                    {{ payment.receipt_number || 'Pending' }}
                                </p>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ payment.reference }}
                                </p>
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"
                            >
                                <p class="font-semibold">
                                    {{ payment.application.first_name }}
                                    {{ payment.application.last_name }}
                                </p>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ payment.application.email }}
                                </p>
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"
                            >
                                {{ payment.program?.title || 'Program' }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100"
                            >
                                {{
                                    formatMoney(
                                        payment.amount,
                                        payment.currency,
                                    )
                                }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-900 capitalize dark:text-gray-100"
                            >
                                {{ payment.payment_type }}
                                <span
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                    >({{ payment.installment_number }}/{{
                                        payment.total_installments
                                    }})</span
                                >
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-1 text-xs font-semibold uppercase',
                                        payment.status === 'successful'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                            : payment.status === 'pending'
                                              ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                                              : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    ]"
                                >
                                    {{ payment.status }}
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"
                            >
                                <div class="space-y-1">
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-1 text-xs font-semibold uppercase',
                                            payment.manual_entry
                                                ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400'
                                                : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400',
                                        ]"
                                    >
                                        {{
                                            payment.manual_entry
                                                ? 'Manual'
                                                : 'Online'
                                        }}
                                    </span>
                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ payment.payment_channel || '—' }}
                                    </p>
                                    <p
                                        v-if="payment.manual_entry"
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Collected by:
                                        {{
                                            payment.recorded_by?.name ||
                                            payment.updated_by?.name ||
                                            'Unknown'
                                        }}
                                    </p>
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ formatDate(payment.paid_at) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <button
                                        type="button"
                                        class="font-medium text-indigo-600 hover:text-indigo-800"
                                        @click="openEditModal(payment)"
                                    >
                                        Edit
                                    </button>
                                    <Link
                                        :href="`/payments/receipts/${payment.id}`"
                                        class="font-medium text-[#42b6c5] hover:text-[#35919e]"
                                        >Receipt</Link
                                    >
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="!hasRows"
                class="px-6 py-10 text-center text-gray-500 dark:text-gray-400"
            >
                No payments found for the current filters.
            </div>

            <div
                v-if="payments.links && payments.links.length > 3"
                class="flex justify-center border-t border-gray-200 px-4 py-3 dark:border-gray-700"
            >
                <nav class="inline-flex -space-x-px rounded-md shadow-sm">
                    <Link
                        v-for="(link, index) in payments.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'border px-4 py-2 text-sm font-medium',
                            link.active
                                ? 'border-[#42b6c5] bg-[#42b6c5] text-white'
                                : 'border-gray-300 bg-white text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300',
                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>

        <!-- Manual Payment Modal -->
        <Dialog :open="showManualModal" @update:open="showManualModal = $event">
            <DialogContent
                class="border border-gray-200 bg-white sm:max-w-xl dark:border-gray-700 dark:bg-gray-900"
            >
                <DialogHeader class="space-y-2">
                    <DialogTitle class="text-gray-900 dark:text-gray-100"
                        >Record Manual Payment</DialogTitle
                    >
                    <DialogDescription class="text-gray-600 dark:text-gray-400">
                        Record onsite or bank-transfer payment into the same
                        payment records.
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="grid max-h-[70vh] grid-cols-1 gap-4 overflow-y-auto pr-1 md:grid-cols-2"
                >
                    <!-- Application Search Combobox -->
                    <div class="md:col-span-2" ref="appSearchRef">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Accepted Application</label
                        >
                        <div class="relative">
                            <!-- Input row with search icon and optional clear button -->
                            <div class="relative flex items-center">
                                <!-- Search icon -->
                                <svg
                                    class="pointer-events-none absolute left-3 h-4 w-4 text-gray-400 dark:text-gray-500"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"
                                    />
                                </svg>

                                <input
                                    ref="appInputRef"
                                    v-model="appSearchQuery"
                                    type="text"
                                    autocomplete="off"
                                    :placeholder="
                                        manualForm.application_id
                                            ? 'Type to search another...'
                                            : 'Search by name, email, ID or program…'
                                    "
                                    class="w-full rounded-lg border bg-white py-2 pr-8 pl-9 text-gray-900 transition placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                    :class="[
                                        manualForm.errors.application_id
                                            ? 'border-red-400 dark:border-red-500'
                                            : manualForm.application_id
                                              ? 'border-[#42b6c5] dark:border-[#42b6c5]'
                                              : 'border-gray-300 dark:border-gray-600',
                                    ]"
                                    @focus="onAppInputFocus"
                                    @blur="onAppInputBlur"
                                    @keydown="onAppKeydown"
                                />

                                <!-- Clear button when something is selected -->
                                <button
                                    v-if="manualForm.application_id"
                                    type="button"
                                    tabindex="-1"
                                    class="absolute right-2 rounded p-0.5 text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-200"
                                    @mousedown.prevent="clearApplication"
                                    title="Clear selection"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 18 18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <!-- Selected badge -->
                            <div
                                v-if="
                                    manualForm.application_id && !appSearchOpen
                                "
                                class="mt-1.5 flex items-center gap-1.5"
                            >
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-cyan-200 bg-cyan-50 px-2 py-0.5 text-xs text-cyan-700 dark:border-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-3 w-3"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m4.5 12.75 6 6 9-13.5"
                                        />
                                    </svg>
                                    Application #{{
                                        manualForm.application_id
                                    }}
                                    selected
                                </span>
                            </div>

                            <!-- Dropdown -->
                            <div
                                v-if="appSearchOpen"
                                class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"
                            >
                                <div
                                    v-if="filteredApplications.length === 0"
                                    class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    No applications match your search.
                                </div>
                                <button
                                    v-for="(app, index) in filteredApplications"
                                    :key="app.id"
                                    type="button"
                                    class="flex w-full flex-col gap-0.5 px-4 py-2.5 text-left text-sm transition-colors"
                                    :class="[
                                        index === highlightedIndex
                                            ? 'bg-[#42b6c5]/10 text-gray-900 dark:bg-[#42b6c5]/20 dark:text-gray-100'
                                            : 'text-gray-800 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/50',
                                        String(app.id) ===
                                        String(manualForm.application_id)
                                            ? 'bg-cyan-50 dark:bg-cyan-900/20'
                                            : '',
                                    ]"
                                    @mousedown.prevent="selectApplication(app)"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span class="truncate font-medium">{{
                                            app.applicant_name
                                        }}</span>
                                        <span
                                            class="shrink-0 font-mono text-xs text-gray-400 dark:text-gray-500"
                                            >#{{ app.id }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        <span class="truncate">{{
                                            app.program_title
                                        }}</span>
                                        <span class="shrink-0">·</span>
                                        <span
                                            class="shrink-0 font-medium text-[#42b6c5]"
                                            >{{
                                                formatMoney(
                                                    app.remaining_amount,
                                                )
                                            }}
                                            remaining</span
                                        >
                                    </div>
                                    <span
                                        v-if="app.email"
                                        class="truncate text-xs text-gray-400 dark:text-gray-500"
                                        >{{ app.email }}</span
                                    >
                                </button>
                            </div>
                        </div>
                        <p
                            v-if="manualForm.errors.application_id"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ manualForm.errors.application_id }}
                        </p>
                    </div>

                    <!-- Payment summary info box -->
                    <div
                        class="rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-3 md:col-span-2 dark:border-cyan-800 dark:bg-cyan-900/20"
                        v-if="selectedApplication"
                    >
                        <div
                            class="grid grid-cols-1 gap-2 text-xs sm:grid-cols-2"
                        >
                            <p class="text-cyan-800 dark:text-cyan-200">
                                Paid:
                                {{
                                    formatMoney(selectedApplication.paid_amount)
                                }}
                                /
                                {{
                                    formatMoney(
                                        selectedApplication.program_price,
                                    )
                                }}
                            </p>
                            <p class="text-cyan-800 dark:text-cyan-200">
                                Remaining before:
                                {{
                                    formatMoney(
                                        selectedApplication.remaining_amount,
                                    )
                                }}
                            </p>
                            <p
                                class="font-semibold text-cyan-800 dark:text-cyan-200"
                            >
                                Remaining after this record:
                                {{ formatMoney(manualRemainingAfter) }}
                            </p>
                            <p class="text-cyan-800 dark:text-cyan-200">
                                Installments after record:
                                {{ manualInstallmentsAfter }} /
                                {{ selectedApplication.max_installments }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Amount</label
                        >
                        <input
                            v-model="manualForm.amount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                        <p
                            v-if="selectedApplication"
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            For successful payments, amount below remaining is
                            treated as installment automatically.
                        </p>
                        <p
                            v-if="manualForm.errors.amount"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ manualForm.errors.amount }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Phone</label
                        >
                        <input
                            v-model="manualForm.payer_phone"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                        <p
                            v-if="manualForm.errors.payer_phone"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ manualForm.errors.payer_phone }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Provider</label
                        >
                        <select
                            v-model="manualForm.provider"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="CASH">CASH</option>
                            <option value="BANK_TRANSFER">BANK TRANSFER</option>
                            <option value="MTN">MTN</option>
                            <option value="ORANGE">ORANGE</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Channel</label
                        >
                        <select
                            v-model="manualForm.payment_channel"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="ONSITE">ONSITE</option>
                            <option value="BANK_TRANSFER">BANK TRANSFER</option>
                            <option value="CASH">CASH</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Status</label
                        >
                        <select
                            v-model="manualForm.status"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="successful">successful</option>
                            <option value="pending">pending</option>
                            <option value="failed">failed</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Payment Type</label
                        >
                        <select
                            v-model="manualForm.payment_type"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="full">full</option>
                            <option value="installment">installment</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Paid At (optional)</label
                        >
                        <input
                            v-model="manualForm.paid_at"
                            type="datetime-local"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Failure Reason (if failed)</label
                        >
                        <input
                            v-model="manualForm.failure_reason"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Admin Notes</label
                        >
                        <textarea
                            v-model="manualForm.admin_notes"
                            rows="2"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        ></textarea>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Cancel
                        </button>
                    </DialogClose>
                    <button
                        type="button"
                        class="rounded-lg bg-[#42b6c5] px-4 py-2 font-medium text-white hover:bg-[#35919e]"
                        :disabled="manualForm.processing"
                        @click="recordManualPayment"
                    >
                        {{
                            manualForm.processing
                                ? 'Saving...'
                                : 'Record Payment'
                        }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Payment Modal -->
        <Dialog :open="showEditModal" @update:open="showEditModal = $event">
            <DialogContent
                class="border border-gray-200 bg-white sm:max-w-2xl dark:border-gray-700 dark:bg-gray-900"
            >
                <DialogHeader class="space-y-2">
                    <DialogTitle class="text-gray-900 dark:text-gray-100"
                        >Edit Payment</DialogTitle
                    >
                    <DialogDescription class="text-gray-600 dark:text-gray-400">
                        Update payment status, amount, source, and metadata.
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="grid max-h-[70vh] grid-cols-1 gap-4 overflow-y-auto pr-1 md:grid-cols-2"
                >
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Reference</label
                        >
                        <input
                            v-model="editForm.reference"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Receipt Number</label
                        >
                        <input
                            v-model="editForm.receipt_number"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Amount</label
                        >
                        <input
                            v-model="editForm.amount"
                            type="number"
                            step="0.01"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Phone</label
                        >
                        <input
                            v-model="editForm.payer_phone"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Provider</label
                        >
                        <select
                            v-model="editForm.provider"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="MTN">MTN</option>
                            <option value="ORANGE">ORANGE</option>
                            <option value="CASH">CASH</option>
                            <option value="BANK_TRANSFER">BANK TRANSFER</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Channel</label
                        >
                        <select
                            v-model="editForm.payment_channel"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="">—</option>
                            <option value="ONLINE">ONLINE</option>
                            <option value="ONSITE">ONSITE</option>
                            <option value="BANK_TRANSFER">BANK TRANSFER</option>
                            <option value="CASH">CASH</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Status</label
                        >
                        <select
                            v-model="editForm.status"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="successful">successful</option>
                            <option value="pending">pending</option>
                            <option value="failed">failed</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Payment Type</label
                        >
                        <select
                            v-model="editForm.payment_type"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="full">full</option>
                            <option value="installment">installment</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Installment No</label
                        >
                        <input
                            v-model="editForm.installment_number"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Total Installments</label
                        >
                        <input
                            v-model="editForm.total_installments"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Paid At</label
                        >
                        <input
                            v-model="editForm.paid_at"
                            type="datetime-local"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >MeSomb Transaction ID</label
                        >
                        <input
                            v-model="editForm.mesomb_transaction_id"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Failure Reason</label
                        >
                        <input
                            v-model="editForm.failure_reason"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Admin Notes</label
                        >
                        <textarea
                            v-model="editForm.admin_notes"
                            rows="2"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-[#42b6c5] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        ></textarea>
                    </div>

                    <label
                        class="flex items-center gap-2 text-sm text-gray-700 md:col-span-2 dark:text-gray-300"
                    >
                        <input
                            v-model="editForm.manual_entry"
                            type="checkbox"
                            class="rounded border-gray-300 bg-white text-[#42b6c5] focus:ring-[#42b6c5] dark:border-gray-500 dark:bg-gray-800"
                        />
                        Mark as manual entry
                    </label>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Cancel
                        </button>
                    </DialogClose>
                    <button
                        type="button"
                        class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700"
                        :disabled="editForm.processing"
                        @click="updatePayment"
                    >
                        {{
                            editForm.processing
                                ? 'Updating...'
                                : 'Update Payment'
                        }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
