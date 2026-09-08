<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';

import { useToast } from '@/composables/useToast';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Event {
    id: number;
    title: string;
    slug: string;
    description: string;
    event_date: string;
    location: string;
    is_online: boolean;
    event_url: string;
    capacity: number;
    registered_count: number;
    category: string;
    image_url: string;
    agenda: string;
}

interface Props {
    event: Event;
    isRegistered: boolean;
}

const props = defineProps<Props>();

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
const isUpcoming = (date: string) => new Date(date) > new Date();

const page = usePage();
const toast = useToast();

const form = useForm({
    event_id: props.event.id,
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    country: '',
});

// Pre-populate fields from authenticated user
onMounted(() => {
    const authUser: any = (page as any).props?.auth?.user;
    if (authUser) {
        form.first_name =
            authUser.first_name || authUser.name?.split(' ')[0] || '';
        form.last_name =
            authUser.last_name ||
            authUser.name?.split(' ').slice(1).join(' ') ||
            '';
        form.email = authUser.email || '';
        form.phone = authUser.phone || '';
    }
});

const submit = () => {
    form.post('/events/register', {
        onSuccess: () => {
            toast.success(
                'Registered successfully! Check your email for details.',
            );
        },
    });
};
</script>

<template>
    <PublicLayout>
        <Head :title="`${event.title} - Traitz Academy`" />

        <!-- Header -->
        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] py-16 text-white"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <Link
                    href="/events"
                    class="mb-4 inline-flex items-center text-[#42b6c5] hover:text-white"
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
                    Back to Events
                </Link>
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">
                    {{ event.title }}
                </h1>
                <p class="text-xl text-gray-300">
                    {{ formatDate(event.event_date) }}
                </p>
            </div>
        </section>

        <!-- Main Content -->
        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <!-- Left Column -->
                    <div class="lg:col-span-2">
                        <!-- Event Image -->
                        <div
                            class="mb-8 h-96 overflow-hidden rounded-lg bg-gradient-to-br from-[#381998] to-[#42b6c5]"
                        >
                            <img
                                :src="'/storage/' + event.image_url"
                                :alt="event.title"
                                class="h-full w-full object-cover opacity-80"
                            />
                        </div>

                        <!-- Description -->
                        <div class="mb-12">
                            <h2 class="mb-4 text-3xl font-bold text-[#000928]">
                                About This Event
                            </h2>
                            <p class="text-lg leading-relaxed text-gray-700">
                                {{ event.description }}
                            </p>
                        </div>

                        <!-- Agenda -->
                        <div class="mb-12">
                            <h2 class="mb-4 text-3xl font-bold text-[#000928]">
                                Event Agenda
                            </h2>
                            <p
                                class="text-lg leading-relaxed whitespace-pre-line text-gray-700"
                            >
                                {{ event.agenda }}
                            </p>
                        </div>

                        <!-- Event Details -->
                        <div
                            class="grid grid-cols-1 gap-8 rounded-lg bg-gray-50 p-8 md:grid-cols-2"
                        >
                            <div>
                                <h3
                                    class="mb-2 flex items-center text-xl font-bold text-[#000928]"
                                >
                                    <svg
                                        class="mr-2 h-6 w-6 text-[#42b6c5]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    Date & Time
                                </h3>
                                <p class="text-gray-700">
                                    {{ formatDate(event.event_date) }}
                                </p>
                            </div>

                            <div v-if="event.is_online || event.location">
                                <h3
                                    class="mb-2 flex items-center text-xl font-bold text-[#000928]"
                                >
                                    <svg
                                        class="mr-2 h-6 w-6 text-[#42b6c5]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                    </svg>
                                    Location
                                </h3>
                                <div
                                    v-if="event.is_online"
                                    class="text-gray-700"
                                >
                                    <p
                                        class="mb-1 font-semibold text-[#42b6c5]"
                                    >
                                        Online Event
                                    </p>
                                    <p v-if="event.event_url">
                                        <a
                                            :href="event.event_url"
                                            target="_blank"
                                            class="text-[#42b6c5] hover:underline"
                                            >Join via Zoom/Meet</a
                                        >
                                    </p>
                                </div>
                                <p v-if="event.location" class="text-gray-700">
                                    {{ event.location }}
                                </p>
                            </div>

                            <div>
                                <h3
                                    class="mb-2 flex items-center text-xl font-bold text-[#000928]"
                                >
                                    <svg
                                        class="mr-2 h-6 w-6 text-[#42b6c5]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    Category
                                </h3>
                                <p class="text-gray-700 capitalize">
                                    {{ event.category || 'General Event' }}
                                </p>
                            </div>

                            <div>
                                <h3
                                    class="mb-2 flex items-center text-xl font-bold text-[#000928]"
                                >
                                    <svg
                                        class="mr-2 h-6 w-6 text-[#42b6c5]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10h.01M11 20h4M11 10h.01M7 20h4M7 10h.01M3 20h4m0-2a3 3 0 00-5.856-1.487M3 10h.01"
                                        />
                                    </svg>
                                    Participants
                                </h3>
                                <p class="text-gray-700">
                                    {{ event.registered_count }}/{{
                                        event.capacity || '∞'
                                    }}
                                    registered
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Registration -->
                    <div>
                        <!-- Already Registered -->
                        <div
                            v-if="isRegistered && isUpcoming(event.event_date)"
                            class="sticky top-20 rounded-lg border border-emerald-200 bg-emerald-50 p-8"
                        >
                            <div class="text-center">
                                <div
                                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100"
                                >
                                    <svg
                                        class="h-8 w-8 text-emerald-600"
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
                                </div>
                                <h2
                                    class="mb-2 text-2xl font-bold text-emerald-800"
                                >
                                    You're Registered!
                                </h2>
                                <p class="mb-4 text-emerald-700">
                                    You've already registered for this event.
                                    We'll send you a reminder before it starts.
                                </p>
                                <div
                                    class="rounded-lg bg-white/60 p-4 text-sm text-emerald-800"
                                >
                                    <p class="mb-1 font-semibold">Event Date</p>
                                    <p>{{ formatDate(event.event_date) }}</p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="isUpcoming(event.event_date)"
                            class="sticky top-20 rounded-lg bg-gray-50 p-8"
                        >
                            <h2 class="mb-6 text-2xl font-bold text-[#000928]">
                                Register for Event
                            </h2>

                            <form @submit.prevent="submit" class="space-y-4">
                                <div>
                                    <label
                                        for="first_name"
                                        class="mb-1 block text-sm font-semibold text-gray-700"
                                        >First Name *</label
                                    >
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500':
                                                form.errors.first_name,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.first_name"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ form.errors.first_name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="last_name"
                                        class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Last Name *</label
                                    >
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500':
                                                form.errors.last_name,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.last_name"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ form.errors.last_name }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="email"
                                        class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Email *</label
                                    >
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500': form.errors.email,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.email"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="phone"
                                        class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Phone *</label
                                    >
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                        :class="{
                                            'border-red-500': form.errors.phone,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.phone"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ form.errors.phone }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="country"
                                        class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Country</label
                                    >
                                    <input
                                        id="country"
                                        v-model="form.country"
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full rounded-lg bg-[#42b6c5] px-6 py-3 font-bold text-white transition-colors hover:bg-[#35919e] disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span v-if="form.processing"
                                        >Registering...</span
                                    >
                                    <span v-else>Register Now</span>
                                </button>
                            </form>

                            <p class="mt-4 text-center text-xs text-gray-600">
                                We'll send you event details and updates via
                                email
                            </p>
                        </div>

                        <!-- Event Ended -->
                        <div
                            v-else
                            class="sticky top-20 rounded-lg bg-gray-50 p-8"
                        >
                            <p class="text-center font-semibold text-gray-600">
                                This event has already ended.
                            </p>
                            <Link
                                href="/events"
                                class="mt-4 block rounded-lg bg-[#000928] px-6 py-3 text-center font-bold text-white transition-colors hover:bg-[#381998]"
                            >
                                View Upcoming Events
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
