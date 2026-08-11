<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const toast = useToast();

// Show each server flash message exactly once. The flash object stays set on
// page.props until the next navigation, so we dedupe by content to avoid
// re-firing when other props (notification counts, cart, etc.) change.
let lastFlashKey = '';
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) {
            return;
        }

        const key = JSON.stringify([flash.success, flash.error, flash.warning, flash.info]);
        if (key === lastFlashKey || key === '[null,null,null,null]') {
            return;
        }
        lastFlashKey = key;

        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
        if (flash.warning) {
            toast.warning(flash.warning);
        }
        if (flash.info) {
            toast.info(flash.info);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                <div class="mx-auto w-full max-w-[1400px]">
                    <slot />
                </div>
            </div>
        </AppContent>
    </AppShell>
    <Toaster />
</template>
