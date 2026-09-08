<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';

/**
 * Wraps every public TAC page in the site's normal header/footer chrome.
 * Community navigation lives in the main header's "Community" dropdown
 * (see PublicLayout) rather than a second nav bar underneath it — a page
 * shouldn't feel like it's nested inside its own sub-site.
 */
defineProps<{
    /** Reserved for future use (e.g. breadcrumbs); not rendered here. */
    active?: string;
    bare?: boolean;
}>();
</script>

<template>
    <PublicLayout>
        <div class="tac-page">
            <slot />
        </div>
    </PublicLayout>
</template>
