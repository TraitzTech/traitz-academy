<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';

interface Course {
    id: number;
    title: string;
    slug: string;
    price: string;
    sale_price: string | null;
    cover_image: string | null;
    max_installments: number;
}

interface Summary {
    course_price: number;
    paid_amount: number;
    remaining_amount: number;
    max_installments: number;
    installment_amount: number;
    completed_installments: number;
    next_installment_number: number;
    online_surcharge_percentage: number;
    can_pay: boolean;
}

interface Props {
    course: Course;
    summary: Summary;
    defaultPhone: string;
}

const props = defineProps<Props>();
defineOptions({ layout: AppLayout });

const toast = useToast();

const form = useForm({
    payer_phone: props.defaultPhone || '',
    provider: 'MTN',
    payment_mode: 'full' as 'full' | 'installment',
});

const installmentDue = computed(() =>
    Math.min(props.summary.installment_amount, props.summary.remaining_amount),
);
const selectedBaseAmount = computed(() =>
    form.payment_mode === 'installment'
        ? installmentDue.value
        : props.summary.remaining_amount,
);
const selectedSurchargeAmount = computed(() => {
    return (
        (selectedBaseAmount.value * props.summary.online_surcharge_percentage) /
        100
    );
});
const selectedAmount = computed(
    () => selectedBaseAmount.value + selectedSurchargeAmount.value,
);
const balanceAfterPayment = computed(() =>
    Math.max(0, props.summary.remaining_amount - selectedBaseAmount.value),
);
const installmentsAfterPayment = computed(() => {
    if (
        form.payment_mode === 'installment' &&
        props.summary.remaining_amount > 0
    ) {
        return Math.min(
            props.summary.max_installments,
            props.summary.completed_installments + 1,
        );
    }

    if (form.payment_mode === 'full' && props.summary.remaining_amount > 0) {
        return props.summary.max_installments;
    }

    return props.summary.completed_installments;
});

const submit = () => {
    form.post(`/dashboard/courses/${props.course.id}/checkout`, {
        preserveScroll: true,
        onSuccess: () => {
            // Flash message handled by global watcher (CoursePaymentController::store flashes 'success'/'error')
        },
        onError: () => {
            toast.error(
                'Payment could not be completed. Please verify your details.',
            );
        },
    });
};

const formatMoney = (amount: number) => {
    return new Intl.NumberFormat('en-CM', {
        style: 'currency',
        currency: 'XAF',
    }).format(amount || 0);
};
</script>

<template>
    <div class="lms-page mx-auto max-w-5xl">
        <Head :title="`Pay — ${course.title}`" />

        <div class="mb-8">
            <Link
                :href="`/dashboard/courses/${course.id}`"
                class="mb-4 inline-flex items-center text-[#42b6c5] hover:text-[#35919e]"
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
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                Back to course
            </Link>
            <h2 class="lms-title">Course payment</h2>
            <p class="lms-subtitle">
                Secure mobile money payment powered by MeSomb
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lms-panel lg:col-span-2">
                <h3
                    class="mb-4 text-xl font-semibold text-gray-900 dark:text-gray-100"
                >
                    Payment details
                </h3>

                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Mobile money number</label
                        >
                        <input
                            v-model="form.payer_phone"
                            type="text"
                            placeholder="e.g. 670000000"
                            class="lms-input"
                        />
                        <p
                            v-if="form.errors.payer_phone"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.payer_phone }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Provider</label
                        >
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer">
                                <input
                                    v-model="form.provider"
                                    type="radio"
                                    value="MTN"
                                    class="sr-only"
                                />
                                <div
                                    :class="[
                                        'rounded-lg border p-3 text-center font-semibold transition-colors',
                                        form.provider === 'MTN'
                                            ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#42b6c5]'
                                            : 'border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-300',
                                    ]"
                                >
                                    MTN
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input
                                    v-model="form.provider"
                                    type="radio"
                                    value="ORANGE"
                                    class="sr-only"
                                />
                                <div
                                    :class="[
                                        'rounded-lg border p-3 text-center font-semibold transition-colors',
                                        form.provider === 'ORANGE'
                                            ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#42b6c5]'
                                            : 'border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-300',
                                    ]"
                                >
                                    ORANGE
                                </div>
                            </label>
                        </div>
                        <p
                            v-if="form.errors.provider"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.provider }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Payment mode</label
                        >
                        <div class="space-y-3">
                            <label
                                class="block cursor-pointer rounded-lg border border-gray-300 p-4 dark:border-gray-600"
                                :class="
                                    form.payment_mode === 'full'
                                        ? 'border-[#42b6c5] ring-2 ring-[#42b6c5]'
                                        : ''
                                "
                            >
                                <input
                                    v-model="form.payment_mode"
                                    type="radio"
                                    value="full"
                                    class="mr-2"
                                />
                                <span
                                    class="font-semibold text-gray-900 dark:text-gray-100"
                                    >Pay full remaining balance</span
                                >
                                <p class="lms-subtitle">
                                    Pay
                                    {{
                                        formatMoney(
                                            summary.remaining_amount +
                                                (summary.remaining_amount *
                                                    summary.online_surcharge_percentage) /
                                                    100,
                                        )
                                    }}
                                    now (including
                                    {{ summary.online_surcharge_percentage }}%
                                    surcharge).
                                </p>
                            </label>

                            <label
                                v-if="summary.max_installments > 1"
                                class="block cursor-pointer rounded-lg border border-gray-300 p-4 dark:border-gray-600"
                                :class="
                                    form.payment_mode === 'installment'
                                        ? 'border-[#42b6c5] ring-2 ring-[#42b6c5]'
                                        : ''
                                "
                            >
                                <input
                                    v-model="form.payment_mode"
                                    type="radio"
                                    value="installment"
                                    class="mr-2"
                                />
                                <span
                                    class="font-semibold text-gray-900 dark:text-gray-100"
                                    >Pay next installment</span
                                >
                                <p class="lms-subtitle">
                                    Installment
                                    {{ summary.next_installment_number }} of
                                    {{ summary.max_installments }}
                                    •
                                    {{
                                        formatMoney(
                                            installmentDue +
                                                (installmentDue *
                                                    summary.online_surcharge_percentage) /
                                                    100,
                                        )
                                    }}
                                    including surcharge
                                </p>
                            </label>
                        </div>
                        <p
                            v-if="form.errors.payment_mode"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.payment_mode }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="lms-btn-accent w-full"
                    >
                        {{ form.processing ? 'Processing…' : 'Pay now' }}
                    </button>
                </form>
            </div>

            <div class="lms-panel h-fit">
                <h3
                    class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    Payment summary
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Course</span
                        >
                        <span
                            class="text-right font-medium text-gray-900 dark:text-gray-100"
                            >{{ course.title }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Total fee</span
                        >
                        <span
                            class="font-medium text-gray-900 dark:text-gray-100"
                            >{{ formatMoney(summary.course_price) }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Paid so far</span
                        >
                        <span
                            class="font-medium text-green-700 dark:text-green-400"
                            >{{ formatMoney(summary.paid_amount) }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Remaining</span
                        >
                        <span
                            class="font-semibold text-amber-700 dark:text-amber-400"
                            >{{ formatMoney(summary.remaining_amount) }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Online surcharge ({{
                                summary.online_surcharge_percentage
                            }}%)</span
                        >
                        <span
                            class="font-medium text-gray-900 dark:text-gray-100"
                            >{{ formatMoney(selectedSurchargeAmount) }}</span
                        >
                    </div>
                    <div
                        class="flex justify-between border-t border-gray-200 pt-3 dark:border-gray-700"
                    >
                        <span
                            class="font-medium text-gray-700 dark:text-gray-300"
                            >You pay now</span
                        >
                        <span class="font-bold text-[#42b6c5]">{{
                            formatMoney(selectedAmount)
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Fee balance after payment</span
                        >
                        <span
                            class="font-medium text-gray-900 dark:text-gray-100"
                            >{{ formatMoney(balanceAfterPayment) }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Installments</span
                        >
                        <span
                            class="font-medium text-gray-900 dark:text-gray-100"
                            >{{ installmentsAfterPayment }} /
                            {{ summary.max_installments }}</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
