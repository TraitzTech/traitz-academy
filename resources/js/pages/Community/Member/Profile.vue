<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import CommunityShell from '@/components/community/CommunityShell.vue';
import SocialLinksEditor from '@/components/community/SocialLinksEditor.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';
import type {
    CommunityMember,
    SelectOption,
    TacTrack,
} from '@/types/community';

interface Props {
    member: CommunityMember;
    selectedTrackIds: number[];
    tracks: TacTrack[];
    statuses: SelectOption[];
}

const props = defineProps<Props>();
const { asset, initials } = useCommunity();

const form = useForm({
    first_name: props.member.first_name,
    last_name: props.member.last_name ?? '',
    phone: props.member.phone ?? '',
    school: props.member.school ?? '',
    bio: props.member.bio ?? '',
    current_status: props.member.current_status,
    social_links: Object.entries(props.member.social_links ?? {})
        .filter(([, value]) => value)
        .map(([key, value]) => ({ key, value: value as string })) as {
        key: string;
        value: string;
    }[],
    directory_opt_in: props.member.directory_opt_in,
    email_opt_in: props.member.email_opt_in,
    track_ids: [...props.selectedTrackIds],
    avatar: null as File | null,
});

const toggleTrack = (id: number) => {
    const index = form.track_ids.indexOf(id);
    if (index > -1) form.track_ids.splice(index, 1);
    else form.track_ids.push(id);
};

const submit = () =>
    form
        .transform((data) => ({
            ...data,
            social_links: Object.fromEntries(
                data.social_links
                    .filter((row) => row.key.trim() && row.value.trim())
                    .map((row) => [row.key.trim(), row.value.trim()]),
            ),
        }))
        .post('/community/me/profile', {
            preserveScroll: true,
            forceFormData: true,
        });
</script>

<template>
    <CommunityShell active="member">
        <Head title="My profile — TAC" />

        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <nav class="mb-6 text-sm" aria-label="Breadcrumb">
                <Link
                    href="/community/me"
                    class="font-semibold text-gray-500 transition-colors hover:text-[#000928]"
                >
                    ← Back to my member area
                </Link>
            </nav>

            <h1
                class="text-3xl font-black tracking-tight text-[#000928]"
            >
                My profile
            </h1>
            <p class="mt-2 text-gray-600">
                Keep this current so we point the right opportunities your way.
            </p>

            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <fieldset :disabled="form.processing" class="space-y-6">
                    <!-- Identity -->
                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <h2
                            class="text-sm font-bold tracking-wide text-[#000928] uppercase"
                        >
                            About you
                        </h2>

                        <div class="mt-5 flex items-center gap-5">
                            <span
                                class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl"
                            >
                                <img
                                    v-if="asset(member.avatar_path)"
                                    :src="asset(member.avatar_path)!"
                                    :alt="member.full_name"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#000928] to-[#381998] text-xl font-black text-white"
                                    aria-hidden="true"
                                    >{{ initials(member.full_name) }}</span
                                >
                            </span>
                            <div class="flex-1">
                                <label for="avatar" class="lms-label"
                                    >Profile photo</label
                                >
                                <input
                                    id="avatar"
                                    type="file"
                                    accept="image/*"
                                    class="lms-input mt-1.5"
                                    @input="
                                        form.avatar = (
                                            $event.target as HTMLInputElement
                                        ).files?.[0] ?? null
                                    "
                                />
                                <InputError :message="form.errors.avatar" />
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="first_name" class="lms-label"
                                    >First name
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    id="first_name"
                                    v-model="form.first_name"
                                    type="text"
                                    required
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
                                <label class="lms-label">Email</label>
                                <input
                                    :value="member.email"
                                    type="email"
                                    disabled
                                    class="lms-input mt-1.5 cursor-not-allowed opacity-60"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Your email is tied to your account and
                                    cannot be changed here.
                                </p>
                            </div>
                            <div>
                                <label for="phone" class="lms-label"
                                    >Phone</label
                                >
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="lms-input mt-1.5"
                                />
                                <InputError :message="form.errors.phone" />
                            </div>
                            <div>
                                <label for="school" class="lms-label"
                                    >School / institution</label
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
                                    >I am a</label
                                >
                                <select
                                    id="current_status"
                                    v-model="form.current_status"
                                    class="lms-input mt-1.5"
                                >
                                    <option
                                        v-for="status in statuses"
                                        :key="status.value"
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="bio" class="lms-label">Bio</label>
                            <textarea
                                id="bio"
                                v-model="form.bio"
                                rows="3"
                                maxlength="1000"
                                class="lms-input mt-1.5 resize-y"
                            />
                            <InputError :message="form.errors.bio" />
                        </div>
                    </section>

                    <!-- Tracks -->
                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <h2
                            class="text-sm font-bold tracking-wide text-[#000928] uppercase"
                        >
                            My tracks
                        </h2>
                        <p
                            class="mt-1 text-sm text-gray-500"
                        >
                            Keep at least one selected.
                        </p>

                        <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
                            <button
                                v-for="track in tracks"
                                :key="track.id"
                                type="button"
                                role="checkbox"
                                :aria-checked="form.track_ids.includes(track.id)"
                                :class="[
                                    'flex items-center gap-3 rounded-xl border p-3 text-left transition-all',
                                    form.track_ids.includes(track.id)
                                        ? 'border-[#42b6c5] bg-[#42b6c5]/8 ring-1 ring-[#42b6c5]'
                                        : 'border-gray-200 hover:bg-gray-50',
                                ]"
                                @click="toggleTrack(track.id)"
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[11px] font-black"
                                    :style="{
                                        backgroundColor: `${track.accent_color ?? '#42b6c5'}22`,
                                        color: track.accent_color ?? '#42b6c5',
                                    }"
                                    aria-hidden="true"
                                >
                                    {{ initials(track.name) }}
                                </span>
                                <span
                                    class="text-sm font-semibold text-[#000928]"
                                    >{{ track.name }}</span
                                >
                            </button>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.track_ids"
                        />
                    </section>

                    <!-- Links -->
                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <h2
                            class="text-sm font-bold tracking-wide text-[#000928] uppercase"
                        >
                            Links
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            LinkedIn, X, Instagram, GitHub — whatever you
                            actually use.
                        </p>
                        <SocialLinksEditor
                            v-model="form.social_links"
                            id-prefix="profile_social"
                            class="mt-4"
                        />
                    </section>

                    <!-- Privacy -->
                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <h2
                            class="text-sm font-bold tracking-wide text-[#000928] uppercase"
                        >
                            Privacy & email
                        </h2>

                        <div class="mt-4 space-y-3">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4"
                            >
                                <input
                                    v-model="form.directory_opt_in"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                />
                                <span class="text-sm">
                                    <span
                                        class="block font-semibold text-[#000928]"
                                        >List me in the member directory</span
                                    >
                                    <span
                                        class="mt-0.5 block text-gray-500"
                                        >Signed-in members can find you by track
                                        and school. Your email and phone are
                                        never shown.</span
                                    >
                                </span>
                            </label>

                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4"
                            >
                                <input
                                    v-model="form.email_opt_in"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                                />
                                <span class="text-sm">
                                    <span
                                        class="block font-semibold text-[#000928]"
                                        >Email me about community
                                        activities</span
                                    >
                                    <span
                                        class="mt-0.5 block text-gray-500"
                                        >Announcements, reminders and
                                        opportunities in your tracks. Turning
                                        this off stops all community email.</span
                                    >
                                </span>
                            </label>
                        </div>
                    </section>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            class="lms-btn-primary"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Saving…' : 'Save changes' }}
                        </button>
                        <Link href="/community/me" class="lms-btn-outline"
                            >Cancel</Link
                        >
                        <span
                            v-if="form.recentlySuccessful"
                            class="text-sm font-semibold text-emerald-600"
                            >Saved</span
                        >
                    </div>
                </fieldset>
            </form>
        </div>
    </CommunityShell>
</template>
