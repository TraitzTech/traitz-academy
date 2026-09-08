<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import { useCommunity } from '@/composables/useCommunity';
import type { TacLeader, TacPartner } from '@/types/community';

interface TierGroup {
    tier: string;
    label: string;
    partners: TacPartner[];
}

interface Props {
    tiers: TierGroup[];
    featured: TacPartner[];
    total: number;
    partnershipLeads: TacLeader[];
    contactEmail: string;
}

defineProps<Props>();

const { asset, initials } = useCommunity();
</script>

<template>
    <CommunityShell active="partners">
        <SeoHead
            title="Partners & Sponsors"
            description="The organisations backing the Traitz Academy Community — sponsors, academic partners and collaborators."
        />

        <section
            class="border-b border-gray-100 bg-white"
        >
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                <h1
                    class="text-3xl font-black tracking-tight text-[#000928] sm:text-4xl"
                >
                    Partners & Sponsors
                </h1>
                <p
                    class="mt-4 max-w-2xl text-base leading-relaxed text-gray-600"
                >
                    TAC runs on the support of organisations who believe in
                    growing tech talent here. {{ total }} partners currently
                    back the community.
                </p>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <EmptyState
                v-if="!tiers.length"
                icon="users"
                title="Partnerships in progress"
                description="We're building our partner network. If your organisation wants to support TAC, we'd love to talk."
            >
                <a :href="`mailto:${contactEmail}`" class="lms-btn-accent">
                    Talk to us about partnering
                </a>
            </EmptyState>

            <section
                v-for="group in tiers"
                :key="group.tier"
                class="mb-12"
            >
                <h2
                    class="text-sm font-bold tracking-widest text-gray-500 uppercase"
                >
                    {{ group.label }}s
                </h2>

                <div
                    :class="[
                        'mt-5 grid gap-5',
                        group.tier === 'platinum' || group.tier === 'gold'
                            ? 'sm:grid-cols-2 lg:grid-cols-3'
                            : 'sm:grid-cols-3 lg:grid-cols-4',
                    ]"
                >
                    <component
                        :is="partner.website_url ? 'a' : 'div'"
                        v-for="partner in group.partners"
                        :key="partner.id"
                        :href="partner.website_url ?? undefined"
                        :target="partner.website_url ? '_blank' : undefined"
                        rel="noopener noreferrer"
                        class="group flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-6 transition-all hover:-translate-y-1 hover:border-[#42b6c5]/40 hover:shadow-lg"
                    >
                        <div class="flex h-14 items-center">
                            <img
                                v-if="asset(partner.logo_path)"
                                :src="asset(partner.logo_path)!"
                                :alt="partner.name"
                                loading="lazy"
                                class="max-h-14 w-auto max-w-[180px] object-contain"
                            />
                            <span
                                v-else
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#000928] font-black text-white"
                                aria-hidden="true"
                            >
                                {{ initials(partner.name) }}
                            </span>
                        </div>

                        <h3
                            class="mt-4 font-bold text-[#000928]"
                        >
                            {{ partner.name }}
                        </h3>
                        <p
                            v-if="partner.description"
                            class="mt-2 line-clamp-3 text-sm text-gray-600"
                        >
                            {{ partner.description }}
                        </p>

                        <span
                            v-if="partner.website_url"
                            class="mt-auto pt-4 text-xs font-bold text-[#381998] transition-transform group-hover:translate-x-0.5"
                        >
                            Visit website →
                        </span>
                    </component>
                </div>
            </section>

            <!-- Become a partner -->
            <section
                class="mt-6 overflow-hidden rounded-3xl bg-gradient-to-r from-[#000928] to-[#381998]"
            >
                <div
                    class="grid gap-8 px-6 py-12 sm:px-12 lg:grid-cols-2 lg:items-center"
                >
                    <div>
                        <h2 class="text-2xl font-black text-white sm:text-3xl">
                            Partner with TAC
                        </h2>
                        <p class="mt-4 leading-relaxed text-white/70">
                            Sponsor a bootcamp, host a workshop, offer
                            internships, or put your tooling in the hands of the
                            people who will be building with it for the next
                            decade.
                        </p>
                        <a
                            :href="`mailto:${contactEmail}?subject=TAC%20partnership`"
                            class="mt-7 inline-block rounded-xl bg-[#42b6c5] px-8 py-3.5 font-bold text-white shadow-xl shadow-[#42b6c5]/25 transition-all hover:-translate-y-0.5 hover:bg-[#35919e]"
                        >
                            Start a conversation
                        </a>
                    </div>

                    <div v-if="partnershipLeads.length">
                        <p
                            class="text-xs font-bold tracking-widest text-white/50 uppercase"
                        >
                            Talk to our partnership leads
                        </p>
                        <ul class="mt-4 space-y-3">
                            <li
                                v-for="lead in partnershipLeads"
                                :key="lead.id"
                                class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 p-3 backdrop-blur"
                            >
                                <span
                                    class="h-11 w-11 shrink-0 overflow-hidden rounded-lg"
                                >
                                    <img
                                        v-if="asset(lead.photo_path)"
                                        :src="asset(lead.photo_path)!"
                                        :alt="lead.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-[#42b6c5] text-sm font-black text-white"
                                        aria-hidden="true"
                                    >
                                        {{ initials(lead.name) }}
                                    </span>
                                </span>
                                <div class="min-w-0">
                                    <p class="font-bold text-white">
                                        {{ lead.name }}
                                    </p>
                                    <a
                                        v-if="lead.email"
                                        :href="`mailto:${lead.email}`"
                                        class="truncate text-sm text-[#42b6c5] hover:underline"
                                    >
                                        {{ lead.email }}
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </CommunityShell>
</template>
