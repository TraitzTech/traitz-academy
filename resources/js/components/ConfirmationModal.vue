<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'

interface Props {
    open: boolean
    title: string
    description?: string
    confirmText?: string
    cancelText?: string
    variant?: 'destructive' | 'default'
    processing?: boolean
}

withDefaults(defineProps<Props>(), {
    description: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'destructive',
    processing: false,
})

const emit = defineEmits<{
    'update:open': [value: boolean]
    confirm: []
}>()
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
                <slot name="body" />
            </DialogHeader>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">{{ cancelText }}</Button>
                </DialogClose>
                <Button
                    :variant="variant"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    <span v-if="processing">Processing...</span>
                    <span v-else>{{ confirmText }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
