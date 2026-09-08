<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    CommunityMember,
    SelectOption,
    TacActivityRsvp,
    TacCompetitionEntry,
    TacLeader,
    TacTrack,
} from '@/types/community';

interface Props {
    member: CommunityMember & {
        rsvps?: TacActivityRsvp[];
        competition_entries?: TacCompetitionEntry[];
        leadership?: TacLeader | null;
    };
    sourceLabel: string;
    tracks: TacTrack[];
    options: {
        currentStatuses: SelectOption[];
        membershipStatuses: SelectOption[];
        sources: SelectOption[];
        lifecycleStatuses: SelectOption[];
    };
    can: { update: boolean; delete: boolean };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { asset, initials, formatDate, dateRange, rsvpStatus, membershipStatus } =
    useCommunity();

const showDelete = ref(false);

const form = useForm({
    first_name: props.member.first_name,
    last_name: props.member.last_name ?? '',
    email: props.member.email,
    phone: props.member.phone ?? '',
    school: props.member.school ?? '',
    bio: props.member.bio ?? '',
    current_status: props.member.current_status,
    membership_status: props.member.membership_status,
    lifecycle_status: props.member.lifecycle_status,
    directory_opt_in: props.member.directory_opt_in,
    email_opt_in: props.member.email_opt_in,
    admin_notes: props.member.admin_notes ?? '',
    track_ids: (props.member.tracks ?? []).map((track) => track.id),
});

const submit = () =>
    form.put(`/admin/community/members/${props.member.id}`, {
        preserveScroll: true,
    });

const destroy = () =>
    router.delete(`/admin/community/members/${props.member.id}`, {
        onSuccess: () => router.visit('/admin/community/members'),
    });
</script>

<template>
    <div class="lms-page">
        <Head :title="`${member.full_name} — Community member`" />

        <nav class="text-sm" aria-label="Breadcrumb">
            <Link
                href="/admin/community/members"
                class="font-semibold text-gray-500 hover:text-[#000928] dark:hover:text-white"
            >
                ← All members
            </Link>
        </nav>

        <!-- Header -->
        <div class="lms-hero">
            <div class="flex flex-wrap items-start gap-5">
                <span class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl">
                    <img
                        v-if="asset(member.avatar_path)"
                        :src="asset(member.avatar_path)!"
                        :alt="member.full_name"
                        class="h-full w-full object-cover"
                    />
                    <span
                        v-else
                        class="flex h-full w-full items-center justify-center bg-white/10 text-xl font-black backdrop-blur"
                        aria-hidden="true"
                        >{{ initials(member.full_name) }}</span
                    >
                </span>

                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-black tracking-tight">
                        {{ member.full_name }}
                    </h1>
                    <p class="mt-1 text-sm text-white/70">
                        {{ member.email }}
                        <template v-if="member.phone"
                            >· {{ member.phone }}</template
                        >
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-[11px] font-bold backdrop-blur"
                        >
                            {{
                                membershipStatus(member.membership_status).label
                            }}
                        </span>
                        <span
                            class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-[11px] font-bold backdrop-blur"
                        >
                            {{ sourceLabel }}
                        </span>
                        <span
                            v-if="member.user"
                            class="rounded-full bg-[#42b6c5] px-2.5 py-1 text-[11px] font-bold"
                        >
                            Has an account
                        </span>
                        <span
                            v-if="member.leadership"
                            class="rounded-full bg-amber-400 px-2.5 py-1 text-[11px] font-bold text-[#000928]"
                        >
                            {{
                                member.leadership.role_title ??
                                member.leadership.role_type.replace('_', ' ')
                            }}
                        </span>
                    </div>
                </div>

                <dl class="grid grid-cols-3 gap-3 text-center">
                    <div
                        v-for="stat in [
                            { label: 'Engagement', value: member.engagement_score },
                            { label: 'RSVPs', value: member.rsvps?.length ?? 0 },
                            {
                                label: 'Entries',
                                value: member.competition_entries?.length ?? 0,
                            },
                        ]"
                        :key="stat.label"
                        class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur"
                    >
                        <dd class="text-lg font-black">{{ stat.value }}</dd>
                        <dt
                            class="text-[10px] font-semibold tracking-wider text-white/60 uppercase"
                        >
                            {{ stat.label }}
                        </dt>
                    </div>
                </dl>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
            <!-- Edit -->
            <section class="lms-panel">
                <h2 class="lms-title text-lg">Member details</h2>
                <p v-if="!can.update" class="lms-subtitle">
                    You have read-only access to this record.
                </p>

                <form class="mt-5" @submit.prevent="submit">
                    <fieldset
                        :disabled="!can.update || form.processing"
                        class="space-y-4"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="first_name" class="lms-label"
                                    >First name</label
                                >
                                <input
                                    id="first_name"
                                    v-model="form.first_name"
                                    type="text"
                                    class="lms-input mt-1.5"
                                />
                                <InputError :message="form.errors.first_name" />
                            </div>
                            <div>
                                <label for="last_name" class="lms-label"
                                    >Last name</label
                                >
                                <input
                                    id="last_name"
                                    v-model="form.last_name"
                                    type="text"
                                    class="lms-input mt-1.5"
                                />
                            </div>
                            <div>
                                <label for="email" class="lms-label">Email</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="lms-input mt-1.5"
                                />
                                <InputError :message="form.errors.email" />
                            </div>
                            <div>
                                <label for="phone" class="lms-label">Phone</label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="lms-input mt-1.5"
                                />
                            </div>
                            <div>
                                <label for="school" class="lms-label"
                                    >School</label
                                >
                                <input
                                    id="school"
                                    v-model="form.school"
                                    type="text"
                                    class="lms-input mt-1.5"
                                />
                            </div>
                            <div>
                                <label for="current_status" class="lms-label"
                                    >They are a</label
                                >
                                <select
                                    id="current_status"
                                    v-model="form.current_status"
                                    class="lms-input mt-1.5"
                                >
                                    <option
                                        v-for="option in options.currentStatuses"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label for="membership_status" class="lms-label"
                                    >Standing in TAC</label
                                >
                                <select
                                    id="membership_status"
                                    v-model="form.membership_status"
                                    class="lms-input mt-1.5"
                                >
                                    <option
                                        v-for="option in options.membershipStatuses"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Promote a member to mentor or lead as they
                                    grow into it.
                                </p>
                            </div>
                            <div>
                                <label for="lifecycle_status" class="lms-label"
                                    >Account status</label
                                >
                                <select
                                    id="lifecycle_status"
                                    v-model="form.lifecycle_status"
                                    class="lms-input mt-1.5"
                                >
                                    <option
                                        v-for="option in options.lifecycleStatuses"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="bio" class="lms-label">Bio</label>
                            <textarea
                                id="bio"
                                v-model="form.bio"
                                rows="3"
                                class="lms-input mt-1.5 resize-y"
                            />
                        </div>

                        <fieldset>
                            <legend class="lms-label">Tracks</legend>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <label
                                    v-for="track in tracks"
                                    :key="track.id"
                                    :class="[
                                        'cursor-pointer rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
                                        form.track_ids.includes(track.id)
                                            ? 'border-[#42b6c5] bg-[#42b6c5]/10 text-[#26808c]'
                                            : 'border-gray-200 text-gray-600 dark:border-white/10 dark:text-gray-300',
                                    ]"
                                >
                                    <input
                                        v-model="form.track_ids"
                                        type="checkbox"
                                        :value="track.id"
                                        class="sr-only"
                                    />
                                    {{ track.name }}
                                </label>
                            </div>
                        </fieldset>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label
                                class="flex items-start gap-2.5 rounded-xl border border-gray-200 p-3 text-sm dark:border-white/10"
                            >
                                <input
                                    v-model="form.directory_opt_in"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                />
                                <span class="text-gray-600 dark:text-gray-400"
                                    >Listed in the member directory</span
                                >
                            </label>
                            <label
                                class="flex items-start gap-2.5 rounded-xl border border-gray-200 p-3 text-sm dark:border-white/10"
                            >
                                <input
                                    v-model="form.email_opt_in"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                />
                                <span class="text-gray-600 dark:text-gray-400"
                                    >Receives community email</span
                                >
                            </label>
                        </div>

                        <div>
                            <label for="admin_notes" class="lms-label"
                                >Internal notes</label
                            >
                            <textarea
                                id="admin_notes"
                                v-model="form.admin_notes"
                                rows="3"
                                placeholder="Only staff see this."
                                class="lms-input mt-1.5 resize-y"
                            />
                        </div>

                        <div
                            v-if="can.update"
                            class="flex flex-wrap items-center gap-3 pt-2"
                        >
                            <button type="submit" class="lms-btn-primary">
                                {{ form.processing ? 'Saving…' : 'Save changes' }}
                            </button>
                            <span
                                v-if="form.recentlySuccessful"
                                class="text-sm font-semibold text-emerald-600"
                                >Saved</span
                            >
                            <button
                                v-if="can.delete"
                                type="button"
                                class="ml-auto text-sm font-semibold text-red-600 hover:underline"
                                @click="showDelete = true"
                            >
                                Remove from community
                            </button>
                        </div>
                    </fieldset>
                </form>
            </section>

            <!-- Activity -->
            <div class="space-y-6">
                <section class="lms-panel">
                    <h2 class="lms-title text-lg">Joined via</h2>
                    <dl class="mt-4 space-y-2.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Source</dt>
                            <dd
                                class="text-right font-semibold text-[#000928] dark:text-white"
                            >
                                {{ sourceLabel }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Joined</dt>
                            <dd
                                class="text-right font-semibold text-[#000928] dark:text-white"
                            >
                                {{ formatDate(member.joined_at) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Welcome email</dt>
                            <dd
                                class="text-right font-semibold text-[#000928] dark:text-white"
                            >
                                {{
                                    member.welcomed_at
                                        ? formatDate(member.welcomed_at)
                                        : 'Not sent'
                                }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Last active</dt>
                            <dd
                                class="text-right font-semibold text-[#000928] dark:text-white"
                            >
                                {{
                                    member.last_engaged_at
                                        ? formatDate(member.last_engaged_at)
                                        : '—'
                                }}
                            </dd>
                        </div>
                        <div
                            v-if="member.heard_about"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-gray-500">Heard about TAC via</dt>
                            <dd
                                class="text-right font-semibold text-[#000928] dark:text-white"
                            >
                                {{ member.heard_about }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="lms-panel">
                    <h2 class="lms-title text-lg">Activity history</h2>

                    <ul
                        v-if="member.rsvps?.length"
                        class="mt-4 divide-y divide-gray-100 dark:divide-white/5"
                    >
                        <li
                            v-for="rsvp in member.rsvps"
                            :key="rsvp.id"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="`/admin/community/activities/${rsvp.activity?.slug}`"
                                    class="block truncate text-sm font-semibold text-[#000928] hover:underline dark:text-white"
                                >
                                    {{ rsvp.activity?.title }}
                                </Link>
                                <p class="text-xs text-gray-500">
                                    {{
                                        rsvp.activity
                                            ? dateRange(rsvp.activity)
                                            : ''
                                    }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold',
                                    rsvpStatus(rsvp.status).classes,
                                ]"
                            >
                                {{ rsvpStatus(rsvp.status).label }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="lms-subtitle mt-4">
                        This member has not registered for anything yet.
                    </p>
                </section>

                <section
                    v-if="member.competition_entries?.length"
                    class="lms-panel"
                >
                    <h2 class="lms-title text-lg">Competition entries</h2>
                    <ul
                        class="mt-4 divide-y divide-gray-100 dark:divide-white/5"
                    >
                        <li
                            v-for="entry in member.competition_entries"
                            :key="entry.id"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-[#000928] dark:text-white"
                                >
                                    {{ entry.title }}
                                </p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ entry.activity?.title }}
                                </p>
                            </div>
                            <span class="shrink-0 text-right">
                                <span
                                    v-if="entry.rank"
                                    class="block text-sm font-black text-[#000928] dark:text-white"
                                    >#{{ entry.rank }}</span
                                >
                                <span
                                    class="text-[10px] text-gray-500 capitalize"
                                    >{{ entry.status.replace('_', ' ') }}</span
                                >
                            </span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <ConfirmationModal
            :open="showDelete"
            title="Remove this member?"
            :description="`This permanently deletes ${member.full_name}'s community record and their RSVP history. It cannot be undone.`"
            confirm-text="Remove member"
            @update:open="showDelete = $event"
            @confirm="destroy"
        />
    </div>
</template>
