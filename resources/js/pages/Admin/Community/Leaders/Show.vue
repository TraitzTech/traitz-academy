<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import ConfirmationModal from '@/components/ConfirmationModal.vue';
import InputError from '@/components/InputError.vue';
import { useCommunity } from '@/composables/useCommunity';
import AppLayout from '@/layouts/AppLayout.vue';
import type { TacLeader } from '@/types/community';

interface Props {
    leader: TacLeader;
    can: { manageResponsibilities: boolean; manageReviews: boolean };
}

const props = defineProps<Props>();

defineOptions({ layout: AppLayout });

const { asset, initials, formatDate } = useCommunity();

const STATUS_META: Record<string, { label: string; classes: string }> = {
    pending: { label: 'Pending', classes: 'bg-gray-500/12 text-gray-600 dark:text-gray-300' },
    in_progress: { label: 'In progress', classes: 'bg-amber-500/12 text-amber-700 dark:text-amber-300' },
    completed: { label: 'Completed', classes: 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300' },
};

/* -------------------------------------------------------- Responsibilities */

const showResponsibilityForm = ref(false);
const editingResponsibility = ref<number | null>(null);

const responsibilityForm = useForm({
    title: '',
    description: '',
    due_date: '',
});

const openAddResponsibility = () => {
    editingResponsibility.value = null;
    responsibilityForm.reset();
    responsibilityForm.clearErrors();
    showResponsibilityForm.value = true;
};

const openEditResponsibility = (r: { id: number; title: string; description: string | null; due_date: string | null }) => {
    editingResponsibility.value = r.id;
    responsibilityForm.clearErrors();
    Object.assign(responsibilityForm, {
        title: r.title,
        description: r.description ?? '',
        due_date: r.due_date ?? '',
    });
    showResponsibilityForm.value = true;
};

const submitResponsibility = () => {
    const url = editingResponsibility.value
        ? `/admin/community/leaders/${props.leader.id}/responsibilities/${editingResponsibility.value}`
        : `/admin/community/leaders/${props.leader.id}/responsibilities`;

    responsibilityForm.post(url, {
        preserveScroll: true,
        onSuccess: () => (showResponsibilityForm.value = false),
    });
};

const confirmDeleteResponsibility = ref<number | null>(null);

const deleteResponsibility = () => {
    if (!confirmDeleteResponsibility.value) return;
    router.delete(
        `/admin/community/leaders/${props.leader.id}/responsibilities/${confirmDeleteResponsibility.value}`,
        { preserveScroll: true, onSuccess: () => (confirmDeleteResponsibility.value = null) },
    );
};

/* --------------------------------------------------------- Performance -- */

const reviewForm = useForm({
    rating: 5,
    period_label: '',
    notes: '',
});

const showReviewForm = ref(false);

const submitReview = () =>
    reviewForm.post(`/admin/community/leaders/${props.leader.id}/reviews`, {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset();
            showReviewForm.value = false;
        },
    });

const confirmDeleteReview = ref<number | null>(null);

const deleteReview = () => {
    if (!confirmDeleteReview.value) return;
    router.delete(`/admin/community/leaders/${props.leader.id}/reviews/${confirmDeleteReview.value}`, {
        preserveScroll: true,
        onSuccess: () => (confirmDeleteReview.value = null),
    });
};

const averageRating = () => {
    const reviews = props.leader.performance_reviews ?? [];
    if (!reviews.length) return null;
    return (reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length).toFixed(1);
};
</script>

<template>
    <div class="lms-page">
        <Head :title="`${leader.name} — Leader profile`" />

        <nav class="text-sm" aria-label="Breadcrumb">
            <Link
                href="/admin/community/leaders"
                class="font-semibold text-gray-500 hover:text-[#000928] dark:hover:text-white"
            >
                ← All leaders
            </Link>
        </nav>

        <!-- Header -->
        <div class="lms-hero">
            <div class="flex flex-wrap items-center gap-5">
                <span class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl">
                    <img
                        v-if="asset(leader.photo_path)"
                        :src="asset(leader.photo_path)!"
                        :alt="leader.name"
                        class="h-full w-full object-cover"
                    />
                    <span
                        v-else
                        class="flex h-full w-full items-center justify-center bg-white/10 text-xl font-black backdrop-blur"
                        aria-hidden="true"
                        >{{ initials(leader.name) }}</span
                    >
                </span>

                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-black tracking-tight">{{ leader.name }}</h1>
                    <p class="mt-1 text-sm text-white/70">
                        {{ leader.role_title || leader.role_type.replace('_', ' ') }}
                        <template v-if="leader.track">— {{ leader.track.name }}</template>
                        <template v-if="leader.school">— {{ leader.school }}</template>
                    </p>
                    <p v-if="leader.email" class="mt-0.5 text-xs text-white/50">
                        {{ leader.email }}
                        <span v-if="leader.user_id">· has a login</span>
                        <span v-else>· no login yet</span>
                    </p>
                </div>

                <div
                    v-if="averageRating()"
                    class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-center backdrop-blur"
                >
                    <p class="text-2xl font-black">{{ averageRating() }} <span class="text-base text-white/50">/ 5</span></p>
                    <p class="text-[10px] font-semibold tracking-wider text-white/60 uppercase">
                        Average rating
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Responsibilities -->
            <section class="lms-panel">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="lms-title text-lg">Responsibilities</h2>
                        <p class="lms-subtitle">
                            What {{ leader.name.split(' ')[0] }} is accountable for.
                        </p>
                    </div>
                    <button
                        v-if="can.manageResponsibilities"
                        type="button"
                        class="lms-btn-outline"
                        @click="openAddResponsibility"
                    >
                        Assign
                    </button>
                </div>

                <ul
                    v-if="leader.responsibilities?.length"
                    class="mt-5 divide-y divide-gray-100 dark:divide-white/5"
                >
                    <li
                        v-for="r in leader.responsibilities"
                        :key="r.id"
                        class="py-3.5"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-[#000928] dark:text-white">
                                    {{ r.title }}
                                </p>
                                <p
                                    v-if="r.description"
                                    class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{ r.description }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    <template v-if="r.due_date">Due {{ formatDate(r.due_date, { day: 'numeric', month: 'short', year: 'numeric' }) }} · </template>
                                    <template v-if="r.assigned_by">Assigned by {{ r.assigned_by.name }}</template>
                                </p>
                            </div>
                            <span
                                :class="['shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold', STATUS_META[r.status]?.classes]"
                            >
                                {{ STATUS_META[r.status]?.label ?? r.status }}
                            </span>
                        </div>

                        <div v-if="can.manageResponsibilities" class="mt-2 flex gap-3 text-xs">
                            <button
                                type="button"
                                class="font-bold text-[#381998] hover:underline dark:text-[#42b6c5]"
                                @click="openEditResponsibility(r)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="font-bold text-red-600 hover:underline"
                                @click="confirmDeleteResponsibility = r.id"
                            >
                                Remove
                            </button>
                        </div>
                    </li>
                </ul>
                <p v-else class="lms-subtitle mt-5">No responsibilities assigned yet.</p>
            </section>

            <!-- Performance -->
            <section class="lms-panel">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="lms-title text-lg">Performance reviews</h2>
                        <p class="lms-subtitle">Staff-written, visible to {{ leader.name.split(' ')[0] }}.</p>
                    </div>
                    <button
                        v-if="can.manageReviews"
                        type="button"
                        class="lms-btn-outline"
                        @click="showReviewForm = !showReviewForm"
                    >
                        {{ showReviewForm ? 'Cancel' : 'Add review' }}
                    </button>
                </div>

                <form
                    v-if="showReviewForm"
                    class="mt-4 space-y-3 rounded-xl border border-gray-100 p-4 dark:border-white/10"
                    @submit.prevent="submitReview"
                >
                    <fieldset :disabled="reviewForm.processing" class="space-y-3">
                        <div>
                            <label class="lms-label">Rating</label>
                            <div class="mt-1.5 flex gap-1">
                                <button
                                    v-for="n in 5"
                                    :key="n"
                                    type="button"
                                    :aria-label="`${n} star`"
                                    :class="[
                                        'flex h-9 w-9 items-center justify-center rounded-lg border text-sm font-bold transition-colors',
                                        reviewForm.rating >= n
                                            ? 'border-amber-400 bg-amber-400/15 text-amber-600'
                                            : 'border-gray-200 text-gray-300 dark:border-gray-700',
                                    ]"
                                    @click="reviewForm.rating = n"
                                >
                                    ★
                                </button>
                            </div>
                            <InputError :message="reviewForm.errors.rating" />
                        </div>
                        <div>
                            <label for="period_label" class="lms-label">Period (optional)</label>
                            <input
                                id="period_label"
                                v-model="reviewForm.period_label"
                                type="text"
                                placeholder="e.g. September 2026"
                                class="lms-input mt-1.5"
                            />
                        </div>
                        <div>
                            <label for="review_notes" class="lms-label">Notes</label>
                            <textarea
                                id="review_notes"
                                v-model="reviewForm.notes"
                                rows="3"
                                class="lms-input mt-1.5 resize-y"
                            />
                        </div>
                        <button type="submit" class="lms-btn-primary">
                            {{ reviewForm.processing ? 'Saving…' : 'Save review' }}
                        </button>
                    </fieldset>
                </form>

                <ul
                    v-if="leader.performance_reviews?.length"
                    class="mt-5 divide-y divide-gray-100 dark:divide-white/5"
                >
                    <li v-for="review in leader.performance_reviews" :key="review.id" class="py-3.5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-bold text-[#000928] dark:text-white">
                                    <span class="text-amber-500">{{ '★'.repeat(review.rating) }}</span
                                    ><span class="text-gray-300">{{ '★'.repeat(5 - review.rating) }}</span>
                                </p>
                                <p v-if="review.period_label" class="text-xs font-semibold text-gray-500">
                                    {{ review.period_label }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-400">
                                {{ formatDate(review.created_at, { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                <template v-if="review.reviewed_by">· {{ review.reviewed_by.name }}</template>
                            </span>
                        </div>
                        <p v-if="review.notes" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ review.notes }}
                        </p>
                        <button
                            v-if="can.manageReviews"
                            type="button"
                            class="mt-2 text-xs font-bold text-red-600 hover:underline"
                            @click="confirmDeleteReview = review.id"
                        >
                            Remove
                        </button>
                    </li>
                </ul>
                <p v-else class="lms-subtitle mt-5">No reviews yet.</p>
            </section>
        </div>

        <!-- Add/edit responsibility modal -->
        <ConfirmationModal
            :open="showResponsibilityForm"
            :title="editingResponsibility ? 'Edit responsibility' : 'Assign a responsibility'"
            :confirm-text="editingResponsibility ? 'Save changes' : 'Assign'"
            variant="default"
            :processing="responsibilityForm.processing"
            @update:open="showResponsibilityForm = $event"
            @confirm="submitResponsibility"
        >
            <template #body>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="resp_title" class="lms-label">Title *</label>
                        <input
                            id="resp_title"
                            v-model="responsibilityForm.title"
                            type="text"
                            placeholder="e.g. Run a monthly track meetup"
                            class="lms-input mt-1.5"
                        />
                        <InputError :message="responsibilityForm.errors.title" />
                    </div>
                    <div>
                        <label for="resp_description" class="lms-label">Description</label>
                        <textarea
                            id="resp_description"
                            v-model="responsibilityForm.description"
                            rows="3"
                            class="lms-input mt-1.5 resize-y"
                        />
                    </div>
                    <div>
                        <label for="resp_due" class="lms-label">Due date</label>
                        <input
                            id="resp_due"
                            v-model="responsibilityForm.due_date"
                            type="date"
                            class="lms-input mt-1.5"
                        />
                    </div>
                </div>
            </template>
        </ConfirmationModal>

        <ConfirmationModal
            :open="confirmDeleteResponsibility !== null"
            title="Remove this responsibility?"
            description="This cannot be undone."
            confirm-text="Remove"
            @update:open="confirmDeleteResponsibility = null"
            @confirm="deleteResponsibility"
        />

        <ConfirmationModal
            :open="confirmDeleteReview !== null"
            title="Remove this review?"
            description="This cannot be undone."
            confirm-text="Remove"
            @update:open="confirmDeleteReview = null"
            @confirm="deleteReview"
        />
    </div>
</template>
