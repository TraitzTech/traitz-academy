<script setup lang="ts">
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Bold,
    Code,
    Eraser,
    Heading1,
    Heading2,
    Heading3,
    Image as ImageIcon,
    IndentDecrease,
    IndentIncrease,
    Italic,
    Link2Off,
    Link as LinkIcon,
    List,
    ListOrdered,
    Minus,
    MoreHorizontal,
    Pilcrow,
    Quote,
    Redo2,
    Strikethrough,
    Subscript,
    Superscript,
    Underline,
    Undo2,
    Upload,
} from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

interface Props {
    modelValue: string;
    placeholder?: string;
    uploadUrl?: string;
    disabled?: boolean;
    /** Extra classes for the editable surface (e.g. taller min-height for lesson bodies). */
    bodyClass?: string;
    /** Start with just the essentials (bold/italic/list/image) behind a "more" toggle — for light, everyday writing rather than long-form content. */
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Write your email content...',
    uploadUrl: '/admin/emails/media',
    disabled: false,
    bodyClass: '',
    compact: false,
});

const showFullToolbar = ref(!props.compact);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'upload-error', value: string): void;
}>();

const editorRef = ref<HTMLDivElement | null>(null);
const mediaInputRef = ref<HTMLInputElement | null>(null);
const isUploading = ref(false);

/** Contenteditable DOM changes are not reactive; track placeholder with explicit updates. */
const showPlaceholder = ref(true);

function isEditorMeaningfullyEmpty(editor: HTMLDivElement | null): boolean {
    if (!editor) {
        return true;
    }

    const raw = editor.textContent ?? '';
    const normalized = raw
        .replace(/\u00a0/g, ' ')
        .replace(/\u200b/g, '')
        .trim();
    const hasImage = editor.querySelector('img') !== null;

    return normalized.length === 0 && !hasImage;
}

function refreshPlaceholderState(): void {
    showPlaceholder.value = isEditorMeaningfullyEmpty(editorRef.value);
}

function onEditorInput(): void {
    refreshPlaceholderState();
    emit('update:modelValue', editorRef.value?.innerHTML || '');
}

function onEditorPaste(): void {
    nextTick(() => {
        refreshPlaceholderState();
        emit('update:modelValue', editorRef.value?.innerHTML || '');
    });
}

const syncEditorFromModel = async (value: string) => {
    await nextTick();

    if (!editorRef.value) return;
    if (editorRef.value.innerHTML === value) return;

    editorRef.value.innerHTML = value || '';
    refreshPlaceholderState();
};

watch(
    () => props.modelValue,
    (value) => {
        void syncEditorFromModel(value);
    },
    { immediate: true },
);

const emitContent = () => {
    refreshPlaceholderState();
    emit('update:modelValue', editorRef.value?.innerHTML || '');
};

const focusEditor = () => {
    editorRef.value?.focus();
};

const runCommand = (command: string, value: string | null = null) => {
    if (props.disabled) return;

    focusEditor();
    document.execCommand(command, false, value);
    emitContent();
};

const formatHeading = (tag: 'h2' | 'h3' | 'p') => {
    runCommand('formatBlock', `<${tag}>`);
};

const formatLargeHeading = () => {
    runCommand('formatBlock', '<h1>');
};

const formatCodeBlock = () => {
    runCommand('formatBlock', '<pre>');
};

const align = (direction: 'left' | 'center' | 'right' | 'justify') => {
    const commandMap = {
        left: 'justifyLeft',
        center: 'justifyCenter',
        right: 'justifyRight',
        justify: 'justifyFull',
    } as const;

    runCommand(commandMap[direction]);
};

const unlink = () => {
    runCommand('unlink');
};

const insertHorizontalRule = () => {
    runCommand('insertHorizontalRule');
};

const insertLink = () => {
    if (props.disabled) return;

    const url = window.prompt('Enter link URL (https://...)');
    if (!url) return;

    runCommand('createLink', url);
};

const insertImageUrl = () => {
    if (props.disabled) return;

    const url = window.prompt('Enter image URL (https://...)');
    if (!url) return;

    runCommand('insertImage', url);
};

const triggerMediaUpload = () => {
    if (props.disabled || isUploading.value) return;
    mediaInputRef.value?.click();
};

const uploadMedia = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    target.value = '';

    if (!file) return;

    isUploading.value = true;

    try {
        const formData = new FormData();
        formData.append('media', file);

        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';

        const response = await fetch(props.uploadUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const payload = await response.json();

        if (!response.ok) {
            const message = payload?.message || 'Failed to upload image.';
            emit('upload-error', message);
            return;
        }

        if (!payload?.url) {
            emit('upload-error', 'Upload succeeded but URL was not returned.');
            return;
        }

        runCommand('insertImage', payload.url as string);
    } catch {
        emit('upload-error', 'Image upload failed. Please try again.');
    } finally {
        isUploading.value = false;
    }
};
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-gray-200 bg-white transition-colors focus-within:border-[#42b6c5] focus-within:ring-2 focus-within:ring-[#42b6c5]/20 dark:border-gray-600 dark:bg-gray-700"
    >
        <div
            class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50/80 p-1.5 dark:border-gray-600 dark:bg-gray-800/60"
        >
            <!-- Always-visible essentials -->
            <div class="flex items-center gap-0.5">
                <button
                    type="button"
                    class="editor-btn"
                    title="Bold"
                    :disabled="disabled"
                    @mousedown.prevent="runCommand('bold')"
                >
                    <Bold class="h-3.5 w-3.5" />
                </button>
                <button
                    type="button"
                    class="editor-btn"
                    title="Italic"
                    :disabled="disabled"
                    @mousedown.prevent="runCommand('italic')"
                >
                    <Italic class="h-3.5 w-3.5" />
                </button>
                <button
                    type="button"
                    class="editor-btn"
                    title="Bullet list"
                    :disabled="disabled"
                    @mousedown.prevent="runCommand('insertUnorderedList')"
                >
                    <List class="h-3.5 w-3.5" />
                </button>
                <button
                    type="button"
                    class="editor-btn"
                    :title="isUploading ? 'Uploading…' : 'Upload image'"
                    :disabled="disabled || isUploading"
                    @mousedown.prevent="triggerMediaUpload"
                >
                    <Upload
                        :class="[
                            'h-3.5 w-3.5',
                            isUploading ? 'animate-pulse' : '',
                        ]"
                    />
                </button>
            </div>

            <button
                v-if="compact"
                type="button"
                class="editor-btn"
                :title="
                    showFullToolbar
                        ? 'Fewer options'
                        : 'More formatting options'
                "
                @mousedown.prevent="showFullToolbar = !showFullToolbar"
            >
                <MoreHorizontal class="h-3.5 w-3.5" />
            </button>

            <template v-if="showFullToolbar">
                <div
                    class="mx-1 h-5 w-px shrink-0 bg-gray-300 dark:bg-gray-500"
                ></div>

                <div class="flex items-center gap-0.5">
                    <button
                        type="button"
                        class="editor-btn"
                        title="Underline"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('underline')"
                    >
                        <Underline class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Strikethrough"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('strikeThrough')"
                    >
                        <Strikethrough class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Subscript"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('subscript')"
                    >
                        <Subscript class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Superscript"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('superscript')"
                    >
                        <Superscript class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div
                    class="mx-1 h-5 w-px shrink-0 bg-gray-300 dark:bg-gray-500"
                ></div>

                <div class="flex items-center gap-0.5">
                    <button
                        type="button"
                        class="editor-btn"
                        title="Heading 1"
                        :disabled="disabled"
                        @mousedown.prevent="formatLargeHeading"
                    >
                        <Heading1 class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Heading 2"
                        :disabled="disabled"
                        @mousedown.prevent="formatHeading('h2')"
                    >
                        <Heading2 class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Heading 3"
                        :disabled="disabled"
                        @mousedown.prevent="formatHeading('h3')"
                    >
                        <Heading3 class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Paragraph"
                        :disabled="disabled"
                        @mousedown.prevent="formatHeading('p')"
                    >
                        <Pilcrow class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Code block"
                        :disabled="disabled"
                        @mousedown.prevent="formatCodeBlock"
                    >
                        <Code class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div
                    class="mx-1 h-5 w-px shrink-0 bg-gray-300 dark:bg-gray-500"
                ></div>

                <div class="flex items-center gap-0.5">
                    <button
                        type="button"
                        class="editor-btn"
                        title="Numbered list"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('insertOrderedList')"
                    >
                        <ListOrdered class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Indent"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('indent')"
                    >
                        <IndentIncrease class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Outdent"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('outdent')"
                    >
                        <IndentDecrease class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Quote"
                        :disabled="disabled"
                        @mousedown.prevent="
                            runCommand('formatBlock', '<blockquote>')
                        "
                    >
                        <Quote class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Horizontal rule"
                        :disabled="disabled"
                        @mousedown.prevent="insertHorizontalRule"
                    >
                        <Minus class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div
                    class="mx-1 h-5 w-px shrink-0 bg-gray-300 dark:bg-gray-500"
                ></div>

                <div class="flex items-center gap-0.5">
                    <button
                        type="button"
                        class="editor-btn"
                        title="Align left"
                        :disabled="disabled"
                        @mousedown.prevent="align('left')"
                    >
                        <AlignLeft class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Align center"
                        :disabled="disabled"
                        @mousedown.prevent="align('center')"
                    >
                        <AlignCenter class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Align right"
                        :disabled="disabled"
                        @mousedown.prevent="align('right')"
                    >
                        <AlignRight class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Justify"
                        :disabled="disabled"
                        @mousedown.prevent="align('justify')"
                    >
                        <AlignJustify class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div
                    class="mx-1 h-5 w-px shrink-0 bg-gray-300 dark:bg-gray-500"
                ></div>

                <div class="flex items-center gap-0.5">
                    <button
                        type="button"
                        class="editor-btn"
                        title="Insert link"
                        :disabled="disabled"
                        @mousedown.prevent="insertLink"
                    >
                        <LinkIcon class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Remove link"
                        :disabled="disabled"
                        @mousedown.prevent="unlink"
                    >
                        <Link2Off class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Insert image from URL"
                        :disabled="disabled"
                        @mousedown.prevent="insertImageUrl"
                    >
                        <ImageIcon class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div
                    class="mx-1 h-5 w-px shrink-0 bg-gray-300 dark:bg-gray-500"
                ></div>

                <div class="flex items-center gap-0.5">
                    <button
                        type="button"
                        class="editor-btn"
                        title="Clear formatting"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('removeFormat')"
                    >
                        <Eraser class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Undo"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('undo')"
                    >
                        <Undo2 class="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        class="editor-btn"
                        title="Redo"
                        :disabled="disabled"
                        @mousedown.prevent="runCommand('redo')"
                    >
                        <Redo2 class="h-3.5 w-3.5" />
                    </button>
                </div>
            </template>
        </div>

        <div class="relative">
            <div
                ref="editorRef"
                :contenteditable="!disabled"
                :class="[
                    'rich-editor overflow-y-auto px-4 py-3 text-gray-900 outline-none dark:text-gray-100',
                    bodyClass || 'max-h-[500px] min-h-[220px]',
                    disabled
                        ? 'cursor-not-allowed bg-gray-100 dark:bg-gray-800'
                        : '',
                ]"
                @input="onEditorInput"
                @compositionend="onEditorInput"
                @paste="onEditorPaste"
            ></div>

            <p
                v-if="showPlaceholder"
                class="pointer-events-none absolute top-3 left-4 text-sm text-gray-400 dark:text-gray-500"
            >
                {{ placeholder }}
            </p>
        </div>

        <input
            ref="mediaInputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="uploadMedia"
        />
    </div>
</template>

<style scoped>
.editor-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.375rem;
    border: 1px solid transparent;
    color: rgb(75 85 99);
    background: transparent;
    transition:
        background-color 120ms ease,
        color 120ms ease,
        opacity 120ms ease;
}

.editor-btn:hover:not(:disabled) {
    background-color: rgb(255 255 255);
    border-color: rgb(209 213 219);
    color: #381998;
}

.editor-btn:active:not(:disabled) {
    background-color: rgb(237 233 254);
}

.editor-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

:global(.dark) .editor-btn {
    color: rgb(209 213 219);
}

:global(.dark) .editor-btn:hover:not(:disabled) {
    background-color: rgb(55 65 81);
    border-color: rgb(107 114 128);
    color: #7fe0ec;
}

:global(.dark) .editor-btn:active:not(:disabled) {
    background-color: rgb(67 56 202 / 0.35);
}

:deep(.rich-editor h1),
:deep(.rich-editor h2),
:deep(.rich-editor h3) {
    font-weight: 700;
    line-height: 1.3;
    margin: 0.65rem 0;
    color: inherit;
}

:deep(.rich-editor h1) {
    font-size: 1.5rem;
}

:deep(.rich-editor h2) {
    font-size: 1.25rem;
}

:deep(.rich-editor h3) {
    font-size: 1.1rem;
}

:deep(.rich-editor p) {
    margin: 0.2rem 0 0.75rem;
    color: inherit;
}

:deep(.rich-editor ul),
:deep(.rich-editor ol) {
    padding-left: 1.4rem;
    margin: 0.5rem 0 0.75rem;
    color: inherit;
}

:deep(.rich-editor ul) {
    list-style: disc;
}

:deep(.rich-editor ol) {
    list-style: decimal;
}

:deep(.rich-editor li) {
    margin-bottom: 0.25rem;
}

:deep(.rich-editor li::marker) {
    color: #42b6c5;
}

:deep(.rich-editor blockquote) {
    border-left: 3px solid #42b6c5;
    padding-left: 0.75rem;
    color: #4b5563;
    margin: 0.5rem 0 0.75rem;
}

:deep(.rich-editor pre) {
    margin: 0.6rem 0 0.9rem;
    padding: 0.65rem 0.8rem;
    border-radius: 0.5rem;
    background: rgba(17, 24, 39, 0.06);
    overflow-x: auto;
    font-size: 0.85rem;
    line-height: 1.5;
}

:deep(.rich-editor code) {
    font-family:
        ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
        'Liberation Mono', 'Courier New', monospace;
}

:deep(.rich-editor hr) {
    border: 0;
    border-top: 1px solid rgba(107, 114, 128, 0.35);
    margin: 0.9rem 0;
}

:deep(.rich-editor a) {
    color: #0f94a2;
    text-decoration: underline;
}

:deep(.rich-editor img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 0.5rem 0;
}

:global(.dark) :deep(.rich-editor li::marker) {
    color: #6ad7e5;
}

:global(.dark) :deep(.rich-editor blockquote) {
    color: rgb(209 213 219);
}

:global(.dark) :deep(.rich-editor pre) {
    background: rgba(255, 255, 255, 0.09);
}

:global(.dark) :deep(.rich-editor hr) {
    border-top-color: rgba(209, 213, 219, 0.35);
}

:global(.dark) :deep(.rich-editor a) {
    color: #7fe0ec;
}
</style>
