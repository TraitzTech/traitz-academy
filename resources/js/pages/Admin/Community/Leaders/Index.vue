<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import SocialLinksEditor from '@/components/community/SocialLinksEditor.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SelectOption, TacLeader, TacTrack } from '@/types/community';

interface Props {
    leaders: TacLeader[];
    filters: { search?: string; role_type?: string; track?: string; state?: string };
    tracks: TacTrack[];
    roleTypes: SelectOption[];
    schools: string[];
    assignableUsers: { id: number; name: string; email: string; role: string }[];
    counts: Record<string, number>;
    can: { manage: boolean };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { asset, initials, formatDate } = useCommunity();

const filters = ref({
    search: props.filters.search ?? '',
    role_type: props.filters.role_type ?? '',
    track: props.filters.track ?? '',
    state: props.filters.state ?? 'active',
});

watch(
    filters,
    () =>
        router.get(
            '/admin/community/leaders',
            Object.fromEntries(
                Object.entries(filters.value).filter(([, v]) => v),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        ),
    { deep: true },
);

/* ------------------------------------------------------------ Form */

const showForm = ref(false);
const editing = ref<TacLeader | null>(null);
const confirmRetire = ref<TacLeader | null>(null);
const confirmDelete = ref<TacLeader | null>(null);

const blank = {
    name: '',
    role_type: 'track_mentor',
    role_title: '',
    tac_track_id: null as number | null,
    school: '',
    bio: '',
    email: '',
    phone: '',
    social_links: [] as { key: string; value: string }[],
    user_id: null as number | null,
    started_on: '',
    is_active: true,
    is_featured: false,
    sort_order: 0,
    photo: null as File | null,
    send_login: true,
};

const form = useForm({ ...blank });

const openCreate = () => {
    editing.value = null;
    form.defaults({ ...blank });
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const openEdit = (leader: TacLeader) => {
    editing.value = leader;
    form.clearErrors();
    Object.assign(form, {
        name: leader.name,
        role_type: leader.role_type,
        role_title: leader.role_title ?? '',
        tac_track_id: leader.tac_track_id,
        school: leader.school ?? '',
        bio: leader.bio ?? '',
        email: leader.email ?? '',
        phone: leader.phone ?? '',
        social_links: Object.entries(leader.social_links ?? {})
            .filter(([, value]) => value)
            .map(([key, value]) => ({ key, value: value as string })),
        user_id: leader.user_id,
        started_on: leader.started_on ?? '',
        is_active: leader.is_active,
        is_featured: leader.is_featured,
        sort_order: leader.sort_order,
        photo: null,
        send_login: !leader.user_id,
    });
    showForm.value = true;
};

const submit = () => {
    const url = editing.value
        ? `/admin/community/leaders/${editing.value.id}`
        : '/admin/community/leaders';

    form.transform((data) => ({
        ...data,
        social_links: Object.fromEntries(
            data.social_links
                .filter((row) => row.key.trim() && row.value.trim())
                .map((row) => [row.key.trim(), row.value.trim()]),
        ),
    })).post(url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showForm.value = false;
            editing.value = null;
            form.reset();
        },
    });
};

const retire = () => {
    if (!confirmRetire.value) return;
    router.post(
        `/admin/community/leaders/${confirmRetire.value.id}/retire`,
        {},
        { preserveScroll: true, onSuccess: () => (confirmRetire.value = null) },
    );
};

const reinstate = (leader: TacLeader) =>
    router.post(
        `/admin/community/leaders/${leader.id}/reinstate`,
        {},
        { preserveScroll: true },
    );

const confirmCreateLogin = ref<TacLeader | null>(null);

const createLogin = () => {
    if (!confirmCreateLogin.value) return;
    router.post(
        `/admin/community/leaders/${confirmCreateLogin.value.id}/create-login`,
        {},
        { preserveScroll: true, onSuccess: () => (confirmCreateLogin.value = null) },
    );
};

const destroy = () => {
    if (!confirmDelete.value) return;
    router.delete(`/admin/community/leaders/${confirmDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => (confirmDelete.value = null),
    });
};

/* ---------------------------------------------------------- Grouping */

const grouped = computed(() =>
    props.roleTypes
        .map((role) => ({
            ...role,
            leaders: props.leaders.filter((l) => l.role_type === role.value),
        }))
        .filter((group) => group.leaders.length > 0),
);

const needsTrack = computed(() => form.role_type === 'track_mentor');
const needsSchool = computed(() => form.role_type === 'school_lead');
</script>

<template>
    <div class="lms-page">
        <Head title="TAC leadership — Admin" />

        <!-- Header -->
        <div class="lms-hero">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight">
                        Leadership roster
                    </h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        Leadership rotates. Appoint, re-assign and retire leaders
                        here — no deploy needed.
                    </p>
                </div>
                <button
                    v-if="can.manage"
                    type="button"
                    class="rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#35919e]"
                    @click="openCreate"
                >
                    Appoint a leader
                </button>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <span
                    v-for="role in roleTypes"
                    :key="role.value"
                    class="rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold backdrop-blur"
                >
                    {{ role.label }}
                    <span class="ml-1 font-black text-[#42b6c5]">{{
                        counts[role.value] ?? 0
                    }}</span>
                </span>
            </div>
        </div>

        <!-- Filters -->
        <section class="lms-panel">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search leaders…"
                    class="lms-input"
                    aria-label="Search leaders"
                />
                <select v-model="filters.role_type" class="lms-input" aria-label="Role">
                    <option value="">All roles</option>
                    <option
                        v-for="role in roleTypes"
                        :key="role.value"
                        :value="role.value"
                    >
                        {{ role.label }}
                    </option>
                </select>
                <select v-model="filters.track" class="lms-input" aria-label="Track">
                    <option value="">All tracks</option>
                    <option
                        v-for="track in tracks"
                        :key="track.id"
                        :value="track.slug"
                    >
                        {{ track.name }}
                    </option>
                </select>
                <select v-model="filters.state" class="lms-input" aria-label="State">
                    <option value="active">Currently serving</option>
                    <option value="retired">Past leaders</option>
                </select>
            </div>
        </section>

        <EmptyState
            v-if="!leaders.length"
            icon="users"
            :title="
                filters.state === 'retired'
                    ? 'No past leaders yet'
                    : 'No leaders appointed yet'
            "
            description="Every role — Lead, Co-Lead, Secretary, Technical Leads, track mentors, school leads and partnership leads — is managed from here."
        >
            <button v-if="can.manage" class="lms-btn-accent" @click="openCreate">
                Appoint the first leader
            </button>
        </EmptyState>

        <!-- Groups -->
        <section
            v-for="group in grouped"
            :key="group.value"
            class="lms-panel"
        >
            <h2 class="lms-title text-lg">{{ group.label }}s</h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="leader in group.leaders"
                    :key="leader.id"
                    class="flex gap-4 rounded-xl border border-gray-100 p-4 dark:border-white/10"
                >
                    <span class="h-14 w-14 shrink-0 overflow-hidden rounded-xl">
                        <img
                            v-if="asset(leader.photo_path)"
                            :src="asset(leader.photo_path)!"
                            :alt="leader.name"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-else
                            class="flex h-full w-full items-center justify-center bg-[#381998]/10 font-black text-[#381998] dark:text-[#b9a5f5]"
                            aria-hidden="true"
                            >{{ initials(leader.name) }}</span
                        >
                    </span>

                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate font-bold text-[#000928] dark:text-white"
                        >
                            {{ leader.name }}
                        </p>
                        <p class="truncate text-xs text-gray-500">
                            {{ leader.role_title || group.label }}
                            <template v-if="leader.track"
                                >· {{ leader.track.name }}</template
                            >
                            <template v-else-if="leader.school"
                                >· {{ leader.school }}</template
                            >
                        </p>
                        <p
                            v-if="leader.email"
                            class="truncate text-xs text-gray-400"
                        >
                            {{ leader.email }}
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span
                                v-if="leader.user_id"
                                class="rounded-full bg-[#42b6c5]/12 px-2 py-0.5 text-[10px] font-bold text-[#26808c] dark:text-[#7fd4df]"
                            >
                                Has admin access
                            </span>
                            <span
                                v-if="leader.is_featured"
                                class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:text-amber-300"
                            >
                                Featured
                            </span>
                            <span
                                v-if="leader.ended_on"
                                class="rounded-full bg-gray-500/12 px-2 py-0.5 text-[10px] font-bold text-gray-600 dark:text-gray-300"
                            >
                                Until {{ formatDate(leader.ended_on, { month: 'short', year: 'numeric' }) }}
                            </span>
                        </div>

                        <div v-if="can.manage" class="mt-3 flex flex-wrap gap-3 text-xs">
                            <Link
                                :href="`/admin/community/leaders/${leader.id}`"
                                class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                            >
                                Profile
                            </Link>
                            <button
                                type="button"
                                class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                                @click="openEdit(leader)"
                            >
                                Edit
                            </button>
                            <button
                                v-if="leader.is_active && !leader.ended_on"
                                type="button"
                                class="font-bold text-amber-600 hover:underline"
                                @click="confirmRetire = leader"
                            >
                                Retire
                            </button>
                            <button
                                v-else
                                type="button"
                                class="font-bold text-emerald-600 hover:underline"
                                @click="reinstate(leader)"
                            >
                                Reinstate
                            </button>
                            <button
                                v-if="!leader.user_id && leader.email"
                                type="button"
                                class="font-bold text-[#42b6c5] hover:underline"
                                @click="confirmCreateLogin = leader"
                            >
                                Create login
                            </button>
                            <button
                                type="button"
                                class="font-bold text-red-600 hover:underline"
                                @click="confirmDelete = leader"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <!-- Appoint / edit modal -->
        <ConfirmationModal
            :open="showForm"
            :title="editing ? `Edit ${editing.name}` : 'Appoint a leader'"
            :confirm-text="editing ? 'Save changes' : 'Appoint'"
            variant="default"
            :processing="form.processing"
            @update:open="showForm = $event"
            @confirm="submit"
        >
            <template #body>
                <div class="mt-4 max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="leader_name" class="lms-label"
                                >Full name *</label
                            >
                            <input
                                id="leader_name"
                                v-model="form.name"
                                type="text"
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div>
                            <label for="leader_role" class="lms-label"
                                >Role *</label
                            >
                            <select
                                id="leader_role"
                                v-model="form.role_type"
                                class="lms-input mt-1.5"
                            >
                                <option
                                    v-for="role in roleTypes"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.role_type" />
                        </div>

                        <div v-if="needsTrack">
                            <label for="leader_track" class="lms-label"
                                >Track *</label
                            >
                            <select
                                id="leader_track"
                                v-model="form.tac_track_id"
                                class="lms-input mt-1.5"
                            >
                                <option :value="null">Choose a track…</option>
                                <option
                                    v-for="track in tracks"
                                    :key="track.id"
                                    :value="track.id"
                                >
                                    {{ track.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.tac_track_id" />
                        </div>

                        <div v-if="needsSchool">
                            <label for="leader_school" class="lms-label"
                                >School *</label
                            >
                            <input
                                id="leader_school"
                                v-model="form.school"
                                type="text"
                                list="school-suggestions"
                                class="lms-input mt-1.5"
                            />
                            <datalist id="school-suggestions">
                                <option
                                    v-for="s in schools"
                                    :key="s"
                                    :value="s"
                                />
                            </datalist>
                            <InputError :message="form.errors.school" />
                        </div>

                        <div>
                            <label for="leader_title" class="lms-label"
                                >Custom title</label
                            >
                            <input
                                id="leader_title"
                                v-model="form.role_title"
                                type="text"
                                placeholder="Leave blank to use the role name"
                                class="lms-input mt-1.5"
                            />
                        </div>

                        <div>
                            <label for="leader_started" class="lms-label"
                                >Started in role</label
                            >
                            <input
                                id="leader_started"
                                v-model="form.started_on"
                                type="date"
                                class="lms-input mt-1.5"
                            />
                        </div>

                        <div>
                            <label for="leader_email" class="lms-label"
                                >Email</label
                            >
                            <input
                                id="leader_email"
                                v-model="form.email"
                                type="email"
                                class="lms-input mt-1.5"
                            />
                        </div>

                        <div>
                            <label for="leader_phone" class="lms-label"
                                >Phone</label
                            >
                            <input
                                id="leader_phone"
                                v-model="form.phone"
                                type="tel"
                                class="lms-input mt-1.5"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="leader_user" class="lms-label"
                            >Link to an account</label
                        >
                        <select
                            id="leader_user"
                            v-model="form.user_id"
                            class="lms-input mt-1.5"
                        >
                            <option :value="null">No account linked</option>
                            <option
                                v-for="user in assignableUsers"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }} — {{ user.email }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Linking an account gives this leader scoped access to
                            the community admin: mentors manage their track,
                            school leads their campus.
                        </p>
                    </div>

                    <div>
                        <label for="leader_bio" class="lms-label">Bio</label>
                        <textarea
                            id="leader_bio"
                            v-model="form.bio"
                            rows="3"
                            class="lms-input mt-1.5 resize-y"
                        />
                    </div>

                    <div>
                        <label for="leader_photo" class="lms-label">Photo</label>
                        <input
                            id="leader_photo"
                            type="file"
                            accept="image/*"
                            class="lms-input mt-1.5"
                            @input="
                                form.photo = (
                                    $event.target as HTMLInputElement
                                ).files?.[0] ?? null
                            "
                        />
                        <InputError :message="form.errors.photo" />
                    </div>

                    <div>
                        <label class="lms-label">Social & contact links</label>
                        <p class="mt-1 text-xs text-gray-500">
                            Add whatever this person actually uses — LinkedIn,
                            X, Instagram, a personal site, anything.
                        </p>
                        <SocialLinksEditor
                            v-model="form.social_links"
                            id-prefix="leader_social"
                            class="mt-2"
                        />
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="form.is_featured"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span class="text-gray-600 dark:text-gray-400"
                                >Feature on the community home page</span
                            >
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span class="text-gray-600 dark:text-gray-400"
                                >Currently serving</span
                            >
                        </label>
                    </div>

                    <label
                        v-if="!form.user_id"
                        class="flex items-start gap-2.5 rounded-xl border border-[#42b6c5]/30 bg-[#42b6c5]/8 p-3 text-sm"
                    >
                        <input
                            v-model="form.send_login"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                        />
                        <span>
                            <span class="block font-semibold text-[#000928] dark:text-white"
                                >Give them a login now</span
                            >
                            <span class="mt-0.5 block text-gray-500 dark:text-gray-400">
                                Creates an account and emails login credentials
                                to their email address (or links an existing
                                account with that email — no email sent in
                                that case). Requires an email above.
                            </span>
                        </span>
                    </label>
                </div>
            </template>
        </ConfirmationModal>

        <ConfirmationModal
            :open="confirmRetire !== null"
            title="Retire this leader?"
            :description="`${confirmRetire?.name} will move to the past-leaders timeline and immediately lose any community admin access. Their record is kept.`"
            confirm-text="Retire"
            variant="default"
            @update:open="confirmRetire = null"
            @confirm="retire"
        />

        <ConfirmationModal
            :open="confirmCreateLogin !== null"
            title="Create a login for this leader?"
            :description="`A new account will be created for ${confirmCreateLogin?.email} and login credentials emailed to them right away. If an account with that email already exists, it will be linked instead — nothing is emailed in that case. Once linked, they can sign in and manage their own track/school activities and members.`"
            confirm-text="Create login"
            variant="default"
            @update:open="confirmCreateLogin = null"
            @confirm="createLogin"
        />

        <ConfirmationModal
            :open="confirmDelete !== null"
            title="Delete this leadership record?"
            :description="`This erases ${confirmDelete?.name} from the roster and the public history entirely. Retiring is usually what you want instead.`"
            confirm-text="Delete permanently"
            @update:open="confirmDelete = null"
            @confirm="destroy"
        />
    </div>
</template>
