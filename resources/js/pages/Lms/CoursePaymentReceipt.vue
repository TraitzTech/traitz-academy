<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

interface Course {
    id: number;
    title: string;
    slug: string;
    instructor: { id: number; name: string } | null;
    category: { id: number; name: string } | null;
}

interface Payment {
    id: number;
    reference: string;
    receipt_number: string | null;
    status: string;
    amount: number;
    base_amount: number;
    surcharge_amount: number;
    surcharge_percentage: number;
    currency: string;
    provider: string;
    payer_phone: string;
    payment_type: string;
    installment_number: number | null;
    total_installments: number | null;
    paid_at: string | null;
    created_at: string;
    mesomb_transaction_id: string | null;
    course: Course;
}

interface Props {
    payment: Payment;
}

defineProps<Props>();
defineOptions({ layout: AppLayout });

const formatMoney = (amount: number, currency = 'XAF') => {
    return new Intl.NumberFormat('en-CM', {
        style: 'currency',
        currency,
    }).format(amount || 0);
};

const formatDate = (value: string | null) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <div class="lms-page mx-auto max-w-3xl">
        <Head title="Course payment receipt" />

        <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="lms-title">Payment receipt</h2>
                <p class="lms-subtitle">
                    Receipt #{{ payment.receipt_number || 'Pending' }}
                </p>
            </div>
            <Link href="/dashboard/my-courses" class="lms-btn-accent">
                Go to My Courses
            </Link>
        </div>

        <div class="lms-panel overflow-hidden p-0">
            <div
                class="bg-gradient-to-r from-[#000928] to-[#381998] px-6 py-5 text-white"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p
                            class="text-xs tracking-widest text-cyan-200 uppercase"
                        >
                            Traitz Academy
                        </p>
                        <h3 class="mt-1 text-2xl font-semibold">
                            Online course payment
                        </h3>
                    </div>
                    <span
                        :class="[
                            'rounded-full px-3 py-1 text-xs font-semibold uppercase',
                            payment.status === 'successful'
                                ? 'bg-green-100 text-green-800'
                                : payment.status === 'pending'
                                  ? 'bg-amber-100 text-amber-800'
                                  : 'bg-red-100 text-red-800',
                        ]"
                    >
                        {{ payment.status }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                <div class="space-y-3">
                    <h4
                        class="text-sm tracking-wide text-gray-500 uppercase dark:text-gray-400"
                    >
                        Payment details
                    </h4>
                    <div class="space-y-2 text-sm">
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Receipt number</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{ payment.receipt_number || 'N/A' }}</span
                            >
                        </p>
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Reference</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{ payment.reference }}</span
                            >
                        </p>
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Provider</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{ payment.provider }}</span
                            >
                        </p>
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Phone</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{ payment.payer_phone }}</span
                            >
                        </p>
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Paid at</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{
                                    formatDate(
                                        payment.paid_at || payment.created_at,
                                    )
                                }}</span
                            >
                        </p>
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Course fee</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{
                                    formatMoney(
                                        payment.base_amount,
                                        payment.currency,
                                    )
                                }}</span
                            >
                        </p>
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Surcharge ({{
                                    payment.surcharge_percentage
                                }}%)</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{
                                    formatMoney(
                                        payment.surcharge_amount,
                                        payment.currency,
                                    )
                                }}</span
                            >
                        </p>
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Total charged</span
                            >
                            <span class="font-semibold text-[#42b6c5]">{{
                                formatMoney(payment.amount, payment.currency)
                            }}</span>
                        </p>
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >MeSomb transaction</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{
                                    payment.mesomb_transaction_id || 'N/A'
                                }}</span
                            >
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4
                        class="text-sm tracking-wide text-gray-500 uppercase dark:text-gray-400"
                    >
                        Course
                    </h4>
                    <div class="space-y-2 text-sm">
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Title</span
                            >
                            <span
                                class="text-right font-medium text-gray-900 dark:text-gray-100"
                                >{{ payment.course.title }}</span
                            >
                        </p>
                        <p
                            v-if="payment.course.category"
                            class="flex justify-between gap-4"
                        >
                            <span class="text-gray-500 dark:text-gray-400"
                                >Category</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{ payment.course.category.name }}</span
                            >
                        </p>
                        <p
                            v-if="payment.course.instructor"
                            class="flex justify-between gap-4"
                        >
                            <span class="text-gray-500 dark:text-gray-400"
                                >Instructor</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                                >{{ payment.course.instructor.name }}</span
                            >
                        </p>
                        <p class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400"
                                >Payment type</span
                            >
                            <span
                                class="font-medium text-gray-900 capitalize dark:text-gray-100"
                                >{{ payment.payment_type }}</span
                            >
                        </p>
                        <p
                            v-if="
                                payment.total_installments &&
                                payment.total_installments > 1 &&
                                payment.installment_number
                            "
                            class="flex justify-between gap-4"
                        >
                            <span class="text-gray-500 dark:text-gray-400"
                                >Installment</span
                            >
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100"
                            >
                                {{ payment.installment_number }} /
                                {{ payment.total_installments }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
