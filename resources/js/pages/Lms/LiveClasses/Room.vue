<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

import ChatPanel from '@/components/live-classes/ChatPanel.vue';
import RecordingsList from '@/components/live-classes/RecordingsList.vue';
import { useLiveChat } from '@/composables/useLiveChat';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

declare global {
    interface Window {
        JitsiMeetExternalAPI?: any;
    }
}

const props = defineProps<{
    liveClass: any;
    authUser: { id: number; name: string; role: string };
    canManage: boolean;
    manageRedirectUrl?: string | null;
}>();
const jitsiNode = ref<HTMLElement | null>(null);
const jitsiApi = ref<any | null>(null);
const recording = ref(false);
const recordingBusy = ref(false);
const recordingNotice = ref('');
let heartbeat: number | null = null;
let endRedirectTimer: number | null = null;

const chat = useLiveChat(props.liveClass.messages ?? [], {
    listUrl: `/dashboard/live-classes/${props.liveClass.id}/messages`,
    sendUrl: `/dashboard/live-classes/${props.liveClass.id}/messages`,
    channelName: `live-class.${props.liveClass.id}`,
    eventName: 'live-class.message.sent',
});

async function postJson(
    url: string,
    payload: Record<string, any> = {},
    keepalive = false,
) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN':
                (
                    document.querySelector(
                        'meta[name="csrf-token"]',
                    ) as HTMLMetaElement | null
                )?.content ?? '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin',
        // keepalive lets the request complete even as the page is unloading.
        keepalive,
    });

    if (!response.ok) {
        throw new Error(`Request failed (${response.status})`);
    }
}

function setupJitsi() {
    if (!window.JitsiMeetExternalAPI || !jitsiNode.value) return;
    const isStudent = props.authUser.role === 'user';
    const domain = props.liveClass?.jitsi?.domain || 'meet.jit.si';
    const options: any = {
        roomName:
            props.liveClass?.jitsi?.room_name || props.liveClass.room_name,
        parentNode: jitsiNode.value,
        width: '100%',
        height: '100%',
        userInfo: { displayName: props.authUser.name },
        configOverwrite: {
            disableInviteFunctions: isStudent,
        },
        interfaceConfigOverwrite: isStudent
            ? {
                  TOOLBAR_BUTTONS: [
                      'microphone',
                      'camera',
                      'desktop',
                      'fullscreen',
                      'fodeviceselection',
                      'hangup',
                      'chat',
                      'settings',
                      'raisehand',
                      'videoquality',
                      'tileview',
                  ],
              }
            : {},
    };

    if (props.liveClass?.jitsi?.jwt) {
        options.jwt = props.liveClass.jitsi.jwt;
    }

    const api = new window.JitsiMeetExternalAPI(domain, options);
    jitsiApi.value = api;

    api.addListener('recordingStatusChanged', (payload: { on?: boolean }) => {
        recording.value = Boolean(payload?.on);
    });
}

function jitsiApiScriptUrl(): string {
    const rawDomain = String(
        props.liveClass?.jitsi?.domain || 'meet.jit.si',
    ).trim();
    if (rawDomain.startsWith('http://') || rawDomain.startsWith('https://')) {
        return `${rawDomain.replace(/\/$/, '')}/external_api.js`;
    }

    return `https://${rawDomain}/external_api.js`;
}

function classEndsAtMs(): number {
    const startMs = new Date(props.liveClass.start_time).getTime();
    return startMs + Number(props.liveClass.duration || 0) * 60000;
}

function redirectManagerToUploadPage() {
    if (!props.canManage || !props.manageRedirectUrl) return;
    window.location.href = props.manageRedirectUrl;
}

onMounted(async () => {
    const msUntilEnd = classEndsAtMs() - Date.now();
    if (props.canManage && props.manageRedirectUrl) {
        if (msUntilEnd <= 0) {
            redirectManagerToUploadPage();
            return;
        }

        endRedirectTimer = window.setTimeout(() => {
            redirectManagerToUploadPage();
        }, msUntilEnd);
    }

    try {
        await postJson(
            `/dashboard/live-classes/${props.liveClass.id}/attendance/join`,
        );
    } catch {
        window.location.href = `/dashboard/live-classes/${props.liveClass.id}/details`;
        return;
    }
    heartbeat = window.setInterval(() => {
        postJson(
            `/dashboard/live-classes/${props.liveClass.id}/attendance/ping`,
        ).catch(() => {
            window.location.href = `/dashboard/live-classes/${props.liveClass.id}/details`;
        });
    }, 30000);
    chat.start();
    if (!window.JitsiMeetExternalAPI) {
        const s = document.createElement('script');
        s.src = jitsiApiScriptUrl();
        s.onload = setupJitsi;
        document.body.appendChild(s);
    } else {
        setupJitsi();
    }
    window.addEventListener('beforeunload', leaveClass);
    // pagehide is more reliable than beforeunload (esp. on mobile).
    window.addEventListener('pagehide', leaveClass);
});

async function leaveClass() {
    await postJson(
        `/dashboard/live-classes/${props.liveClass.id}/attendance/leave`,
        {},
        true,
    ).catch(() => {});
}

async function toggleRecording() {
    if (!props.canManage || !jitsiApi.value || recordingBusy.value) return;

    recordingBusy.value = true;
    recordingNotice.value = '';
    try {
        if (recording.value) {
            jitsiApi.value.executeCommand('stopRecording', 'file');
            recordingNotice.value = 'Stopping recording...';
            recording.value = false;
        } else {
            jitsiApi.value.executeCommand('startRecording', { mode: 'file' });
            recordingNotice.value = 'Starting recording...';
            recording.value = true;
        }
    } catch {
        recordingNotice.value =
            'Recording action failed. Please check Jitsi recording permissions.';
    } finally {
        recordingBusy.value = false;
    }
}

onBeforeUnmount(async () => {
    if (heartbeat) window.clearInterval(heartbeat);
    if (endRedirectTimer) window.clearTimeout(endRedirectTimer);
    chat.stop();
    window.removeEventListener('beforeunload', leaveClass);
    window.removeEventListener('pagehide', leaveClass);
    if (jitsiApi.value) {
        jitsiApi.value.dispose();
        jitsiApi.value = null;
    }
    await leaveClass();
});
</script>

<template>
    <div>
        <Head :title="liveClass.title" />
        <div class="mb-4">
            <p
                class="text-xs font-semibold tracking-wide text-red-600 uppercase"
            >
                {{ recording ? 'Recording in progress' : 'Recording is off' }}
            </p>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ liveClass.title }}
            </h1>
            <p class="text-sm text-gray-500">{{ liveClass.description }}</p>
            <div
                v-if="canManage"
                class="mt-3 flex flex-wrap items-center gap-2"
            >
                <button
                    type="button"
                    :disabled="recordingBusy"
                    :class="[
                        'rounded-lg px-3 py-2 text-xs font-semibold text-white disabled:opacity-50',
                        recording
                            ? 'bg-red-600 hover:bg-red-700'
                            : 'bg-[#381998] hover:bg-[#000928]',
                    ]"
                    @click="toggleRecording"
                >
                    {{
                        recordingBusy
                            ? 'Please wait...'
                            : recording
                              ? 'Stop recording'
                              : 'Start recording'
                    }}
                </button>
                <span class="text-xs text-gray-500">{{
                    recording
                        ? 'Recording is active.'
                        : 'Recording is currently off.'
                }}</span>
            </div>
            <p v-if="recordingNotice" class="mt-2 text-xs text-gray-500">
                {{ recordingNotice }}
            </p>
        </div>
        <div class="grid gap-4 lg:grid-cols-[1fr_360px]">
            <div class="space-y-4">
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-black dark:border-gray-700"
                >
                    <div ref="jitsiNode" class="aspect-video w-full" />
                </div>
                <RecordingsList :recordings="liveClass.recordings || []" />
            </div>
            <ChatPanel
                :messages="chat.messages.value"
                :posting="chat.posting.value"
                :connection-mode="chat.connectionMode.value"
                :can-post="true"
                @send="chat.sendMessage"
            />
        </div>
    </div>
</template>
