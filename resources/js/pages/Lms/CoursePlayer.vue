<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { YoutubeIframe } from '@vue-youtube/component'
import { CheckCircle2, Circle, Download, FileText, MessageCircle, NotebookPen, PlayCircle, ThumbsUp, Trash2 } from 'lucide-vue-next'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch, withDefaults } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import { lessonBodyHtml } from '@/utils/lessonContentHtml'
import { STREAMING_IFRAME_ALLOW, streamingEmbedSrc, vimeoVideoIdFromUrl, youtubeVideoIdFromUrl } from '@/utils/videoEmbed'

/** Must match LessonVideoProgress::COMPLETION_PERCENT_THRESHOLD */
const VIDEO_COMPLETE_PERCENT = 90

const PLAYBACK_RATES = [0.75, 1, 1.25, 1.5, 2] as const

interface LessonItem {
  id: number
  title: string
  type: string
  duration: string | null
  is_free: boolean
}

interface CourseSection {
  id: number
  title: string
  lessons: LessonItem[]
}

interface CoursePayload {
  id: number
  title: string
  slug: string
  sections: CourseSection[]
}

interface AttachmentPayload {
  id: number
  name: string
  file_url: string
  file_type: string | null
  file_size: number | null
  formatted_file_size: string
}

interface LessonPayload {
  id: number
  title: string
  type: string
  description: string | null
  content: string | null
  video_url: string | null
  duration: string | null
  is_free: boolean
  attachments: AttachmentPayload[]
  quiz_id: number | null
}

interface VideoProgressPayload {
  watched_seconds: number
  duration_seconds: number
  percentage: number
}

interface DiscussionReplyPayload {
  id: number
  body: string
  is_accepted_answer: boolean
  user: { id: number; name: string }
  can_delete: boolean
  can_accept: boolean
  created_at: string | null
}

interface DiscussionQuestionPayload {
  id: number
  body: string
  user: { id: number; name: string }
  upvotes_count: number
  user_has_upvoted: boolean
  can_delete: boolean
  created_at: string | null
  replies: DiscussionReplyPayload[]
}

interface LessonDiscussionsPayload {
  questions: DiscussionQuestionPayload[]
  can_moderate: boolean
}

interface LessonNotePayload {
  id: number
  content: string
  timestamp: string | null
  timestamp_seconds: number | null
  updated_at: string | null
}

const props = withDefaults(
  defineProps<{
    course: CoursePayload
    lesson: LessonPayload
    completedLessonIds: number[]
    videoProgress: VideoProgressPayload | null
    progressPercent: number
    lessonDiscussions: LessonDiscussionsPayload
    lessonNotes: LessonNotePayload[]
  }>(),
  {
    lessonDiscussions: () => ({ questions: [], can_moderate: false }),
    lessonNotes: () => [],
  }
)

defineOptions({ layout: AppLayout })

const progressPercent = ref(props.progressPercent)
const completedSet = ref(new Set<number>(props.completedLessonIds))
const videoEl = ref<HTMLVideoElement | null>(null)
const ytContainerRef = ref<HTMLElement | null>(null)
interface YTPlayerInstance {
  playVideo?: () => void
  pauseVideo?: () => void
  seekTo: (seconds: number, allowSeekAhead: boolean) => void
  mute?: () => void
  unMute?: () => void
  isMuted?: () => boolean
  setVolume?: (volume: number) => void
  getVolume?: () => number
  getDuration?: () => number
  getCurrentTime?: () => number
  getPlayerState?: () => number
  setPlaybackRate?: (rate: number) => void
}
const ytPlayerRef = ref<YTPlayerInstance | null>(null)
let ytPollHandle: ReturnType<typeof setInterval> | null = null

const playbackRate = ref(1)
const ytCurrentTime = ref(0)
const ytDuration = ref(0)
const ytVolume = ref(100)
const ytPlaying = ref(false)
const ytMuted = ref(false)

const youtubeId = computed(() => youtubeVideoIdFromUrl(props.lesson.video_url))
const vimeoId = computed(() => vimeoVideoIdFromUrl(props.lesson.video_url))

const nativeVideoSrc = computed(() => {
  if (!props.lesson.video_url) return null
  if (youtubeId.value || vimeoId.value) return null
  const u = props.lesson.video_url.trim()
  if (u.startsWith('http://') || u.startsWith('https://')) return u
  return `/storage/${u}`
})

const canTakeTimestampNote = computed(() => Boolean(youtubeId.value || nativeVideoSrc.value))

const textLessonHtml = computed(() =>
  lessonBodyHtml(props.lesson.content, 'No lesson content has been provided yet.')
)

const flatLessons = computed(() =>
  props.course.sections.flatMap((section) =>
    section.lessons.map((lesson) => ({
      ...lesson,
      sectionId: section.id,
      sectionTitle: section.title,
    }))
  )
)

const currentIndex = computed(() => flatLessons.value.findIndex((lsn) => lsn.id === props.lesson.id))
const nextLesson = computed(() => {
  if (currentIndex.value < 0 || currentIndex.value >= flatLessons.value.length - 1) return null
  return flatLessons.value[currentIndex.value + 1]
})

const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? ''
const lastPostedSecond = ref(props.videoProgress?.watched_seconds ?? 0)

const questionBody = ref('')
const replyBodies = ref<Record<number, string>>({})
const discussionForm = useForm({ body: '', parent_id: null as number | null })
const notesPanelOpen = ref(false)
const lessonNoteDraft = ref('')
const notesSaving = ref(false)
const noteMessage = ref('')
const notesPanelSectionRef = ref<HTMLElement | null>(null)
let notesSaveTimer: ReturnType<typeof setTimeout> | null = null

const lessonNotes = ref<LessonNotePayload[]>([])

const generalLessonNote = computed(() => lessonNotes.value.find((note) => note.timestamp_seconds === null) ?? null)
const timestampNotes = computed(() =>
  lessonNotes.value
    .filter((note) => note.timestamp_seconds !== null)
    .sort((a, b) => (a.timestamp_seconds ?? 0) - (b.timestamp_seconds ?? 0))
)

function discussionsBasePath(): string {
  return `/dashboard/courses/${props.course.id}/lessons/${props.lesson.id}/discussions`
}

function submitQuestion() {
  const text = questionBody.value.trim()
  if (!text) return
  discussionForm.body = text
  discussionForm.parent_id = null
  discussionForm.post(discussionsBasePath(), {
    preserveScroll: true,
    onSuccess: () => {
      questionBody.value = ''
      discussionForm.reset()
    },
  })
}

function submitReply(parentId: number) {
  const text = (replyBodies.value[parentId] ?? '').trim()
  if (!text) return
  discussionForm.body = text
  discussionForm.parent_id = parentId
  discussionForm.post(discussionsBasePath(), {
    preserveScroll: true,
    onSuccess: () => {
      replyBodies.value[parentId] = ''
      discussionForm.reset()
    },
  })
}

function toggleUpvote(discussionId: number) {
  router.post(
    `${discussionsBasePath()}/${discussionId}/upvote`,
    {},
    { preserveScroll: true }
  )
}

function deleteDiscussion(discussionId: number) {
  if (!confirm('Remove this post?')) return
  router.delete(`${discussionsBasePath()}/${discussionId}`, { preserveScroll: true })
}

function acceptAnswer(discussionId: number) {
  router.post(
    `${discussionsBasePath()}/${discussionId}/accept`,
    {},
    { preserveScroll: true }
  )
}

function notesBasePath(): string {
  return `/dashboard/courses/${props.course.id}/lessons/${props.lesson.id}/notes`
}

function resetNotesStateFromProps() {
  lessonNotes.value = [...props.lessonNotes]
  lessonNoteDraft.value = generalLessonNote.value?.content ?? ''
  noteMessage.value = ''
  notesSaving.value = false
}

function queueLessonNoteAutosave() {
  noteMessage.value = ''
  if (notesSaveTimer) window.clearTimeout(notesSaveTimer)
  notesSaveTimer = window.setTimeout(() => {
    void saveGeneralLessonNote()
  }, 900)
}

async function saveGeneralLessonNote() {
  notesSaving.value = true
  try {
    const response = await fetch(notesBasePath(), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ content: lessonNoteDraft.value }),
    })

    if (!response.ok) {
      noteMessage.value = 'Unable to save note right now.'
      return
    }

    const payload = await response.json()
    const incoming = payload.note as LessonNotePayload | null
    const withoutGeneral = lessonNotes.value.filter((note) => note.timestamp_seconds !== null)
    lessonNotes.value = incoming ? [incoming, ...withoutGeneral] : withoutGeneral
    noteMessage.value = incoming ? 'Saved' : 'Cleared'
  } catch {
    noteMessage.value = 'Unable to save note right now.'
  } finally {
    notesSaving.value = false
  }
}

async function openNotesPanel() {
  notesPanelOpen.value = true
  await nextTick()
  notesPanelSectionRef.value?.scrollIntoView({
    behavior: 'smooth',
    block: 'start',
  })
}
const postingProgress = ref(false)
let throttleTimer: number | null = null

function lessonUrl(lessonId: number): string {
  return `/dashboard/courses/${props.course.id}/lessons/${lessonId}`
}

function attachmentUrl(path: string): string {
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  return `/storage/${path}`
}

function isCompleted(lessonId: number): boolean {
  return completedSet.value.has(lessonId)
}

function queueSaveVideoProgress(watchedSeconds: number, durationSeconds: number) {
  if (durationSeconds <= 0 || postingProgress.value) return
  if (watchedSeconds <= lastPostedSecond.value && watchedSeconds !== 0) return

  if (throttleTimer) window.clearTimeout(throttleTimer)
  throttleTimer = window.setTimeout(() => {
    saveVideoProgress(watchedSeconds, durationSeconds)
  }, 1200)
}

async function saveVideoProgress(watchedSeconds: number, durationSeconds: number) {
  postingProgress.value = true
  try {
    const response = await fetch(`/dashboard/courses/${props.course.id}/lessons/${props.lesson.id}/progress`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        watched_seconds: Math.floor(watchedSeconds),
        duration_seconds: Math.floor(durationSeconds),
      }),
    })

    if (!response.ok) return
    const payload = await response.json()
    lastPostedSecond.value = Math.floor(watchedSeconds)
    progressPercent.value = payload.progressPercent ?? progressPercent.value
    if ((payload.percentage ?? 0) >= VIDEO_COMPLETE_PERCENT) {
      completedSet.value.add(props.lesson.id)
    }
  } finally {
    postingProgress.value = false
  }
}

function onVideoTimeUpdate(event: Event) {
  const el = event.target as HTMLVideoElement
  queueSaveVideoProgress(el.currentTime, el.duration || 0)
}

function onVideoPause(event: Event) {
  const el = event.target as HTMLVideoElement
  saveVideoProgress(el.currentTime, el.duration || 0)
}

function onVideoEnded(event: Event) {
  const el = event.target as HTMLVideoElement
  saveVideoProgress(el.duration || el.currentTime, el.duration || 0)
}

function onNativeVideoLoaded() {
  if (!videoEl.value) return
  videoEl.value.playbackRate = playbackRate.value
  const resume = props.videoProgress?.watched_seconds
  if (resume && resume > 1) {
    videoEl.value.currentTime = resume
  }
}

function applyPlaybackRate() {
  if (videoEl.value) {
    videoEl.value.playbackRate = playbackRate.value
  }
  ytPlayerRef.value?.setPlaybackRate(playbackRate.value)
}

function formatDuration(seconds: number): string {
  const sec = Math.max(0, Math.floor(seconds || 0))
  const hrs = Math.floor(sec / 3600)
  const mins = Math.floor((sec % 3600) / 60)
  const rem = sec % 60
  if (hrs > 0) return `${hrs}:${String(mins).padStart(2, '0')}:${String(rem).padStart(2, '0')}`
  return `${mins}:${String(rem).padStart(2, '0')}`
}

function toggleYoutubePlayback() {
  const p = ytPlayerRef.value
  if (!p) return
  const state = p.getPlayerState?.()
  if (state === 1) {
    p.pauseVideo?.()
    ytPlaying.value = false
  } else {
    p.playVideo?.()
    ytPlaying.value = true
  }
}

function seekYoutube(seconds: number) {
  const p = ytPlayerRef.value
  if (!p) return
  const next = Math.max(0, Math.min(seconds, ytDuration.value || seconds))
  p.seekTo(next, true)
  ytCurrentTime.value = next
  if (ytDuration.value > 0) {
    queueSaveVideoProgress(next, ytDuration.value)
  }
}

function currentPlaybackSeconds(): number | null {
  if (youtubeId.value && ytPlayerRef.value?.getCurrentTime) {
    return Math.floor(ytPlayerRef.value.getCurrentTime())
  }

  if (videoEl.value) {
    return Math.floor(videoEl.value.currentTime || 0)
  }

  return null
}

function pauseCurrentVideo() {
  if (youtubeId.value) {
    ytPlayerRef.value?.pauseVideo?.()
    ytPlaying.value = false
    return
  }

  videoEl.value?.pause()
}

function seekCurrentVideo(seconds: number) {
  if (youtubeId.value) {
    seekYoutube(seconds)
    ytPlayerRef.value?.playVideo?.()
    ytPlaying.value = true
    return
  }

  if (videoEl.value) {
    videoEl.value.currentTime = Math.max(0, seconds)
    void videoEl.value.play().catch(() => {})
  }
}

async function takeTimestampNote() {
  if (!canTakeTimestampNote.value) {
    noteMessage.value = 'Timestamp capture is currently available for YouTube and uploaded videos.'
    openNotesPanel()
    return
  }

  const seconds = currentPlaybackSeconds()
  if (seconds === null) return

  pauseCurrentVideo()
  openNotesPanel()

  const stamp = formatDuration(seconds)
  lessonNoteDraft.value = lessonNoteDraft.value.trim() === '' ? `[${stamp}] ` : `${lessonNoteDraft.value}\n[${stamp}] `

  try {
    const response = await fetch(`${notesBasePath()}/timestamp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        content: `[${stamp}] ${props.lesson.title}`,
        timestamp_seconds: seconds,
      }),
    })

    if (!response.ok) {
      noteMessage.value = 'Could not save timestamp note.'
      return
    }

    const payload = await response.json()
    if (payload.note) {
      lessonNotes.value = [...lessonNotes.value.filter((note) => note.id !== payload.note.id), payload.note]
      noteMessage.value = 'Timestamp note saved.'
    }
  } catch {
    noteMessage.value = 'Could not save timestamp note.'
  }
}

function jumpToTimestamp(note: LessonNotePayload) {
  if (note.timestamp_seconds === null) return
  seekCurrentVideo(note.timestamp_seconds)
}

function onYoutubeSeekInput(event: Event) {
  const value = Number((event.target as HTMLInputElement).value)
  seekYoutube(value)
}

function setYoutubeVolume(volume: number) {
  const p = ytPlayerRef.value
  if (!p) return
  const next = Math.max(0, Math.min(100, Math.floor(volume)))
  p.setVolume?.(next)
  ytVolume.value = next
}

function onYoutubeVolumeInput(event: Event) {
  const value = Number((event.target as HTMLInputElement).value)
  setYoutubeVolume(value)
}

function toggleYoutubeMute() {
  const p = ytPlayerRef.value
  if (!p) return
  if (ytMuted.value) {
    p.unMute?.()
    ytMuted.value = false
    return
  }
  p.mute?.()
  ytMuted.value = true
}

async function requestYoutubeFullscreen() {
  const el = ytContainerRef.value
  if (!el) return
  if (document.fullscreenElement) {
    await document.exitFullscreen()
    return
  }
  await el.requestFullscreen()
}

function resetYoutubePlayerState() {
  if (ytPollHandle) {
    clearInterval(ytPollHandle)
    ytPollHandle = null
  }
  ytPlayerRef.value = null
  ytCurrentTime.value = 0
  ytDuration.value = 0
  ytVolume.value = 100
  ytPlaying.value = false
  ytMuted.value = false
}

async function handleYoutubeEnded(target: YTPlayerInstance) {
  const duration = target.getDuration?.() ?? ytDuration.value
  const effectiveDuration = Math.max(1, Math.floor(duration || 0))

  // Force a terminal progress save so completion is recorded reliably.
  await saveVideoProgress(effectiveDuration, effectiveDuration)
  completedSet.value.add(props.lesson.id)
}

function startYoutubePolling() {
  if (ytPollHandle) clearInterval(ytPollHandle)
  ytPollHandle = window.setInterval(() => {
    const p = ytPlayerRef.value
    if (!p?.getCurrentTime || !p.getDuration) return
    const cur = p.getCurrentTime()
    const dur = p.getDuration()
    ytCurrentTime.value = cur
    ytDuration.value = dur
    ytPlaying.value = p.getPlayerState?.() === 1
    if (dur > 0) {
      queueSaveVideoProgress(cur, dur)
    }
    ytMuted.value = p.isMuted?.() ?? ytMuted.value
  }, 1000)
}

function onYoutubeReady(event: { target: YTPlayerInstance }) {
  ytPlayerRef.value = event.target
  const resume = props.videoProgress?.watched_seconds
  if (resume && resume > 2) {
    event.target.seekTo(resume, true)
  }
  event.target.setPlaybackRate?.(playbackRate.value)
  ytDuration.value = event.target.getDuration?.() ?? 0
  ytVolume.value = event.target.getVolume?.() ?? 100
  ytMuted.value = event.target.isMuted?.() ?? false
  startYoutubePolling()
}

function onYoutubeStateChange(event: { data?: number; target: YTPlayerInstance }) {
  ytPlaying.value = event.data === 1
  if (event.data === 0) {
    void handleYoutubeEnded(event.target)
  }
}

async function markCurrentLessonComplete() {
  const response = await fetch(`/dashboard/courses/${props.course.id}/lessons/${props.lesson.id}/complete`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: '{}',
  })
  if (!response.ok) return
  const payload = await response.json()
  completedSet.value.add(props.lesson.id)
  progressPercent.value = payload.progressPercent ?? progressPercent.value
}

onBeforeUnmount(() => {
  if (throttleTimer) window.clearTimeout(throttleTimer)
  if (notesSaveTimer) window.clearTimeout(notesSaveTimer)
  resetYoutubePlayerState()
})

watch(
  () => props.lesson.id,
  async () => {
    resetNotesStateFromProps()
    lastPostedSecond.value = props.videoProgress?.watched_seconds ?? 0
    playbackRate.value = 1
    ytCurrentTime.value = 0
    ytDuration.value = 0
    ytVolume.value = 100
    ytPlaying.value = false
    ytMuted.value = false
    resetYoutubePlayerState()
    await nextTick()
    if (nativeVideoSrc.value) {
      await nextTick()
      onNativeVideoLoaded()
    }
  }
)

watch(playbackRate, () => {
  applyPlaybackRate()
})

watch(
  () => props.lessonNotes,
  () => {
    resetNotesStateFromProps()
  }
)

onMounted(async () => {
  resetNotesStateFromProps()
  if (nativeVideoSrc.value) {
    await nextTick()
    onNativeVideoLoaded()
  }
})
</script>

<template>
  <div>
    <Head :title="`${lesson.title} — ${course.title}`" />

    <div class="lms-page">
    <section class="bg-gray-50 py-6">
      <div class="mx-auto grid max-w-7xl gap-6 px-4 lg:grid-cols-[320px_1fr]">
        <aside class="lms-panel p-4">
          <h2 class="line-clamp-2 text-sm font-bold text-[#000928]">{{ course.title }}</h2>
          <div class="mt-3">
            <div class="mb-1 flex items-center justify-between text-xs text-gray-500">
              <span>Progress</span>
              <span>{{ progressPercent }}%</span>
            </div>
            <div class="h-2 rounded-full bg-gray-100">
              <div class="h-2 rounded-full bg-[#42b6c5]" :style="{ width: `${progressPercent}%` }" />
            </div>
          </div>

          <div class="mt-4 max-h-[70vh] space-y-3 overflow-auto pr-1">
            <div v-for="section in course.sections" :key="section.id" class="rounded-xl border border-gray-100">
              <div class="border-b border-gray-100 bg-gray-50 px-3 py-2 text-xs font-semibold text-[#000928]">
                {{ section.title }}
              </div>
              <Link
                v-for="lsn in section.lessons"
                :key="lsn.id"
                :href="lessonUrl(lsn.id)"
                class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50"
                :class="lsn.id === lesson.id ? 'bg-[#381998]/5 text-[#381998]' : 'text-gray-700'"
              >
                <component :is="isCompleted(lsn.id) ? CheckCircle2 : Circle" class="h-4 w-4 shrink-0" />
                <span class="line-clamp-1 flex-1">{{ lsn.title }}</span>
                <PlayCircle v-if="lsn.type === 'video'" class="h-4 w-4 shrink-0 text-gray-400" />
                <FileText v-else-if="lsn.type === 'text'" class="h-4 w-4 shrink-0 text-gray-400" />
              </Link>
            </div>
          </div>
        </aside>

        <main class="lms-panel">
          <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-[#42b6c5]">Course lesson</p>
              <h1 class="text-2xl font-bold text-[#000928]">{{ lesson.title }}</h1>
              <p v-if="lesson.duration" class="mt-1 text-sm text-gray-500">{{ lesson.duration }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                @click="openNotesPanel"
              >
                <NotebookPen class="h-4 w-4" />
                My notes
              </button>
              <button
                v-if="lesson.type !== 'quiz' && lesson.type !== 'text'"
                type="button"
                class="rounded-xl border border-[#42b6c5] bg-[#42b6c5]/10 px-4 py-2 text-sm font-semibold text-[#2a8a96] hover:bg-[#42b6c5]/20"
                @click="markCurrentLessonComplete"
              >
                Mark complete
              </button>
            </div>
          </div>

          <p v-if="lesson.description" class="mb-5 text-gray-600">{{ lesson.description }}</p>

          <div v-if="lesson.type === 'video'" class="space-y-4">
            <div class="flex items-center justify-end">
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-[#381998]/30 bg-[#381998]/5 px-3 py-1.5 text-xs font-semibold text-[#381998] hover:bg-[#381998]/10 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!canTakeTimestampNote"
                @click="takeTimestampNote"
              >
                <NotebookPen class="h-3.5 w-3.5" />
                Take note at current time
              </button>
            </div>
            <!-- YouTube: custom iframe + custom controls -->
            <template v-if="youtubeId">
              <div ref="ytContainerRef" class="overflow-hidden rounded-xl border border-gray-200 bg-black shadow-sm">
                <div class="yt-crop-shell aspect-video min-h-[200px] w-full overflow-hidden">
                  <YoutubeIframe
                    :video-id="youtubeId"
                    class="yt-crop-inner h-full w-full"
                    :player-vars="{
                      controls: 0,
                      rel: 0,
                      playsinline: 1,
                      disablekb: 1,
                      iv_load_policy: 3,
                    }"
                    @ready="onYoutubeReady"
                    @state-change="onYoutubeStateChange"
                  />
                </div>
              </div>
              <div
                class="space-y-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700"
              >
                <div class="flex flex-wrap items-center gap-2">
                  <button type="button" class="lms-btn-primary !px-3 !py-1.5" @click="toggleYoutubePlayback">
                    {{ ytPlaying ? 'Pause' : 'Play' }}
                  </button>
                  <input
                    type="range"
                    min="0"
                    :max="Math.max(1, Math.floor(ytDuration))"
                    :value="Math.floor(ytCurrentTime)"
                    class="h-2 min-w-[220px] flex-1 cursor-pointer accent-[#42b6c5]"
                    @input="onYoutubeSeekInput"
                  />
                  <span class="text-xs font-medium text-gray-600">
                    {{ formatDuration(ytCurrentTime) }} / {{ formatDuration(ytDuration) }}
                  </span>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div class="flex items-center gap-2">
                    <button type="button" class="lms-btn-outline !px-3 !py-1.5" @click="toggleYoutubeMute">
                      {{ ytMuted ? 'Unmute' : 'Mute' }}
                    </button>
                    <span class="text-xs font-medium text-gray-600">Volume</span>
                    <input
                      type="range"
                      min="0"
                      max="100"
                      :value="ytVolume"
                      class="h-2 w-28 cursor-pointer accent-[#42b6c5]"
                      @input="onYoutubeVolumeInput"
                    />
                  </div>
                </div>
                <p class="text-xs text-gray-500">Lesson completes automatically at {{ VIDEO_COMPLETE_PERCENT }}% watched.</p>
              </div>
            </template>
            <!-- Vimeo: embed (use Mark complete; cross-origin limits HTML5-style tracking) -->
            <template v-else-if="vimeoId && streamingEmbedSrc(lesson.video_url)">
              <div class="aspect-video overflow-hidden rounded-xl border border-gray-200 bg-black shadow-sm">
                <iframe
                  :src="streamingEmbedSrc(lesson.video_url) ?? undefined"
                  class="h-full w-full"
                  referrerpolicy="strict-origin-when-cross-origin"
                  :allow="STREAMING_IFRAME_ALLOW"
                  allowfullscreen
                />
              </div>
              <p class="text-xs text-gray-500">
                Use the player’s controls for speed and volume. Tap
                <strong>Mark complete</strong>
                when you have finished this lesson (hosted on Vimeo).
              </p>
            </template>

            <!-- Direct file / progressive URL: HTML5 &lt;video&gt; -->
            <template v-else-if="nativeVideoSrc">
              <div class="overflow-hidden rounded-xl border border-gray-200 bg-black shadow-sm">
                <div class="aspect-video w-full">
                  <video
                    ref="videoEl"
                    :src="nativeVideoSrc"
                    controls
                    playsinline
                    preload="metadata"
                    class="h-full w-full object-contain"
                    @timeupdate="onVideoTimeUpdate"
                    @pause="onVideoPause"
                    @ended="onVideoEnded"
                    @loadedmetadata="onNativeVideoLoaded"
                  />
                </div>
              </div>
              <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700"
              >
                <label class="flex flex-wrap items-center gap-2">
                  <span class="font-medium text-gray-600">Playback speed</span>
                  <select
                    v-model.number="playbackRate"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-[#000928] shadow-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20"
                  >
                    <option v-for="r in PLAYBACK_RATES" :key="r" :value="r">
                      {{ r === 1 ? '1× (normal)' : `${r}×` }}
                    </option>
                  </select>
                </label>
                <p class="text-xs text-gray-500">
                  Lesson completes automatically at {{ VIDEO_COMPLETE_PERCENT }}% watched.
                </p>
              </div>
            </template>

            <p v-else class="text-sm text-gray-500">No video source is available for this lesson.</p>
          </div>

          <article v-else-if="lesson.type === 'text'" class="space-y-8">
            <div
              class="lesson-body-text prose prose-lg prose-slate w-full max-w-3xl leading-relaxed text-gray-800 prose-headings:scroll-mt-24 prose-headings:font-semibold prose-headings:text-[#000928] prose-p:leading-relaxed prose-li:my-1 prose-ul:my-4 prose-ol:my-4 prose-a:text-[#381998] prose-a:font-medium prose-a:no-underline hover:prose-a:underline prose-img:max-w-full prose-img:rounded-lg prose-img:shadow-sm prose-figure:my-6 prose-pre:overflow-x-auto prose-pre:rounded-xl prose-pre:border prose-pre:border-gray-800 prose-pre:bg-[#0d1117] prose-pre:px-4 prose-pre:py-3 prose-pre:text-[13px] prose-pre:leading-relaxed prose-pre:text-gray-100 prose-pre:shadow-inner prose-code:rounded prose-code:bg-gray-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:font-mono prose-code:text-sm prose-code:text-[#381998] prose-code:before:content-none prose-code:after:content-none [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:text-[inherit] dark:prose-invert dark:prose-code:bg-gray-800 dark:prose-code:text-[#7ee8f9]"
              v-html="textLessonHtml"
            />
            <div class="w-full max-w-3xl border-t border-gray-100 pt-8">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl bg-[#000928] px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#381998] disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="isCompleted(lesson.id)"
                @click="markCurrentLessonComplete"
              >
                {{ isCompleted(lesson.id) ? 'Completed' : 'Mark as Complete' }}
              </button>
              <p v-if="isCompleted(lesson.id)" class="mt-3 text-sm text-green-700">
                This lesson is marked complete.
              </p>
            </div>
          </article>

          <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-sm text-gray-500">
            This lesson type is not directly viewable here.
          </div>

          <section ref="notesPanelSectionRef" class="mt-8 rounded-xl border border-gray-100 bg-gray-50/90 px-4 py-5 sm:px-6">
            <button
              type="button"
              class="flex w-full items-center justify-between gap-3 text-left"
              @click="notesPanelOpen = !notesPanelOpen"
            >
              <div>
                <h3 class="text-sm font-semibold text-[#000928]">My lesson notes</h3>
                <p class="mt-1 text-xs text-gray-500">Autosaves as you type. Timestamp notes jump video playback.</p>
              </div>
              <span class="text-xs font-semibold text-[#42b6c5]">
                {{ notesPanelOpen ? 'Hide' : 'Open' }}
              </span>
            </button>

            <div v-if="notesPanelOpen" class="mt-4 space-y-4">
              <div>
                <textarea
                  v-model="lessonNoteDraft"
                  rows="5"
                  maxlength="10000"
                  placeholder="Write your notes for this lesson..."
                  class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20"
                  @input="queueLessonNoteAutosave"
                />
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                  <span>{{ notesSaving ? 'Saving…' : noteMessage || 'Saved automatically' }}</span>
                  <span>{{ lessonNoteDraft.length }}/10000</span>
                </div>
              </div>

              <div v-if="timestampNotes.length" class="rounded-lg border border-gray-200 bg-white p-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Timestamp notes</p>
                <ul class="space-y-2">
                  <li v-for="note in timestampNotes" :key="note.id">
                    <button
                      type="button"
                      class="w-full rounded-lg border border-gray-200 px-3 py-2 text-left text-sm transition-colors hover:border-[#42b6c5]/50 hover:bg-gray-50"
                      @click="jumpToTimestamp(note)"
                    >
                      <span class="font-semibold text-[#381998]">{{ note.timestamp }}</span>
                      <span class="mx-2 text-gray-300">•</span>
                      <span class="text-gray-700">{{ note.content }}</span>
                    </button>
                  </li>
                </ul>
              </div>
            </div>
          </section>

          <section
            v-if="lesson.attachments?.length"
            class="mt-8 rounded-xl border border-gray-100 bg-gray-50/90 px-4 py-5 sm:px-6"
          >
            <h3 class="mb-3 text-sm font-semibold text-[#000928]">Resources</h3>
            <p class="mb-4 text-xs text-gray-500">
              Download supplementary materials for this lesson (PDFs, slides, worksheets, templates).
            </p>
            <ul class="space-y-2">
              <li v-for="file in lesson.attachments" :key="file.id">
                <a
                  :href="attachmentUrl(file.file_url)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm shadow-sm transition-colors hover:border-[#42b6c5]/40 hover:bg-gray-50"
                >
                  <div class="min-w-0">
                    <p class="line-clamp-2 font-medium text-gray-800">{{ file.name }}</p>
                    <p class="text-xs text-gray-500">{{ file.formatted_file_size }}</p>
                  </div>
                  <Download class="h-4 w-4 shrink-0 text-[#42b6c5]" aria-hidden="true" />
                </a>
              </li>
            </ul>
          </section>

          <section class="mt-8 border-t border-gray-100 pt-8">
            <div class="mb-4 flex items-center gap-2">
              <MessageCircle class="h-5 w-5 text-[#42b6c5]" />
              <h2 class="text-lg font-bold text-[#000928]">Questions &amp; answers</h2>
            </div>
            <p class="mb-4 text-sm text-gray-500">
              Ask a question about this lesson. Other enrolled learners and your instructor can reply.
            </p>

            <form class="mb-8 space-y-2" @submit.prevent="submitQuestion">
              <label class="sr-only">Your question</label>
              <textarea
                v-model="questionBody"
                rows="3"
                maxlength="10000"
                placeholder="Write your question…"
                class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20"
              />
              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="discussionForm.processing || !questionBody.trim()"
                  class="lms-btn-primary disabled:opacity-50"
                >
                  {{ discussionForm.processing ? 'Posting…' : 'Post question' }}
                </button>
              </div>
              <p v-if="discussionForm.errors.body" class="text-xs text-red-600">{{ discussionForm.errors.body }}</p>
            </form>

            <div class="space-y-6">
              <div
                v-for="q in lessonDiscussions.questions"
                :key="q.id"
                class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm"
              >
                <div class="flex gap-3">
                  <div class="flex flex-col items-center gap-1 pt-0.5">
                    <button
                      type="button"
                      class="flex flex-col items-center rounded-lg px-2 py-1 text-gray-500 transition-colors hover:bg-gray-50 hover:text-[#381998]"
                      :class="{ 'text-[#381998]': q.user_has_upvoted }"
                      :aria-pressed="q.user_has_upvoted"
                      @click="toggleUpvote(q.id)"
                    >
                      <ThumbsUp class="h-4 w-4" />
                      <span class="text-xs font-semibold">{{ q.upvotes_count }}</span>
                    </button>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="whitespace-pre-wrap text-sm text-gray-800">{{ q.body }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                      <span>{{ q.user.name }}</span>
                      <span v-if="q.created_at">· {{ new Date(q.created_at).toLocaleString() }}</span>
                      <button
                        v-if="q.can_delete"
                        type="button"
                        class="inline-flex items-center gap-1 text-red-600 hover:underline"
                        @click="deleteDiscussion(q.id)"
                      >
                        <Trash2 class="h-3 w-3" /> Delete
                      </button>
                    </div>

                    <div v-if="q.replies?.length" class="mt-4 space-y-3 border-l-2 border-[#42b6c5]/30 pl-4">
                      <div
                        v-for="r in q.replies"
                        :key="r.id"
                        class="rounded-lg bg-gray-50/80 p-3"
                        :class="{ 'ring-2 ring-[#42b6c5]/40': r.is_accepted_answer }"
                      >
                        <p v-if="r.is_accepted_answer" class="mb-1 text-xs font-bold uppercase tracking-wide text-[#42b6c5]">
                          Accepted answer
                        </p>
                        <p class="whitespace-pre-wrap text-sm text-gray-800">{{ r.body }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                          <span>{{ r.user.name }}</span>
                          <span v-if="r.created_at">· {{ new Date(r.created_at).toLocaleString() }}</span>
                          <button
                            v-if="lessonDiscussions.can_moderate && r.can_accept && !r.is_accepted_answer"
                            type="button"
                            class="font-medium text-[#381998] hover:underline"
                            @click="acceptAnswer(r.id)"
                          >
                            Mark as accepted
                          </button>
                          <button
                            v-if="r.can_delete"
                            type="button"
                            class="inline-flex items-center gap-1 text-red-600 hover:underline"
                            @click="deleteDiscussion(r.id)"
                          >
                            <Trash2 class="h-3 w-3" /> Delete
                          </button>
                        </div>
                      </div>
                    </div>

                    <form class="mt-4 space-y-2" @submit.prevent="submitReply(q.id)">
                      <label class="sr-only">Reply</label>
                      <textarea
                        v-model="replyBodies[q.id]"
                        rows="2"
                        maxlength="10000"
                        placeholder="Write a reply…"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20"
                      />
                      <div class="flex justify-end">
                        <button
                          type="submit"
                          :disabled="discussionForm.processing || !(replyBodies[q.id] ?? '').trim()"
                          class="lms-btn-outline text-xs disabled:opacity-50"
                        >
                          {{ discussionForm.processing ? 'Posting…' : 'Reply' }}
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

            <p v-if="!lessonDiscussions.questions.length" class="text-sm text-gray-400">
              No questions yet — be the first to ask.
            </p>
          </section>

          <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4">
            <Link :href="`/online-courses/${course.id}`" class="text-sm font-semibold text-gray-500 hover:text-[#42b6c5]">
              Back to course
            </Link>
            <Link
              v-if="nextLesson"
              :href="lessonUrl(nextLesson.id)"
              class="lms-btn-primary"
            >
              Next lesson
            </Link>
          </div>
        </main>
      </div>
    </section>
    </div>
  </div>
</template>

<style scoped>
.yt-crop-shell :deep(iframe) {
  width: 110% !important;
  height: 110% !important;
  margin: -5% !important;
  pointer-events: none;
}
</style>
