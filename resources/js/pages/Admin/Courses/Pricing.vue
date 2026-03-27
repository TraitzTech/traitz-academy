<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import AppLayout from '@/layouts/AppLayout.vue'

interface Plan {
  id: number
  name: string
  number_of_instalments: number
  amount_per_instalment: string
  interval_in_days: number
  is_active: boolean
}

interface Course {
  id: number
  title: string
  slug: string
  price: string
  sale_price: string | null
  is_featured: boolean
  instalmentPlans?: Plan[]
  instalment_plans?: Plan[]
  instructor: { id: number; name: string } | null
  category: { id: number; name: string; slug: string } | null
}

const props = defineProps<{ course: Course }>()

const plans = computed(() => props.course.instalment_plans ?? props.course.instalmentPlans ?? [])

defineOptions({ layout: AppLayout })

const pricingForm = useForm({
  price: props.course.price,
  sale_price: props.course.sale_price ?? '',
  is_featured: props.course.is_featured,
})

function savePricing() {
  pricingForm.put(`/admin/courses/${props.course.id}/pricing`, { preserveScroll: true })
}

const newPlan = useForm({
  name: '',
  number_of_instalments: 3,
  amount_per_instalment: '',
  interval_in_days: 30,
  is_active: true,
})
const showAddPlanModal = ref(false)

function addPlan() {
  newPlan.post(`/admin/courses/${props.course.id}/instalment-plans`, {
    preserveScroll: true,
    onSuccess: () => {
      newPlan.reset()
      showAddPlanModal.value = false
    },
  })
}

function openAddPlanModal() {
  newPlan.reset()
  showAddPlanModal.value = true
}

const editingId = ref<number | null>(null)
const editPlan = useForm({
  name: '',
  number_of_instalments: 3,
  amount_per_instalment: '',
  interval_in_days: 30,
  is_active: true,
})

function startEdit(p: Plan) {
  editingId.value = p.id
  editPlan.name = p.name
  editPlan.number_of_instalments = p.number_of_instalments
  editPlan.amount_per_instalment = p.amount_per_instalment
  editPlan.interval_in_days = p.interval_in_days
  editPlan.is_active = p.is_active
}

function saveEdit(planId: number) {
  editPlan.put(`/admin/courses/${props.course.id}/instalment-plans/${planId}`, {
    preserveScroll: true,
    onSuccess: () => { editingId.value = null },
  })
}

const deleteTarget = ref<Plan | null>(null)

function confirmDeletePlan() {
  if (!deleteTarget.value) return
  router.delete(`/admin/courses/${props.course.id}/instalment-plans/${deleteTarget.value.id}`, {
    onSuccess: () => { deleteTarget.value = null },
  })
}

function planTotal(p: Plan) {
  return p.number_of_instalments * parseFloat(p.amount_per_instalment)
}

function formatXaf(n: number) {
  return n.toLocaleString(undefined, { maximumFractionDigits: 0 })
}
</script>

<template>
  <div>
    <Head :title="`Pricing: ${course.title}`" />

    <div class="mb-6">
      <Link
        href="/admin/courses"
        class="mb-2 inline-flex items-center text-sm text-[#42b6c5] hover:text-[#35919e]"
      >
        ← Back to Courses
      </Link>
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Pricing & instalments</h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ course.title }}</p>
        </div>
        <Link
          :href="`/admin/courses/${course.id}`"
          class="text-sm font-medium text-[#381998] hover:underline dark:text-[#42b6c5]"
        >
          View course review →
        </Link>
      </div>
    </div>

    <div class="mx-auto max-w-3xl space-y-8">
      <!-- Course pricing -->
      <div class="rounded-xl bg-white p-6 shadow dark:bg-gray-800">
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Course price</h3>
        <form class="space-y-4" @submit.prevent="savePricing">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Price (XAF) *</label>
              <input
                v-model="pricingForm.price"
                type="number"
                min="0"
                step="0.01"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
              />
              <p v-if="pricingForm.errors.price" class="mt-1 text-xs text-red-600">{{ pricingForm.errors.price }}</p>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sale price (XAF)</label>
              <input
                v-model="pricingForm.sale_price"
                type="number"
                min="0"
                step="0.01"
                placeholder="Optional"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
              />
              <p v-if="pricingForm.errors.sale_price" class="mt-1 text-xs text-red-600">{{ pricingForm.errors.sale_price }}</p>
            </div>
          </div>
          <label class="flex cursor-pointer items-center gap-2">
            <input v-model="pricingForm.is_featured" type="checkbox" class="rounded border-gray-300" />
            <span class="text-sm text-gray-700 dark:text-gray-300">Featured on catalogue</span>
          </label>
          <button
            type="submit"
            :disabled="pricingForm.processing"
            class="rounded-lg bg-[#381998] px-5 py-2 text-sm font-semibold text-white hover:bg-[#000928] disabled:opacity-50"
          >
            {{ pricingForm.processing ? 'Saving…' : 'Save pricing' }}
          </button>
        </form>
      </div>

      <!-- Instalment plans -->
      <div class="rounded-xl bg-white p-6 shadow dark:bg-gray-800">
        <h3 class="mb-2 text-base font-semibold text-gray-900 dark:text-gray-100">Instalment plans</h3>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
          Optional payment schedules for this course. Students can choose a plan at checkout when enrolment is enabled.
        </p>

        <div v-if="plans.length === 0" class="mb-6 rounded-lg border border-dashed border-gray-200 py-8 text-center text-sm text-gray-400 dark:border-gray-600">
          No instalment plans yet.
        </div>

        <div v-else class="mb-6 space-y-3">
          <div
            v-for="p in plans"
            :key="p.id"
            class="rounded-lg border border-gray-100 p-4 dark:border-gray-700"
          >
            <div v-if="editingId !== p.id" class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ p.name }}</p>
                <p class="text-sm text-gray-500">
                  {{ p.number_of_instalments }} × {{ formatXaf(parseFloat(p.amount_per_instalment)) }} XAF
                  · every {{ p.interval_in_days }} days
                  · total ≈ {{ formatXaf(planTotal(p)) }} XAF
                </p>
                <span
                  :class="[
                    'mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-medium',
                    p.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500',
                  ]"
                >
                  {{ p.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <div class="flex gap-2">
                <button
                  type="button"
                  class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-[#381998] dark:hover:bg-gray-700"
                  @click="startEdit(p)"
                >
                  <Pencil class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                  @click="deleteTarget = p"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </div>

            <form v-else class="space-y-3" @submit.prevent="saveEdit(p.id)">
              <div class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                  <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Plan name</label>
                  <input v-model="editPlan.name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-gray-600"># Instalments</label>
                  <input v-model.number="editPlan.number_of_instalments" type="number" min="2" max="48" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-gray-600">Amount each (XAF)</label>
                  <input v-model="editPlan.amount_per_instalment" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-gray-600">Interval (days)</label>
                  <input v-model.number="editPlan.interval_in_days" type="number" min="1" max="365" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700" />
                </div>
                <label class="flex items-center gap-2 self-end pb-2">
                  <input v-model="editPlan.is_active" type="checkbox" class="rounded" />
                  <span class="text-sm">Active</span>
                </label>
              </div>
              <div class="flex gap-2">
                <button type="submit" :disabled="editPlan.processing" class="rounded-lg bg-[#42b6c5] px-4 py-1.5 text-xs font-semibold text-white">
                  Save
                </button>
                <button type="button" class="rounded-lg border px-4 py-1.5 text-xs" @click="editingId = null">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <div class="border-t border-gray-100 pt-6 dark:border-gray-700">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-[#000928] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#381998]"
            @click="openAddPlanModal"
          >
            <Plus class="h-4 w-4" /> Add instalment plan
          </button>
        </div>
      </div>
    </div>

    <ConfirmationModal
      :open="!!deleteTarget"
      title="Remove instalment plan"
      :description="deleteTarget ? `Remove &quot;${deleteTarget.name}&quot;?` : ''"
      confirm-text="Remove"
      variant="destructive"
      @update:open="(v) => { if (!v) deleteTarget = null }"
      @confirm="confirmDeletePlan"
    />

    <Teleport to="body">
      <div
        v-if="showAddPlanModal"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
        @click.self="showAddPlanModal = false"
      >
        <div class="w-full max-w-xl rounded-xl bg-gray-50 shadow-2xl dark:bg-gray-900">
          <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">Add instalment plan</h4>
            <button
              type="button"
              class="rounded p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
              @click="showAddPlanModal = false"
            >
              ×
            </button>
          </div>

          <form class="space-y-4 p-6" @submit.prevent="addPlan">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
              <input
                v-model="newPlan.name"
                type="text"
                placeholder="e.g. 3-month plan"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                required
              />
              <p v-if="newPlan.errors.name" class="mt-1 text-xs text-red-600">{{ newPlan.errors.name }}</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Instalments *</label>
                <input
                  v-model.number="newPlan.number_of_instalments"
                  type="number"
                  min="2"
                  max="48"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                />
                <p v-if="newPlan.errors.number_of_instalments" class="mt-1 text-xs text-red-600">{{ newPlan.errors.number_of_instalments }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Amount each (XAF) *</label>
                <input
                  v-model="newPlan.amount_per_instalment"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                  required
                />
                <p v-if="newPlan.errors.amount_per_instalment" class="mt-1 text-xs text-red-600">{{ newPlan.errors.amount_per_instalment }}</p>
              </div>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Days between payments *</label>
              <input
                v-model.number="newPlan.interval_in_days"
                type="number"
                min="1"
                max="365"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
              />
              <p v-if="newPlan.errors.interval_in_days" class="mt-1 text-xs text-red-600">{{ newPlan.errors.interval_in_days }}</p>
            </div>

            <label class="flex items-center gap-2">
              <input v-model="newPlan.is_active" type="checkbox" class="rounded" />
              <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:border-gray-400 dark:border-gray-600 dark:text-gray-300"
                @click="showAddPlanModal = false"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="newPlan.processing"
                class="rounded-lg bg-[#000928] px-4 py-2 text-sm font-semibold text-white hover:bg-[#381998] disabled:opacity-50"
              >
                {{ newPlan.processing ? 'Adding…' : 'Add plan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
