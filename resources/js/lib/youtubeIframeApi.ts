/**
 * Loads the YouTube IFrame API once and resolves when YT.Player is available.
 */

export type YTPlayerInstance = {
  destroy: () => void
  getCurrentTime: () => number
  getDuration: () => number
  getPlayerState: () => number
  getVolume: () => number
  playVideo: () => void
  pauseVideo: () => void
  seekTo: (seconds: number, allowSeekAhead: boolean) => void
  setVolume: (volume: number) => void
  setPlaybackRate: (suggestedRate: number) => void
}

export function loadYouTubeIframeApi(): Promise<void> {
  return new Promise((resolve, reject) => {
    if (typeof window === 'undefined') {
      resolve()
      return
    }
    const w = window as Window & {
      YT?: { Player: new (id: string, config: Record<string, unknown>) => YTPlayerInstance }
      onYouTubeIframeAPIReady?: () => void
    }

    if (w.YT?.Player) {
      resolve()
      return
    }

    if (document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
      const t = window.setInterval(() => {
        if (w.YT?.Player) {
          window.clearInterval(t)
          resolve()
        }
      }, 50)
      window.setTimeout(() => {
        window.clearInterval(t)
        if (!w.YT?.Player) {
          reject(new Error('YouTube IFrame API did not initialize.'))
        }
      }, 20000)
      return
    }

    const prior = w.onYouTubeIframeAPIReady
    w.onYouTubeIframeAPIReady = () => {
      prior?.()
      resolve()
    }

    const tag = document.createElement('script')
    tag.src = 'https://www.youtube.com/iframe_api'
    tag.async = true
    tag.onerror = () => reject(new Error('Failed to load YouTube IFrame API script.'))
    document.head.appendChild(tag)
  })
}
