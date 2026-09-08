<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import EmptyState from '@/components/community/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SelectOption, TacLeader, TacPartner } from '@/types/community';

interface Props {
    partners: TacPartner[];
    filters: { search?: string; tier?: string };
    tiers: SelectOption[];
    partnershipLeads: TacLeader[];
    can: { manage: boolean; delete: boolean };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { asset, initials, formatDate } = useCommunity();

const filters = ref({
    search: props.filters.search ?? '',
    tier: props.filters.tier ?? '',
});

watch(
    filters,
    () =>
        router.get(
            '/admin/community/partners',
            Object.fromEntries(
                Object.entries(filters.value).filter(([, v]) => v),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        ),
    { deep: true },
);

const showForm = ref(false);
const editing = ref<TacPartner | null>(null);
const confirmDelete = ref<TacPartner | null>(null);

const blank = {
    name: '',
    website_url: '',
    tier: 'community',
    description: '',
    contact_name: '',
    contact_email: '',
    contact_phone: '',
    partnership_lead_id: null as number | null,
    started_on: '',
    is_active: true,
    is_featured: false,
    sort_order: 0,
    logo: null as File | null,
};

const form = useForm({ ...blank });

const openCreate = () => {
    editing.value = null;
    form.defaults({ ...blank });
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const openEdit = (partner: TacPartner) => {
    editing.value = partner;
    form.clearErrors();
    Object.assign(form, {
        name: partner.name,
        website_url: partner.website_url ?? '',
        tier: partner.tier,
        description: partner.description ?? '',
        contact_name: partner.contact_name ?? '',
        contact_email: partner.contact_email ?? '',
        contact_phone: partner.contact_phone ?? '',
        partnership_lead_id: partner.partnership_lead_id,
        started_on: partner.started_on ?? '',
        is_active: partner.is_active,
        is_featured: partner.is_featured,
        sort_order: partner.sort_order,
        logo: null,
    });
    showForm.value = true;
};

const submit = () => {
    const url = editing.value
        ? `/admin/community/partners/${editing.value.slug}`
        : '/admin/community/partners';

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
    router.delete(`/admin/community/partners/${confirmDelete.value.slug}`, {
        preserveScroll: true,
        onSuccess: () => (confirmDelete.value = null),
    });
};

const tierClasses = (tier: string) =>
    ({
        platinum: 'bg-slate-500/12 text-slate-700 dark:text-slate-300',
        gold: 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
        silver: 'bg-gray-500/12 text-gray-600 dark:text-gray-300',
        academic: 'bg-[#381998]/12 text-[#381998] dark:text-[#b9a5f5]',
        community: 'bg-[#42b6c5]/12 text-[#26808c] dark:text-[#7fd4df]',
    })[tier] ?? 'bg-gray-500/12 text-gray-600';
</script>

<template>
    <div class="lms-page">
        <Head title="Partners & sponsors — Admin" />

        <div class="lms-hero">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight">
                        Partners & sponsors
                    </h1>
                    <p class="mt-1.5 text-sm text-white/70">
                        The organisations backing TAC, shown on the public
                        partners page.
                    </p>
                </div>
                <button
                    v-if="can.manage"
                    type="button"
                    class="rounded-xl bg-[#42b6c5] px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#35919e]"
                    @click="openCreate"
                >
                    Add a partner
                </button>
            </div>
        </div>

        <section class="lms-panel">
            <div class="grid gap-3 sm:grid-cols-3">
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Search partners…"
                    class="lms-input sm:col-span-2"
                    aria-label="Search partners"
                />
                <select v-model="filters.tier" class="lms-input" aria-label="Tier">
                    <option value="">All tiers</option>
                    <option
                        v-for="tier in tiers"
                        :key="tier.value"
                        :value="tier.value"
                    >
                        {{ tier.label }}
                    </option>
                </select>
            </div>
        </section>

        <EmptyState
            v-if="!partners.length"
            icon="users"
            title="No partners yet"
            description="Add the organisations sponsoring or collaborating with TAC — they appear on the public partners page grouped by tier."
        >
            <button v-if="can.manage" class="lms-btn-accent" @click="openCreate">
                Add the first partner
            </button>
        </EmptyState>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="partner in partners"
                :key="partner.id"
                class="lms-panel flex flex-col"
                :class="!partner.is_active ? 'opacity-60' : ''"
            >
                <div class="flex items-start justify-between gap-3">
                    <span
                        class="flex h-12 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-50 dark:bg-white/5"
                    >
                        <img
                            v-if="asset(partner.logo_path)"
                            :src="asset(partner.logo_path)!"
                            :alt="partner.name"
                            class="max-h-10 w-auto object-contain"
                        />
                        <span
                            v-else
                            class="text-sm font-black text-gray-400"
                            aria-hidden="true"
                            >{{ initials(partner.name) }}</span
                        >
                    </span>

                    <div class="flex flex-col items-end gap-1">
                        <span
                            :class="[
                                'rounded-full px-2.5 py-1 text-[10px] font-bold capitalize',
                                tierClasses(partner.tier),
                            ]"
                        >
                            {{ partner.tier }}
                        </span>
                        <span
                            v-if="partner.is_featured"
                            class="rounded-full bg-amber-500/15 px-2.5 py-0.5 text-[10px] font-bold text-amber-700 dark:text-amber-300"
                            >Featured</span
                        >
                        <span
                            v-if="!partner.is_active"
                            class="rounded-full bg-gray-500/12 px-2.5 py-0.5 text-[10px] font-bold text-gray-600 dark:text-gray-300"
                            >Inactive</span
                        >
                    </div>
                </div>

                <h2 class="mt-4 font-bold text-[#000928] dark:text-white">
                    {{ partner.name }}
                </h2>
                <p
                    v-if="partner.description"
                    class="mt-1.5 line-clamp-3 text-sm text-gray-600 dark:text-gray-400"
                >
                    {{ partner.description }}
                </p>

                <dl class="mt-4 space-y-1 text-xs">
                    <div v-if="partner.contact_name" class="flex gap-2">
                        <dt class="text-gray-500">Contact:</dt>
                        <dd class="text-gray-700 dark:text-gray-300">
                            {{ partner.contact_name }}
                        </dd>
                    </div>
                    <div v-if="partner.partnership_lead" class="flex gap-2">
                        <dt class="text-gray-500">Owned by:</dt>
                        <dd class="text-gray-700 dark:text-gray-300">
                            {{ partner.partnership_lead.name }}
                        </dd>
                    </div>
                    <div v-if="partner.started_on" class="flex gap-2">
                        <dt class="text-gray-500">Since:</dt>
                        <dd class="text-gray-700 dark:text-gray-300">
                            {{
                                formatDate(partner.started_on, {
                                    month: 'short',
                                    year: 'numeric',
                                })
                            }}
                        </dd>
                    </div>
                </dl>

                <div
                    class="mt-auto flex flex-wrap items-center gap-3 pt-4 text-xs"
                >
                    <button
                        v-if="can.manage"
                        type="button"
                        class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                        @click="openEdit(partner)"
                    >
                        Edit
                    </button>
                    <a
                        v-if="partner.website_url"
                        :href="partner.website_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-bold text-gray-500 hover:underline"
                    >
                        Website ↗
                    </a>
                    <button
                        v-if="can.delete"
                        type="button"
                        class="ml-auto font-bold text-red-600 hover:underline"
                        @click="confirmDelete = partner"
                    >
                        Delete
                    </button>
                </div>
            </article>
        </div>

        <ConfirmationModal
            :open="showForm"
            :title="editing ? `Edit ${editing.name}` : 'Add a partner'"
            :confirm-text="editing ? 'Save changes' : 'Add partner'"
            variant="default"
            :processing="form.processing"
            @update:open="showForm = $event"
            @confirm="submit"
        >
            <template #body>
                <div class="mt-4 max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="partner_name" class="lms-label"
                                >Name *</label
                            >
                            <input
                                id="partner_name"
                                v-model="form.name"
                                type="text"
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div>
                            <label for="partner_tier" class="lms-label"
                                >Tier *</label
                            >
                            <select
                                id="partner_tier"
                                v-model="form.tier"
                                class="lms-input mt-1.5"
                            >
                                <option
                                    v-for="tier in tiers"
                                    :key="tier.value"
                                    :value="tier.value"
                                >
                                    {{ tier.label }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="partner_website" class="lms-label"
                                >Website</label
                            >
                            <input
                                id="partner_website"
                                v-model="form.website_url"
                                type="url"
                                placeholder="https://"
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.website_url" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="partner_description" class="lms-label"
                                >Description</label
                            >
                            <textarea
                                id="partner_description"
                                v-model="form.description"
                                rows="3"
                                class="lms-input mt-1.5 resize-y"
                            />
                        </div>
                        <div>
                            <label for="partner_contact_name" class="lms-label"
                                >Contact name</label
                            >
                            <input
                                id="partner_contact_name"
                                v-model="form.contact_name"
                                type="text"
                                class="lms-input mt-1.5"
                            />
                        </div>
                        <div>
                            <label for="partner_contact_email" class="lms-label"
                                >Contact email</label
                            >
                            <input
                                id="partner_contact_email"
                                v-model="form.contact_email"
                                type="email"
                                class="lms-input mt-1.5"
                            />
                            <InputError :message="form.errors.contact_email" />
                        </div>
                        <div>
                            <label for="partner_contact_phone" class="lms-label"
                                >Contact phone</label
                            >
                            <input
                                id="partner_contact_phone"
                                v-model="form.contact_phone"
                                type="tel"
                                class="lms-input mt-1.5"
                            />
                        </div>
                        <div>
                            <label for="partner_lead" class="lms-label"
                                >Partnership lead</label
                            >
                            <select
                                id="partner_lead"
                                v-model="form.partnership_lead_id"
                                class="lms-input mt-1.5"
                            >
                                <option :value="null">Not assigned</option>
                                <option
                                    v-for="lead in partnershipLeads"
                                    :key="lead.id"
                                    :value="lead.id"
                                >
                                    {{ lead.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label for="partner_started" class="lms-label"
                                >Partnership started</label
                            >
                            <input
                                id="partner_started"
                                v-model="form.started_on"
                                type="date"
                                class="lms-input mt-1.5"
                            />
                        </div>
                        <div>
                            <label for="partner_order" class="lms-label"
                                >Display order</label
                            >
                            <input
                                id="partner_order"
                                v-model.number="form.sort_order"
                                type="number"
                                min="0"
                                class="lms-input mt-1.5"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="partner_logo" class="lms-label">Logo</label>
                        <input
                            id="partner_logo"
                            type="file"
                            accept="image/*"
                            class="lms-input mt-1.5"
                            @input="
                                form.logo = (
                                    $event.target as HTMLInputElement
                                ).files?.[0] ?? null
                            "
                        />
                        <InputError :message="form.errors.logo" />
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="form.is_featured"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span class="text-gray-600 dark:text-gray-400"
                                >Featured</span
                            >
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-[#42b6c5] focus:ring-[#42b6c5]"
                            />
                            <span class="text-gray-600 dark:text-gray-400"
                                >Active partnership</span
                            >
                        </label>
                    </div>
                </div>
            </template>
        </ConfirmationModal>

        <ConfirmationModal
            :open="confirmDelete !== null"
            title="Remove this partner?"
            :description="`“${confirmDelete?.name}” will be removed from the public partners page. Deactivating keeps the record instead.`"
            confirm-text="Delete partner"
            @update:open="confirmDelete = null"
            @confirm="destroy"
        />
    </div>
</template>
