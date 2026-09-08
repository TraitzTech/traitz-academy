<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, Phone } from 'lucide-vue-next';
import { computed } from 'vue';

import { useCommunity } from '@/composables/useCommunity';
import type { TacLeader } from '@/types/community';

interface Props {
    leader: TacLeader;
    /** Retired leaders render muted, with their term of service. */
    retired?: boolean;
    showBio?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    retired: false,
    showBio: true,
});

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

const photo = computed(() => asset(props.leader.photo_path));

const roleLabel = computed(
    () =>
        props.leader.role_title ||
        ROLE_LABELS[props.leader.role_type] ||
        'Leader',
);

const context = computed(() => {
    if (props.leader.role_type === 'track_mentor')
        return props.leader.track?.name ?? null;
    if (props.leader.role_type === 'school_lead')
        return props.leader.school ?? null;
    return null;
});

const term = computed(() => {
    if (!props.leader.started_on) return null;
    const from = formatDate(props.leader.started_on, {
        month: 'short',
        year: 'numeric',
    });
    const to = props.leader.ended_on
        ? formatDate(props.leader.ended_on, { month: 'short', year: 'numeric' })
        : 'present';
    return `${from} – ${to}`;
});

/**
 * Social links plus direct contact — a leader who only shared an email and a
 * phone number should still be reachable, not left with an empty row just
 * because they didn't add a LinkedIn profile.
 */
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
        links.push({
            key: 'email',
            label: 'Email',
            href: `mailto:${props.leader.email}`,
            icon: Mail,
        });
    }

    if (props.leader.phone) {
        links.push({
            key: 'phone',
            label: 'Phone',
            href: `tel:${props.leader.phone}`,
            icon: Phone,
        });
    }

    return links;
});
</script>

<template>
    <article
        :class="[
            'group flex h-full flex-col rounded-2xl border p-6 text-center transition-all',
            retired
                ? 'border-gray-200 bg-gray-50/60'
                : 'border-gray-200 bg-white hover:-translate-y-1 hover:border-[#42b6c5]/40 hover:shadow-xl hover:shadow-[#000928]/5',
        ]"
    >
        <Link
            :href="`/community/team/${leader.slug}`"
            class="flex w-full flex-col items-center text-center focus-visible:outline-none"
        >
            <div class="mx-auto">
                <span
                    :class="[
                        'relative flex overflow-hidden rounded-2xl',
                        retired ? 'h-16 w-16' : 'h-20 w-20',
                    ]"
                >
                    <img
                        v-if="photo"
                        :src="photo"
                        :alt="leader.name"
                        loading="lazy"
                        :class="[
                            'h-full w-full object-cover',
                            retired ? 'opacity-70 grayscale' : '',
                        ]"
                    />
                    <span
                        v-else
                        class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#000928] to-[#381998] text-xl font-black text-white"
                        aria-hidden="true"
                    >
                        {{ initials(leader.name) }}
                    </span>
                </span>
            </div>

            <h3
                :class="[
                    'mt-4 font-bold text-[#000928] transition-colors group-hover:text-[#381998]',
                    retired ? 'text-sm' : 'text-base',
                ]"
            >
                {{ leader.name }}
            </h3>

            <p
                class="mt-1 text-xs font-bold tracking-wide text-[#42b6c5] uppercase"
            >
                {{ roleLabel }}
            </p>

            <p
                v-if="context"
                class="mt-1 text-xs font-medium text-gray-500"
            >
                {{ context }}
            </p>

            <p
                v-if="showBio && leader.bio && !retired"
                class="mt-3 line-clamp-3 text-sm text-gray-600"
            >
                {{ leader.bio }}
            </p>

            <p v-if="retired && term" class="mt-2 text-[11px] text-gray-400">
                {{ term }}
            </p>
        </Link>

        <div
            v-if="contactLinks.length && !retired"
            class="mt-auto flex flex-wrap justify-center gap-2 pt-4"
        >
            <a
                v-for="link in contactLinks"
                :key="link.key"
                :href="link.href"
                :target="link.href.startsWith('http') ? '_blank' : undefined"
                :rel="link.href.startsWith('http') ? 'noopener noreferrer' : undefined"
                :aria-label="`${leader.name} — ${link.label}`"
                :title="link.label"
                class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600 transition-colors hover:bg-[#42b6c5] hover:text-white"
            >
                <component :is="link.icon" class="h-4 w-4" />
            </a>
        </div>
    </article>
</template>
