<script setup>
import { Link, usePage } from '@inertiajs/vue3';

import PaymentSummaryCard from '@/components/payments/PaymentSummaryCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const paymentSummaries = page.props.paymentSummaries || [];
</script>

<template>
    <div>
        <div class="mb-8">
            <Link
                href="/dashboard"
                class="text-sm font-semibold text-[#42b6c5] hover:text-[#35919e]"
                >&larr; Back to dashboard</Link
            >
            <h1
                class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100"
            >
                My Programs
            </h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">
                Every accepted program and its payment progress
            </p>
        </div>

        <div
            class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800"
        >
            <div
                v-if="paymentSummaries.length > 0"
                class="divide-y divide-gray-100 dark:divide-gray-700"
            >
                <PaymentSummaryCard
                    v-for="summary in paymentSummaries"
                    :key="summary.application_id"
                    :summary="summary"
                />
            </div>

            <div v-else class="px-6 py-12 text-center">
                <div
                    class="mb-4 inline-block rounded-full bg-gray-100 p-4 dark:bg-gray-700"
                >
                    <svg
                        class="h-10 w-10 text-gray-400 dark:text-gray-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2z"
                        />
                    </svg>
                </div>
                <p class="mb-2 font-medium text-gray-600 dark:text-gray-300">
                    No Payment Required Yet
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Once an application is accepted and has a fee, it will
                    appear here.
                </p>
            </div>
        </div>
    </div>
</template>
