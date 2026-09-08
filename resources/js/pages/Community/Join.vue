<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import SeoHead from '@/components/SeoHead.vue';
import CommunityShell from '@/components/community/CommunityShell.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';
import type { SelectOption, TacTrack } from '@/types/community';

interface Props {
    tracks: TacTrack[];
    statuses: SelectOption[];
    memberCount: number;
    existingMember: {
        id: number;
        first_name: string;
        email: string;
        joined_at: string | null;
    } | null;
    prefill: { email: string; name: string; phone: string | null } | null;
}

const props = defineProps<Props>();
const { initials, formatDate } = useCommunity();

const [firstName, ...rest] = (props.prefill?.name ?? '').split(' ');

const form = useForm({
    first_name: props.existingMember?.first_name ?? firstName ?? '',
    last_name: rest.join(' '),
    email: props.existingMember?.email ?? props.prefill?.email ?? '',
    phone: props.prefill?.phone ?? '',
    school: '',
    current_status: 'student',
    heard_about: '',
    bio: '',
    track_ids: [] as number[],
    directory_opt_in: true,
});

const toggleTrack = (id: number) => {
    const index = form.track_ids.indexOf(id);
    if (index > -1) form.track_ids.splice(index, 1);
    else form.track_ids.push(id);
};

const heardOptions = [
    'A friend or classmate',
    'Traitz Academy internship',
    'An event or workshop',
    'Social media',
    'My school',
    'Search engine',
    'Other',
];

const submit = () => form.post('/community/join', { preserveScroll: true });

const canSubmit = computed(
    () => !form.processing && form.track_ids.length > 0,
);
</script>

<template>
    <CommunityShell active="">
        <SeoHead
            title="Join the Community"
            description="Join TAC — free, open to students, past interns and tech enthusiasts. Pick your tracks and get access to workshops, mentors and competitions all year."
        />

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="grid gap-10 lg:grid-cols-[1fr_1.15fr] lg:gap-14">
                <!-- ============================================ Pitch -->
                <div class="lg:sticky lg:top-28 lg:self-start">
                    <span
                        class="inline-flex items-center rounded-full bg-[#42b6c5]/12 px-3 py-1 text-xs font-bold tracking-wide text-[#26808c] uppercase"
                    >
                        Always open — no deadline
                    </span>

                    <h1
                        class="mt-5 text-3xl font-black tracking-tight text-[#000928] sm:text-4xl"
                    >
                        Become part of TAC
                    </h1>

                    <p
                        class="mt-4 text-base leading-relaxed text-gray-600"
                    >
                        Join
                        <strong class="text-[#000928]">{{
                            memberCount
                        }}</strong>
                        students, interns and tech enthusiasts who stay
                        connected to Traitz Academy all year round. Membership
                        is free and it does not expire.
                    </p>

                    <div class="mt-6 overflow-hidden rounded-2xl">
                        <img
                            src="/images/academy-community/community/game-night-bonding.jpg"
                            alt="TAC members hanging out at a community game night"
                            class="aspect-[16/9] w-full object-cover"
                            loading="lazy"
                        />
                    </div>

                    <ul class="mt-8 space-y-4">
                        <li
                            v-for="benefit in [
                                'Workshops, trainings, bootcamps and competitions across eight tracks',
                                'Mentors who actually work in the track you choose',
                                'First word on internships, programs and openings',
                                'A path to grow into a mentor or lead yourself',
                            ]"
                            :key="benefit"
                            class="flex gap-3"
                        >
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#42b6c5] text-white"
                                aria-hidden="true"
                            >
                                <svg
                                    class="h-3 w-3"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </span>
                            <span
                                class="text-sm text-gray-700"
                                >{{ benefit }}</span
                            >
                        </li>
                    </ul>

                    <!-- Somebody auto-included from an event lands here already a member -->
                    <div
                        v-if="existingMember"
                        class="mt-8 rounded-2xl border border-[#42b6c5]/30 bg-[#42b6c5]/8 p-5"
                    >
                        <h2
                            class="text-sm font-bold text-[#000928]"
                        >
                            You're already a member
                        </h2>
                        <p
                            class="mt-1.5 text-sm text-gray-600"
                        >
                            {{ existingMember.first_name }}, you joined TAC on
                            {{ formatDate(existingMember.joined_at) }} — most
                            likely when you registered for a Traitz Academy
                            program or event. Use the form to update your
                            details and tracks.
                        </p>
                        <Link
                            href="/community/me"
                            class="mt-3 inline-block text-sm font-bold text-[#381998] hover:underline"
                        >
                            Go to my member area →
                        </Link>
                    </div>
                </div>

                <!-- ============================================ Form -->
                <form
                    class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8"
                    @submit.prevent="submit"
                >
                    <fieldset :disabled="form.processing" class="space-y-7">
                        <!-- About you -->
                        <div>
                            <legend
                                class="text-sm font-bold tracking-wide text-[#000928] uppercase"
                            >
                                About you
                            </legend>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
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
                                        autocomplete="given-name"
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
                                        autocomplete="family-name"
                                        class="lms-input mt-1.5"
                                    />
                                    <InputError :message="form.errors.last_name" />
                                </div>

                                <div>
                                    <label for="email" class="lms-label"
                                        >Email
                                        <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        autocomplete="email"
                                        class="lms-input mt-1.5"
                                    />
                                    <InputError :message="form.errors.email" />
                                </div>

                                <div>
                                    <label for="phone" class="lms-label"
                                        >Phone
                                        <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        autocomplete="tel"
                                        placeholder="+237 6XX XXX XXX"
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
                                        placeholder="University of Buea"
                                        class="lms-input mt-1.5"
                                    />
                                    <InputError :message="form.errors.school" />
                                </div>

                                <div>
                                    <label for="current_status" class="lms-label"
                                        >I am a
                                        <span class="text-red-500">*</span></label
                                    >
                                    <select
                                        id="current_status"
                                        v-model="form.current_status"
                                        required
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
                                    <InputError
                                        :message="form.errors.current_status"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Tracks -->
                        <div>
                            <legend
                                class="text-sm font-bold tracking-wide text-[#000928] uppercase"
                            >
                                Your tracks
                                <span class="text-red-500">*</span>
                            </legend>
                            <p
                                class="mt-1 text-sm text-gray-500"
                            >
                                Pick everything that interests you — we'll point
                                the right activities your way. You can change
                                this any time.
                            </p>

                            <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
                                <button
                                    v-for="track in tracks"
                                    :key="track.id"
                                    type="button"
                                    role="checkbox"
                                    :aria-checked="
                                        form.track_ids.includes(track.id)
                                    "
                                    :class="[
                                        'flex items-start gap-3 rounded-xl border p-3.5 text-left transition-all',
                                        form.track_ids.includes(track.id)
                                            ? 'border-[#42b6c5] bg-[#42b6c5]/8 ring-1 ring-[#42b6c5]'
                                            : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50',
                                    ]"
                                    @click="toggleTrack(track.id)"
                                >
                                    <span
                                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[11px] font-black"
                                        :style="{
                                            backgroundColor: `${track.accent_color ?? '#42b6c5'}22`,
                                            color: track.accent_color ?? '#42b6c5',
                                        }"
                                        aria-hidden="true"
                                    >
                                        {{ initials(track.name) }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block text-sm font-bold text-[#000928]"
                                        >
                                            {{ track.name }}
                                        </span>
                                        <span
                                            v-if="track.tagline"
                                            class="mt-0.5 block text-xs text-gray-500"
                                        >
                                            {{ track.tagline }}
                                        </span>
                                    </span>
                                    <span
                                        v-if="form.track_ids.includes(track.id)"
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#42b6c5] text-white"
                                        aria-hidden="true"
                                    >
                                        <svg
                                            class="h-3 w-3"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </span>
                                </button>
                            </div>
                            <InputError
                                class="mt-2"
                                :message="form.errors.track_ids"
                            />
                        </div>

                        <!-- Optional -->
                        <div>
                            <legend
                                class="text-sm font-bold tracking-wide text-[#000928] uppercase"
                            >
                                A little more (optional)
                            </legend>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="heard_about" class="lms-label"
                                        >How did you hear about TAC?</label
                                    >
                                    <select
                                        id="heard_about"
                                        v-model="form.heard_about"
                                        class="lms-input mt-1.5"
                                    >
                                        <option value="">Prefer not to say</option>
                                        <option
                                            v-for="option in heardOptions"
                                            :key="option"
                                            :value="option"
                                        >
                                            {{ option }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label for="bio" class="lms-label"
                                        >Tell us about yourself</label
                                    >
                                    <textarea
                                        id="bio"
                                        v-model="form.bio"
                                        rows="3"
                                        maxlength="1000"
                                        placeholder="What are you building, studying or hoping to learn?"
                                        class="lms-input mt-1.5 resize-y"
                                    />
                                    <InputError :message="form.errors.bio" />
                                </div>
                            </div>
                        </div>

                        <!-- Privacy -->
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-50/60 p-4"
                        >
                            <input
                                v-model="form.directory_opt_in"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span class="text-sm">
                                <span
                                    class="block font-semibold text-[#000928]"
                                >
                                    List me in the member directory
                                </span>
                                <span
                                    class="mt-0.5 block text-gray-500"
                                >
                                    Other signed-in members can find you by
                                    track and school. Your email and phone are
                                    never shown. You can turn this off any time.
                                </span>
                            </span>
                        </label>

                        <div>
                            <button
                                type="submit"
                                :disabled="!canSubmit"
                                class="w-full rounded-xl bg-[#000928] px-6 py-3.5 text-base font-bold text-white transition-all hover:bg-[#381998] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{
                                    form.processing
                                        ? 'Joining…'
                                        : existingMember
                                          ? 'Update my membership'
                                          : 'Join the community'
                                }}
                            </button>
                            <p
                                v-if="form.track_ids.length === 0"
                                class="mt-2 text-center text-xs text-gray-500"
                            >
                                Choose at least one track to continue.
                            </p>
                            <p
                                class="mt-3 text-center text-xs text-gray-500"
                            >
                                We'll email you a confirmation. No spam — you
                                can unsubscribe from your member area.
                            </p>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </CommunityShell>
</template>
