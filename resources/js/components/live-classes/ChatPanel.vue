<script setup lang="ts">
import { ref, watch } from 'vue';

interface ChatMessage {
    id: number;
    user_name: string | null;
    user_role: string | null;
    message: string;
    created_at: string | null;
}

const props = defineProps<{
    messages: ChatMessage[];
    canPost?: boolean;
    posting?: boolean;
    connectionMode?: 'realtime' | 'fallback';
}>();

const emit = defineEmits<{
    send: [message: string];
}>();

const draft = ref('');
const bodyRef = ref<HTMLDivElement | null>(null);

function submitMessage() {
    const text = draft.value.trim();
    if (!text) return;
    emit('send', text);
    draft.value = '';
}

watch(
    () => props.messages.length,
    () => {
        if (!bodyRef.value) return;
        bodyRef.value.scrollTop = bodyRef.value.scrollHeight;
    },
);

const when = (value: string | null) =>
    value ? new Date(value).toLocaleTimeString() : '';
</script>

<template>
    <aside
        class="flex h-full min-h-[360px] flex-col rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    >
        <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-semibold text-[#000928] dark:text-white">
                    Live chat
                </p>
                <span
                    :class="[
                        'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                        props.connectionMode === 'realtime'
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                    ]"
                >
                    {{
                        props.connectionMode === 'realtime'
                            ? 'Realtime connected'
                            : 'Using fallback sync'
                    }}
                </span>
            </div>
        </div>
        <div ref="bodyRef" class="flex-1 space-y-2 overflow-y-auto px-3 py-3">
            <div
                v-if="messages.length === 0"
                class="rounded-lg border border-dashed border-gray-200 p-3 text-xs text-gray-500 dark:border-gray-600"
            >
                No messages yet. Ask your question to start the conversation.
            </div>
            <div
                v-for="m in messages"
                :key="m.id"
                class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/40"
            >
                <div class="mb-1 flex items-center justify-between gap-2">
                    <p
                        :class="[
                            'text-xs font-semibold',
                            m.user_role === 'tutor'
                                ? 'text-[#381998]'
                                : 'text-gray-700 dark:text-gray-200',
                        ]"
                    >
                        {{ m.user_name || 'User' }}
                    </p>
                    <span class="text-[11px] text-gray-400">{{
                        when(m.created_at)
                    }}</span>
                </div>
                <p
                    class="text-sm whitespace-pre-wrap text-gray-700 dark:text-gray-200"
                >
                    {{ m.message }}
                </p>
            </div>
        </div>
        <form
            v-if="canPost"
            class="border-t border-gray-100 p-3 dark:border-gray-700"
            @submit.prevent="submitMessage"
        >
            <div class="flex items-end gap-2">
                <textarea
                    v-model="draft"
                    rows="2"
                    placeholder="Type a message..."
                    class="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:ring-2 focus:ring-[#42b6c5]/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                />
                <button
                    type="submit"
                    :disabled="posting || !draft.trim()"
                    class="rounded-lg bg-[#381998] px-3 py-2 text-xs font-semibold text-white disabled:opacity-50"
                >
                    {{ posting ? 'Sending...' : 'Send' }}
                </button>
            </div>
        </form>
    </aside>
</template>
