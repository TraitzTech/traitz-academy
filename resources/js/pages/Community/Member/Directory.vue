<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { ref, watch } from 'vue';

import CommunityShell from '@/components/community/CommunityShell.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import { useCommunity } from '@/composables/useCommunity';
import type { Paginated, TacTrack } from '@/types/community';

interface DirectoryMember {
    id: number;
    full_name: string;
    school: string | null;
    bio: string | null;
    avatar_path: string | null;
    membership_status: 'member' | 'contributor' | 'mentor' | 'lead' | 'alumni';
    status_label: string;
    social_links: Record<string, string | null> | null;
    tracks: TacTrack[];
}

interface Props {
    members: Paginated<DirectoryMember>;
    filters: { search?: string; track?: string; school?: string };
    tracks: TacTrack[];
    schools: string[];
    isListed: boolean;
}

const props = defineProps<Props>();
const { asset, initials, membershipStatus, socialIcon } = useCommunity();

const search = ref(props.filters.search ?? '');
const track = ref(props.filters.track ?? '');
const school = ref(props.filters.school ?? '');

const go = () =>
    router.get(
        '/community/me/directory',
        {
            search: search.value || undefined,
            track: track.value || undefined,
            school: school.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );

const goDebounced = debounce(go, 350);

watch(search, goDebounced);
watch([track, school], () => {
    goDebounced.cancel();
    go();
});
</script>

<template>
    <CommunityShell active="member">
        <Head title="Member directory — TAC" />

        <section
            class="border-b border-gray-100 bg-white"
        >
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <nav class="mb-5 text-sm" aria-label="Breadcrumb">
                    <Link
                        href="/community/me"
                        class="font-semibold text-gray-500 transition-colors hover:text-[#000928]"
                    >
                        ← My member area
                    </Link>
                </nav>

                <h1
                    class="text-3xl font-black tracking-tight text-[#000928]"
                >
                    Member directory
                </h1>
                <p
                    class="mt-2 max-w-2xl text-sm text-gray-600"
                >
                    {{ members.total }} members have chosen to be listed. Contact
                    details are never shown — reach people through the links they
                    added themselves.
                </p>

                <div
                    v-if="!isListed"
                    class="mt-5 flex flex-wrap items-center gap-3 rounded-xl border border-amber-300/50 bg-amber-50 p-4"
                >
                    <p
                        class="flex-1 text-sm text-amber-800"
                    >
                        You are not listed in the directory, so other members
                        cannot find you here.
                    </p>
                    <Link
                        href="/community/me/profile"
                        class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-amber-600"
                    >
                        Add me to the directory
                    </Link>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div
                class="mb-8 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 sm:flex-row"
            >
                <div class="flex-1">
                    <label for="dir-search" class="sr-only">Search members</label>
                    <input
                        id="dir-search"
                        v-model="search"
                        type="search"
                        placeholder="Search by name or school…"
                        class="lms-input"
                    />
                </div>
                <label for="dir-track" class="sr-only">Filter by track</label>
                <select id="dir-track" v-model="track" class="lms-input sm:w-52">
                    <option value="">All tracks</option>
                    <option v-for="t in tracks" :key="t.id" :value="t.slug">
                        {{ t.name }}
                    </option>
                </select>
                <label for="dir-school" class="sr-only">Filter by school</label>
                <select id="dir-school" v-model="school" class="lms-input sm:w-56">
                    <option value="">All schools</option>
                    <option v-for="s in schools" :key="s" :value="s">
                        {{ s }}
                    </option>
                </select>
            </div>

            <div
                v-if="members.data.length"
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <article
                    v-for="member in members.data"
                    :key="member.id"
                    class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 transition-all hover:-translate-y-1 hover:shadow-lg"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="h-12 w-12 shrink-0 overflow-hidden rounded-xl"
                        >
                            <img
                                v-if="asset(member.avatar_path)"
                                :src="asset(member.avatar_path)!"
                                :alt="member.full_name"
                                loading="lazy"
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#000928] to-[#381998] font-black text-white"
                                aria-hidden="true"
                                >{{ initials(member.full_name) }}</span
                            >
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2
                                class="truncate font-bold text-[#000928]"
                            >
                                {{ member.full_name }}
                            </h2>
                            <p
                                v-if="member.school"
                                class="truncate text-xs text-gray-500"
                            >
                                {{ member.school }}
                            </p>
                            <span
                                :class="[
                                    'mt-1.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold',
                                    membershipStatus(member.membership_status)
                                        .classes,
                                ]"
                            >
                                {{
                                    membershipStatus(member.membership_status)
                                        .label
                                }}
                            </span>
                        </div>
                    </div>

                    <p
                        v-if="member.bio"
                        class="mt-3 line-clamp-3 text-sm text-gray-600"
                    >
                        {{ member.bio }}
                    </p>

                    <div
                        v-if="member.tracks.length"
                        class="mt-4 flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="t in member.tracks"
                            :key="t.id"
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                            :style="{
                                backgroundColor: `${t.accent_color ?? '#42b6c5'}1f`,
                                color: t.accent_color ?? '#42b6c5',
                            }"
                        >
                            {{ t.name }}
                        </span>
                    </div>

                    <div
                        v-if="member.social_links"
                        class="mt-auto flex gap-2 pt-4"
                    >
                        <a
                            v-for="[network, url] in Object.entries(
                                member.social_links,
                            ).filter(([, u]) => u)"
                            :key="network"
                            :href="url as string"
                            target="_blank"
                            rel="noopener noreferrer"
                            :aria-label="`${member.full_name} on ${network}`"
                            :title="network"
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-gray-600 transition-colors hover:bg-[#42b6c5] hover:text-white"
                        >
                            <component
                                :is="socialIcon(network, url as string)"
                                class="h-3.5 w-3.5"
                            />
                        </a>
                    </div>
                </article>
            </div>

            <EmptyState
                v-else
                icon="users"
                title="No members match that"
                description="Try a different track, school or search term."
            />

            <nav
                v-if="members.last_page > 1"
                class="mt-10 flex flex-wrap items-center justify-center gap-1"
                aria-label="Pagination"
            >
                <component
                    :is="link.url ? Link : 'span'"
                    v-for="(link, index) in members.links"
                    :key="index"
                    :href="link.url ?? undefined"
                    preserve-scroll
                    :aria-current="link.active ? 'page' : undefined"
                    :class="[
                        'min-w-9 rounded-lg px-3 py-2 text-center text-sm font-semibold transition-colors',
                        link.active
                            ? 'bg-[#000928] text-white'
                            : link.url
                              ? 'text-gray-600 hover:bg-gray-100'
                              : 'cursor-not-allowed text-gray-300',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </div>
    </CommunityShell>
</template>
