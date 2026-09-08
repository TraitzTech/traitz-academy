<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface TrackOption {
    id: number;
    name: string;
    member_count: number;
}

interface SchoolOption {
    name: string;
    member_count: number;
}

interface Props {
    myTracks: TrackOption[];
    mySchools: SchoolOption[];
    allTracks: TrackOption[];
    allSchools: SchoolOption[];
    isExecutive: boolean;
    totalMembers: number | null;
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

type Audience = 'my_track' | 'my_school' | 'track' | 'school' | 'all_members';

const form = useForm({
    audience: (props.myTracks.length
        ? 'my_track'
        : props.mySchools.length
          ? 'my_school'
          : 'all_members') as Audience,
    track_id: null as number | null,
    school: null as string | null,
    subject: '',
    message: '',
    action_text: '',
    action_url: '',
});

const audienceOptions = computed(() => {
    const options: { value: Audience; label: string; hint: string }[] = [];

    if (props.myTracks.length) {
        options.push({
            value: 'my_track',
            label: 'My track(s)',
            hint: props.myTracks.map((t) => t.name).join(', '),
        });
    }
    if (props.mySchools.length) {
        options.push({
            value: 'my_school',
            label: 'My school',
            hint: props.mySchools.map((s) => s.name).join(', '),
        });
    }
    if (props.isExecutive) {
        options.push(
            { value: 'track', label: 'A specific track', hint: 'Any TAC track' },
            { value: 'school', label: 'A specific school', hint: 'Any campus' },
            { value: 'all_members', label: 'Every member', hint: 'The entire community' },
        );
    }

    return options;
});

/** A rough estimate — a member in more than one selected track is only
 * counted once when they actually receive the email, but shown as "up to"
 * here since we don't dedupe client-side. */
const estimatedRecipients = computed(() => {
    switch (form.audience) {
        case 'my_track':
            return props.myTracks.reduce((sum, t) => sum + t.member_count, 0);
        case 'my_school':
            return props.mySchools.reduce((sum, s) => sum + s.member_count, 0);
        case 'track':
            return props.allTracks.find((t) => t.id === form.track_id)?.member_count ?? null;
        case 'school':
            return props.allSchools.find((s) => s.name === form.school)?.member_count ?? null;
        case 'all_members':
            return props.totalMembers;
        default:
            return null;
    }
});

const submit = () =>
    form.post('/admin/community/announcements', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('subject', 'message', 'action_text', 'action_url');
        },
    });
</script>

<template>
    <div class="lms-page">
        <Head title="Announcements — Community Admin" />

        <div class="lms-hero">
            <h1 class="text-2xl font-black tracking-tight">Announcements</h1>
            <p class="mt-1.5 text-sm text-white/70">
                Email the people you actually lead — your track, your school,
                or (if you're a TAC executive) anyone in the community.
            </p>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <fieldset :disabled="form.processing" class="space-y-6">
                <!-- Audience -->
                <section class="lms-panel">
                    <h2 class="lms-title text-lg">Who gets this</h2>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <button
                            v-for="option in audienceOptions"
                            :key="option.value"
                            type="button"
                            :class="[
                                'rounded-xl border p-4 text-left transition-all',
                                form.audience === option.value
                                    ? 'border-[#42b6c5] bg-[#42b6c5]/8 ring-1 ring-[#42b6c5]'
                                    : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600',
                            ]"
                            @click="form.audience = option.value"
                        >
                            <span class="block font-bold text-[#000928] dark:text-white">
                                {{ option.label }}
                            </span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                                {{ option.hint }}
                            </span>
                        </button>
                    </div>

                    <div
                        v-if="form.audience === 'track'"
                        class="mt-4"
                    >
                        <label for="track_id" class="lms-label">Track</label>
                        <select
                            id="track_id"
                            v-model.number="form.track_id"
                            class="lms-input mt-1.5"
                        >
                            <option :value="null">Choose a track…</option>
                            <option
                                v-for="track in allTracks"
                                :key="track.id"
                                :value="track.id"
                            >
                                {{ track.name }} ({{ track.member_count }} members)
                            </option>
                        </select>
                        <InputError :message="form.errors.track_id" />
                    </div>

                    <div
                        v-if="form.audience === 'school'"
                        class="mt-4"
                    >
                        <label for="school" class="lms-label">School</label>
                        <select
                            id="school"
                            v-model="form.school"
                            class="lms-input mt-1.5"
                        >
                            <option :value="null">Choose a school…</option>
                            <option
                                v-for="school in allSchools"
                                :key="school.name"
                                :value="school.name"
                            >
                                {{ school.name }} ({{ school.member_count }} members)
                            </option>
                        </select>
                        <InputError :message="form.errors.school" />
                    </div>

                    <p
                        v-if="estimatedRecipients !== null"
                        class="mt-4 text-sm text-gray-500 dark:text-gray-400"
                    >
                        Estimated reach: up to
                        <strong class="text-[#000928] dark:text-white">{{
                            estimatedRecipients
                        }}</strong>
                        member(s) who receive community email.
                    </p>
                </section>

                <!-- Message -->
                <section class="lms-panel">
                    <h2 class="lms-title text-lg">Your message</h2>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="subject" class="lms-label"
                                >Subject *</label
                            >
                            <input
                                id="subject"
                                v-model="form.subject"
                                type="text"
                                required
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.subject" />
                        </div>

                        <div>
                            <label class="lms-label">Message *</label>
                            <RichTextEditor
                                v-model="form.message"
                                class="mt-1.5"
                                placeholder="Write your announcement…"
                                upload-url="/admin/community/announcements/media"
                            />
                            <InputError :message="form.errors.message" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="action_text" class="lms-label"
                                    >Button text (optional)</label
                                >
                                <input
                                    id="action_text"
                                    v-model="form.action_text"
                                    type="text"
                                    placeholder="e.g. View the activity"
                                    class="lms-input mt-1.5"
                                />
                                <InputError :message="form.errors.action_text" />
                            </div>
                            <div>
                                <label for="action_url" class="lms-label"
                                    >Button link</label
                                >
                                <input
                                    id="action_url"
                                    v-model="form.action_url"
                                    type="url"
                                    placeholder="https://…"
                                    class="lms-input mt-1.5"
                                />
                                <InputError :message="form.errors.action_url" />
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex items-center gap-3">
                    <button type="submit" class="lms-btn-primary">
                        {{ form.processing ? 'Sending…' : 'Send announcement' }}
                    </button>
                    <span
                        v-if="form.recentlySuccessful"
                        class="text-sm font-semibold text-emerald-600"
                        >Sent</span
                    >
                </div>
            </fieldset>
        </form>
    </div>
</template>
