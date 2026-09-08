<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { reactive, watch } from 'vue';

import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { type NavGroup, type NavItem } from '@/types';

const props = defineProps<{
    groups: NavGroup[];
    standalone?: NavItem[];
}>();

const { urlIsActive } = useActiveUrl();

function isGroupActive(group: NavGroup): boolean {
    if (
        group.items?.some((item) =>
            urlIsActive(item.href, undefined, item.activeMatch ?? 'exact'),
        )
    )
        return true;
    return group.groups?.some(isGroupActive) ?? false;
}

// Only groups that explicitly opt in (e.g. AI Forge) collapse; every other
// group renders flat/always-expanded exactly as before. State persists per
// label in localStorage so a collapsed group stays collapsed across visits.
const STORAGE_KEY = 'sidebar:group-open-state';

function loadStoredState(): Record<string, boolean> {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}');
    } catch {
        return {};
    }
}

const stored = loadStoredState();
const openState = reactive<Record<string, boolean>>({});
function registerOpenState(group: NavGroup) {
    if (group.collapsible) {
        openState[group.label] =
            stored[group.label] ??
            isGroupActive(group) ??
            group.defaultOpen ??
            true;
    }
    group.groups?.forEach(registerOpenState);
}
props.groups.forEach(registerOpenState);

watch(
    openState,
    (value) => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
    },
    { deep: true },
);
</script>

<template>
    <!-- Standalone items (e.g. Dashboard) -->
    <SidebarGroup v-if="standalone?.length" class="px-2 py-0">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in standalone" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="
                        urlIsActive(
                            item.href,
                            undefined,
                            item.activeMatch ?? 'exact',
                        )
                    "
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <!-- Top-level groups -->
    <SidebarGroup v-for="group in groups" :key="group.label" class="px-2 py-0">
        <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>

        <SidebarMenu v-if="group.items?.length">
            <SidebarMenuItem v-for="item in group.items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="
                        urlIsActive(
                            item.href,
                            undefined,
                            item.activeMatch ?? 'exact',
                        )
                    "
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                        <span
                            v-if="item.badge"
                            class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>

        <!-- Nested collapsible subgroups (e.g. "AI Forge" under "Events") -->
        <SidebarMenu v-if="group.groups?.length">
            <SidebarMenuItem v-for="sub in group.groups" :key="sub.label">
                <Collapsible v-model:open="openState[sub.label]">
                    <CollapsibleTrigger
                        class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left transition-colors hover:bg-sidebar-accent/50"
                    >
                        <component
                            :is="sub.icon"
                            v-if="sub.icon"
                            class="size-4 shrink-0 text-sidebar-foreground/70"
                        />
                        <span class="text-sm font-medium">{{ sub.label }}</span>
                        <ChevronRight
                            class="ml-auto size-4 shrink-0 text-sidebar-foreground/50 transition-transform duration-200"
                            :class="{ 'rotate-90': openState[sub.label] }"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenu>
                            <SidebarMenuItem
                                v-for="item in sub.items"
                                :key="item.title"
                            >
                                <SidebarMenuButton
                                    as-child
                                    :is-active="
                                        urlIsActive(
                                            item.href,
                                            undefined,
                                            item.activeMatch ?? 'exact',
                                        )
                                    "
                                    :tooltip="item.title"
                                >
                                    <Link :href="item.href">
                                        <component :is="item.icon" />
                                        <span>{{ item.title }}</span>
                                        <span
                                            v-if="item.badge"
                                            class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white"
                                        >
                                            {{ item.badge }}
                                        </span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </CollapsibleContent>
                </Collapsible>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
