<script setup lang="ts">
import type { PageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface AppPageProps extends PageProps {
    flash?: {
        success?: string;
    };
}

interface OptionalPref {
    key: string;
    label: string;
    enabled: boolean;
}

interface Props {
    optionalPreferences: OptionalPref[];
    mandatoryNotice: string;
}

const props = defineProps<Props>();

const page = usePage<AppPageProps>();

const flashSuccess = computed(() => page.props.flash?.success);

const preferences = ref<Record<string, boolean>>({});
for (const row of props.optionalPreferences) {
    preferences.value[row.key] = row.enabled;
}

watch(
    () => props.optionalPreferences,
    (rows: OptionalPref[]) => {
        preferences.value = {};
        for (const row of rows) {
            preferences.value[row.key] = row.enabled;
        }
    },
    { deep: true },
);

function setPreferenceChecked(key: string, value: boolean | 'indeterminate'): void {
    preferences.value[key] = value === true;
}

const processing = ref(false);

const submit = () => {
    processing.value = true;
    router.patch(
        '/settings/notifications',
        { preferences: preferences.value },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notifications',
        href: '/settings/notifications',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Notification settings" />

        <h1 class="sr-only">Notification settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Notifications"
                    description="Choose which optional alerts you receive in the app and by email."
                />

                <div
                    v-if="flashSuccess"
                    class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100"
                >
                    {{ flashSuccess }}
                </div>

                <p class="text-sm text-muted-foreground">
                    {{ mandatoryNotice }}
                </p>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="space-y-4">
                        <div
                            v-for="row in optionalPreferences"
                            :key="row.key"
                            class="flex flex-row items-center justify-between gap-4 rounded-lg border p-4"
                        >
                            <Label :for="`pref-${row.key}`" class="text-base leading-snug">{{ row.label }}</Label>
                            <Checkbox
                                :id="`pref-${row.key}`"
                                :checked="preferences[row.key]"
                                class="size-5"
                                @update:checked="setPreferenceChecked(row.key, $event)"
                            />
                        </div>
                    </div>

                    <Button type="submit" :disabled="processing">Save preferences</Button>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
