<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import CommunityShell from '@/components/community/CommunityShell.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useCommunity } from '@/composables/useCommunity';
import type { TacLeader, TacTrack } from '@/types/community';

interface Props {
    tracks: TacTrack[];
    contacts: {
        email: string;
        phone: string | null;
        whatsapp: string | null;
    };
    partnershipLeads: TacLeader[];
    schoolLeads: TacLeader[];
    isMember: boolean;
}

const props = defineProps<Props>();
const { asset, initials } = useCommunity();

const mailto = (subject: string) =>
    `mailto:${props.contacts.email}?subject=${encodeURIComponent(subject)}`;

const paths = [
    {
        title: 'Become a member',
        body: 'The simplest way in. Free, permanent, and open to students, past interns and anyone curious about tech.',
        cta: 'Join TAC',
        href: '/community/join',
        internal: true,
        emoji: '🎓',
    },
    {
        title: 'Mentor a track',
        body: 'Work in one of our eight areas? Guide the members in it, shape the activities, and help people find their footing.',
        cta: 'Offer to mentor',
        href: 'Mentoring a TAC track',
        internal: false,
        emoji: '🧭',
    },
    {
        title: 'Lead TAC at your school',
        body: 'Organise TAC’s presence on your campus — run meetups, recruit members, and be the point of contact there.',
        cta: 'Become a school lead',
        href: 'Leading TAC at my school',
        internal: false,
        emoji: '🏫',
    },
    {
        title: 'Partner or sponsor',
        body: 'Back a bootcamp, host a workshop, offer internships, or put your tooling in the hands of the next generation.',
        cta: 'Talk about partnering',
        href: 'TAC partnership',
        internal: false,
        emoji: '🤝',
    },
    {
        title: 'Speak or run a session',
        body: 'Have something worth teaching? Run a workshop, give a talk, or set a competition challenge for the community.',
        cta: 'Propose a session',
        href: 'Proposing a TAC session',
        internal: false,
        emoji: '🎤',
    },
    {
        title: 'Host a competition',
        body: 'Sponsor prizes, set a brief, or judge entries in a TAC competition across any of the tracks.',
        cta: 'Host a competition',
        href: 'Hosting a TAC competition',
        internal: false,
        emoji: '🏆',
    },
];
</script>

<template>
    <CommunityShell active="get-involved">
        <SeoHead
            title="Get Involved"
            description="Ways to take part in TAC: join as a member, mentor a track, lead at your school, partner with us, or run a session."
        />

        <section
            class="border-b border-gray-100 bg-white"
        >
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <h1
                    class="text-3xl font-black tracking-tight text-[#000928] sm:text-4xl"
                >
                    Get involved
                </h1>
                <p
                    class="mt-4 max-w-2xl text-base leading-relaxed text-gray-600"
                >
                    TAC runs on people who show up. There is more than one way
                    in — pick whichever fits where you are right now.
                </p>
            </div>
        </section>

        <!-- Paths -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="path in paths"
                    :key="path.title"
                    class="flex flex-col rounded-2xl border border-gray-200 bg-white p-7 transition-all hover:-translate-y-1 hover:border-[#42b6c5]/40 hover:shadow-lg"
                >
                    <span class="text-3xl" aria-hidden="true">{{
                        path.emoji
                    }}</span>
                    <h2
                        class="mt-4 text-lg font-bold text-[#000928]"
                    >
                        {{ path.title }}
                    </h2>
                    <p
                        class="mt-2 flex-1 text-sm leading-relaxed text-gray-600"
                    >
                        {{ path.body }}
                    </p>

                    <Link
                        v-if="path.internal"
                        :href="path.href"
                        class="mt-5 text-sm font-bold text-[#381998] hover:underline"
                    >
                        {{ path.cta }} →
                    </Link>
                    <a
                        v-else
                        :href="mailto(path.href)"
                        class="mt-5 text-sm font-bold text-[#381998] hover:underline"
                    >
                        {{ path.cta }} →
                    </a>
                </div>
            </div>
        </section>

        <!-- Tracks that need mentors -->
        <section
            class="border-y border-gray-100 bg-gray-50/70 py-14"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
                    <div>
                        <h2
                            class="text-2xl font-black tracking-tight text-[#000928]"
                        >
                            Which track would you mentor?
                        </h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Tell us the area and we'll get back to you about what
                            mentoring involves.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a
                                v-for="track in tracks"
                                :key="track.id"
                                :href="mailto(`Mentoring the ${track.name} track`)"
                                class="rounded-full border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-[#000928] transition-all hover:-translate-y-0.5 hover:border-[#42b6c5] hover:shadow-md"
                            >
                                {{ track.name }}
                            </a>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl">
                        <img
                            src="/images/academy-community/classroom/coding-session-evening.jpg"
                            alt="A mentor working with members during an evening coding session"
                            class="aspect-[4/3] w-full object-cover"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Contacts -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-2">
                <div>
                    <h2
                        class="text-2xl font-black tracking-tight text-[#000928]"
                    >
                        Talk to us
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        However you want to be involved, this reaches the right
                        person.
                    </p>

                    <ul class="mt-6 space-y-3">
                        <li>
                            <a
                                :href="mailto('TAC enquiry')"
                                class="flex items-center gap-4 rounded-2xl border border-gray-200 p-5 transition-colors hover:border-[#42b6c5]/50 hover:bg-gray-50"
                            >
                                <span class="text-2xl" aria-hidden="true">✉️</span>
                                <span>
                                    <span
                                        class="block text-xs font-bold tracking-widest text-gray-400 uppercase"
                                        >Email</span
                                    >
                                    <span
                                        class="font-semibold text-[#000928]"
                                        >{{ contacts.email }}</span
                                    >
                                </span>
                            </a>
                        </li>
                        <li v-if="contacts.phone">
                            <a
                                :href="`tel:${contacts.phone}`"
                                class="flex items-center gap-4 rounded-2xl border border-gray-200 p-5 transition-colors hover:border-[#42b6c5]/50 hover:bg-gray-50"
                            >
                                <span class="text-2xl" aria-hidden="true">📞</span>
                                <span>
                                    <span
                                        class="block text-xs font-bold tracking-widest text-gray-400 uppercase"
                                        >Phone</span
                                    >
                                    <span
                                        class="font-semibold text-[#000928]"
                                        >{{ contacts.phone }}</span
                                    >
                                </span>
                            </a>
                        </li>
                        <li v-if="contacts.whatsapp">
                            <a
                                :href="contacts.whatsapp"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center gap-4 rounded-2xl border border-gray-200 p-5 transition-colors hover:border-[#42b6c5]/50 hover:bg-gray-50"
                            >
                                <span class="text-2xl" aria-hidden="true">💬</span>
                                <span>
                                    <span
                                        class="block text-xs font-bold tracking-widest text-gray-400 uppercase"
                                        >WhatsApp</span
                                    >
                                    <span
                                        class="font-semibold text-[#000928]"
                                        >Message the community</span
                                    >
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- People -->
                <div class="space-y-8">
                    <div v-if="partnershipLeads.length">
                        <h3
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                        >
                            Partnership leads
                        </h3>
                        <ul class="mt-3 space-y-2.5">
                            <li
                                v-for="lead in partnershipLeads"
                                :key="lead.id"
                                class="flex items-center gap-3 rounded-xl border border-gray-200 p-3"
                            >
                                <span
                                    class="h-10 w-10 shrink-0 overflow-hidden rounded-lg"
                                >
                                    <img
                                        v-if="asset(lead.photo_path)"
                                        :src="asset(lead.photo_path)!"
                                        :alt="lead.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-[#381998] text-xs font-black text-white"
                                        aria-hidden="true"
                                        >{{ initials(lead.name) }}</span
                                    >
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-bold text-[#000928]"
                                    >
                                        {{ lead.name }}
                                    </p>
                                    <a
                                        v-if="lead.email"
                                        :href="`mailto:${lead.email}`"
                                        class="truncate text-xs text-[#42b6c5] hover:underline"
                                        >{{ lead.email }}</a
                                    >
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div v-if="schoolLeads.length">
                        <h3
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                        >
                            School leads
                        </h3>
                        <ul class="mt-3 space-y-2.5">
                            <li
                                v-for="lead in schoolLeads"
                                :key="lead.id"
                                class="flex items-center gap-3 rounded-xl border border-gray-200 p-3"
                            >
                                <span
                                    class="h-10 w-10 shrink-0 overflow-hidden rounded-lg"
                                >
                                    <img
                                        v-if="asset(lead.photo_path)"
                                        :src="asset(lead.photo_path)!"
                                        :alt="lead.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-[#42b6c5] text-xs font-black text-white"
                                        aria-hidden="true"
                                        >{{ initials(lead.name) }}</span
                                    >
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-bold text-[#000928]"
                                    >
                                        {{ lead.name }}
                                    </p>
                                    <p class="truncate text-xs text-gray-500">
                                        {{ lead.school }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </CommunityShell>
</template>
