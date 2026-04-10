<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { CheckCircle2, Circle, Download, FileText, MessageCircle, PlayCircle, ThumbsUp, Trash2 } from 'lucide-vue-next'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch, withDefaults } from 'vue'

import { loadYouTubeIframeApi, type YTPlayerInstance } from '@/lib/youtubeIframeApi'
import PublicLayout from '@/layouts/PublicLayout.vue'
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

const props = withDefaults(
  defineProps<{
    course: CoursePayload
    lesson: LessonPayload
    completedLessonIds: number[]
    videoProgress: VideoProgressPayload | null
    progressPercent: number
    lessonDiscussions: LessonDiscussionsPayload
  }>(),
  {
    lessonDiscussions: () => ({ questions: [], can_moderate: false }),
  }
)

const progressPercent = ref(props.progressPercent)
const completedSet = ref(new Set<number>(props.completedLessonIds))
const videoEl = ref<HTMLVideoElement | null>(null)
const ytHostRef = ref<HTMLElement | null>(null)
const ytContainerRef = ref<HTMLElement | null>(null)
const ytPlayerRef = ref<YTPlayerInstance | null>(null)
let ytPollHandle: ReturnType<typeof setInterval> | null = null

const playbackRate = ref(1)
const ytCurrentTime = ref(0)
const ytDuration = ref(0)
const ytVolume = ref(100)
const ytPlaying = ref(false)

const youtubeId = computed(() => youtubeVideoIdFromUrl(props.lesson.video_url))
const vimeoId = computed(() => vimeoVideoIdFromUrl(props.lesson.video_url))

const nativeVideoSrc = computed(() => {
  if (!props.lesson.video_url) return null
  if (youtubeId.value || vimeoId.value) return null
  const u = props.lesson.video_url.trim()
  if (u.startsWith('http://') || u.startsWith('https://')) return u
  return `/storage/${u}`
})

const ytContainerDomId = computed(() => `lms-yt-${props.course.id}-${props.lesson.id}`)

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

async function requestYoutubeFullscreen() {
  const el = ytContainerRef.value
  if (!el) return
  if (document.fullscreenElement) {
    await document.exitFullscreen()
    return
  }
  await el.requestFullscreen()
}

function destroyYoutubePlayer() {
  if (ytPollHandle) {
    clearInterval(ytPollHandle)
    ytPollHandle = null
  }
  try {
    ytPlayerRef.value?.destroy()
  } catch {
    /* ignore */
  }
  ytPlayerRef.value = null
}

function hardenYouTubeIframeSurface() {
  // Prevent direct clicks on YouTube iframe UI (logo/overlay links).
  // Playback remains controllable through our custom controls and API.
  const host = ytHostRef.value
  if (!host) return
  const iframe = host.querySelector('iframe')
  if (!iframe) return
  iframe.setAttribute('tabindex', '-1')
  iframe.setAttribute('title', 'Course lesson video')
  ;(iframe as HTMLIFrameElement).style.pointerEvents = 'none'
}

async function handleYoutubeEnded(target: YTPlayerInstance) {
  const duration = target.getDuration?.() ?? ytDuration.value
  const effectiveDuration = Math.max(1, Math.floor(duration || 0))

  // Force a terminal progress save so completion is recorded reliably.
  await saveVideoProgress(effectiveDuration, effectiveDuration)
  completedSet.value.add(props.lesson.id)
}

async function initYoutubePlayer() {
  if (!youtubeId.value) return
  destroyYoutubePlayer()
  await loadYouTubeIframeApi()
  await nextTick()

  const w = window as unknown as {
    YT: { Player: new (id: string, config: Record<string, unknown>) => YTPlayerInstance }
  }

  const width = ytHostRef.value?.clientWidth ?? 640
  const height = Math.round((width * 9) / 16)

  ytPlayerRef.value = new w.YT.Player(ytContainerDomId.value, {
    width,
    height,
    videoId: youtubeId.value,
    playerVars: {
      controls: 0,
      modestbranding: 1,
      rel: 0,
      iv_load_policy: 3,
      disablekb: 1,
      fs: 0,
      playsinline: 1,
      enablejsapi: 1,
      origin: window.location.origin,
    },
    events: {
      onReady: (e: { target: YTPlayerInstance }) => {
        const resume = props.videoProgress?.watched_seconds
        if (resume && resume > 2) {
          e.target.seekTo(resume, true)
        }
        e.target.setPlaybackRate(playbackRate.value)
        ytDuration.value = e.target.getDuration?.() ?? 0
        ytVolume.value = e.target.getVolume?.() ?? 100
        hardenYouTubeIframeSurface()
      },
      onStateChange: (e: { data?: number; target: YTPlayerInstance }) => {
        // YT state 0 = ended
        if (e.data === 0) {
          void handleYoutubeEnded(e.target)
        }
      },
    },
  })

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
    hardenYouTubeIframeSurface()
  }, 1200)
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
  destroyYoutubePlayer()
})

watch(
  () => props.lesson.id,
  async () => {
    lastPostedSecond.value = props.videoProgress?.watched_seconds ?? 0
    playbackRate.value = 1
    ytCurrentTime.value = 0
    ytDuration.value = 0
    ytVolume.value = 100
    ytPlaying.value = false
    destroyYoutubePlayer()
    await nextTick()
    if (youtubeId.value) {
      await initYoutubePlayer()
    } else if (nativeVideoSrc.value) {
      await nextTick()
      onNativeVideoLoaded()
    }
  }
)

watch(playbackRate, () => {
  applyPlaybackRate()
})

onMounted(async () => {
  if (youtubeId.value) {
    await initYoutubePlayer()
  } else if (nativeVideoSrc.value) {
    await nextTick()
    onNativeVideoLoaded()
  }
})
</script>

<template>
  <PublicLayout>
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
            <button
              v-if="lesson.type !== 'quiz' && lesson.type !== 'text'"
              type="button"
              class="rounded-xl border border-[#42b6c5] bg-[#42b6c5]/10 px-4 py-2 text-sm font-semibold text-[#2a8a96] hover:bg-[#42b6c5]/20"
              @click="markCurrentLessonComplete"
            >
              Mark complete
            </button>
          </div>

          <p v-if="lesson.description" class="mb-5 text-gray-600">{{ lesson.description }}</p>

          <div v-if="lesson.type === 'video'" class="space-y-4">
            <!-- YouTube: IFrame API player (progress + playback rate) -->
            <template v-if="youtubeId">
              <div ref="ytContainerRef" class="overflow-hidden rounded-xl border border-gray-200 bg-black shadow-sm">
                <div class="aspect-video min-h-[200px] w-full">
                  <div
                    :id="ytContainerDomId"
                    ref="ytHostRef"
                    class="h-full w-full"
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
                  <label class="flex items-center gap-2">
                    <span class="text-xs font-medium text-gray-600">Playback speed</span>
                    <select
                      v-model.number="playbackRate"
                      class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-[#000928] shadow-sm focus:border-[#42b6c5] focus:outline-none focus:ring-2 focus:ring-[#42b6c5]/20"
                    >
                      <option v-for="r in PLAYBACK_RATES" :key="r" :value="r">
                        {{ r === 1 ? '1× (normal)' : `${r}×` }}
                      </option>
                    </select>
                  </label>
                  <button type="button" class="lms-btn-outline !px-3 !py-1.5" @click="requestYoutubeFullscreen">
                    Fullscreen
                  </button>
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

          <div v-else-if="lesson.attachments?.length" class="space-y-3">
            <h3 class="text-sm font-semibold text-[#000928]">Lesson files</h3>
            <div class="space-y-2">
              <a
                v-for="file in lesson.attachments"
                :key="file.id"
                :href="attachmentUrl(file.file_url)"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm hover:bg-gray-50"
              >
                <div class="min-w-0">
                  <p class="line-clamp-1 font-medium text-gray-800">{{ file.name }}</p>
                  <p class="text-xs text-gray-500">{{ file.formatted_file_size }}</p>
                </div>
                <Download class="h-4 w-4 shrink-0 text-gray-500" />
              </a>
            </div>
          </div>

          <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-sm text-gray-500">
            This lesson type is not directly viewable here.
          </div>

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
  </PublicLayout>
</template>
