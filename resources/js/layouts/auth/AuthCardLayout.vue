<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings as Record<string, string> | undefined);
const logoUrl = computed(() => siteSettings.value?.logo_url || '/images/Tratz Academy-Horizontal Profile.svg');
const siteName = computed(() => siteSettings.value?.site_title || 'Traitz Academy');
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
    >
        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium"
            >
                <div class="flex h-12 w-auto items-center justify-center">
                    <img 
                        :src="logoUrl" 
                        :alt="siteName" 
                        class="h-12 w-auto object-contain"
                    />
                </div>
            </Link>

            <div class="flex flex-col gap-6">
                <Card class="rounded-xl">
                    <CardHeader class="px-10 pt-8 pb-0 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-10 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
