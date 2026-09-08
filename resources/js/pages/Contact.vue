<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import SeoHead from '@/components/SeoHead.vue';
import { useToast } from '@/composables/useToast';
import PublicLayout from '@/layouts/PublicLayout.vue';

const page = usePage();
const toast = useToast();

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
});

const settings = computed(() => page.props.siteSettings);

const submitForm = () => {
    form.post('/contact', {
        onSuccess: () => {
            form.reset();
            toast.success(
                "Message sent successfully! We'll get back to you soon.",
            );
        },
        onError: (errors) => {
            toast.error(
                'Failed to send message. Please check the form and try again.',
            );
            console.error(errors);
        },
    });
};

// Format WhatsApp link
const getWhatsAppLink = () => {
    if (!settings.value.contact_whatsapp) return '#';
    const phone = settings.value.contact_whatsapp.replace(/[^0-9]/g, '');
    return `https://wa.me/${phone}`;
};
</script>

<template>
    <PublicLayout>
        <SeoHead
            title="Contact Us"
            description="Get in touch with Traitz Academy — questions about programs, internships or partnerships. We usually reply within a day."
        />

        <!-- Header -->
        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] py-16 text-white"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h1 class="mb-4 text-4xl font-bold md:text-5xl">
                    Get in Touch
                </h1>
                <p class="text-xl text-gray-300">
                    Have questions? We'd love to hear from you. Contact us
                    today.
                </p>
            </div>
        </section>

        <!-- Contact Methods -->
        <section class="bg-white py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 grid grid-cols-1 gap-8 md:grid-cols-3">
                    <!-- Email -->
                    <div class="rounded-lg bg-gray-50 p-8 text-center">
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-8 w-8 text-[#42b6c5]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Email
                        </h3>
                        <a
                            v-if="settings.contact_email"
                            :href="`mailto:${settings.contact_email}`"
                            class="mb-2 block font-semibold text-[#42b6c5] hover:text-[#35919e]"
                        >
                            {{ settings.contact_email }}
                        </a>
                        <p class="text-sm text-gray-600">
                            We'll respond within 24 hours
                        </p>
                    </div>

                    <!-- WhatsApp -->
                    <div class="rounded-lg bg-gray-50 p-8 text-center">
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-8 w-8 text-[#42b6c5]"
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
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            WhatsApp
                        </h3>
                        <a
                            v-if="settings.contact_whatsapp"
                            :href="getWhatsAppLink()"
                            target="_blank"
                            class="mb-2 block font-semibold text-[#42b6c5] hover:text-[#35919e]"
                        >
                            {{ settings.contact_whatsapp }}
                        </a>
                        <a
                            v-else
                            href="#"
                            class="mb-2 block font-semibold text-[#42b6c5] hover:text-[#35919e]"
                        >
                            Chat with us
                        </a>
                        <p class="text-sm text-gray-600">
                            Quick responses to your questions
                        </p>
                    </div>

                    <!-- Phone -->
                    <div class="rounded-lg bg-gray-50 p-8 text-center">
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-[#42b6c5]/10"
                        >
                            <svg
                                class="h-8 w-8 text-[#42b6c5]"
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
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-[#000928]">
                            Phone
                        </h3>
                        <a
                            v-if="settings.contact_phone"
                            :href="`tel:${settings.contact_phone}`"
                            class="mb-2 block font-semibold text-[#42b6c5] hover:text-[#35919e]"
                        >
                            {{ settings.contact_phone }}
                        </a>
                        <p class="text-sm text-gray-600">
                            Mon-Fri, 9AM - 5PM WAT
                        </p>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="mx-auto max-w-2xl rounded-lg bg-gray-50 p-8">
                    <h2 class="mb-6 text-2xl font-bold text-[#000928]">
                        Send us a Message
                    </h2>

                    <form @submit.prevent="submitForm" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label
                                    for="name"
                                    class="mb-2 block text-sm font-semibold text-gray-700"
                                    >Name *</label
                                >
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                    placeholder="Your full name"
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    for="email"
                                    class="mb-2 block text-sm font-semibold text-gray-700"
                                    >Email *</label
                                >
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                    placeholder="your@email.com"
                                />
                                <p
                                    v-if="form.errors.email"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                for="subject"
                                class="mb-2 block text-sm font-semibold text-gray-700"
                                >Subject *</label
                            >
                            <input
                                id="subject"
                                v-model="form.subject"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                placeholder="What is this regarding?"
                            />
                            <p
                                v-if="form.errors.subject"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.subject }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="message"
                                class="mb-2 block text-sm font-semibold text-gray-700"
                                >Message *</label
                            >
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="6"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 transition outline-none focus:border-transparent focus:ring-2 focus:ring-[#42b6c5]"
                                placeholder="Tell us more about your inquiry..."
                            ></textarea>
                            <p
                                v-if="form.errors.message"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.message }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-lg bg-[#42b6c5] px-6 py-3 text-lg font-bold text-white transition-colors hover:bg-[#35919e] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{
                                form.processing ? 'Sending...' : 'Send Message'
                            }}
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-12 text-center text-3xl font-bold text-[#000928]">
                    Frequently Asked Questions
                </h2>

                <div class="space-y-4">
                    <details class="group rounded-lg bg-white p-6">
                        <summary
                            class="flex cursor-pointer items-center justify-between font-bold text-[#000928]"
                        >
                            What are the prerequisites to join a program?
                            <span
                                class="transform transition-transform group-open:rotate-180"
                            >
                                <svg
                                    class="h-5 w-5 text-[#42b6c5]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                    />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-4 text-gray-600">
                            Most of our programs are open to anyone with a
                            passion to learn. Some programs may require basic
                            programming knowledge or professional experience.
                            Check the specific program details for requirements.
                        </p>
                    </details>

                    <details class="group rounded-lg bg-white p-6">
                        <summary
                            class="flex cursor-pointer items-center justify-between font-bold text-[#000928]"
                        >
                            How long do programs take?
                            <span
                                class="transform transition-transform group-open:rotate-180"
                            >
                                <svg
                                    class="h-5 w-5 text-[#42b6c5]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                    />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-4 text-gray-600">
                            Program durations range from 4 weeks for workshops
                            to 6 months for comprehensive bootcamps. Each
                            program page lists the exact duration.
                        </p>
                    </details>

                    <details class="group rounded-lg bg-white p-6">
                        <summary
                            class="flex cursor-pointer items-center justify-between font-bold text-[#000928]"
                        >
                            Are the programs online or in-person?
                            <span
                                class="transform transition-transform group-open:rotate-180"
                            >
                                <svg
                                    class="h-5 w-5 text-[#42b6c5]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                    />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-4 text-gray-600">
                            We offer both online and hybrid options. Most core
                            programs are in-person to maximize hands-on
                            learning, but we provide flexibility where needed.
                        </p>
                    </details>

                    <details class="group rounded-lg bg-white p-6">
                        <summary
                            class="flex cursor-pointer items-center justify-between font-bold text-[#000928]"
                        >
                            What kind of certification do I get?
                            <span
                                class="transform transition-transform group-open:rotate-180"
                            >
                                <svg
                                    class="h-5 w-5 text-[#42b6c5]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                    />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-4 text-gray-600">
                            All successful completions earn an
                            industry-recognized Traitz Academy certificate. More
                            importantly, you'll have real projects in your
                            portfolio that demonstrate your capabilities to
                            employers.
                        </p>
                    </details>

                    <details class="group rounded-lg bg-white p-6">
                        <summary
                            class="flex cursor-pointer items-center justify-between font-bold text-[#000928]"
                        >
                            Do you help with job placement?
                            <span
                                class="transform transition-transform group-open:rotate-180"
                            >
                                <svg
                                    class="h-5 w-5 text-[#42b6c5]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                    />
                                </svg>
                            </span>
                        </summary>
                        <p class="mt-4 text-gray-600">
                            Yes! We provide career support including resume
                            review, interview preparation, and connect graduates
                            with our network of hiring partners.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section
            class="bg-gradient-to-r from-[#000928] to-[#381998] py-16 text-white"
        >
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="mb-6 text-3xl font-bold md:text-4xl">
                    Ready to Take the Next Step?
                </h2>
                <p class="mb-8 text-xl text-gray-300">
                    Explore our programs and start your journey today
                </p>
                <Link
                    href="/programs"
                    class="inline-flex transform items-center rounded-lg bg-[#42b6c5] px-8 py-3 text-lg font-bold text-[#000928] transition-all duration-200 hover:scale-105 hover:bg-white"
                >
                    View Programs
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
