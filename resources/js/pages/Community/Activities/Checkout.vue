<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import CommunityShell from '@/components/community/CommunityShell.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';

interface Props {
    activity: {
        id: number;
        title: string;
        slug: string;
        type: string;
        starts_at: string | null;
        location: string | null;
        price: number;
        currency: string;
        cover_image: string | null;
    };
    rsvp: {
        id: number;
        status: string;
        payment_status: string;
        amount: number;
        currency: string;
        payment_phone: string | null;
    };
    member: {
        first_name: string;
        last_name: string | null;
        email: string;
        phone: string | null;
    } | null;
}

const props = defineProps<Props>();
const { money, formatDate, asset } = useCommunity();

const form = useForm({
    phone: props.rsvp.payment_phone ?? props.member?.phone ?? '',
    provider: 'MTN' as 'MTN' | 'ORANGE',
});

const submit = () =>
    form.post(`/community/activities/${props.activity.slug}/checkout`, {
        preserveScroll: true,
    });

const providers = [
    { value: 'MTN', label: 'MTN Mobile Money', hint: 'MTN Cameroon numbers' },
    { value: 'ORANGE', label: 'Orange Money', hint: 'Orange Cameroon numbers' },
] as const;
</script>

<template>
    <CommunityShell active="activities">
        <Head :title="`Payment — ${activity.title}`" />

        <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <nav class="mb-6 text-sm" aria-label="Breadcrumb">
                <Link
                    :href="`/community/activities/${activity.slug}`"
                    class="font-semibold text-gray-500 transition-colors hover:text-[#000928]"
                >
                    ← Back to {{ activity.title }}
                </Link>
            </nav>

            <h1
                class="text-3xl font-black tracking-tight text-[#000928]"
            >
                Complete your payment
            </h1>
            <p class="mt-2 text-gray-600">
                Your place is held. Pay with Mobile Money to confirm it.
            </p>

            <!-- Summary -->
            <div
                class="mt-8 flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-5"
            >
                <div
                    class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-[#000928] to-[#381998]"
                >
                    <img
                        v-if="asset(activity.cover_image)"
                        :src="asset(activity.cover_image)!"
                        :alt="''"
                        class="h-full w-full object-cover"
                    />
                </div>
                <div class="min-w-0 flex-1">
                    <p
                        class="text-[11px] font-bold tracking-wide text-[#42b6c5] uppercase"
                    >
                        {{ activity.type }}
                    </p>
                    <h2
                        class="mt-0.5 truncate font-bold text-[#000928]"
                    >
                        {{ activity.title }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ formatDate(activity.starts_at) }}
                        <template v-if="activity.location"
                            >· {{ activity.location }}</template
                        >
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    <p
                        class="text-lg font-black text-[#000928]"
                    >
                        {{ money(activity.price, activity.currency) }}
                    </p>
                </div>
            </div>

            <!-- Payment form -->
            <form
                class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 sm:p-8"
                @submit.prevent="submit"
            >
                <fieldset :disabled="form.processing">
                    <legend
                        class="text-sm font-bold tracking-wide text-[#000928] uppercase"
                    >
                        Choose your provider
                    </legend>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <button
                            v-for="provider in providers"
                            :key="provider.value"
                            type="button"
                            role="radio"
                            :aria-checked="form.provider === provider.value"
                            :class="[
                                'rounded-xl border p-4 text-left transition-all',
                                form.provider === provider.value
                                    ? 'border-[#42b6c5] bg-[#42b6c5]/8 ring-1 ring-[#42b6c5]'
                                    : 'border-gray-200 hover:border-gray-300',
                            ]"
                            @click="form.provider = provider.value"
                        >
                            <span
                                class="block font-bold text-[#000928]"
                            >
                                {{ provider.label }}
                            </span>
                            <span
                                class="mt-0.5 block text-xs text-gray-500"
                            >
                                {{ provider.hint }}
                            </span>
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.provider" />

                    <div class="mt-6">
                        <label for="payment_phone" class="lms-label">
                            Mobile Money number
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="payment_phone"
                            v-model="form.phone"
                            type="tel"
                            required
                            inputmode="tel"
                            placeholder="+237 6XX XXX XXX"
                            class="lms-input mt-1.5"
                        />
                        <InputError :message="form.errors.phone" />
                        <p
                            class="mt-2 text-xs text-gray-500"
                        >
                            You'll get a prompt on this phone. Approve it to
                            complete the payment — keep this page open.
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="mt-7 w-full rounded-xl bg-[#000928] px-6 py-3.5 text-base font-bold text-white transition-colors hover:bg-[#381998] disabled:opacity-60"
                    >
                        {{
                            form.processing
                                ? 'Waiting for approval on your phone…'
                                : `Pay ${money(activity.price, activity.currency)}`
                        }}
                    </button>

                    <p
                        class="mt-4 text-center text-xs text-gray-500"
                    >
                        Payments are processed securely by MeSomb. We never see
                        or store your Mobile Money PIN.
                    </p>
                </fieldset>
            </form>
        </div>
    </CommunityShell>
</template>
