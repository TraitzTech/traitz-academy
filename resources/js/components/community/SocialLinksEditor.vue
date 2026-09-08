<script setup lang="ts">
/**
 * A free-form list of (platform label, URL) pairs — LinkedIn, X, Instagram,
 * GitHub, a personal site, or anything else a person actually uses. Unlike a
 * fixed set of named fields, this never forces someone into "linkedin/
 * github/twitter/website" when their real presence looks different.
 */
interface Row {
    key: string;
    value: string;
}

interface Props {
    modelValue: Row[];
    idPrefix?: string;
}

const props = withDefaults(defineProps<Props>(), { idPrefix: 'social' });

const emit = defineEmits<{ 'update:modelValue': [Row[]] }>();

const rows = () => props.modelValue;

const addRow = () =>
    emit('update:modelValue', [...rows(), { key: '', value: '' }]);

const removeRow = (index: number) =>
    emit(
        'update:modelValue',
        rows().filter((_, i) => i !== index),
    );

const update = (index: number, field: keyof Row, value: string) => {
    const next = rows().map((row, i) =>
        i === index ? { ...row, [field]: value } : row,
    );
    emit('update:modelValue', next);
};

const suggestions = [
    'LinkedIn',
    'X (Twitter)',
    'Instagram',
    'GitHub',
    'Website',
    'Facebook',
    'YouTube',
    'TikTok',
];
</script>

<template>
    <div class="space-y-2.5">
        <div
            v-for="(row, index) in modelValue"
            :key="index"
            class="flex gap-2"
        >
            <input
                :id="`${idPrefix}_label_${index}`"
                :value="row.key"
                type="text"
                list="social-platform-suggestions"
                placeholder="Platform (e.g. LinkedIn)"
                class="lms-input w-40 shrink-0"
                :aria-label="`Platform ${index + 1}`"
                @input="
                    update(
                        index,
                        'key',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />
            <input
                :id="`${idPrefix}_url_${index}`"
                :value="row.value"
                type="url"
                placeholder="https://…"
                class="lms-input flex-1"
                :aria-label="`Link ${index + 1}`"
                @input="
                    update(
                        index,
                        'value',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />
            <button
                type="button"
                class="shrink-0 rounded-xl border border-gray-200 px-3 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50"
                :aria-label="`Remove link ${index + 1}`"
                @click="removeRow(index)"
            >
                ✕
            </button>
        </div>

        <datalist id="social-platform-suggestions">
            <option v-for="s in suggestions" :key="s" :value="s" />
        </datalist>

        <button
            type="button"
            class="text-sm font-bold text-[#381998] hover:underline"
            @click="addRow"
        >
            + Add a link
        </button>
    </div>
</template>
