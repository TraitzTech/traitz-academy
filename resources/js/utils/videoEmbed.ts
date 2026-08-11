/**
 * Builds iframe src for embedded streaming providers with presentation tuned
 * to reduce visible third-party branding (within each provider's allowed iframe options).
 *
 * Note: YouTube does not allow fully removing all branding, the “Watch on …” affordance,
 * or every overlay inside the official embed. These parameters only minimize what the
 * platform exposes (modest branding, related videos, etc.).
 */

/** YouTube iframe API params: https://developers.google.com/youtube/player_parameters */
const YOUTUBE_PRESENTATION_PARAMS = [
  'modestbranding=1',
  'rel=0',
  'playsinline=1',
  'iv_load_policy=3',
  'disablekb=1',
  'cc_load_policy=0',
  'color=white',
  'controls=0',
  'fs=0',
  'autoplay=0',
].join('&')

/**
 * Omit picture-in-picture (and web-share) so fewer secondary surfaces show provider UI.
 * Keep fullscreen for accessibility.
 */
export const STREAMING_IFRAME_ALLOW =
  'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; fullscreen'

export function youtubeVideoIdFromUrl(url: string | null | undefined): string | null {
  if (!url) {
    return null
  }
  return extractYouTubeVideoId(url.trim())
}

function extractYouTubeVideoId(raw: string): string | null {
  try {
    const trimmed = raw.trim()
    const parsed = new URL(trimmed.startsWith('http') ? trimmed : `https://${trimmed}`)
    const host = parsed.hostname.replace(/^www\./, '')

    if (host === 'youtu.be') {
      const id = parsed.pathname.split('/').filter(Boolean)[0]
      return id || null
    }

    if (host === 'youtube.com' || host === 'youtube-nocookie.com' || host === 'm.youtube.com') {
      if (parsed.pathname.startsWith('/embed/')) {
        return parsed.pathname.slice('/embed/'.length).split('/')[0] || null
      }
      const v = parsed.searchParams.get('v')
      if (v) return v
    }
  } catch {
    return null
  }
  return null
}

function youtubeEmbedSrc(raw: string): string | null {
  const id = extractYouTubeVideoId(raw)
  if (!id) return null
  return `https://www.youtube-nocookie.com/embed/${encodeURIComponent(id)}?${YOUTUBE_PRESENTATION_PARAMS}`
}

export function vimeoVideoIdFromUrl(url: string | null | undefined): string | null {
  if (!url) {
    return null
  }
  try {
    const trimmed = url.trim()
    const parsed = new URL(trimmed.startsWith('http') ? trimmed : `https://${trimmed}`)
    if (!parsed.hostname.includes('vimeo.com')) {
      return null
    }
    const segments = parsed.pathname.split('/').filter(Boolean)
    const last = segments[segments.length - 1]
    if (!last || !/^\d+$/.test(last)) {
      return null
    }
    return last
  } catch {
    return null
  }
}

export function vimeoPlayerEmbedSrc(url: string | null | undefined): string | null {
  const id = vimeoVideoIdFromUrl(url)
  if (!id) {
    return null
  }
  // title/byline/portrait/badge=0 strip the top overlay + corner badge; dnt=1
  // is privacy (do-not-track); pip=0 removes the picture-in-picture surface so
  // no secondary Vimeo-branded UI appears. Vimeo's own logo cannot be removed
  // on free/Basic plans.
  return `https://player.vimeo.com/video/${id}?title=0&byline=0&portrait=0&badge=0&dnt=1&pip=0`
}

function vimeoEmbedSrc(raw: string): string | null {
  return vimeoPlayerEmbedSrc(raw)
}

/**
 * Returns an iframe-ready HTTPS URL for YouTube or Vimeo watch/share links, or null if not a known embeddable stream.
 */
export function streamingEmbedSrc(url: string | null | undefined): string | null {
  if (!url) return null
  return youtubeEmbedSrc(url) || vimeoEmbedSrc(url)
}
