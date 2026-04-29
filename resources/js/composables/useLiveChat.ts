import { ref } from 'vue'

import { usePrivateChannel } from '@/composables/useRealtimeChannel'

interface LiveChatOptions {
  listUrl: string
  sendUrl: string
  channelName: string
  eventName: string
}

export function useLiveChat(initialMessages: any[], options: LiveChatOptions) {
  const messages = ref<any[]>([...initialMessages])
  const posting = ref(false)
  const connectionMode = ref<'realtime' | 'fallback'>('fallback')
  let poller: number | null = null
  let unlisten: (() => void) | null = null

  function upsertMessage(message: any) {
    if (!message) return
    const id = message.id
    if (id !== undefined && id !== null) {
      const existingIndex = messages.value.findIndex((row) => row?.id === id)
      if (existingIndex >= 0) {
        messages.value[existingIndex] = message
        return
      }
    }
    messages.value.push(message)
  }

  function startPolling() {
    if (poller) return
    refreshMessages().catch(() => null)
    poller = window.setInterval(() => {
      refreshMessages().catch(() => null)
    }, 4000)
  }

  async function refreshMessages() {
    const res = await fetch(options.listUrl, { credentials: 'same-origin' })
    const data = await res.json()
    messages.value = data.messages ?? []
  }

  async function sendMessage(text: string) {
    posting.value = true
    try {
      const res = await fetch(options.sendUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ message: text }),
        credentials: 'same-origin',
      })
      if (!res.ok) {
        throw new Error(`Failed to send message (${res.status})`)
      }
      const data = await res.json()
      if (data?.message) upsertMessage(data.message)
      await refreshMessages()
    } finally {
      posting.value = false
    }
  }

  function start() {
    // Keep polling active as a robust fallback when websocket auth/connection fails.
    startPolling()
    unlisten = usePrivateChannel(options.channelName, options.eventName, (payload) => {
      if (!payload?.message) return
      upsertMessage(payload.message)
    }) ?? null
    connectionMode.value = unlisten ? 'realtime' : 'fallback'
  }

  function stop() {
    if (poller) window.clearInterval(poller)
    poller = null
    if (unlisten) unlisten()
    unlisten = null
    connectionMode.value = 'fallback'
  }

  return {
    messages,
    posting,
    connectionMode,
    sendMessage,
    refreshMessages,
    start,
    stop,
  }
}
