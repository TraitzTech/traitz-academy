import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echoInstance: Echo<'reverb'> | null = null

function env(key: string, fallback = ''): string {
  const value = import.meta.env[key]
  return typeof value === 'string' && value.length > 0 ? value : fallback
}

export function getEcho(): Echo<'reverb'> | null {
  if (echoInstance) return echoInstance

  const appKey = env('VITE_REVERB_APP_KEY')
  if (!appKey) return null

  const wsHost = env('VITE_REVERB_HOST', window.location.hostname)
  const wsPort = Number(env('VITE_REVERB_PORT', '8080'))
  const forceTls = env('VITE_REVERB_SCHEME', 'https') === 'https'

  ;(window as any).Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: appKey,
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS: forceTls,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    withCredentials: true,
  })

  return echoInstance
}
