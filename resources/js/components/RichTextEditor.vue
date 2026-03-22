<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'

interface Props {
  modelValue: string
  placeholder?: string
  uploadUrl?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Write your email content...',
  uploadUrl: '/admin/emails/media',
  disabled: false,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'upload-error', value: string): void
}>()

const editorRef = ref<HTMLDivElement | null>(null)
const mediaInputRef = ref<HTMLInputElement | null>(null)
const isUploading = ref(false)

const isEmpty = computed(() => {
  const editor = editorRef.value
  if (!editor) return true

  const text = editor.textContent?.trim() || ''
  const hasImage = editor.querySelector('img') !== null

  return text.length === 0 && !hasImage
})

const syncEditorFromModel = async (value: string) => {
  await nextTick()

  if (!editorRef.value) return
  if (editorRef.value.innerHTML === value) return

  editorRef.value.innerHTML = value || ''
}

watch(
  () => props.modelValue,
  (value) => {
    void syncEditorFromModel(value)
  },
  { immediate: true },
)

const emitContent = () => {
  emit('update:modelValue', editorRef.value?.innerHTML || '')
}

const focusEditor = () => {
  editorRef.value?.focus()
}

const runCommand = (command: string, value: string | null = null) => {
  if (props.disabled) return

  focusEditor()
  document.execCommand(command, false, value)
  emitContent()
}

const formatHeading = (tag: 'h2' | 'h3' | 'p') => {
  runCommand('formatBlock', `<${tag}>`)
}

const formatLargeHeading = () => {
  runCommand('formatBlock', '<h1>')
}

const formatCodeBlock = () => {
  runCommand('formatBlock', '<pre>')
}

const align = (direction: 'left' | 'center' | 'right' | 'justify') => {
  const commandMap = {
    left: 'justifyLeft',
    center: 'justifyCenter',
    right: 'justifyRight',
    justify: 'justifyFull',
  } as const

  runCommand(commandMap[direction])
}

const unlink = () => {
  runCommand('unlink')
}

const insertHorizontalRule = () => {
  runCommand('insertHorizontalRule')
}

const insertLink = () => {
  if (props.disabled) return

  const url = window.prompt('Enter link URL (https://...)')
  if (!url) return

  runCommand('createLink', url)
}

const insertImageUrl = () => {
  if (props.disabled) return

  const url = window.prompt('Enter image URL (https://...)')
  if (!url) return

  runCommand('insertImage', url)
}

const triggerMediaUpload = () => {
  if (props.disabled || isUploading.value) return
  mediaInputRef.value?.click()
}

const uploadMedia = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  target.value = ''

  if (!file) return

  isUploading.value = true

  try {
    const formData = new FormData()
    formData.append('media', file)

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

    const response = await fetch(props.uploadUrl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
    })

    const payload = await response.json()

    if (!response.ok) {
      const message = payload?.message || 'Failed to upload image.'
      emit('upload-error', message)
      return
    }

    if (!payload?.url) {
      emit('upload-error', 'Upload succeeded but URL was not returned.')
      return
    }

    runCommand('insertImage', payload.url as string)
  } catch {
    emit('upload-error', 'Image upload failed. Please try again.')
  } finally {
    isUploading.value = false
  }
}
</script>

<template>
  <div class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-700">
    <div class="border-b border-gray-200 dark:border-gray-600 p-2 flex flex-wrap items-center gap-2">
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('bold')"><strong>B</strong></button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('italic')"><em>I</em></button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('underline')"><u>U</u></button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('strikeThrough')"><s>S</s></button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('subscript')">X₂</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('superscript')">X²</button>

      <div class="h-6 w-px bg-gray-300 dark:bg-gray-500"></div>

      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="formatLargeHeading">H1</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="formatHeading('h2')">H2</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="formatHeading('h3')">H3</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="formatHeading('p')">P</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="formatCodeBlock">Code</button>

      <div class="h-6 w-px bg-gray-300 dark:bg-gray-500"></div>

      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('insertUnorderedList')">• List</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('insertOrderedList')">1. List</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('indent')">Indent</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('outdent')">Outdent</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('formatBlock', '<blockquote>')">Quote</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="insertHorizontalRule">HR</button>

      <div class="h-6 w-px bg-gray-300 dark:bg-gray-500"></div>

      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="align('left')">Left</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="align('center')">Center</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="align('right')">Right</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="align('justify')">Justify</button>

      <div class="h-6 w-px bg-gray-300 dark:bg-gray-500"></div>

      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="insertLink">Link</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="unlink">Unlink</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="insertImageUrl">Image URL</button>
      <button type="button" class="editor-btn" :disabled="disabled || isUploading" @mousedown.prevent="triggerMediaUpload">
        {{ isUploading ? 'Uploading...' : 'Upload Image' }}
      </button>

      <div class="h-6 w-px bg-gray-300 dark:bg-gray-500"></div>

      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('removeFormat')">Clear</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('undo')">Undo</button>
      <button type="button" class="editor-btn" :disabled="disabled" @mousedown.prevent="runCommand('redo')">Redo</button>
    </div>

    <div class="relative">
      <div
        ref="editorRef"
        :contenteditable="!disabled"
        :class="[
          'rich-editor min-h-[220px] max-h-[500px] overflow-y-auto px-4 py-3 outline-none text-gray-900 dark:text-gray-100',
          disabled ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : ''
        ]"
        @input="emitContent"
      ></div>

      <p
        v-if="isEmpty"
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
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 0.375rem;
  border: 1px solid rgb(209 213 219);
  color: rgb(55 65 81);
  background: transparent;
  transition: background-color 150ms ease, opacity 150ms ease;
}

.editor-btn:hover:not(:disabled) {
  background-color: rgb(243 244 246);
}

.editor-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

:global(.dark) .editor-btn {
  border-color: rgb(107 114 128);
  color: rgb(229 231 235);
}

:global(.dark) .editor-btn:hover:not(:disabled) {
  background-color: rgb(75 85 99);
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
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
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
