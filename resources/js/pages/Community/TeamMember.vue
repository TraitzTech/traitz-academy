<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Mail, Phone } from 'lucide-vue-next';
import { computed } from 'vue';

import ActivityCard from '@/components/community/ActivityCard.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import { useCommunity } from '@/composables/useCommunity';
import type { TacActivity, TacLeader } from '@/types/community';

interface Props {
    leader: TacLeader;
    isRetired: boolean;
    activities: TacActivity[];
}

const props = defineProps<Props>();
const { asset, initials, formatDate, socialIcon } = useCommunity();

const ROLE_LABELS: Record<string, string> = {
    lead: 'Lead',
    co_lead: 'Co-Lead',
    secretary: 'Secretary',
    technical_lead: 'Technical Lead',
    track_mentor: 'Track Mentor',
    school_lead: 'School Lead',
    partnership_lead: 'Partnership Lead',
};

const roleLabel = computed(
    () => props.leader.role_title || ROLE_LABELS[props.leader.role_type] || 'Leader',
);

const context = computed(() => {
    if (props.leader.role_type === 'track_mentor') return props.leader.track?.name ?? null;
    if (props.leader.role_type === 'school_lead') return props.leader.school ?? null;
    return null;
});

const term = computed(() => {
    if (!props.leader.started_on) return null;
    const from = formatDate(props.leader.started_on, { month: 'short', year: 'numeric' });
    const to = props.leader.ended_on
        ? formatDate(props.leader.ended_on, { month: 'short', year: 'numeric' })
        : 'present';
    return `${from} – ${to}`;
});

const contactLinks = computed(() => {
    const links: { key: string; label: string; href: string; icon: unknown }[] =
        Object.entries(props.leader.social_links ?? {})
            .filter(
                (entry): entry is [string, string] =>
                    typeof entry[1] === 'string' && entry[1].length > 0,
            )
            .map(([network, url]) => ({
                key: network,
                label: network,
                href: url,
                icon: socialIcon(network, url),
            }));

    if (props.leader.email) {
        links.push({ key: 'email', label: 'Email', href: `mailto:${props.leader.email}`, icon: Mail });
    }
    if (props.leader.phone) {
        links.push({ key: 'phone', label: 'Phone', href: `tel:${props.leader.phone}`, icon: Phone });
    }

    return links;
});
</script>

<template>
    <CommunityShell active="team">
        <Head :title="`${leader.name} — TAC Team`">
            <meta
                name="description"
                :content="leader.bio ?? `${leader.name}, ${roleLabel} at the Traitz Academy Community.`"
            />
        </Head>

        <!-- Hero -->
        <section class="bg-gradient-to-r from-[#000928] to-[#381998] py-14 text-white lg:py-20">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <nav class="mb-6 text-sm" aria-label="Breadcrumb">
                    <Link href="/community/team" class="font-semibold text-white/60 transition-colors hover:text-white">
                        ← Team & Leadership
                    </Link>
                </nav>

                <div class="flex flex-wrap items-center gap-6">
                    <span
                        :class="[
                            'h-28 w-28 shrink-0 overflow-hidden rounded-2xl',
                            isRetired ? 'opacity-80 grayscale' : '',
                        ]"
                    >
                        <img
                            v-if="asset(leader.photo_path)"
                            :src="asset(leader.photo_path)!"
                            :alt="leader.name"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-else
                            class="flex h-full w-full items-center justify-center bg-white/10 text-3xl font-black backdrop-blur"
                            aria-hidden="true"
                        >
                            {{ initials(leader.name) }}
                        </span>
                    </span>

                    <div class="min-w-0">
                        <span
                            v-if="isRetired"
                            class="mb-2 inline-block rounded-full border border-white/25 bg-white/10 px-3 py-1 text-[11px] font-bold tracking-wide uppercase"
                        >
                            Alumni leader
                        </span>
                        <h1 class="text-3xl font-black tracking-tight sm:text-4xl">
                            {{ leader.name }}
                        </h1>
                        <p class="mt-2 text-lg text-white/80">
                            {{ roleLabel }}
                            <span v-if="context" class="text-white/60">— {{ context }}</span>
                        </p>
                        <p v-if="term" class="mt-1 text-sm text-white/50">
                            {{ isRetired ? 'Served' : 'In role since' }} {{ term }}
                        </p>
                    </div>
                </div>

                <div v-if="contactLinks.length" class="mt-8 flex flex-wrap gap-2">
                    <a
                        v-for="link in contactLinks"
                        :key="link.key"
                        :href="link.href"
                        :target="link.href.startsWith('http') ? '_blank' : undefined"
                        :rel="link.href.startsWith('http') ? 'noopener noreferrer' : undefined"
                        class="flex items-center gap-2 rounded-lg border border-white/20 bg-white/5 px-3.5 py-2 text-sm font-semibold transition-colors hover:bg-white/15"
                    >
                        <component :is="link.icon" class="h-4 w-4" />
                        {{ link.label }}
                    </a>
                </div>
            </div>
        </section>

        <!-- Bio -->
        <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
            <div v-if="leader.bio" class="max-w-3xl">
                <h2 class="text-xs font-bold tracking-widest text-gray-400 uppercase">About</h2>
                <p class="mt-3 leading-relaxed whitespace-pre-line text-gray-700">
                    {{ leader.bio }}
                </p>
            </div>
            <p v-else class="text-gray-500">
                {{ leader.name }} hasn't added a bio yet.
            </p>

            <div v-if="leader.track" class="mt-8">
                <Link
                    :href="`/community/tracks/${leader.track.slug}`"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-[#000928] transition-colors hover:border-[#42b6c5]/40 hover:bg-gray-50"
                >
                    <span
                        class="h-2.5 w-2.5 rounded-full"
                        :style="{ backgroundColor: leader.track.accent_color ?? '#42b6c5' }"
                        aria-hidden="true"
                    />
                    View the {{ leader.track.name }} track →
                </Link>
            </div>
        </section>

        <!-- Activities organised -->
        <section v-if="activities.length" class="border-t border-gray-100 bg-gray-50/70 py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl font-black tracking-tight text-[#000928]">
                    Activities organised by {{ leader.name.split(' ')[0] }}
                </h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <ActivityCard
                        v-for="activity in activities"
                        :key="activity.id"
                        :activity="activity"
                        compact
                    />
                </div>
            </div>
        </section>
    </CommunityShell>
</template>
