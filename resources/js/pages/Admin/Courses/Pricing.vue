<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

interface Course {
  id: number
  title: string
  slug: string
  price: string
  sale_price: string | null
  max_installments: number
  is_featured: boolean
  instructor: { id: number; name: string } | null
  category: { id: number; name: string; slug: string } | null
}

const props = defineProps<{ course: Course }>()

defineOptions({ layout: AppLayout })

const pricingForm = useForm({
  price: props.course.price,
  sale_price: props.course.sale_price ?? '',
  max_installments: props.course.max_installments ?? 1,
  is_featured: props.course.is_featured,
})

pricingForm.transform((data) => ({
  ...data,
  sale_price: data.sale_price === '' || data.sale_price === null ? null : data.sale_price,
}))

const priceNum = computed(() => parseFloat(String(pricingForm.price)) || 0)
const approxPerInstallment = computed(() => {
  const max = Math.max(1, Number(pricingForm.max_installments) || 1)
  if (priceNum.value <= 0) return 0
  const sale = pricingForm.sale_price === '' || pricingForm.sale_price === null
    ? null
    : parseFloat(String(pricingForm.sale_price))
  const effective = sale != null && sale > 0 && sale < priceNum.value ? sale : priceNum.value
  return Math.round((effective / max) * 100) / 100
})

function savePricing() {
  pricingForm.put(`/admin/courses/${props.course.id}/pricing`, { preserveScroll: true })
}
</script>

<template>
  <div class="mx-auto max-w-5xl">
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
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Pricing &amp; installments</h2>
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

    <div class="mx-auto max-w-3xl">
      <div class="rounded-xl bg-white p-6 shadow dark:bg-gray-800">
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
          Same model as programs: set the course price and how many installments are allowed. Each installment is the total fee divided by this number (before online surcharge at checkout).
        </p>
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

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Max installments *</label>
            <input
              v-model.number="pricingForm.max_installments"
              type="number"
              min="1"
              max="12"
              :disabled="priceNum <= 0"
              class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 disabled:opacity-60"
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Set to 1 for one-time payment. Free courses always use 1.
            </p>
            <p
              v-if="priceNum > 0 && (pricingForm.max_installments ?? 1) > 1"
              class="mt-2 text-sm text-gray-600 dark:text-gray-300"
            >
              ≈ {{ approxPerInstallment.toLocaleString() }} XAF per installment (of {{ pricingForm.max_installments }}).
            </p>
            <p v-if="pricingForm.errors.max_installments" class="mt-1 text-xs text-red-600">{{ pricingForm.errors.max_installments }}</p>
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
    </div>
  </div>
</template>
