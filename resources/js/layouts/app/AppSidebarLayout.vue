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

watch(
    () => [
        page.props.flash?.success,
        page.props.flash?.error,
        page.props.flash?.warning,
        page.props.flash?.info,
    ],
    ([success, error, warning, info]) => {
        if (success) {
            toast.success(success);
        }
        if (error) {
            toast.error(error);
        }
        if (warning) {
            toast.warning(warning);
        }
        if (info) {
            toast.info(info);
        }
    },
    { immediate: true },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="px-4 sm:px-6 lg:px-8 py-6">
                <slot />
            </div>
        </AppContent>
    </AppShell>
    <Toaster />
</template>
