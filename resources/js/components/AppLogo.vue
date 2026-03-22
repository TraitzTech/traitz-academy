<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as Record<string, string> | undefined);
const logoUrl = computed(() => siteSettings.value?.logo_url || '/images/Tratz Academy-Horizontal Profile.svg');
const siteName = computed(() => siteSettings.value?.site_title || 'Traitz Academy');
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden bg-white"
    >
        <img :src="logoUrl" :alt="siteName" class="size-8 object-contain" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ siteName }}</span>
    </div>
</template>
