<script setup lang="ts">
import { useToast } from '@/composables/useToast'

const { toasts, removeToast } = useToast()

const getToastClasses = (type: string) => {
  const base = 'relative overflow-hidden flex items-start gap-3 px-4 py-3 rounded-xl border shadow-xl backdrop-blur-md'
  switch (type) {
    case 'success':
      return `${base} bg-gradient-to-r from-emerald-50 to-green-50 border-emerald-200 text-emerald-900`
    case 'error':
      return `${base} bg-gradient-to-r from-rose-50 to-red-50 border-rose-200 text-rose-900`
    case 'warning':
      return `${base} bg-gradient-to-r from-amber-50 to-yellow-50 border-amber-200 text-amber-900`
    case 'info':
      return `${base} bg-gradient-to-r from-sky-50 to-cyan-50 border-sky-200 text-sky-900`
    default:
      return `${base} bg-gradient-to-r from-gray-50 to-slate-50 border-gray-200 text-gray-900`
  }
}

const getIcon = (type: string) => {
  switch (type) {
    case 'success':
      return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    case 'error':
      return 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
    case 'warning':
      return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
    case 'info':
      return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    default:
      return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
  }
}

const getIconColor = (type: string) => {
  switch (type) {
    case 'success': return 'text-emerald-600 bg-emerald-100'
    case 'error': return 'text-rose-600 bg-rose-100'
    case 'warning': return 'text-amber-600 bg-amber-100'
    case 'info': return 'text-sky-600 bg-sky-100'
    default: return 'text-gray-600 bg-gray-100'
  }
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="getToastClasses(toast.type)"
          class="pointer-events-auto animate-in slide-in-from-right-5 ring-1 ring-black/5"
        >
          <div :class="['w-8 h-8 rounded-lg grid place-items-center flex-shrink-0', getIconColor(toast.type)]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(toast.type)" />
            </svg>
          </div>
          <span class="flex-1 text-sm font-semibold leading-5">{{ toast.message }}</span>
          <button
            @click="removeToast(toast.id)"
            class="flex-shrink-0 p-1 rounded-md hover:bg-black/10 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>
