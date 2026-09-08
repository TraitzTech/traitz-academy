import { getEcho } from '@/lib/realtime/echo';

type Unsubscribe = () => void;

export function usePrivateChannel(
    channelName: string,
    eventName: string,
    handler: (payload: any) => void,
): Unsubscribe | null {
    const echo = getEcho();
    if (!echo) {
        return null;
    }

    const channel = echo.private(channelName);
    channel.listen(`.${eventName}`, handler);

    const cleanup = () => {
        echo.leave(`private-${channelName}`);
    };

    return cleanup;
}
