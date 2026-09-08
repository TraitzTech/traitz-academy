<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
    icon?: 'calendar' | 'users' | 'search' | 'trophy';
}

withDefaults(defineProps<Props>(), { description: '', icon: 'calendar' });
</script>

<template>
    <div
        class="flex flex-col items-center rounded-2xl border border-dashed border-gray-300 bg-gray-50/60 px-6 py-14 text-center"
    >
        <span
            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#42b6c5]/10 text-[#42b6c5]"
            aria-hidden="true"
        >
            <svg
                class="h-7 w-7"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                viewBox="0 0 24 24"
            >
                <path
                    v-if="icon === 'calendar'"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
                <path
                    v-else-if="icon === 'users'"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                />
                <path
                    v-else-if="icon === 'trophy'"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 21h8m-4-4v4m6-17H6v5a6 6 0 0012 0V4zm0 0h3v3a3 3 0 01-3 3M6 4H3v3a3 3 0 003 3"
                />
                <path
                    v-else
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
            </svg>
        </span>

        <h3 class="mt-4 text-base font-bold text-[#000928]">
            {{ title }}
        </h3>
        <p
            v-if="description"
            class="mt-1.5 max-w-md text-sm text-gray-500"
        >
            {{ description }}
        </p>

        <div class="mt-5"><slot /></div>
    </div>
</template>
