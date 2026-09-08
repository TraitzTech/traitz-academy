<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, Phone } from 'lucide-vue-next';
import { computed } from 'vue';

import { useCommunity } from '@/composables/useCommunity';
import type { TacLeader } from '@/types/community';

/**
 * A single leader as one compact horizontal row — for lists where most
 * entries (a track's one mentor, a school's one lead) would otherwise be a
 * whole card sitting mostly empty. Full detail (bio, larger photo) lives on
 * {@see LeaderCard.vue}; this is the scan-friendly directory line.
 */
interface Props {
    leader: TacLeader;
}

const props = defineProps<Props>();
const { asset, initials, socialIcon } = useCommunity();

const photo = computed(() => asset(props.leader.photo_path));

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
    <div
        class="flex items-center gap-3 rounded-xl border border-gray-200 px-3.5 py-2.5 transition-colors hover:border-[#42b6c5]/40 hover:bg-gray-50"
    >
        <Link
            :href="`/community/team/${leader.slug}`"
            class="flex min-w-0 flex-1 items-center gap-3 focus-visible:outline-none"
        >
            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg">
                <img
                    v-if="photo"
                    :src="photo"
                    :alt="leader.name"
                    loading="lazy"
                    class="h-full w-full object-cover"
                />
                <span
                    v-else
                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#000928] to-[#381998] text-xs font-black text-white"
                    aria-hidden="true"
                >
                    {{ initials(leader.name) }}
                </span>
            </span>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-[#000928] hover:text-[#381998]">
                    {{ leader.name }}
                </p>
                <p v-if="leader.role_title" class="truncate text-xs text-gray-500">
                    {{ leader.role_title }}
                </p>
            </div>
        </Link>

        <div v-if="contactLinks.length" class="flex shrink-0 items-center gap-1">
            <a
                v-for="link in contactLinks"
                :key="link.key"
                :href="link.href"
                :target="link.href.startsWith('http') ? '_blank' : undefined"
                :rel="link.href.startsWith('http') ? 'noopener noreferrer' : undefined"
                :aria-label="`${leader.name} — ${link.label}`"
                :title="link.label"
                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-[#42b6c5] hover:text-white"
            >
                <component :is="link.icon" class="h-3.5 w-3.5" />
            </a>
        </div>
    </div>
</template>
