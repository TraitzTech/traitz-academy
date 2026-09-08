<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type { TacTrack } from '@/types/community';

interface Props {
    tracks: (TacTrack & {
        mentors_count?: number;
        upcoming_activities_count?: number;
    })[];
    can: { create: boolean; manageTrackIds: number[] };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { asset, initials } = useCommunity();

const showForm = ref(false);
const editing = ref<TacTrack | null>(null);
const confirmDelete = ref<TacTrack | null>(null);

const blank = {
    name: '',
    slug: '',
    tagline: '',
    description: '',
    accent_color: '#42b6c5',
    sort_order: 0,
    is_active: true,
    cover_image: null as File | null,
};

const form = useForm({ ...blank });

const canManage = (track: TacTrack) =>
    props.can.create || props.can.manageTrackIds.includes(track.id);

const openCreate = () => {
    editing.value = null;
    form.defaults({ ...blank });
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const openEdit = (track: TacTrack) => {
    editing.value = track;
    form.clearErrors();
    Object.assign(form, {
        name: track.name,
        slug: track.slug,
        tagline: track.tagline ?? '',
        description: track.description ?? '',
        accent_color: track.accent_color ?? '#42b6c5',
        sort_order: track.sort_order ?? 0,
        is_active: track.is_active ?? true,
        cover_image: null,
    });
    showForm.value = true;
};

const submit = () => {
    const url = editing.value
        ? `/admin/community/tracks/${editing.value.slug}`
        : '/admin/community/tracks';

    form.post(url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showForm.value = false;
            editing.value = null;
            form.reset();
        },
    });
};

const destroy = () => {
    if (!confirmDelete.value) return;
    router.delete(`/admin/community/tracks/${confirmDelete.value.slug}`, {
        preserveScroll: true,
        onSuccess: () => (confirmDelete.value = null),
    });
};
</script>

<template>
    <div class="lms-page">
        <Head title="Community tracks — Admin" />

        <div class="lms-hero">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight">Tracks</h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        The technology areas TAC organises around. Rename,
                        reorder, retire or add to them without a deploy.
                    </p>
                </div>
                <button
                    v-if="can.create"
                    type="button"
                    class="rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#35919e]"
                    @click="openCreate"
                >
                    Add a track
                </button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="track in tracks"
                :key="track.id"
                class="lms-panel flex flex-col"
                :class="!track.is_active ? 'opacity-60' : ''"
            >
                <div class="flex items-start justify-between gap-3">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-base font-black"
                        :style="{
                            backgroundColor: `${track.accent_color ?? '#42b6c5'}22`,
                            color: track.accent_color ?? '#42b6c5',
                        }"
                        aria-hidden="true"
                    >
                        {{ initials(track.name) }}
                    </span>
                    <span
                        v-if="!track.is_active"
                        class="rounded-full bg-gray-500/12 px-2.5 py-1 text-[10px] font-bold text-gray-600 dark:text-gray-300"
                    >
                        Inactive
                    </span>
                </div>

                <h2
                    class="mt-4 font-bold text-[#000928] dark:text-white"
                >
                    {{ track.name }}
                </h2>
                <p
                    v-if="track.tagline"
                    class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                >
                    {{ track.tagline }}
                </p>

                <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div
                        v-for="stat in [
                            { label: 'Members', value: track.members_count ?? 0 },
                            { label: 'Mentors', value: track.mentors_count ?? 0 },
                            {
                                label: 'Upcoming',
                                value: track.upcoming_activities_count ?? 0,
                            },
                        ]"
                        :key="stat.label"
                        class="rounded-lg bg-gray-50 py-2 dark:bg-white/5"
                    >
                        <dd
                            class="text-base font-black text-[#000928] dark:text-white"
                        >
                            {{ stat.value }}
                        </dd>
                        <dt
                            class="text-[10px] font-semibold tracking-wider text-gray-500 uppercase"
                        >
                            {{ stat.label }}
                        </dt>
                    </div>
                </dl>

                <div
                    v-if="track.mentors?.length"
                    class="mt-4 flex items-center gap-2"
                >
                    <div class="flex -space-x-2">
                        <span
                            v-for="mentor in track.mentors.slice(0, 4)"
                            :key="mentor.id"
                            class="h-7 w-7 overflow-hidden rounded-full ring-2 ring-white dark:ring-gray-800"
                            :title="mentor.name"
                        >
                            <img
                                v-if="asset(mentor.photo_path)"
                                :src="asset(mentor.photo_path)!"
                                :alt="mentor.name"
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="flex h-full w-full items-center justify-center bg-[#381998] text-[9px] font-bold text-white"
                                aria-hidden="true"
                                >{{ initials(mentor.name) }}</span
                            >
                        </span>
                    </div>
                    <span class="text-xs text-gray-500">mentoring</span>
                </div>
                <p v-else class="mt-4 text-xs font-medium text-amber-600">
                    No mentor assigned
                </p>

                <div
                    class="mt-auto flex flex-wrap items-center gap-3 pt-4 text-xs"
                >
                    <button
                        v-if="canManage(track)"
                        type="button"
                        class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                        @click="openEdit(track)"
                    >
                        Edit
                    </button>
                    <Link
                        :href="`/admin/community/members?track=${track.slug}`"
                        class="font-bold text-gray-500 hover:underline"
                    >
                        Members
                    </Link>
                    <Link
                        :href="`/community/tracks/${track.slug}`"
                        class="font-bold text-gray-500 hover:underline"
                    >
                        Public page
                    </Link>
                    <button
                        v-if="can.create"
                        type="button"
                        class="ml-auto font-bold text-red-600 hover:underline"
                        @click="confirmDelete = track"
                    >
                        Delete
                    </button>
                </div>
            </article>
        </div>

        <!-- Form -->
        <ConfirmationModal
            :open="showForm"
            :title="editing ? `Edit ${editing.name}` : 'Add a track'"
            :confirm-text="editing ? 'Save changes' : 'Create track'"
            variant="default"
            :processing="form.processing"
            @update:open="showForm = $event"
            @confirm="submit"
        >
            <template #body>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="track_name" class="lms-label">Name *</label>
                        <input
                            id="track_name"
                            v-model="form.name"
                            type="text"
                            class="lms-input mt-1.5"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <label for="track_tagline" class="lms-label"
                            >Tagline</label
                        >
                        <input
                            id="track_tagline"
                            v-model="form.tagline"
                            type="text"
                            placeholder="One line that captures what this track is about"
                            class="lms-input mt-1.5"
                        />
                    </div>

                    <div>
                        <label for="track_description" class="lms-label"
                            >Description</label
                        >
                        <textarea
                            id="track_description"
                            v-model="form.description"
                            rows="3"
                            class="lms-input mt-1.5 resize-y"
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="track_color" class="lms-label"
                                >Accent colour</label
                            >
                            <div class="mt-1.5 flex items-center gap-2">
                                <input
                                    id="track_color"
                                    v-model="form.accent_color"
                                    type="color"
                                    class="h-10 w-14 cursor-pointer rounded-lg border border-gray-200 dark:border-white/10"
                                />
                                <input
                                    v-model="form.accent_color"
                                    type="text"
                                    class="lms-input"
                                    aria-label="Accent colour hex"
                                />
                            </div>
                        </div>
                        <div>
                            <label for="track_order" class="lms-label"
                                >Display order</label
                            >
                            <input
                                id="track_order"
                                v-model.number="form.sort_order"
                                type="number"
                                min="0"
                                class="lms-input mt-1.5"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="track_cover" class="lms-label"
                            >Cover image</label
                        >
                        <input
                            id="track_cover"
                            type="file"
                            accept="image/*"
                            class="lms-input mt-1.5"
                            @input="
                                form.cover_image = (
                                    $event.target as HTMLInputElement
                                ).files?.[0] ?? null
                            "
                        />
                    </div>

                    <label class="flex items-center gap-2.5 text-sm">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                        />
                        <span class="text-gray-600 dark:text-gray-400"
                            >Active — shown publicly and selectable when
                            joining</span
                        >
                    </label>
                </div>
            </template>
        </ConfirmationModal>

        <ConfirmationModal
            :open="confirmDelete !== null"
            title="Delete this track?"
            :description="`“${confirmDelete?.name}” will be removed. If it has members or activities, deactivate it instead — the delete will be refused.`"
            confirm-text="Delete track"
            @update:open="confirmDelete = null"
            @confirm="destroy"
        />
    </div>
</template>
