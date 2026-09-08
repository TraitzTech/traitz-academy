<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    SelectOption,
    TacActivity,
    TacLeader,
    TacTrack,
} from '@/types/community';

interface Props {
    activity: TacActivity | null;
    tracks: TacTrack[];
    leaders: TacLeader[];
    programs: { id: number; title: string }[];
    types: SelectOption[];
    canPublish: boolean;
    defaultTimezone: string;
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { asset } = useCommunity();

/** datetime-local needs "YYYY-MM-DDTHH:MM", not an ISO string with a zone. */
const toLocal = (value: string | null | undefined): string => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const activity = props.activity;

const form = useForm({
    title: activity?.title ?? '',
    type: activity?.type ?? 'workshop',
    // A leader scoped to a single track (most track mentors) gets it
    // pre-selected — there is only one valid choice for them anyway.
    tac_track_id:
        activity?.tac_track_id ?? (props.tracks.length === 1 ? props.tracks[0].id : null),
    program_id: activity?.program_id ?? null,
    summary: activity?.summary ?? '',
    description: activity?.description ?? '',
    cover_image: null as File | null,

    location_type: activity?.location_type ?? 'physical',
    location: activity?.location ?? '',
    meeting_url: activity?.meeting_url ?? '',

    starts_at: toLocal(activity?.starts_at),
    ends_at: toLocal(activity?.ends_at),
    timezone: activity?.timezone ?? props.defaultTimezone,

    is_recurring: activity?.is_recurring ?? false,
    recurrence: {
        frequency: activity?.recurrence?.frequency ?? 'weekly',
        count: activity?.recurrence?.count ?? 4,
    },

    capacity: activity?.capacity ?? null,
    registration_required: activity?.registration_required ?? true,
    registration_opens_at: toLocal(activity?.registration_opens_at),
    registration_closes_at: toLocal(activity?.registration_closes_at),

    is_paid: activity?.is_paid ?? false,
    price: activity?.price ?? 0,
    currency: activity?.currency ?? 'XAF',

    organizer_leader_id: activity?.organizer_leader_id ?? null,
    status: activity?.status ?? 'draft',
    is_featured: activity?.is_featured ?? false,
    outcome_summary: activity?.outcome_summary ?? '',

    criteria: (activity?.competition_criteria ?? []).map((criterion) => ({
        id: criterion.id as number | null,
        label: criterion.label,
        description: criterion.description ?? '',
        max_score: criterion.max_score,
        weight: criterion.weight,
    })),
});

const isCompetition = computed(() => form.type === 'competition');
const isVirtual = computed(() => form.location_type === 'virtual');
const isInternship = computed(() => form.type === 'internship');

const addCriterion = () =>
    form.criteria.push({
        id: null as number | null,
        label: '',
        description: '',
        max_score: 10,
        weight: 1,
    });

const removeCriterion = (index: number) => form.criteria.splice(index, 1);

const submit = () => {
    const url = activity
        ? `/admin/community/activities/${activity.slug}`
        : '/admin/community/activities';

    form.post(url, { forceFormData: true, preserveScroll: true });
};

const statusOptions = computed(() => {
    const options = [{ value: 'draft', label: 'Draft — not visible publicly' }];
    if (props.canPublish) {
        options.push(
            { value: 'published', label: 'Published — live on the site' },
            { value: 'completed', label: 'Completed — moved to the archive' },
            { value: 'cancelled', label: 'Cancelled' },
        );
    }
    return options;
});
</script>

<template>
    <div class="lms-page">
        <Head :title="activity ? `Edit ${activity.title}` : 'New activity'" />

        <nav class="text-sm" aria-label="Breadcrumb">
            <Link
                href="/admin/community/activities"
                class="font-semibold text-gray-500 hover:text-[#000928] dark:hover:text-white"
            >
                ← All activities
            </Link>
        </nav>

        <div class="lms-hero">
            <h1 class="text-2xl font-black tracking-tight">
                {{ activity ? 'Edit activity' : 'Create an activity' }}
            </h1>
            <p class="mt-1.5 text-sm text-white/70">
                {{
                    activity
                        ? 'Changes go live as soon as you save a published activity.'
                        : 'Save as a draft first, then publish when the details are settled.'
                }}
            </p>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <fieldset :disabled="form.processing" class="space-y-6">
                <!-- Basics -->
                <section class="lms-panel">
                    <h2 class="lms-title text-lg">The basics</h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="title" class="lms-label">Title *</label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                required
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div>
                            <label for="type" class="lms-label">Type *</label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="lms-input mt-1.5"
                            >
                                <option
                                    v-for="option in types"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div>
                            <label for="tac_track_id" class="lms-label"
                                >Track</label
                            >
                            <select
                                id="tac_track_id"
                                v-model="form.tac_track_id"
                                class="lms-input mt-1.5"
                            >
                                <option :value="null">
                                    Not track-specific
                                </option>
                                <option
                                    v-for="track in tracks"
                                    :key="track.id"
                                    :value="track.id"
                                >
                                    {{ track.name }}
                                </option>
                            </select>
                        </div>

                        <div v-if="isInternship" class="sm:col-span-2">
                            <label for="program_id" class="lms-label"
                                >Linked internship program</label
                            >
                            <select
                                id="program_id"
                                v-model="form.program_id"
                                class="lms-input mt-1.5"
                            >
                                <option :value="null">Not linked</option>
                                <option
                                    v-for="program in programs"
                                    :key="program.id"
                                    :value="program.id"
                                >
                                    {{ program.title }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                Links this back to the main Traitz Academy
                                internship program.
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="summary" class="lms-label"
                                >Short summary</label
                            >
                            <input
                                id="summary"
                                v-model="form.summary"
                                type="text"
                                maxlength="500"
                                placeholder="One line shown on activity cards"
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.summary" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="lms-label">Full description</label>
                            <RichTextEditor
                                v-model="form.description"
                                class="mt-1.5"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="sm:col-span-2">
                            <label for="cover_image" class="lms-label"
                                >Cover image</label
                            >
                            <div class="mt-1.5 flex items-center gap-4">
                                <img
                                    v-if="asset(activity?.cover_image)"
                                    :src="asset(activity!.cover_image)!"
                                    alt=""
                                    class="h-16 w-28 rounded-lg object-cover"
                                />
                                <input
                                    id="cover_image"
                                    type="file"
                                    accept="image/*"
                                    class="lms-input"
                                    @input="
                                        form.cover_image = (
                                            $event.target as HTMLInputElement
                                        ).files?.[0] ?? null
                                    "
                                />
                            </div>
                            <InputError :message="form.errors.cover_image" />
                        </div>
                    </div>
                </section>

                <!-- When & where -->
                <section class="lms-panel">
                    <h2 class="lms-title text-lg">When & where</h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="starts_at" class="lms-label"
                                >Starts</label
                            >
                            <input
                                id="starts_at"
                                v-model="form.starts_at"
                                type="datetime-local"
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.starts_at" />
                        </div>
                        <div>
                            <label for="ends_at" class="lms-label">Ends</label>
                            <input
                                id="ends_at"
                                v-model="form.ends_at"
                                type="datetime-local"
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.ends_at" />
                        </div>

                        <div>
                            <label for="location_type" class="lms-label"
                                >Format *</label
                            >
                            <select
                                id="location_type"
                                v-model="form.location_type"
                                class="lms-input mt-1.5"
                            >
                                <option value="physical">In person</option>
                                <option value="virtual">Online</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>

                        <div>
                            <label for="location" class="lms-label">
                                Location
                                <span v-if="!isVirtual" class="text-red-500"
                                    >*</span
                                >
                            </label>
                            <input
                                id="location"
                                v-model="form.location"
                                type="text"
                                placeholder="Traitz Academy, Buea"
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.location" />
                        </div>

                        <div
                            v-if="form.location_type !== 'physical'"
                            class="sm:col-span-2"
                        >
                            <label for="meeting_url" class="lms-label">
                                Join link
                                <span v-if="isVirtual" class="text-red-500"
                                    >*</span
                                >
                            </label>
                            <input
                                id="meeting_url"
                                v-model="form.meeting_url"
                                type="url"
                                placeholder="https://meet.google.com/…"
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.meeting_url" />
                        </div>
                    </div>

                    <!-- Recurrence -->
                    <div
                        class="mt-5 rounded-xl border border-gray-100 p-4 dark:border-white/10"
                    >
                        <label class="flex items-center gap-2.5 text-sm">
                            <input
                                v-model="form.is_recurring"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span
                                class="font-semibold text-[#000928] dark:text-white"
                                >This is a recurring series</span
                            >
                        </label>

                        <div
                            v-if="form.is_recurring"
                            class="mt-4 grid gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <label for="frequency" class="lms-label"
                                    >Repeats</label
                                >
                                <select
                                    id="frequency"
                                    v-model="form.recurrence.frequency"
                                    class="lms-input mt-1.5"
                                >
                                    <option value="weekly">Weekly</option>
                                    <option value="biweekly">
                                        Every two weeks
                                    </option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            <div>
                                <label for="count" class="lms-label"
                                    >Total occurrences</label
                                >
                                <input
                                    id="count"
                                    v-model.number="form.recurrence.count"
                                    type="number"
                                    min="2"
                                    max="52"
                                    class="lms-input mt-1.5"
                                />
                            </div>
                            <p
                                class="text-xs text-gray-500 sm:col-span-2"
                            >
                                Occurrences are created as their own activities
                                on save, so each one can take its own RSVPs and
                                write-up. Existing occurrences are never
                                duplicated.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Registration -->
                <section class="lms-panel">
                    <h2 class="lms-title text-lg">Registration & pricing</h2>

                    <label class="mt-5 flex items-center gap-2.5 text-sm">
                        <input
                            v-model="form.registration_required"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                        />
                        <span
                            class="font-semibold text-[#000928] dark:text-white"
                            >People need to register</span
                        >
                    </label>

                    <div
                        v-if="form.registration_required"
                        class="mt-4 grid gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <label for="capacity" class="lms-label"
                                >Capacity</label
                            >
                            <input
                                id="capacity"
                                v-model.number="form.capacity"
                                type="number"
                                min="1"
                                placeholder="Leave blank for unlimited"
                                class="lms-input mt-1.5"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Once full, further RSVPs go to a waitlist and
                                are promoted automatically if someone cancels.
                            </p>
                        </div>
                        <div />
                        <div>
                            <label
                                for="registration_opens_at"
                                class="lms-label"
                                >Registration opens</label
                            >
                            <input
                                id="registration_opens_at"
                                v-model="form.registration_opens_at"
                                type="datetime-local"
                                class="lms-input mt-1.5"
                            />
                        </div>
                        <div>
                            <label
                                for="registration_closes_at"
                                class="lms-label"
                                >Registration closes</label
                            >
                            <input
                                id="registration_closes_at"
                                v-model="form.registration_closes_at"
                                type="datetime-local"
                                class="lms-input mt-1.5"
                            />
                            <InputError
                                :message="form.errors.registration_closes_at"
                            />
                        </div>
                    </div>

                    <div
                        class="mt-5 rounded-xl border border-gray-100 p-4 dark:border-white/10"
                    >
                        <label class="flex items-center gap-2.5 text-sm">
                            <input
                                v-model="form.is_paid"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span
                                class="font-semibold text-[#000928] dark:text-white"
                                >This is a paid activity</span
                            >
                        </label>

                        <div
                            v-if="form.is_paid"
                            class="mt-4 grid gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <label for="price" class="lms-label"
                                    >Price *</label
                                >
                                <input
                                    id="price"
                                    v-model.number="form.price"
                                    type="number"
                                    min="0"
                                    class="lms-input mt-1.5"
                                />
                                <InputError :message="form.errors.price" />
                            </div>
                            <div>
                                <label for="currency" class="lms-label"
                                    >Currency</label
                                >
                                <input
                                    id="currency"
                                    v-model="form.currency"
                                    type="text"
                                    maxlength="8"
                                    class="lms-input mt-1.5"
                                />
                            </div>
                            <p
                                class="text-xs text-gray-500 sm:col-span-2"
                            >
                                Paid via MTN Mobile Money and Orange Money
                                (MeSomb). A place is held as pending until
                                payment clears.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Competition rubric -->
                <section v-if="isCompetition" class="lms-panel">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="lms-title text-lg">Judging criteria</h2>
                            <p class="lms-subtitle">
                                Entries are scored against each criterion, then
                                weighted and averaged across judges.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="lms-btn-outline"
                            @click="addCriterion"
                        >
                            Add criterion
                        </button>
                    </div>

                    <div v-if="form.criteria.length" class="mt-5 space-y-3">
                        <div
                            v-for="(criterion, index) in form.criteria"
                            :key="index"
                            class="grid gap-3 rounded-xl border border-gray-100 p-4 sm:grid-cols-[2fr_1fr_1fr_auto] dark:border-white/10"
                        >
                            <div>
                                <label
                                    :for="`criterion_label_${index}`"
                                    class="lms-label"
                                    >Criterion</label
                                >
                                <input
                                    :id="`criterion_label_${index}`"
                                    v-model="criterion.label"
                                    type="text"
                                    placeholder="e.g. Technical execution"
                                    class="lms-input mt-1.5"
                                />
                            </div>
                            <div>
                                <label
                                    :for="`criterion_max_${index}`"
                                    class="lms-label"
                                    >Max score</label
                                >
                                <input
                                    :id="`criterion_max_${index}`"
                                    v-model.number="criterion.max_score"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="lms-input mt-1.5"
                                />
                            </div>
                            <div>
                                <label
                                    :for="`criterion_weight_${index}`"
                                    class="lms-label"
                                    >Weight</label
                                >
                                <input
                                    :id="`criterion_weight_${index}`"
                                    v-model.number="criterion.weight"
                                    type="number"
                                    min="1"
                                    max="10"
                                    class="lms-input mt-1.5"
                                />
                            </div>
                            <div class="flex items-end">
                                <button
                                    type="button"
                                    class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50 dark:border-white/10"
                                    @click="removeCriterion(index)"
                                >
                                    Remove
                                </button>
                            </div>
                            <div class="sm:col-span-4">
                                <input
                                    v-model="criterion.description"
                                    type="text"
                                    placeholder="What judges should look for (optional)"
                                    class="lms-input"
                                    :aria-label="`Description for criterion ${index + 1}`"
                                />
                            </div>
                        </div>
                    </div>
                    <p v-else class="lms-subtitle mt-5">
                        No criteria yet. Add at least one so judges have
                        something to score against.
                    </p>
                </section>

                <!-- Publishing -->
                <section class="lms-panel">
                    <h2 class="lms-title text-lg">Organiser & publishing</h2>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="organizer_leader_id" class="lms-label"
                                >Organising lead / mentor</label
                            >
                            <select
                                id="organizer_leader_id"
                                v-model="form.organizer_leader_id"
                                class="lms-input mt-1.5"
                            >
                                <option :value="null">Not assigned</option>
                                <option
                                    v-for="leader in leaders"
                                    :key="leader.id"
                                    :value="leader.id"
                                >
                                    {{ leader.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="status" class="lms-label">Status</label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="lms-input mt-1.5"
                            >
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <p
                                v-if="!canPublish"
                                class="mt-1 text-xs text-amber-600"
                            >
                                Only TAC executives can publish. Save as a draft
                                and ask them to review it.
                            </p>
                        </div>

                        <div
                            v-if="
                                form.status === 'completed' ||
                                activity?.status === 'completed'
                            "
                            class="sm:col-span-2"
                        >
                            <label for="outcome_summary" class="lms-label"
                                >How it went (archive write-up)</label
                            >
                            <textarea
                                id="outcome_summary"
                                v-model="form.outcome_summary"
                                rows="4"
                                placeholder="Outcomes, winners, highlights — this is what people read in the archive years later."
                                class="lms-input mt-1.5 resize-y"
                            />
                        </div>

                        <label
                            v-if="canPublish"
                            class="flex items-center gap-2.5 text-sm sm:col-span-2"
                        >
                            <input
                                v-model="form.is_featured"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span class="text-gray-600 dark:text-gray-400"
                                >Feature this at the top of the public
                                calendar</span
                            >
                        </label>
                    </div>
                </section>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="lms-btn-primary">
                        {{
                            form.processing
                                ? 'Saving…'
                                : activity
                                  ? 'Save changes'
                                  : 'Create activity'
                        }}
                    </button>
                    <Link
                        href="/admin/community/activities"
                        class="lms-btn-outline"
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
</template>
