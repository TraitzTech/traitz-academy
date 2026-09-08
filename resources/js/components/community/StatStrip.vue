<script setup lang="ts">
interface Stat {
    label: string;
    value: number | string;
    suffix?: string;
}

interface Props {
    stats: Stat[];
    /** `dark` sits on the navy hero; `light` sits on a white section. */
    tone?: 'dark' | 'light';
}

withDefaults(defineProps<Props>(), { tone: 'dark' });

const format = (value: number | string) =>
    typeof value === 'number'
        ? new Intl.NumberFormat('en-US').format(value)
        : value;
</script>

<template>
    <dl
        :class="[
            'grid gap-px overflow-hidden rounded-2xl',
            tone === 'dark'
                ? 'border border-white/10 bg-white/10'
                : 'border border-gray-200 bg-gray-200',
            stats.length >= 5
                ? 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5'
                : stats.length === 4
                  ? 'grid-cols-2 lg:grid-cols-4'
                  : 'grid-cols-1 sm:grid-cols-3',
        ]"
    >
        <div
            v-for="stat in stats"
            :key="stat.label"
            :class="[
                'px-4 py-5 text-center sm:px-6',
                tone === 'dark'
                    ? 'bg-[#000928]'
                    : 'bg-white',
            ]"
        >
            <dd
                :class="[
                    'text-2xl font-black tracking-tight sm:text-3xl',
                    tone === 'dark' ? 'text-white' : 'text-[#000928]',
                ]"
            >
                {{ format(stat.value)
                }}<span class="text-[#42b6c5]">{{ stat.suffix ?? '' }}</span>
            </dd>
            <dt
                :class="[
                    'mt-1 text-[11px] font-semibold tracking-wider uppercase',
                    tone === 'dark'
                        ? 'text-white/50'
                        : 'text-gray-500',
                ]"
            >
                {{ stat.label }}
            </dt>
        </div>
    </dl>
</template>
